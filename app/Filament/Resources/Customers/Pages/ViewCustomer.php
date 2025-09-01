<?php

namespace App\Filament\Resources\Customers\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }
}
