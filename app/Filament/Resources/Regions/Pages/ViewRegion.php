<?php

namespace App\Filament\Resources\Regions\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\Regions\RegionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRegion extends ViewRecord
{
    protected static string $resource = RegionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
