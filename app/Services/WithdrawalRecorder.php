<?php

namespace App\Services;

use App\Enums\CustomerAccountType;
use App\Enums\CustomerStorageType;
use App\Models\Bar;
use App\Models\Customer;
use App\Models\Metal;
use App\Models\MetalPool;
use App\Models\UnallocatedPosition;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WithdrawalRecorder
{
    /**
     * @param  array{
     *     customer_id: string,
     *     metal_id: string,
     *     quantity_kg?: numeric-string|float|int,
     *     bar_ids?: array<int, string>
     * }  $payload
     *
     * @throws ValidationException
     */
    public function record(array $payload): Withdrawal
    {
        $validator = Validator::make($payload, [
            'customer_id' => ['required', 'exists:customers,id'],
            'metal_id' => ['required', 'exists:metals,id'],
            'quantity_kg' => ['nullable', 'numeric', 'gt:0'],
            'bar_ids' => ['nullable', 'array'],
            'bar_ids.*' => ['string', 'distinct'],
        ]);

        $validated = $validator->validate();

        $customer = Customer::query()->findOrFail($validated['customer_id']);
        Metal::query()->findOrFail($validated['metal_id']);

        $storageType = $customer->account_type === CustomerAccountType::Institutional
            ? CustomerStorageType::Allocated
            : CustomerStorageType::Unallocated;

        return DB::transaction(function () use ($customer, $storageType, $validated): Withdrawal {
            $selectedBars = collect();
            $quantityKg = $validated['quantity_kg'] ?? null;
            $unitsToDebit = 0.0;

            if ($storageType === CustomerStorageType::Allocated) {
                $barIds = $validated['bar_ids'] ?? [];

                if ($barIds === []) {
                    throw ValidationException::withMessages([
                        'bar_ids' => 'At least one bar must be selected for allocated withdrawals.',
                    ]);
                }

                $selectedBars = Bar::query()
                    ->whereIn('id', $barIds)
                    ->whereNull('withdrawal_id')
                    ->whereHas('deposit', function ($query) use ($validated): void {
                        $query->where('customer_id', $validated['customer_id'])
                            ->where('metal_id', $validated['metal_id'])
                            ->where('storage_type', CustomerStorageType::Allocated->value);
                    })
                    ->lockForUpdate()
                    ->get();

                if ($selectedBars->count() !== count($barIds)) {
                    throw ValidationException::withMessages([
                        'bar_ids' => 'One or more selected bars are not available for this customer and metal.',
                    ]);
                }

                $quantityKg = $selectedBars->sum(fn (Bar $bar): float => (float) $bar->weight_kg);
            } else {
                if ($quantityKg === null || (float) $quantityKg <= 0) {
                    throw ValidationException::withMessages([
                        'quantity_kg' => 'Quantity is required for unallocated withdrawals.',
                    ]);
                }

                $pool = MetalPool::query()
                    ->where('metal_id', $validated['metal_id'])
                    ->lockForUpdate()
                    ->first();

                $position = UnallocatedPosition::query()
                    ->where('customer_id', $validated['customer_id'])
                    ->where('metal_id', $validated['metal_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $pool || ! $position) {
                    throw ValidationException::withMessages([
                        'quantity_kg' => 'No unallocated holdings available for this customer and metal.',
                    ]);
                }

                $poolQuantityKg = (float) $pool->total_quantity_kg;
                $poolUnits = (float) $pool->total_units;
                $positionUnits = (float) $position->units;
                $quantityToWithdrawKg = (float) $quantityKg;

                if ($poolQuantityKg <= 0 || $poolUnits <= 0) {
                    throw ValidationException::withMessages([
                        'quantity_kg' => 'No unallocated holdings available for this metal pool.',
                    ]);
                }

                $availableQuantityKg = $positionUnits * ($poolQuantityKg / $poolUnits);
                if ($quantityToWithdrawKg > $availableQuantityKg) {
                    throw ValidationException::withMessages([
                        'quantity_kg' => 'Requested quantity exceeds available unallocated balance.',
                    ]);
                }

                $unitsToDebit = $quantityToWithdrawKg * ($poolUnits / $poolQuantityKg);
                if ($unitsToDebit > $positionUnits) {
                    throw ValidationException::withMessages([
                        'quantity_kg' => 'Requested quantity exceeds available unallocated balance.',
                    ]);
                }

                $pool->update([
                    'total_quantity_kg' => number_format(max(0, $poolQuantityKg - $quantityToWithdrawKg), 4, '.', ''),
                    'total_units' => number_format(max(0, $poolUnits - $unitsToDebit), 8, '.', ''),
                ]);

                $position->update([
                    'units' => number_format(max(0, $positionUnits - $unitsToDebit), 8, '.', ''),
                ]);
            }

            $withdrawal = Withdrawal::query()->create([
                'customer_id' => $customer->id,
                'metal_id' => $validated['metal_id'],
                'quantity_kg' => number_format((float) $quantityKg, 4, '.', ''),
            ]);

            if ($selectedBars->isNotEmpty()) {
                $selectedBars->each(function (Bar $bar) use ($withdrawal): void {
                    $bar->update([
                        'withdrawal_id' => $withdrawal->id,
                        'withdrawn_at' => $withdrawal->withdrawn_at,
                    ]);
                });
            }

            $currentPortfolioValue = (float) ($customer->portfolio_value ?? 0);
            $nextPortfolioValue = max(0, $currentPortfolioValue - (float) $withdrawal->value_snapshot);

            $customer->update([
                'portfolio_value' => number_format($nextPortfolioValue, 2, '.', ''),
            ]);

            return $withdrawal->refresh();
        });
    }
}
