<?php

namespace App\Filament\Resources\Stocktakings\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Stocktakings\StocktakingResource;
use App\Services\Resources\StocktakingCalculatorService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStocktaking extends EditRecord
{
    protected static string $resource = StocktakingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Calculate the total from items
        if (isset($data['items']) && is_array($data['items'])) {
            $data['total'] = StocktakingCalculatorService::calculateStocktakingTotal($data['items']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
