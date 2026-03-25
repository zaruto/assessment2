<?php

namespace App\Services;

use App\Enums\CustomerAccountType;
use App\Enums\CustomerStorageType;
use App\Models\Bar;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\Metal;
use App\Models\MetalPool;
use App\Models\UnallocatedPosition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DepositRecorder
{
    /**
     * @param  array{
     *     customer_id: string,
     *     metal_id: string,
     *     quantity_kg?: numeric-string|float|int,
     *     bars?: array<int, array{
     *         serial_number?: string|null,
     *         weight_kg?: numeric-string|float|int|null
     *     }>
     * }  $payload
     *
     * @throws ValidationException
     */
    public function record(array $payload): Deposit
    {
        $validator = Validator::make($payload, [
            'customer_id' => ['required', 'exists:customers,id'],
            'metal_id' => ['required', 'exists:metals,id'],
            'quantity_kg' => ['nullable', 'numeric', 'gt:0'],
            'bars' => ['nullable', 'array'],
            'bars.*.serial_number' => ['nullable', 'string'],
            'bars.*.weight_kg' => ['nullable', 'numeric', 'gt:0'],
        ]);

        $validated = $validator->validate();

        $customer = Customer::query()->findOrFail($validated['customer_id']);
        Metal::query()->findOrFail($validated['metal_id']);

        $storageType = $customer->account_type === CustomerAccountType::Institutional
            ? CustomerStorageType::Allocated
            : CustomerStorageType::Unallocated;

        $bars = $this->normalizeBars($validated['bars'] ?? []);
        $quantityKg = $validated['quantity_kg'] ?? null;

        if ($storageType === CustomerStorageType::Allocated) {
            if ($bars === []) {
                throw ValidationException::withMessages([
                    'bars' => 'At least one bar with serial number and weight is required for allocated storage.',
                ]);
            }

            $duplicatesInPayload = collect($bars)
                ->pluck('serial_number')
                ->countBy()
                ->filter(fn (int $count): bool => $count > 1)
                ->keys()
                ->values();

            if ($duplicatesInPayload->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'bars' => 'Duplicate serial numbers in the same deposit are not allowed: '.$duplicatesInPayload->join(', '),
                ]);
            }

            $existingSerials = Bar::query()
                ->whereIn('serial_number', collect($bars)->pluck('serial_number')->all())
                ->pluck('serial_number')
                ->all();

            if ($existingSerials !== []) {
                throw ValidationException::withMessages([
                    'bars' => 'Serial numbers already exist: '.implode(', ', $existingSerials),
                ]);
            }

            $quantityKg = collect($bars)->sum(fn (array $bar): float => (float) $bar['weight_kg']);
        } else {
            $bars = [];
            if ($quantityKg === null || (float) $quantityKg <= 0) {
                throw ValidationException::withMessages([
                    'quantity_kg' => 'Quantity is required for unallocated storage.',
                ]);
            }
        }

        return DB::transaction(function () use ($bars, $customer, $quantityKg, $validated): Deposit {
            if ($bars === []) {
                $pool = MetalPool::query()
                    ->where('metal_id', $validated['metal_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $pool) {
                    $pool = MetalPool::query()->create([
                        'metal_id' => $validated['metal_id'],
                        'total_quantity_kg' => 0,
                        'total_units' => 0,
                    ]);
                }

                $position = UnallocatedPosition::query()
                    ->where('customer_id', $validated['customer_id'])
                    ->where('metal_id', $validated['metal_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $position) {
                    $position = UnallocatedPosition::query()->create([
                        'customer_id' => $validated['customer_id'],
                        'metal_id' => $validated['metal_id'],
                        'units' => 0,
                    ]);
                }

                $quantityToDepositKg = (float) $quantityKg;
                $poolQuantityKg = (float) $pool->total_quantity_kg;
                $poolUnits = (float) $pool->total_units;

                $unitsToCredit = ($poolQuantityKg <= 0 || $poolUnits <= 0)
                    ? $quantityToDepositKg
                    : $quantityToDepositKg * ($poolUnits / $poolQuantityKg);

                $pool->update([
                    'total_quantity_kg' => number_format($poolQuantityKg + $quantityToDepositKg, 4, '.', ''),
                    'total_units' => number_format($poolUnits + $unitsToCredit, 8, '.', ''),
                ]);

                $position->update([
                    'units' => number_format(((float) $position->units) + $unitsToCredit, 8, '.', ''),
                ]);
            }

            $deposit = Deposit::query()->create([
                'customer_id' => $customer->id,
                'metal_id' => $validated['metal_id'],
                'quantity_kg' => number_format((float) $quantityKg, 4, '.', ''),
            ]);

            if ($bars !== []) {
                $deposit->bars()->createMany(
                    $bars,
                );
            }

            $currentPortfolioValue = (float) ($customer->portfolio_value ?? 0);
            $customer->update([
                'portfolio_value' => number_format(
                    $currentPortfolioValue + (float) $deposit->value_snapshot,
                    2,
                    '.',
                    '',
                ),
            ]);

            return $deposit->refresh();
        });
    }

    /**
     * @param  array<int, array{serial_number?: string|null, weight_kg?: numeric-string|float|int|null}>  $bars
     * @return array<int, array{serial_number: string, weight_kg: string}>
     */
    protected function normalizeBars(array $bars): array
    {
        return collect($bars)
            ->map(function (array $bar): ?array {
                $serialNumber = trim((string) ($bar['serial_number'] ?? ''));
                $weight = $bar['weight_kg'] ?? null;

                if ($serialNumber === '' || $weight === null || (float) $weight <= 0) {
                    return null;
                }

                return [
                    'serial_number' => $serialNumber,
                    'weight_kg' => number_format((float) $weight, 4, '.', ''),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
