<?php

namespace App\Filament\Resources\ConsumableProducts;

use App\Enums\ProductType;
use App\Filament\Resources\ConsumableProducts\Pages\CreateConsumableProduct;
use App\Filament\Resources\ConsumableProducts\Pages\EditConsumableProduct;
use App\Filament\Resources\ConsumableProducts\Pages\ListConsumableProducts;
use App\Filament\Resources\ConsumableProducts\Pages\ViewConsumableProduct;
use App\Filament\Resources\ConsumableProducts\Schemas\ConsumableProductForm;
use App\Filament\Resources\ConsumableProducts\Tables\ConsumableProductsTable;
use App\Filament\Traits\AdminAccess;
use App\Models\Product;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ConsumableProductResource extends Resource
{
    use AdminAccess;

    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة المنتجات';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return 'منتج استهلاكي';
    }

    public static function getPluralModelLabel(): string
    {
        return 'المنتجات الاستهلاكية';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', ProductType::Consumable->value);
    }

    public static function form(Schema $schema): Schema
    {
        return ConsumableProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConsumableProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsumableProducts::route('/'),
            'create' => CreateConsumableProduct::route('/create'),
            'view' => ViewConsumableProduct::route('/{record}'),
            'edit' => EditConsumableProduct::route('/{record}/edit'),
        ];
    }
}
