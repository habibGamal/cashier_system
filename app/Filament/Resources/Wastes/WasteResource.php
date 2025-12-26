<?php

namespace App\Filament\Resources\Wastes;

use App\Filament\Resources\Wastes\Pages\CreateWaste;
use App\Filament\Resources\Wastes\Pages\EditWaste;
use App\Filament\Resources\Wastes\Pages\ListWastes;
use App\Filament\Resources\Wastes\Pages\ViewWaste;
use App\Filament\Resources\Wastes\Schemas\WasteForm;
use App\Filament\Resources\Wastes\Schemas\WasteInfolist;
use App\Filament\Resources\Wastes\Tables\WastesTable;
use App\Filament\Traits\AdminAccess;
use App\Models\Waste;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WasteResource extends Resource
{
    use AdminAccess;

    protected static ?string $model = Waste::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trash';

    protected static ?string $navigationLabel = 'التالف';

    protected static ?string $modelLabel = 'سجل تالف';

    protected static ?string $pluralModelLabel = 'سجلات التالف';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة المخزون';

    public static function form(Schema $schema): Schema
    {
        return WasteForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WasteInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WastesTable::configure($table);
    }

    public static function canEdit(Model $record): bool
    {
        return is_null($record->closed_at);
    }

    public static function canDelete(Model $record): bool
    {
        return is_null($record->closed_at);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWastes::route('/'),
            'create' => CreateWaste::route('/create'),
            'view' => ViewWaste::route('/{record}'),
            'edit' => EditWaste::route('/{record}/edit'),
        ];
    }
}
