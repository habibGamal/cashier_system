<?php

namespace App\Filament\Resources\ManufacturedProducts;

use App\Enums\ProductType;
use App\Filament\Resources\ManufacturedProducts\Pages\CreateManufacturedProduct;
use App\Filament\Resources\ManufacturedProducts\Pages\EditManufacturedProduct;
use App\Filament\Resources\ManufacturedProducts\Pages\ListManufacturedProducts;
use App\Filament\Resources\ManufacturedProducts\Pages\ViewManufacturedProduct;
use App\Filament\Resources\ManufacturedProducts\Schemas\ManufacturedProductForm;
use App\Filament\Resources\ManufacturedProducts\Tables\ManufacturedProductsTable;
use App\Filament\Traits\AdminAccess;
use App\Models\Product;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManufacturedProductResource extends Resource
{
    use AdminAccess;

    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة المنتجات';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'منتج مُصنع';
    }

    public static function getPluralModelLabel(): string
    {
        return 'المنتجات المُصنعة';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', ProductType::Manufactured->value);
    }

    public static function form(Schema $schema): Schema
    {
        return ManufacturedProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ManufacturedProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListManufacturedProducts::route('/'),
            'create' => CreateManufacturedProduct::route('/create'),
            'view' => ViewManufacturedProduct::route('/{record}'),
            'edit' => EditManufacturedProduct::route('/{record}/edit'),
        ];
    }
}
