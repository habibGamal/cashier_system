<?php

namespace App\Filament\Resources\Drivers\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Drivers\DriverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDrivers extends ListRecords
{
    protected static string $resource = DriverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
