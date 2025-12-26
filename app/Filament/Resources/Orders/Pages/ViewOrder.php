<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reportOrder')
                ->label('إرسال للزكاة')
                ->icon('heroicon-o-document-check')
                ->color('success')
                ->requiresConfirmation()
                ->action(function (OrderResource $resource, \App\Models\Order $record, \App\Services\Zatca\ZatcaReportingService $service) {
                    $result = $service->reportOrder($record);

                    if ($result['status'] === 'success') {
                        \Filament\Notifications\Notification::make()
                            ->title('تم الإرسال بنجاح')
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('فشل الإرسال')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn(\App\Models\Order $record) => !in_array($record->zatca_status, ['REPORTED', 'CLEARED'])),
        ];
    }
}
