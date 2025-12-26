<?php

namespace App\Filament\Resources\ExpenseTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExpenseTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم نوع المصروف')
                    ->required()
                    ->maxLength(255),
                TextInput::make('avg_month_rate')
                    ->label('متوسط الميزانية الشهرية ('.currency_name().')')
                    ->numeric()
                    ->step(0.01)
                    ->suffix(currency_name())
                    ->helperText('متوسط المبلغ المتوقع شهرياً لهذا النوع من المصروفات')
                    ->nullable(),
            ]);
    }
}
