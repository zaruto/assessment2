<?php

namespace App\Filament\Resources\MarketPrices\Widgets;

use App\Models\Metal;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MarketPriceOverview extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $metals = Metal::query()->get()->keyBy(fn (Metal $metal): string => strtoupper($metal->code));
        $lastUpdatedAt = Metal::query()->max('updated_at');

        $trackedMetals = $metals->count();
        $coreMetalsTracked = collect(['AU', 'AG', 'PT'])
            ->filter(fn (string $code): bool => $metals->has($code))
            ->count();
        $updatedInLast24Hours = Metal::query()
            ->where('updated_at', '>=', now()->subDay())
            ->count();
        $daysSinceLastUpdate = filled($lastUpdatedAt)
            ? Carbon::parse($lastUpdatedAt)->diffInDays(now())
            : null;

        return [
            Stat::make('Tracked Metals', number_format($trackedMetals)),
            Stat::make('Core Metals Tracked', number_format($coreMetalsTracked)),
            Stat::make('Updated in Last 24h', number_format($updatedInLast24Hours)),
            Stat::make('Days Since Last Update', $daysSinceLastUpdate !== null ? number_format($daysSinceLastUpdate) : '-'),
        ];
    }
}
