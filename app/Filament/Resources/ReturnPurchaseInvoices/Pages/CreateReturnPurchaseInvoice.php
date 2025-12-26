<?php

namespace App\Filament\Resources\ReturnPurchaseInvoices\Pages;

use App\Filament\Resources\ReturnPurchaseInvoices\ReturnPurchaseInvoiceResource;
use App\Services\Resources\PurchaseInvoiceCalculatorService;
use Filament\Resources\Pages\CreateRecord;

class CreateReturnPurchaseInvoice extends CreateRecord
{
    protected static string $resource = ReturnPurchaseInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['total'] = PurchaseInvoiceCalculatorService::calculateInvoiceTotal($data['items'] ?? []);

        return $data;
    }
}
