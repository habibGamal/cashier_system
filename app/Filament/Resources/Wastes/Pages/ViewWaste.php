<?php

namespace App\Filament\Resources\Wastes\Pages;

use Filament\Actions\EditAction;
use App\Filament\Actions\CloseWasteAction;
use App\Filament\Resources\Wastes\WasteResource;
use App\Filament\Resources\Wastes\RelationManagers\ItemsRelationManager;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWaste extends ViewRecord
{
    protected static string $resource = WasteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CloseWasteAction::make(),
            EditAction::make(),
        ];
    }


    public function getRelationManagers(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }
}
