<?php

namespace App\Filament\Resources\ExpenseTypes;

use App\Filament\Resources\ExpenseTypes\Schemas\ExpenseTypeForm;
use App\Filament\Resources\ExpenseTypes\Tables\ExpenseTypesTable;
use App\Filament\Resources\ExpenseTypes\Pages\ListExpenseTypes;
use App\Filament\Resources\ExpenseTypes\Pages\CreateExpenseType;
use App\Filament\Resources\ExpenseTypes\Pages\ViewExpenseType;
use App\Filament\Resources\ExpenseTypes\Pages\EditExpenseType;
use App\Models\ExpenceType;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Traits\AdminAccess;

class ExpenseTypeResource extends Resource
{
    use AdminAccess;

    protected static ?string $model = ExpenceType::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة المصروفات';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'نوع مصروف';
    }

    public static function getPluralModelLabel(): string
    {
        return 'أنواع المصروفات';
    }

    public static function form(Schema $schema): Schema
    {
        return ExpenseTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpenseTypesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenseTypes::route('/'),
            'create' => CreateExpenseType::route('/create'),
            'view' => ViewExpenseType::route('/{record}'),
            'edit' => EditExpenseType::route('/{record}/edit'),
        ];
    }
}
