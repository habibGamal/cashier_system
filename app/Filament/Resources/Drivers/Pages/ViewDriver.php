<?php

namespace App\Filament\Resources\Drivers\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\Drivers\RelationManagers\OrdersRelationManager;
use App\Filament\Resources\Drivers\DriverResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDriver extends ViewRecord
{
    protected static string $resource = DriverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            OrdersRelationManager::class,
        ];
    }
}
