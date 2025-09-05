<?php

namespace App\Filament\Resources\Regions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RegionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم المنطقة')
                    ->required()
                    ->maxLength(255),
                TextInput::make('delivery_cost')
                    ->label('تكلفة التوصيل')
                    ->numeric()
                    ->default(0)
                    ->prefix('ج.م'),
            ]);
    }
}
