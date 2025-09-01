<?php

namespace App\Filament\Resources\ManufacturedProducts\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\ManufacturedProducts\ManufacturedProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewManufacturedProduct extends ViewRecord
{
    protected static string $resource = ManufacturedProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
