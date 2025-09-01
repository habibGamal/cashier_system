<?php

namespace App\Filament\Resources\PurchaseInvoices\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseInvoices extends ListRecords
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
