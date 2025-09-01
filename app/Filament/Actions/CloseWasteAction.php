<?php

namespace App\Filament\Actions;

use Exception;
use App\Models\Waste;
use App\Services\WasteService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class CloseWasteAction
{
    public static function make(?string $name = null): Action
    {
        return Action::make($name ?? 'close')
            ->label('إغلاق سجل التالف')
            ->icon('heroicon-o-lock-closed')
            ->color('danger')
            ->visible(fn(Waste $record): bool => is_null($record->closed_at))
            ->requiresConfirmation()
            ->modalHeading('إغلاق سجل التالف')
            ->modalDescription('هل أنت متأكد من إغلاق سجل التالف؟ سيتم خصم جميع الأصناف من المخزون ولن يمكن تعديل السجل بعد ذلك.')
            ->modalSubmitActionLabel('إغلاق السجل')
            ->modalCancelActionLabel('إلغاء')
            ->action(function (Waste $record, $livewire) {
                try {
                    $livewire->save(true);
                    $wasteService = app(WasteService::class);
                    $wasteService->closeWaste($record);

                    Notification::make()
                        ->title('تم إغلاق سجل التالف بنجاح')
                        ->body('تم خصم جميع الأصناف التالفة من المخزون')
                        ->success()
                        ->send();

                } catch (Exception $e) {
                    Notification::make()
                        ->title('فشل في إغلاق سجل التالف')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function table(?string $name = null): Action
    {
        return Action::make($name ?? 'close')
            ->label('إغلاق')
            ->icon('heroicon-o-lock-closed')
            ->color('danger')
            ->visible(fn(Waste $record): bool => is_null($record->closed_at))
            ->requiresConfirmation()
            ->modalHeading('إغلاق سجل التالف')
            ->modalDescription('هل أنت متأكد من إغلاق سجل التالف؟ سيتم خصم جميع الأصناف من المخزون ولن يمكن تعديل السجل بعد ذلك.')
            ->action(function (Waste $record) {
                try {
                    $wasteService = app(WasteService::class);
                    $wasteService->closeWaste($record);

                    Notification::make()
                        ->title('تم إغلاق سجل التالف بنجاح')
                        ->body('تم خصم جميع الأصناف التالفة من المخزون')
                        ->success()
                        ->send();
                } catch (Exception $e) {
                    Notification::make()
                        ->title('فشل في إغلاق سجل التالف')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
