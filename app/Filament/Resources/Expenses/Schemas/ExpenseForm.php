<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Models\ExpenceType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('expence_type_id')
                    ->label('نوع المصروف')
                    ->options(ExpenceType::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                TextInput::make('amount')
                    ->label('المبلغ')
                    ->required()
                    ->numeric()
                    ->prefix(currency_symbol()),
                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3)
                    ->maxLength(1000),
            ]);
    }
}
