<?php

namespace App\Filament\Resources\ReturnPurchaseInvoices\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ReturnPurchaseInvoices\ReturnPurchaseInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReturnPurchaseInvoices extends ListRecords
{
    protected static string $resource = ReturnPurchaseInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
