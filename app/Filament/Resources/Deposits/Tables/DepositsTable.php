<?php

namespace App\Filament\Resources\Deposits\Tables;

use App\Enums\CustomerStorageType;
use App\Models\Deposit;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DepositsTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label('Deposit #')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('metal_display')
                    ->label('Metal')
                    ->state(fn (Deposit $record): string => "{$record->metal->name} ({$record->metal->code})")
                    ->icon(Heroicon::CircleStack)
                    ->iconColor(fn (Deposit $record): string => static::metalColor($record)),

                TextColumn::make('storage_type')
                    ->label('Storage')
                    ->badge()
                    ->sortable(),

                TextColumn::make('quantity_kg')
                    ->label('Quantity')
                    ->formatStateUsing(fn (string $state): string => number_format((float) $state, 2).' kg')
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('value_snapshot')
                    ->label('Value')
                    ->formatStateUsing(fn (string $state): string => 'MVR '.number_format((float) $state, 2))
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('deposited_at')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('deposited_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    protected static function metalColor(Deposit $deposit): string
    {
        return match (strtoupper($deposit->metal->code)) {
            'AU' => 'warning',
            'AG' => 'info',
            'PT' => 'gray',
            default => $deposit->storage_type === CustomerStorageType::Allocated ? 'success' : 'primary',
        };
    }
}
