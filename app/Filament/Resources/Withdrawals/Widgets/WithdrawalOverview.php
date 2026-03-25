<?php

namespace App\Filament\Resources\Withdrawals\Widgets;

use App\Models\Withdrawal;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WithdrawalOverview extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = CarbonImmutable::today();

        $withdrawalsToday = Withdrawal::query()
            ->whereDate('withdrawn_at', $today->toDateString())
            ->count();

        $withdrawalsLast30Days = Withdrawal::query()
            ->whereDate('withdrawn_at', '>=', $today->subDays(29)->toDateString())
            ->count();
        $allTimeWithdrawals = Withdrawal::query()->count();
        $customersWithWithdrawals = Withdrawal::query()->distinct('customer_id')->count('customer_id');

        return [
            Stat::make('Withdrawals Today', number_format($withdrawalsToday)),
            Stat::make('Withdrawals (30 Days)', number_format($withdrawalsLast30Days)),
            Stat::make('Withdrawals (All Time)', number_format($allTimeWithdrawals)),
            Stat::make('Customers With Withdrawals', number_format($customersWithWithdrawals)),
        ];
    }
}
