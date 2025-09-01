<?php

namespace App\Filament\Resources\TableTypes;

use App\Filament\Resources\TableTypes\Schemas\TableTypeForm;
use App\Filament\Resources\TableTypes\Tables\TableTypesTable;
use App\Filament\Resources\TableTypes\Pages\ListTableTypes;
use App\Filament\Resources\TableTypes\Pages\CreateTableType;
use App\Filament\Resources\TableTypes\Pages\EditTableType;
use App\Filament\Traits\AdminAccess;
use App\Models\TableType;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TableTypeResource extends Resource
{
    use AdminAccess;
    protected static ?string $model = TableType::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'أنواع الطاولات';

    protected static ?string $modelLabel = 'نوع طاولة';

    protected static ?string $pluralModelLabel = 'أنواع الطاولات';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة المطعم';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return TableTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TableTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTableTypes::route('/'),
            'create' => CreateTableType::route('/create'),
            'edit' => EditTableType::route('/{record}/edit'),
        ];
    }
}
