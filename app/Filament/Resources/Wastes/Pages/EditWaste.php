<?php

namespace App\Filament\Resources\Wastes\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Wastes\WasteResource;
use App\Services\Resources\WasteCalculatorService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWaste extends EditRecord
{
    protected static string $resource = WasteResource::class;

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
            $data['total'] = WasteCalculatorService::calculateWasteTotal($data['items']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
