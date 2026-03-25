<?php

namespace App\Filament\Resources\Withdrawals\Schemas;

use App\Enums\CustomerAccountType;
use App\Enums\CustomerStorageType;
use App\Models\Bar;
use App\Models\Customer;
use App\Models\Metal;
use App\Models\MetalPool;
use App\Models\UnallocatedPosition;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class WithdrawalForm
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
                        $set('bar_ids', []);

                        return;
                    }

                    $isInstitutional = $customer->account_type === CustomerAccountType::Institutional;
                    $set('storage_type', $isInstitutional
                        ? CustomerStorageType::Allocated->value
                        : CustomerStorageType::Unallocated->value);
                    $set('bar_ids', []);
                })
                ->helperText(function (Get $get): ?string {
                    $customer = Customer::query()->find($get('customer_id'));

                    if (! $customer) {
                        return null;
                    }

                    return $customer->account_type === CustomerAccountType::Institutional
                        ? 'Allocated withdrawal: select specific bars.'
                        : 'Unallocated withdrawal: quantity is deducted from pooled balance.';
                }),

            Hidden::make('storage_type'),

            Select::make('metal_id')
                ->label('Metal Type')
                ->options(static::metalOptions())
                ->native(false)
                ->preload()
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set): mixed => $set('bar_ids', [])),

            Select::make('bar_ids')
                ->label('Bars to Withdraw')
                ->multiple()
                ->options(function (Get $get): array {
                    return static::availableBarOptions(
                        (string) ($get('customer_id') ?? ''),
                        (string) ($get('metal_id') ?? ''),
                    );
                })
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                    $barIds = is_array($state) ? $state : [];

                    if ($barIds === []) {
                        $set('quantity_kg', null);

                        return;
                    }

                    $sum = Bar::query()
                        ->whereIn('id', $barIds)
                        ->sum('weight_kg');

                    $set('quantity_kg', number_format((float) $sum, 4, '.', ''));
                })
                ->visible(fn (Get $get): bool => $get('storage_type') === CustomerStorageType::Allocated->value),

            TextInput::make('quantity_kg')
                ->label('Quantity (kg)')
                ->placeholder('e.g., 12.5')
                ->numeric()
                ->minValue(0.0001)
                ->readOnly(fn (Get $get): bool => $get('storage_type') === CustomerStorageType::Allocated->value)
                ->helperText(function (Get $get): ?string {
                    if ($get('storage_type') === CustomerStorageType::Allocated->value) {
                        return 'Auto-calculated from selected bars.';
                    }

                    $customerId = (string) ($get('customer_id') ?? '');
                    $metalId = (string) ($get('metal_id') ?? '');

                    if ($customerId === '' || $metalId === '') {
                        return null;
                    }

                    $availableQuantity = number_format(static::availableUnallocatedQuantityKg($customerId, $metalId), 4);

                    return new HtmlString("<span class=\"font-semibold text-warning-600\">Available unallocated: {$availableQuantity} kg</span>");
                })
                ->required(),
        ];
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

    /**
     * @return array<string, string>
     */
    protected static function availableBarOptions(string $customerId, string $metalId): array
    {
        if ($customerId === '' || $metalId === '') {
            return [];
        }

        return Bar::query()
            ->whereNull('withdrawal_id')
            ->whereHas('deposit', function ($query) use ($customerId, $metalId): void {
                $query->where('customer_id', $customerId)
                    ->where('metal_id', $metalId)
                    ->where('storage_type', CustomerStorageType::Allocated->value);
            })
            ->orderBy('serial_number')
            ->get()
            ->mapWithKeys(
                fn (Bar $bar): array => [
                    $bar->id => "{$bar->serial_number} ({$bar->weight_kg} kg)",
                ],
            )
            ->all();
    }

    protected static function availableUnallocatedQuantityKg(string $customerId, string $metalId): float
    {
        $pool = MetalPool::query()
            ->where('metal_id', $metalId)
            ->first();

        $position = UnallocatedPosition::query()
            ->where('customer_id', $customerId)
            ->where('metal_id', $metalId)
            ->first();

        if (! $pool || ! $position) {
            return 0;
        }

        $poolQuantityKg = (float) $pool->total_quantity_kg;
        $poolUnits = (float) $pool->total_units;
        $positionUnits = (float) $position->units;

        if ($poolQuantityKg <= 0 || $poolUnits <= 0 || $positionUnits <= 0) {
            return 0;
        }

        return $positionUnits * ($poolQuantityKg / $poolUnits);
    }
}
