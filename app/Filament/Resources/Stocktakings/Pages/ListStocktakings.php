<?php

namespace App\Filament\Resources\Stocktakings\Pages;

use App\Filament\Resources\Stocktakings\StocktakingResource;
use Filament\Actions\CreateAction;
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
