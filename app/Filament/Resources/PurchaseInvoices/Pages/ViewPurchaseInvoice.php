<?php

namespace App\Filament\Resources\PurchaseInvoices\Pages;

use App\Filament\Actions\ClosePurchaseInvoiceAction;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Filament\Resources\PurchaseInvoices\RelationManagers\ItemsRelationManager;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Models\PurchaseInvoice;
use App\Services\PurchaseService;
use Filament\Notifications\Notification;

class ViewPurchaseInvoice extends ViewRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;


    public function getRelationManagers(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }
}
