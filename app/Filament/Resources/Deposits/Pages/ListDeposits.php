<?php

namespace App\Filament\Resources\Deposits\Pages;

use App\Filament\Resources\Deposits\DepositResource;
use App\Filament\Resources\Deposits\Schemas\DepositForm;
use App\Filament\Resources\Deposits\Widgets\DepositOverview;
use App\Services\DepositRecorder;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeposits extends ListRecords
{
    protected static string $resource = DepositResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            DepositOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Deposit')
                ->modalWidth('3xl')
                ->modalHeading('Record New Deposit')
                ->modalDescription('Record a metal deposit for a customer. Storage type is determined by account type.')
                ->schema(DepositForm::components())
                ->using(fn (array $data, DepositRecorder $depositRecorder) => $depositRecorder->record($data)),
        ];
    }
}
