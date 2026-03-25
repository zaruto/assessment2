<?php

namespace Database\Seeders;

use App\Enums\CustomerAccountType;
use App\Enums\CustomerStorageType;
use App\Models\Bar;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\Metal;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\DepositRecorder;
use App\Services\WithdrawalRecorder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ],
        );

        $metals = collect([
            ['name' => 'Gold', 'code' => 'AU', 'price' => 98000],
            ['name' => 'Silver', 'code' => 'AG', 'price' => 1250],
            ['name' => 'Platinum', 'code' => 'PT', 'price' => 42000],
        ])->mapWithKeys(function (array $metal): array {
            return [
                $metal['code'] => Metal::query()->updateOrCreate(
                    ['code' => $metal['code']],
                    ['name' => $metal['name'], 'price' => $metal['price']],
                ),
            ];
        });

        $customers = collect([
            [
                'name' => 'Atlas Capital',
                'email' => 'atlas.capital@demo.local',
                'account_type' => CustomerAccountType::Institutional->value,
                'storage_type' => CustomerStorageType::Allocated->value,
                'joined_at' => Carbon::now()->subMonths(14),
            ],
            [
                'name' => 'Meridian Trust',
                'email' => 'meridian.trust@demo.local',
                'account_type' => CustomerAccountType::Institutional->value,
                'storage_type' => CustomerStorageType::Allocated->value,
                'joined_at' => Carbon::now()->subMonths(10),
            ],
            [
                'name' => 'Northstar Holdings',
                'email' => 'northstar.holdings@demo.local',
                'account_type' => CustomerAccountType::Institutional->value,
                'storage_type' => CustomerStorageType::Allocated->value,
                'joined_at' => Carbon::now()->subMonths(9),
            ],
            [
                'name' => 'Helix Treasury',
                'email' => 'helix.treasury@demo.local',
                'account_type' => CustomerAccountType::Institutional->value,
                'storage_type' => CustomerStorageType::Allocated->value,
                'joined_at' => Carbon::now()->subMonths(7),
            ],
            [
                'name' => 'Sara Retail',
                'email' => 'sara.retail@demo.local',
                'account_type' => CustomerAccountType::Retail->value,
                'storage_type' => CustomerStorageType::Unallocated->value,
                'joined_at' => Carbon::now()->subMonths(8),
            ],
            [
                'name' => 'Omar Retail',
                'email' => 'omar.retail@demo.local',
                'account_type' => CustomerAccountType::Retail->value,
                'storage_type' => CustomerStorageType::Unallocated->value,
                'joined_at' => Carbon::now()->subMonths(6),
            ],
            [
                'name' => 'Lina Retail',
                'email' => 'lina.retail@demo.local',
                'account_type' => CustomerAccountType::Retail->value,
                'storage_type' => CustomerStorageType::Unallocated->value,
                'joined_at' => Carbon::now()->subMonths(5),
            ],
            [
                'name' => 'Dev Retail',
                'email' => 'dev.retail@demo.local',
                'account_type' => CustomerAccountType::Retail->value,
                'storage_type' => CustomerStorageType::Unallocated->value,
                'joined_at' => Carbon::now()->subMonths(4),
            ],
            [
                'name' => 'Maya Retail',
                'email' => 'maya.retail@demo.local',
                'account_type' => CustomerAccountType::Retail->value,
                'storage_type' => CustomerStorageType::Unallocated->value,
                'joined_at' => Carbon::now()->subMonths(4),
            ],
            [
                'name' => 'Noah Retail',
                'email' => 'noah.retail@demo.local',
                'account_type' => CustomerAccountType::Retail->value,
                'storage_type' => CustomerStorageType::Unallocated->value,
                'joined_at' => Carbon::now()->subMonths(3),
            ],
        ])->mapWithKeys(function (array $customer): array {
            return [
                $customer['email'] => Customer::query()->firstOrCreate(
                    ['email' => $customer['email']],
                    [
                        'name' => $customer['name'],
                        'account_type' => $customer['account_type'],
                        'storage_type' => $customer['storage_type'],
                        'joined_at' => $customer['joined_at'],
                        'portfolio_value' => 0,
                    ],
                ),
            ];
        });

        $this->seedTransactions($customers, $metals);
    }

    /**
     * @param  Collection<string, Customer>  $customers
     * @param  Collection<string, Metal>  $metals
     */
    protected function seedTransactions(Collection $customers, Collection $metals): void
    {
        $depositRecorder = app(DepositRecorder::class);
        $withdrawalRecorder = app(WithdrawalRecorder::class);

        $atlas = $customers['atlas.capital@demo.local'];
        if (! $this->hasDeposit($atlas, $metals['AU']->id, CustomerStorageType::Allocated)) {
            $depositRecorder->record([
                'customer_id' => $atlas->id,
                'metal_id' => $metals['AU']->id,
                'bars' => [
                    ['serial_number' => 'BM-AU-ATLAS-0001', 'weight_kg' => 12.5],
                    ['serial_number' => 'BM-AU-ATLAS-0002', 'weight_kg' => 10.0],
                    ['serial_number' => 'BM-AU-ATLAS-0003', 'weight_kg' => 7.5],
                ],
            ]);
        }

        $meridian = $customers['meridian.trust@demo.local'];
        if (! $this->hasDeposit($meridian, $metals['PT']->id, CustomerStorageType::Allocated)) {
            $depositRecorder->record([
                'customer_id' => $meridian->id,
                'metal_id' => $metals['PT']->id,
                'bars' => [
                    ['serial_number' => 'BM-PT-MERIDIAN-0001', 'weight_kg' => 5.0],
                    ['serial_number' => 'BM-PT-MERIDIAN-0002', 'weight_kg' => 4.0],
                ],
            ]);
        }

        $northstar = $customers['northstar.holdings@demo.local'];
        if (! $this->hasDeposit($northstar, $metals['AU']->id, CustomerStorageType::Allocated)) {
            $depositRecorder->record([
                'customer_id' => $northstar->id,
                'metal_id' => $metals['AU']->id,
                'bars' => [
                    ['serial_number' => 'BM-AU-NORTHSTAR-0001', 'weight_kg' => 9.2],
                    ['serial_number' => 'BM-AU-NORTHSTAR-0002', 'weight_kg' => 8.8],
                ],
            ]);
        }
        if (! $this->hasDeposit($northstar, $metals['PT']->id, CustomerStorageType::Allocated)) {
            $depositRecorder->record([
                'customer_id' => $northstar->id,
                'metal_id' => $metals['PT']->id,
                'bars' => [
                    ['serial_number' => 'BM-PT-NORTHSTAR-0001', 'weight_kg' => 6.4],
                ],
            ]);
        }

        $helix = $customers['helix.treasury@demo.local'];
        if (! $this->hasDeposit($helix, $metals['AG']->id, CustomerStorageType::Allocated)) {
            $depositRecorder->record([
                'customer_id' => $helix->id,
                'metal_id' => $metals['AG']->id,
                'bars' => [
                    ['serial_number' => 'BM-AG-HELIX-0001', 'weight_kg' => 14.0],
                    ['serial_number' => 'BM-AG-HELIX-0002', 'weight_kg' => 11.0],
                    ['serial_number' => 'BM-AG-HELIX-0003', 'weight_kg' => 10.5],
                ],
            ]);
        }

        $sara = $customers['sara.retail@demo.local'];
        if (! $this->hasDeposit($sara, $metals['AU']->id, CustomerStorageType::Unallocated)) {
            $depositRecorder->record([
                'customer_id' => $sara->id,
                'metal_id' => $metals['AU']->id,
                'quantity_kg' => 8.0,
            ]);
        }
        if (! $this->hasDeposit($sara, $metals['AG']->id, CustomerStorageType::Unallocated)) {
            $depositRecorder->record([
                'customer_id' => $sara->id,
                'metal_id' => $metals['AG']->id,
                'quantity_kg' => 25.0,
            ]);
        }

        $omar = $customers['omar.retail@demo.local'];
        if (! $this->hasDeposit($omar, $metals['AG']->id, CustomerStorageType::Unallocated)) {
            $depositRecorder->record([
                'customer_id' => $omar->id,
                'metal_id' => $metals['AG']->id,
                'quantity_kg' => 18.0,
            ]);
        }
        if (! $this->hasDeposit($omar, $metals['PT']->id, CustomerStorageType::Unallocated)) {
            $depositRecorder->record([
                'customer_id' => $omar->id,
                'metal_id' => $metals['PT']->id,
                'quantity_kg' => 3.5,
            ]);
        }

        $lina = $customers['lina.retail@demo.local'];
        if (! $this->hasDeposit($lina, $metals['AU']->id, CustomerStorageType::Unallocated)) {
            $depositRecorder->record([
                'customer_id' => $lina->id,
                'metal_id' => $metals['AU']->id,
                'quantity_kg' => 4.0,
            ]);
        }
        if (! $this->hasDeposit($lina, $metals['PT']->id, CustomerStorageType::Unallocated)) {
            $depositRecorder->record([
                'customer_id' => $lina->id,
                'metal_id' => $metals['PT']->id,
                'quantity_kg' => 2.0,
            ]);
        }

        $dev = $customers['dev.retail@demo.local'];
        if (! $this->hasDeposit($dev, $metals['AG']->id, CustomerStorageType::Unallocated)) {
            $depositRecorder->record([
                'customer_id' => $dev->id,
                'metal_id' => $metals['AG']->id,
                'quantity_kg' => 12.0,
            ]);
        }

        $maya = $customers['maya.retail@demo.local'];
        if (! $this->hasDeposit($maya, $metals['AU']->id, CustomerStorageType::Unallocated)) {
            $depositRecorder->record([
                'customer_id' => $maya->id,
                'metal_id' => $metals['AU']->id,
                'quantity_kg' => 6.0,
            ]);
        }

        $noah = $customers['noah.retail@demo.local'];
        if (! $this->hasDeposit($noah, $metals['PT']->id, CustomerStorageType::Unallocated)) {
            $depositRecorder->record([
                'customer_id' => $noah->id,
                'metal_id' => $metals['PT']->id,
                'quantity_kg' => 1.2,
            ]);
        }

        if (! $this->hasWithdrawal($atlas, $metals['AU']->id, CustomerStorageType::Allocated)) {
            $barToWithdraw = Bar::query()
                ->whereNull('withdrawal_id')
                ->whereHas('deposit', function ($query) use ($atlas, $metals): void {
                    $query
                        ->where('customer_id', $atlas->id)
                        ->where('metal_id', $metals['AU']->id);
                })
                ->first();

            if ($barToWithdraw) {
                $withdrawalRecorder->record([
                    'customer_id' => $atlas->id,
                    'metal_id' => $metals['AU']->id,
                    'bar_ids' => [$barToWithdraw->id],
                ]);
            }
        }

        if (! $this->hasWithdrawal($meridian, $metals['PT']->id, CustomerStorageType::Allocated)) {
            $barToWithdraw = Bar::query()
                ->whereNull('withdrawal_id')
                ->whereHas('deposit', function ($query) use ($meridian, $metals): void {
                    $query
                        ->where('customer_id', $meridian->id)
                        ->where('metal_id', $metals['PT']->id);
                })
                ->first();

            if ($barToWithdraw) {
                $withdrawalRecorder->record([
                    'customer_id' => $meridian->id,
                    'metal_id' => $metals['PT']->id,
                    'bar_ids' => [$barToWithdraw->id],
                ]);
            }
        }

        if (! $this->hasWithdrawal($helix, $metals['AG']->id, CustomerStorageType::Allocated)) {
            $barToWithdraw = Bar::query()
                ->whereNull('withdrawal_id')
                ->whereHas('deposit', function ($query) use ($helix, $metals): void {
                    $query
                        ->where('customer_id', $helix->id)
                        ->where('metal_id', $metals['AG']->id);
                })
                ->first();

            if ($barToWithdraw) {
                $withdrawalRecorder->record([
                    'customer_id' => $helix->id,
                    'metal_id' => $metals['AG']->id,
                    'bar_ids' => [$barToWithdraw->id],
                ]);
            }
        }

        if (! $this->hasWithdrawal($sara, $metals['AU']->id, CustomerStorageType::Unallocated)) {
            $withdrawalRecorder->record([
                'customer_id' => $sara->id,
                'metal_id' => $metals['AU']->id,
                'quantity_kg' => 2.0,
            ]);
        }

        if (! $this->hasWithdrawal($omar, $metals['AG']->id, CustomerStorageType::Unallocated)) {
            $withdrawalRecorder->record([
                'customer_id' => $omar->id,
                'metal_id' => $metals['AG']->id,
                'quantity_kg' => 5.0,
            ]);
        }

        if (! $this->hasWithdrawal($lina, $metals['PT']->id, CustomerStorageType::Unallocated)) {
            $withdrawalRecorder->record([
                'customer_id' => $lina->id,
                'metal_id' => $metals['PT']->id,
                'quantity_kg' => 0.5,
            ]);
        }

        if (! $this->hasWithdrawal($maya, $metals['AU']->id, CustomerStorageType::Unallocated)) {
            $withdrawalRecorder->record([
                'customer_id' => $maya->id,
                'metal_id' => $metals['AU']->id,
                'quantity_kg' => 0.8,
            ]);
        }
    }

    protected function hasDeposit(Customer $customer, string $metalId, CustomerStorageType $storageType): bool
    {
        return Deposit::query()
            ->where('customer_id', $customer->id)
            ->where('metal_id', $metalId)
            ->where('storage_type', $storageType->value)
            ->exists();
    }

    protected function hasWithdrawal(Customer $customer, string $metalId, CustomerStorageType $storageType): bool
    {
        return Withdrawal::query()
            ->where('customer_id', $customer->id)
            ->where('metal_id', $metalId)
            ->where('storage_type', $storageType->value)
            ->exists();
    }
}
