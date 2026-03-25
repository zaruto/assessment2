<?php

namespace App\Filament\Resources\MarketPrices\Pages;

use App\Filament\Resources\MarketPrices\MarketPriceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMarketPrice extends CreateRecord
{
    protected static string $resource = MarketPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
