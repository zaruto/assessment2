<?php

namespace App\Filament\Resources\MarketPrices;

use App\Filament\Resources\MarketPrices\Schemas\MarketPriceForm;
use App\Filament\Resources\MarketPrices\Tables\MarketPricesTable;
use App\Models\Metal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MarketPriceResource extends Resource
{
    protected static ?string $model = Metal::class;

    protected static ?string $slug = 'market-prices';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentCurrencyDollar;

    public static function form(Schema $schema): Schema
    {
        return MarketPriceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketPricesTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketPrices::route('/'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
