<?php

namespace App\Filament\Resources\Printers\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\Printers\PrinterResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPrinter extends ViewRecord
{
    protected static string $resource = PrinterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
