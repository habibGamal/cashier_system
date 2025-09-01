<?php

namespace App\Filament\Resources\Wastes\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Wastes\WasteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWastes extends ListRecords
{
    protected static string $resource = WasteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
