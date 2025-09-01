<?php

namespace App\Filament\Actions;

use Exception;
use App\Models\ReturnPurchaseInvoice;
use App\Services\PurchaseService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class CloseReturnPurchaseInvoiceAction
{
    public static function make(?string $name = null): Action
    {
        return Action::make($name ?? 'close')
            ->label('إغلاق المرتجع')
            ->icon('heroicon-o-lock-closed')
            ->color('danger')
            ->visible(fn(ReturnPurchaseInvoice $record): bool => is_null($record->closed_at))
            ->requiresConfirmation()
            ->modalHeading('إغلاق مرتجع الشراء')
            ->modalDescription('هل أنت متأكد من إغلاق هذا المرتجع؟ سيتم خصم جميع الأصناف من المخزون ولن يمكن تعديل المرتجع بعد ذلك.')
            ->modalSubmitActionLabel('إغلاق المرتجع')
            ->modalCancelActionLabel('إلغاء')
            ->action(function (ReturnPurchaseInvoice $record, $livewire) {
                try {
                    $livewire->save(true);
                    $purchaseService = app(PurchaseService::class);
                    $purchaseService->closeReturnPurchaseInvoice($record);

                    Notification::make()
                        ->title('تم إغلاق المرتجع بنجاح')
                        ->body('تم خصم جميع الأصناف من المخزون')
                        ->success()
                        ->send();

                } catch (Exception $e) {
                    Notification::make()
                        ->title('فشل في إغلاق المرتجع')
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
            ->visible(fn(ReturnPurchaseInvoice $record): bool => is_null($record->closed_at))
            ->requiresConfirmation()
            ->modalHeading('إغلاق مرتجع الشراء')
            ->modalDescription('هل أنت متأكد من إغلاق هذا المرتجع؟ سيتم خصم جميع الأصناف من المخزون ولن يمكن تعديل المرتجع بعد ذلك.')
            ->action(function (ReturnPurchaseInvoice $record) {
                try {
                    $purchaseService = app(PurchaseService::class);
                    $purchaseService->closeReturnPurchaseInvoice($record);

                    Notification::make()
                        ->title('تم إغلاق المرتجع بنجاح')
                        ->body('تم خصم جميع الأصناف من المخزون')
                        ->success()
                        ->send();
                } catch (Exception $e) {
                    Notification::make()
                        ->title('فشل في إغلاق المرتجع')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
