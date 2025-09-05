<?php

namespace App\Filament\Resources\Regions;

use Filament\Schemas\Schema;
use App\Filament\Resources\Regions\Schemas\RegionForm;
use App\Filament\Resources\Regions\Tables\RegionsTable;
use App\Filament\Resources\Regions\Pages\ListRegions;
use App\Filament\Resources\Regions\Pages\CreateRegion;
use App\Filament\Resources\Regions\Pages\ViewRegion;
use App\Filament\Resources\Regions\Pages\EditRegion;
use App\Filament\Resources\RegionResource\Pages;
use App\Models\Region;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use \App\Filament\Traits\AdminAccess;

class RegionResource extends Resource
{
    use AdminAccess;

    protected static ?string $model = Region::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة المطعم';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return 'منطقة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'المناطق';
    }

    public static function form(Schema $schema): Schema
    {
        return RegionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RegionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegions::route('/'),
            'create' => CreateRegion::route('/create'),
            'view' => ViewRegion::route('/{record}'),
            'edit' => EditRegion::route('/{record}/edit'),
        ];
    }
}
