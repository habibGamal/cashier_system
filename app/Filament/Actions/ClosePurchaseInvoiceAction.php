<?php

namespace App\Filament\Actions;

use Exception;
use App\Models\PurchaseInvoice;
use App\Services\PurchaseService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ClosePurchaseInvoiceAction
{
    public static function make(?string $name = null): Action
    {
        return Action::make($name ?? 'close')
            ->label('إغلاق الفاتورة')
            ->icon('heroicon-o-lock-closed')
            ->color('success')
            ->visible(fn(PurchaseInvoice $record): bool => is_null($record->closed_at))
            ->requiresConfirmation()
            ->modalHeading('إغلاق فاتورة الشراء')
            ->modalDescription('هل أنت متأكد من إغلاق هذه الفاتورة؟ سيتم إضافة جميع الأصناف إلى المخزون ولن يمكن تعديل الفاتورة بعد ذلك.')
            ->modalSubmitActionLabel('إغلاق الفاتورة')
            ->modalCancelActionLabel('إلغاء')
            ->action(function (PurchaseInvoice $record, $livewire) {
                try {
                    $livewire->save(true);
                    $purchaseService = app(PurchaseService::class);
                    $purchaseService->closePurchaseInvoice($record);

                    Notification::make()
                        ->title('تم إغلاق الفاتورة بنجاح')
                        ->body('تم إضافة جميع الأصناف إلى المخزون')
                        ->success()
                        ->send();

                } catch (Exception $e) {
                    Notification::make()
                        ->title('فشل في إغلاق الفاتورة')
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
            ->visible(fn(PurchaseInvoice $record): bool => is_null($record->closed_at))
            ->requiresConfirmation()
            ->modalHeading('إغلاق فاتورة الشراء')
            ->modalDescription('هل أنت متأكد من إغلاق هذه الفاتورة؟ سيتم إضافة جميع الأصناف إلى المخزون ولن يمكن تعديل الفاتورة بعد ذلك.')
            ->action(function (PurchaseInvoice $record) {
                try {
                    $purchaseService = app(PurchaseService::class);
                    $purchaseService->closePurchaseInvoice($record);

                    Notification::make()
                        ->title('تم إغلاق الفاتورة بنجاح')
                        ->body('تم إضافة جميع الأصناف إلى المخزون')
                        ->success()
                        ->send();
                } catch (Exception $e) {
                    Notification::make()
                        ->title('فشل في إغلاق الفاتورة')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
