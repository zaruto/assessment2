<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Enums\CustomerAccountType;
use App\Enums\CustomerStorageType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Group::make(self::formFields())
                    ->visibleOn('create'),

                Section::make()
                    ->visibleOn('edit')
                    ->columns()
                    ->schema(self::formFields()),
            ]);

    }

    protected static function formFields(): array
    {
        return [
            TextInput::make('name')
                ->label('Full Name')
                ->autocomplete('off')
                ->required(),

            TextInput::make('email')
                ->unique(ignoreRecord: true)
                ->autocomplete('off')
                ->email()
                ->required(),

            Select::make('account_type')
                ->options(CustomerAccountType::class)
                ->native(false)
                ->preload()
                ->live()
                ->afterStateUpdated(function (Set $set, mixed $state): void {
                    if ($state instanceof CustomerAccountType) {
                        $state = $state->value;
                    }

                    if ($state === CustomerAccountType::Institutional->value) {
                        $set('storage_type', CustomerStorageType::Allocated->value);

                        return;
                    }

                    if ($state === CustomerAccountType::Retail->value) {
                        $set('storage_type', CustomerStorageType::Unallocated->value);
                    }
                })
                ->helperText(function (Get $get): ?string {
                    $accountType = $get('account_type');

                    if ($accountType instanceof CustomerAccountType) {
                        $accountType = $accountType->value;
                    }

                    return match ($accountType) {
                        CustomerAccountType::Institutional->value => 'Bars are individually tracked with serial numbers.',
                        CustomerAccountType::Retail->value => 'Metals stored in pooled bulk customer holds a percentage share.',
                        default => null,
                    };
                }),

            Hidden::make('storage_type'),

            DatePicker::make('joined_at')
                ->native(false)
                ->default(now())
                ->disabledOn('create')
                ->saved()
                ->label('Joined Date'),
        ];
    }
}
