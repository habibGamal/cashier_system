<?php

namespace App\Filament\Resources\ManufacturedProducts\Pages;

use App\Filament\Resources\ManufacturedProducts\ManufacturedProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListManufacturedProducts extends ListRecords
{
    protected static string $resource = ManufacturedProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
