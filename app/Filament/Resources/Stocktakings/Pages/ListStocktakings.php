<?php

namespace App\Filament\Resources\Stocktakings\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Stocktakings\StocktakingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStocktakings extends ListRecords
{
    protected static string $resource = StocktakingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
