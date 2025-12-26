<?php

namespace App\Filament\Resources\Stocktakings;

use App\Filament\Resources\Stocktakings\Pages\CreateStocktaking;
use App\Filament\Resources\Stocktakings\Pages\EditStocktaking;
use App\Filament\Resources\Stocktakings\Pages\ListStocktakings;
use App\Filament\Resources\Stocktakings\Pages\ViewStocktaking;
use App\Filament\Resources\Stocktakings\Schemas\StocktakingForm;
use App\Filament\Resources\Stocktakings\Schemas\StocktakingInfolist;
use App\Filament\Resources\Stocktakings\Tables\StocktakingsTable;
use App\Filament\Traits\AdminAccess;
use App\Models\Stocktaking;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StocktakingResource extends Resource
{
    use AdminAccess;

    protected static ?string $model = Stocktaking::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'الجرد';

    protected static ?string $modelLabel = 'جرد';

    protected static ?string $pluralModelLabel = 'الجرد';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة المخزون';

    public static function form(Schema $schema): Schema
    {
        return StocktakingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StocktakingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StocktakingsTable::configure($table);
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
            'index' => ListStocktakings::route('/'),
            'create' => CreateStocktaking::route('/create'),
            'view' => ViewStocktaking::route('/{record}'),
            'edit' => EditStocktaking::route('/{record}/edit'),
        ];
    }
}
