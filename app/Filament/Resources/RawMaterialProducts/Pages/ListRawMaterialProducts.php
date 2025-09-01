<?php

namespace App\Filament\Resources\RawMaterialProducts\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\RawMaterialProducts\RawMaterialProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRawMaterialProducts extends ListRecords
{
    protected static string $resource = RawMaterialProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
