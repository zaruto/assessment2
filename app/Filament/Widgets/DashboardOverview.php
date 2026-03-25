<?php

namespace App\Filament\Widgets;

use App\Models\Bar;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Services\InventorySnapshotService;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardOverview extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = CarbonImmutable::today();
        $inventorySnapshotService = app(InventorySnapshotService::class);

        $allocatedSnapshot = $inventorySnapshotService->allocatedSnapshot();
        $unallocatedSnapshot = $inventorySnapshotService->unallocatedSnapshot();

        $totalCustomers = Customer::query()->count();
        $allocatedBarsInCustody = Bar::query()
            ->whereNull('withdrawal_id')
            ->count();
        $totalHoldingsKg = (float) $allocatedSnapshot['totals']['weight_kg'] + (float) $unallocatedSnapshot['totals']['holdings_kg'];
        $totalAssetValue = (float) $allocatedSnapshot['totals']['value'] + (float) $unallocatedSnapshot['totals']['value'];
        $depositsLast30Days = Deposit::query()
            ->whereDate('deposited_at', '>=', $today->subDays(29)->toDateString())
            ->count();
        $withdrawalsLast30Days = Withdrawal::query()
            ->whereDate('withdrawn_at', '>=', $today->subDays(29)->toDateString())
            ->count();

        return [
            Stat::make('Total Customers', number_format($totalCustomers)),
            Stat::make('Assets in Custody (kg)', number_format($totalHoldingsKg, 2)),
            Stat::make('Live Asset Value', 'MVR '.number_format($totalAssetValue, 2)),
            Stat::make('Allocated Bars in Custody', number_format($allocatedBarsInCustody)),
            Stat::make('Deposits (30 Days)', number_format($depositsLast30Days)),
            Stat::make('Withdrawals (30 Days)', number_format($withdrawalsLast30Days)),
        ];
    }
}
