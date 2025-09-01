<?php

namespace App\Filament\Resources\ReturnPurchaseInvoices\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Actions\CloseReturnPurchaseInvoiceAction;
use App\Filament\Resources\ReturnPurchaseInvoices\ReturnPurchaseInvoiceResource;
use App\Services\Resources\PurchaseInvoiceCalculatorService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class EditReturnPurchaseInvoice extends EditRecord
{
    protected static string $resource = ReturnPurchaseInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CloseReturnPurchaseInvoiceAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['total'] = PurchaseInvoiceCalculatorService::calculateInvoiceTotal($data['items'] ?? []);
        $record->update($data);
        return $record;
    }

}
