<?php

namespace App\Filament\Resources\ExpenseTypes\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\ExpenseTypes\ExpenseTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewExpenseType extends ViewRecord
{
    protected static string $resource = ExpenseTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
