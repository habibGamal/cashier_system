<?php

namespace App\Filament\Resources\TableTypes\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use App\Models\TableType;

class TableTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات نوع الطاولة')
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم نوع الطاولة')
                            ->required()
                            ->maxLength(255)
                            ->unique(TableType::class, 'name', ignoreRecord: true)
                            ->placeholder('مثال: VIP، كلاسيك، بدوي')
                            ->helperText('أدخل اسم نوع الطاولة (يجب أن يكون فريداً)')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }
}
