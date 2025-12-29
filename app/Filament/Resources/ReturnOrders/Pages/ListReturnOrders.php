<?php

namespace App\Filament\Resources\ReturnOrders\Pages;

use App\Filament\Resources\ReturnOrders\ReturnOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListReturnOrders extends ListRecords
{
    protected static string $resource = ReturnOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // View-only resource, no create action
        ];
    }
}
