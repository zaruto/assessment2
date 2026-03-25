<?php

namespace App\Filament\Resources\Customers\Widgets;

use App\Enums\CustomerStorageType;
use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerOverview extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalCustomers = Customer::query()->count();
        $allocatedCustomers = Customer::query()
            ->where('storage_type', CustomerStorageType::Allocated->value)
            ->count();
        $unallocatedCustomers = Customer::query()
            ->where('storage_type', CustomerStorageType::Unallocated->value)
            ->count();
        $customersWithoutDeposits = Customer::query()
            ->whereDoesntHave('deposits')
            ->count();

        return [
            Stat::make('Total Customers', number_format($totalCustomers)),
            Stat::make('Allocated Accounts', number_format($allocatedCustomers)),
            Stat::make('Unallocated Accounts', number_format($unallocatedCustomers)),
            Stat::make('No Deposits Yet', number_format($customersWithoutDeposits)),
        ];
    }
}
