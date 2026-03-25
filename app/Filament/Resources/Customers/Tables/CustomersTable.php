<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomersTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('account_type')
                    ->badge(),

                TextColumn::make('portfolio_value')
                    ->label('Portfolio Value')
                    ->default('—')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => $state === null ? '—' : number_format((float) $state, 2))
                    ->badge()
                    ->alignRight(),

                TextColumn::make('joined_at')
                    ->label('Joined Date')
                    ->alignLeft()
                    ->date(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('legegure')
                        ->icon(Heroicon::RectangleStack)
                        ->modalHeading(fn (Customer $record): string => "{$record->name} Legger")
                        ->modalDescription('Customer deposit and withdrawal ledger entries.')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->slideOver()
                        ->modalWidth('4xl')
                        ->modalContent(fn (Customer $record): View => view('filament.actions.customers.legegure', [
                            'customer' => $record,
                            'ledger' => self::getCustomerLegger($record),
                        ])),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getCustomerLegger(Customer $customer, int $perPage = 10): LengthAwarePaginator
    {
        $deposits = DB::table('deposits')
            ->join('metals', 'metals.id', '=', 'deposits.metal_id')
            ->where('deposits.customer_id', $customer->getKey())
            ->whereNull('deposits.deleted_at')
            ->selectRaw("
                'Deposit' as entry_type,
                deposits.reference_number as reference_number,
                deposits.quantity_kg as quantity_kg,
                deposits.value_snapshot as value_snapshot,
                deposits.deposited_at as recorded_at,
                metals.name as metal_name,
                metals.code as metal_code
            ");

        $withdrawals = DB::table('withdrawals')
            ->join('metals', 'metals.id', '=', 'withdrawals.metal_id')
            ->where('withdrawals.customer_id', $customer->getKey())
            ->whereNull('withdrawals.deleted_at')
            ->selectRaw("
                'Withdrawal' as entry_type,
                withdrawals.reference_number as reference_number,
                withdrawals.quantity_kg as quantity_kg,
                withdrawals.value_snapshot as value_snapshot,
                withdrawals.withdrawn_at as recorded_at,
                metals.name as metal_name,
                metals.code as metal_code
            ");

        return DB::query()
            ->fromSub($deposits->unionAll($withdrawals), 'customer_ledger')
            ->orderByDesc('recorded_at')
            ->orderByDesc('reference_number')
            ->paginate($perPage, ['*'], 'legegurePage');
    }
}
