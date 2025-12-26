<?php

namespace App\Filament\Resources\InventoryItems\Tables;

use App\Enums\ProductType;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('اسم المنتج')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.category.name')
                    ->label('الفئة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record): string => match (true) {
                        $record->quantity > ($record->product->min_stock * 2) => 'success',
                        $record->quantity > $record->product->min_stock => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('product.min_stock')
                    ->label('الحد الأدنى للمخزون')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('product.unit')
                    ->label('الوحدة')
                    ->badge(),
                TextColumn::make('product.type')
                    ->label('نوع المنتج')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('product.category_id')
                    ->label('الفئة')
                    ->relationship('product.category', 'name'),
                Filter::make('product_type')
                    ->label('نوع المنتج')
                    ->schema([
                        Select::make('type')
                            ->label('نوع المنتج')
                            ->options(ProductType::toSelectArray())
                            ->placeholder('اختر نوع المنتج'),
                    ])
                    ->query(
                        fn (Builder $query, array $data) => $query
                            ->when(
                                $data['type'] ?? null,
                                fn (Builder $query) => $query->whereHas('product', fn (Builder $productQuery) => $productQuery->where('type', $data['type'] ?? null))
                            )
                    ),
                Filter::make('low_stock')
                    ->label('مخزون منخفض')
                    ->query(fn ($query) => $query->whereRaw('quantity <= (SELECT min_stock FROM products WHERE products.id = inventory_items.product_id)')),
                Filter::make('critical_stock')
                    ->label('مخزون حرج')
                    ->query(fn ($query) => $query->whereRaw('quantity < (SELECT min_stock FROM products WHERE products.id = inventory_items.product_id)')),
                Filter::make('out_of_stock')
                    ->label('نفد المخزون')
                    ->query(fn ($query) => $query->where('quantity', '<=', 0)),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('عرض التفاصيل'),
            ])
            ->toolbarActions([
                // No bulk actions - read-only resource
            ])
            ->defaultSort('quantity', 'asc'); // Show low stock items first
    }
}
