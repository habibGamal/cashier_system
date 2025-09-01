<?php

namespace App\Filament\Resources\PurchaseInvoices\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Actions\ClosePurchaseInvoiceAction;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Services\Resources\PurchaseInvoiceCalculatorService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPurchaseInvoice extends EditRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ClosePurchaseInvoiceAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['total'] = PurchaseInvoiceCalculatorService::calculateInvoiceTotal($record->items);
        $record->update($data);
        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
