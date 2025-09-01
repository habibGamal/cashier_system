<?php

namespace App\Filament\Resources\RawMaterialProducts\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\RawMaterialProducts\RawMaterialProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRawMaterialProduct extends ViewRecord
{
    protected static string $resource = RawMaterialProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
