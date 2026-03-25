<?php

namespace App\Services;

use App\Enums\CustomerStorageType;
use App\Models\Bar;
use App\Models\Deposit;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventorySnapshotService
{
    /**
     * @return array{
     *     totals: array{bars: int, weight_kg: float, value: float},
     *     rows: array<int, array{
     *         serial_number: string,
     *         metal_name: string,
     *         metal_code: string,
     *         metal_color: string,
     *         owner_name: string,
     *         deposit_reference: string,
     *         weight_kg: float,
     *         value: float,
     *         date_added: CarbonInterface|null
     *     }>
     * }
     */
    public function allocatedSnapshot(): array
    {
        /** @var Collection<int, object> $rawRows */
        $rawRows = Bar::query()
            ->join('deposits', 'deposits.id', '=', 'bars.deposit_id')
            ->join('customers', 'customers.id', '=', 'deposits.customer_id')
            ->join('metals', 'metals.id', '=', 'deposits.metal_id')
            ->where('deposits.storage_type', CustomerStorageType::Allocated->value)
            ->whereNull('bars.withdrawal_id')
            ->orderByDesc('deposits.deposited_at')
            ->select([
                'bars.serial_number',
                'bars.weight_kg',
                'customers.name as owner_name',
                'deposits.reference_number as deposit_reference',
                'deposits.deposited_at as date_added',
                'metals.name as metal_name',
                'metals.code as metal_code',
                DB::raw('(bars.weight_kg * metals.price) as live_value'),
            ])
            ->get();

        $rows = $rawRows
            ->map(function (object $row): array {
                $weightKg = (float) $row->weight_kg;

                return [
                    'serial_number' => (string) $row->serial_number,
                    'metal_name' => (string) $row->metal_name,
                    'metal_code' => (string) $row->metal_code,
                    'metal_color' => $this->metalColor((string) $row->metal_code),
                    'owner_name' => (string) $row->owner_name,
                    'deposit_reference' => (string) $row->deposit_reference,
                    'weight_kg' => $weightKg,
                    'value' => (float) $row->live_value,
                    'date_added' => filled($row->date_added) ? Carbon::parse((string) $row->date_added) : null,
                ];
            })
            ->values()
            ->all();

        $totalWeightKg = array_sum(array_column($rows, 'weight_kg'));
        $totalValue = array_sum(array_column($rows, 'value'));

        return [
            'totals' => [
                'bars' => count($rows),
                'weight_kg' => $totalWeightKg,
                'value' => $totalValue,
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{
     *     totals: array{holdings_kg: float, value: float, active_positions: int},
     *     pools: array<int, array{
     *         metal_id: string,
     *         metal_name: string,
     *         metal_code: string,
     *         metal_color: string,
     *         total_kg: float,
     *         total_value: float,
     *         owners: array<int, array{
     *             customer_id: string,
     *             customer_name: string,
     *             quantity_kg: float,
     *             pool_share_percentage: float,
     *             value: float
     *         }>
     *     }>,
     *     positions: array<int, array{
     *         customer_id: string,
     *         customer_name: string,
     *         metal_id: string,
     *         metal_name: string,
     *         metal_code: string,
     *         metal_color: string,
     *         quantity_kg: float,
     *         pool_share_percentage: float,
     *         value: float
     *     }>
     * }
     */
    public function unallocatedSnapshot(): array
    {
        /** @var Collection<int, object> $rawPositions */
        $rawPositions = DB::query()
            ->fromSub($this->unallocatedDepositTotalsSubquery(), 'deposit_totals')
            ->leftJoinSub(
                $this->unallocatedWithdrawalTotalsSubquery(),
                'withdrawal_totals',
                fn ($join) => $join
                    ->on('withdrawal_totals.customer_id', '=', 'deposit_totals.customer_id')
                    ->on('withdrawal_totals.metal_id', '=', 'deposit_totals.metal_id'),
            )
            ->join('customers', 'customers.id', '=', 'deposit_totals.customer_id')
            ->join('metals', 'metals.id', '=', 'deposit_totals.metal_id')
            ->whereRaw('(deposit_totals.deposited_kg - COALESCE(withdrawal_totals.withdrawn_kg, 0)) > 0')
            ->orderBy('metals.name')
            ->orderBy('customers.name')
            ->select([
                'customers.id as customer_id',
                'customers.name as customer_name',
                'metals.id as metal_id',
                'metals.name as metal_name',
                'metals.code as metal_code',
                'metals.price as metal_price',
                DB::raw('(deposit_totals.deposited_kg - COALESCE(withdrawal_totals.withdrawn_kg, 0)) as net_kg'),
            ])
            ->get();

        $positionRows = $rawPositions
            ->map(function (object $row): array {
                $quantityKg = (float) $row->net_kg;
                $value = $quantityKg * (float) $row->metal_price;

                return [
                    'customer_id' => (string) $row->customer_id,
                    'customer_name' => (string) $row->customer_name,
                    'metal_id' => (string) $row->metal_id,
                    'metal_name' => (string) $row->metal_name,
                    'metal_code' => (string) $row->metal_code,
                    'metal_color' => $this->metalColor((string) $row->metal_code),
                    'quantity_kg' => $quantityKg,
                    'pool_share_percentage' => 0.0,
                    'value' => $value,
                ];
            })
            ->values();

        $pools = $positionRows
            ->groupBy('metal_id')
            ->map(function (Collection $group): array {
                $first = $group->first();
                $poolKg = (float) $group->sum('quantity_kg');
                $poolValue = (float) $group->sum('value');

                $owners = $group
                    ->sortByDesc('quantity_kg')
                    ->values()
                    ->map(function (array $owner) use ($poolKg): array {
                        $share = $poolKg > 0 ? (($owner['quantity_kg'] / $poolKg) * 100) : 0.0;

                        return [
                            'customer_id' => $owner['customer_id'],
                            'customer_name' => $owner['customer_name'],
                            'quantity_kg' => $owner['quantity_kg'],
                            'pool_share_percentage' => $share,
                            'value' => $owner['value'],
                        ];
                    })
                    ->all();

                return [
                    'metal_id' => $first['metal_id'],
                    'metal_name' => $first['metal_name'],
                    'metal_code' => $first['metal_code'],
                    'metal_color' => $first['metal_color'],
                    'total_kg' => $poolKg,
                    'total_value' => $poolValue,
                    'owners' => $owners,
                ];
            })
            ->sortBy('metal_name')
            ->values();

        $poolTotalsByMetal = $pools
            ->mapWithKeys(fn (array $pool): array => [$pool['metal_id'] => $pool['total_kg']]);

        $positions = $positionRows
            ->map(function (array $position) use ($poolTotalsByMetal): array {
                $poolKg = (float) ($poolTotalsByMetal[$position['metal_id']] ?? 0.0);
                $position['pool_share_percentage'] = $poolKg > 0
                    ? (($position['quantity_kg'] / $poolKg) * 100)
                    : 0.0;

                return $position;
            })
            ->sortBy(['customer_name', 'metal_name'])
            ->values()
            ->all();

        return [
            'totals' => [
                'holdings_kg' => (float) $pools->sum('total_kg'),
                'value' => (float) $pools->sum('total_value'),
                'active_positions' => count($positions),
            ],
            'pools' => $pools->all(),
            'positions' => $positions,
        ];
    }

    protected function unallocatedDepositTotalsSubquery(): EloquentBuilder|Builder
    {
        return Deposit::query()
            ->where('storage_type', CustomerStorageType::Unallocated->value)
            ->groupBy('customer_id', 'metal_id')
            ->selectRaw('customer_id, metal_id, SUM(quantity_kg) as deposited_kg');
    }

    protected function unallocatedWithdrawalTotalsSubquery(): EloquentBuilder|Builder
    {
        if (! Schema::hasTable('withdrawals')) {
            return DB::query()
                ->selectRaw('NULL as customer_id, NULL as metal_id, 0 as withdrawn_kg')
                ->whereRaw('1 = 0');
        }

        return Withdrawal::query()
            ->where('storage_type', CustomerStorageType::Unallocated->value)
            ->groupBy('customer_id', 'metal_id')
            ->selectRaw('customer_id, metal_id, SUM(quantity_kg) as withdrawn_kg');
    }

    protected function metalColor(string $metalCode): string
    {
        return match (strtoupper($metalCode)) {
            'AU' => 'amber',
            'AG' => 'slate',
            'PT' => 'gray',
            default => 'zinc',
        };
    }
}
