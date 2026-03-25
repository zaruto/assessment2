<?php

namespace App\Filament\Resources\MarketPrices\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MarketPriceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->autocomplete('off')
                    ->columnSpanFull()
                    ->required(),

                TextInput::make('code')
                    ->autocomplete('off')
                    ->columnSpanFull()
                    ->required(),

                TextInput::make('price')
                    ->helperText('Price per kg')
                    ->prefix('MVR')
                    ->columnSpanFull()
                    ->numeric()
                    ->required(),
            ]);
    }
}
