<?php

namespace App\Filament\Resources\ReturnOrders\Pages;

use App\Filament\Resources\ReturnOrders\ReturnOrderResource;
use Filament\Resources\Pages\ViewRecord;

class ViewReturnOrder extends ViewRecord
{
    protected static string $resource = ReturnOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // View-only resource, no edit action
        ];
    }
}
