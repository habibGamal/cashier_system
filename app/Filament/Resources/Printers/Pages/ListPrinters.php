<?php

namespace App\Filament\Resources\Printers\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Printers\PrinterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrinters extends ListRecords
{
    protected static string $resource = PrinterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
