<?php

namespace App\Filament\Resources\TableTypes\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\TableTypes\TableTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTableType extends EditRecord
{
    protected static string $resource = TableTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف'),
        ];
    }

    public function getTitle(): string
    {
        return 'تعديل نوع الطاولة';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث نوع الطاولة بنجاح';
    }
}
