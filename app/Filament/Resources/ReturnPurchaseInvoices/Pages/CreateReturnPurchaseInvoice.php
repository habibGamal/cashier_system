<?php

namespace App\Filament\Resources\ReturnPurchaseInvoices\Pages;

use App\Filament\Resources\ReturnPurchaseInvoices\ReturnPurchaseInvoiceResource;
use App\Services\Resources\PurchaseInvoiceCalculatorService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateReturnPurchaseInvoice extends CreateRecord
{
    protected static string $resource = ReturnPurchaseInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['total'] = PurchaseInvoiceCalculatorService::calculateInvoiceTotal($data['items'] ?? []);
        return $data;
    }
}
