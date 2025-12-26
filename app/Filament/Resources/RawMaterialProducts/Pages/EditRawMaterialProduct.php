<?php

namespace App\Filament\Resources\RawMaterialProducts\Pages;

use App\Filament\Resources\RawMaterialProducts\RawMaterialProductResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRawMaterialProduct extends EditRecord
{
    protected static string $resource = RawMaterialProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
