<?php

namespace App\Filament\Resources\ConsumableProducts\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ConsumableProducts\ConsumableProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsumableProducts extends ListRecords
{
    protected static string $resource = ConsumableProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
