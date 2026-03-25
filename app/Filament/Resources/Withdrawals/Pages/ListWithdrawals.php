<?php

namespace App\Filament\Resources\Withdrawals\Pages;

use App\Filament\Resources\Withdrawals\Schemas\WithdrawalForm;
use App\Filament\Resources\Withdrawals\Widgets\WithdrawalOverview;
use App\Filament\Resources\Withdrawals\WithdrawalResource;
use App\Services\WithdrawalRecorder;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Validation\ValidationException;

class ListWithdrawals extends ListRecords
{
    protected static string $resource = WithdrawalResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            WithdrawalOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Withdrawal')
                ->modalWidth('3xl')
                ->modalHeading('Record New Withdrawal')
                ->modalDescription('Record a metal withdrawal for a customer.')
                ->schema(WithdrawalForm::components())
                ->using(function (array $data, WithdrawalRecorder $withdrawalRecorder) {
                    try {
                        return $withdrawalRecorder->record($data);
                    } catch (ValidationException $exception) {
                        $message = collect($exception->errors())->flatten()->first() ?? 'Validation failed.';

                        Notification::make()
                            ->danger()
                            ->title('Unable to record withdrawal')
                            ->body($message)
                            ->send();

                        $this->halt();

                        throw $exception;
                    }
                }),
        ];
    }
}
