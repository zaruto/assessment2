<?php

namespace App\Filament\Resources\MarketPrices\Pages;

use App\Filament\Resources\MarketPrices\MarketPriceResource;
use App\Filament\Resources\MarketPrices\Widgets\MarketPriceOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarketPrices extends ListRecords
{
    protected static string $resource = MarketPriceResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            MarketPriceOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth('md')
                ->modal(),
        ];
    }
}
