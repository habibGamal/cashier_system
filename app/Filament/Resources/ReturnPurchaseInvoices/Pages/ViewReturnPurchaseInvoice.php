<?php

namespace App\Filament\Resources\ReturnPurchaseInvoices\Pages;

use App\Filament\Actions\CloseReturnPurchaseInvoiceAction;
use App\Filament\Resources\ReturnPurchaseInvoices\ReturnPurchaseInvoiceResource;
use App\Filament\Resources\ReturnPurchaseInvoices\RelationManagers\ItemsRelationManager;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Models\ReturnPurchaseInvoice;
use App\Services\PurchaseService;
use Filament\Notifications\Notification;

class ViewReturnPurchaseInvoice extends ViewRecord
{
    protected static string $resource = ReturnPurchaseInvoiceResource::class;

    public function getRelationManagers(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }
}
