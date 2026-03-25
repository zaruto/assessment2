<?php

namespace App\Filament\Resources\Deposits\Schemas;

use App\Enums\CustomerAccountType;
use App\Enums\CustomerStorageType;
use App\Models\Customer;
use App\Models\Metal;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class DepositForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::components());
    }

    /**
     * @return array<int, Component>
     */
    public static function components(): array
    {
        return [
            Select::make('customer_id')
                ->label('Customer')
                ->options(static::customerOptions())
                ->native(false)
                ->preload()
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set, mixed $state): void {
                    $customer = Customer::query()->find($state);

                    if (! $customer) {
                        $set('storage_type', null);
                        $set('bars', []);

                        return;
                    }

                    $isInstitutional = $customer->account_type === CustomerAccountType::Institutional;
                    $storageType = $isInstitutional
                        ? CustomerStorageType::Allocated->value
                        : CustomerStorageType::Unallocated->value;

                    $set('storage_type', $storageType);

                    if (! $isInstitutional) {
                        $set('bars', []);
                    }
                })
                ->helperText(function (Get $get): ?string {
                    $customer = Customer::query()->find($get('customer_id'));

                    if (! $customer) {
                        return null;
                    }

                    return $customer->account_type === CustomerAccountType::Institutional
                        ? 'Bars are individually tracked with serial numbers.'
                        : 'Metals stored in pooled bulk; customer holds a percentage share.';
                }),

            Hidden::make('storage_type'),

            Select::make('metal_id')
                ->label('Metal Type')
                ->options(static::metalOptions())
                ->native(false)
                ->preload()
                ->searchable()
                ->required(),

            TextInput::make('quantity_kg')
                ->label('Quantity (kg)')
                ->placeholder('e.g., 12.5')
                ->numeric()
                ->minValue(0.0001)
                ->readOnly(fn (Get $get): bool => $get('storage_type') === CustomerStorageType::Allocated->value)
                ->helperText(fn (Get $get): ?string => $get('storage_type') === CustomerStorageType::Allocated->value
                    ? 'Auto-calculated from bar weights.'
                    : null)
                ->required(),

            Repeater::make('bars')
                ->label('Bars')
                ->schema([
                    TextInput::make('serial_number')
                        ->label('Bar Serial Number')
                        ->placeholder('e.g., AU-2024-00010')
                        ->required(),
                    TextInput::make('weight_kg')
                        ->label('Weight (kg)')
                        ->numeric()
                        ->minValue(0.0001)
                        ->step('0.0001')
                        ->required(),
                ])
                ->columns(2)
                ->addActionLabel('Add Bar')
                ->defaultItems(1)
                ->live()
                ->afterStateUpdated(function (Set $set, ?array $state): void {
                    $set('quantity_kg', static::sumBarWeights($state));
                })
                ->visible(fn (Get $get): bool => $get('storage_type') === CustomerStorageType::Allocated->value)
                ->helperText('Enter each bar serial and its exact weight. Total quantity is calculated automatically.'),
        ];
    }

    protected static function sumBarWeights(?array $bars): string
    {
        $sum = collect($bars ?? [])
            ->sum(fn (array $bar): float => (float) ($bar['weight_kg'] ?? 0));

        return number_format($sum, 4, '.', '');
    }

    /**
     * @return array<string, string>
     */
    protected static function customerOptions(): array
    {
        return Customer::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(
                fn (Customer $customer): array => [
                    $customer->id => "{$customer->name} (".strtolower($customer->account_type->value).')',
                ],
            )
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected static function metalOptions(): array
    {
        return Metal::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(
                fn (Metal $metal): array => [
                    $metal->id => "{$metal->name} ({$metal->code})",
                ],
            )
            ->all();
    }
}
