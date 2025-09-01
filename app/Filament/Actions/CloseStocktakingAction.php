<?php

namespace App\Filament\Actions;

use Exception;
use App\Models\Stocktaking;
use App\Services\StocktakingService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class CloseStocktakingAction
{
    public static function make(?string $name = null): Action
    {
        return Action::make($name ?? 'close')
            ->label('إغلاق الجرد')
            ->icon('heroicon-o-lock-closed')
            ->color('success')
            ->visible(fn(Stocktaking $record): bool => is_null($record->closed_at))
            ->requiresConfirmation()
            ->modalHeading('إغلاق الجرد')
            ->modalDescription('هل أنت متأكد من إغلاق هذا الجرد؟ سيتم تحديث كميات المخزون بناءً على الكميات الفعلية ولن يمكن تعديل الجرد بعد ذلك.')
            ->modalSubmitActionLabel('إغلاق الجرد')
            ->modalCancelActionLabel('إلغاء')
            ->action(function (Stocktaking $record, $livewire) {
                try {
                    $livewire->save(true);
                    $stocktakingService = app(StocktakingService::class);
                    $stocktakingService->closeStocktaking($record);

                    Notification::make()
                        ->title('تم إغلاق الجرد بنجاح')
                        ->body('تم تحديث كميات المخزون بناءً على الكميات الفعلية')
                        ->success()
                        ->send();

                } catch (Exception $e) {
                    Notification::make()
                        ->title('فشل في إغلاق الجرد')
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
            ->color('success')
            ->visible(fn(Stocktaking $record): bool => is_null($record->closed_at))
            ->requiresConfirmation()
            ->modalHeading('إغلاق الجرد')
            ->modalDescription('هل أنت متأكد من إغلاق هذا الجرد؟ سيتم تحديث كميات المخزون بناءً على الكميات الفعلية ولن يمكن تعديل الجرد بعد ذلك.')
            ->action(function (Stocktaking $record) {
                try {
                    $stocktakingService = app(StocktakingService::class);
                    $stocktakingService->closeStocktaking($record);

                    Notification::make()
                        ->title('تم إغلاق الجرد بنجاح')
                        ->body('تم تحديث كميات المخزون بناءً على الكميات الفعلية')
                        ->success()
                        ->send();
                } catch (Exception $e) {
                    Notification::make()
                        ->title('فشل في إغلاق الجرد')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
