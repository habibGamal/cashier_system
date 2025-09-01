<?php

namespace App\Filament\Resources\ManufacturedProducts\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\ManufacturedProducts\ManufacturedProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManufacturedProduct extends EditRecord
{
    protected static string $resource = ManufacturedProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
