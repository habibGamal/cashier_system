<?php

namespace App\Filament\Resources\TableTypes\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\TableTypes\TableTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTableTypes extends ListRecords
{
    protected static string $resource = TableTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إنشاء نوع طاولة جديد'),
        ];
    }

    public function getTitle(): string
    {
        return 'أنواع الطاولات';
    }
}
