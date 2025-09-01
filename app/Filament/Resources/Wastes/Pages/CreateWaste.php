<?php

namespace App\Filament\Resources\Wastes\Pages;

use App\Filament\Resources\Wastes\WasteResource;
use App\Services\Resources\WasteCalculatorService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWaste extends CreateRecord
{
    protected static string $resource = WasteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Calculate the total from items
        if (isset($data['items']) && is_array($data['items'])) {
            $data['total'] = WasteCalculatorService::calculateWasteTotal($data['items']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
