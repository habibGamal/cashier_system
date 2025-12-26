<?php

namespace App\Filament\Resources\Wastes\Pages;

use App\Filament\Actions\CloseWasteAction;
use App\Filament\Resources\Wastes\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\Wastes\WasteResource;
use Filament\Actions\EditAction;
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
