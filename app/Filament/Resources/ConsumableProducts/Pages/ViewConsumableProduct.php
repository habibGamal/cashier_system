<?php

namespace App\Filament\Resources\ConsumableProducts\Pages;

use App\Filament\Resources\ConsumableProducts\ConsumableProductResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewConsumableProduct extends ViewRecord
{
    protected static string $resource = ConsumableProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
