<?php

namespace App\Filament\Resources\Deposits\Widgets;

use App\Models\Deposit;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DepositOverview extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = CarbonImmutable::today();

        $depositsToday = Deposit::query()
            ->whereDate('deposited_at', $today->toDateString())
            ->count();
        $depositsLast30Days = Deposit::query()
            ->whereDate('deposited_at', '>=', $today->subDays(29)->toDateString())
            ->count();
        $allTimeDeposits = Deposit::query()->count();
        $customersWithDeposits = Deposit::query()->distinct('customer_id')->count('customer_id');

        return [
            Stat::make('Deposits Today', number_format($depositsToday)),
            Stat::make('Deposits (30 Days)', number_format($depositsLast30Days)),
            Stat::make('Deposits (All Time)', number_format($allTimeDeposits)),
            Stat::make('Customers With Deposits', number_format($customersWithDeposits)),
        ];
    }
}
