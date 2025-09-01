<?php

namespace App\Filament\Resources\Regions\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Regions\RegionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRegions extends ListRecords
{
    protected static string $resource = RegionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
