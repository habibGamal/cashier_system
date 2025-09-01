<?php

namespace App\Filament\Resources\ConsumableProducts\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\ConsumableProducts\ConsumableProductResource;
use Filament\Actions;
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
