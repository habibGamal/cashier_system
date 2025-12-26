<?php

namespace App\Filament\Resources\ConsumableProducts\Pages;

use App\Filament\Resources\ConsumableProducts\ConsumableProductResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditConsumableProduct extends EditRecord
{
    protected static string $resource = ConsumableProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
