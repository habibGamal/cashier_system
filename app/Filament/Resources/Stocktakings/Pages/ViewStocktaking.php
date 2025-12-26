<?php

namespace App\Filament\Resources\Stocktakings\Pages;

use App\Filament\Actions\CloseStocktakingAction;
use App\Filament\Resources\Stocktakings\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\Stocktakings\StocktakingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStocktaking extends ViewRecord
{
    protected static string $resource = StocktakingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CloseStocktakingAction::make(),
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
