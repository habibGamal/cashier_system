<?php

namespace App\Filament\Resources\RawMaterialProducts;

use App\Filament\Resources\RawMaterialProducts\Schemas\RawMaterialProductForm;
use App\Filament\Resources\RawMaterialProducts\Tables\RawMaterialProductsTable;
use App\Filament\Resources\RawMaterialProducts\Pages\ListRawMaterialProducts;
use App\Filament\Resources\RawMaterialProducts\Pages\CreateRawMaterialProduct;
use App\Filament\Resources\RawMaterialProducts\Pages\ViewRawMaterialProduct;
use App\Filament\Resources\RawMaterialProducts\Pages\EditRawMaterialProduct;
use App\Models\Product;
use App\Enums\ProductType;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Traits\AdminAccess;

class RawMaterialProductResource extends Resource
{
    use AdminAccess;

    protected static ?string $model = Product::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة المنتجات';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'مادة خام';
    }

    public static function getPluralModelLabel(): string
    {
        return 'المواد الخام';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', ProductType::RawMaterial->value);
    }

    public static function form(Schema $schema): Schema
    {
        return RawMaterialProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RawMaterialProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRawMaterialProducts::route('/'),
            'create' => CreateRawMaterialProduct::route('/create'),
            'view' => ViewRawMaterialProduct::route('/{record}'),
            'edit' => EditRawMaterialProduct::route('/{record}/edit'),
        ];
    }
}
