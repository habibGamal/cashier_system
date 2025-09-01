<?php

namespace App\Filament\Resources\Stocktakings\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'أصناف الجرد';

    protected static ?string $modelLabel = 'صنف';

    protected static ?string $pluralModelLabel = 'أصناف';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // No form needed for read-only relation manager
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                TextColumn::make('product.name')
                    ->label('المنتج')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product.category.name')
                    ->label('الفئة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('stock_quantity')
                    ->label('الكمية الاصلية')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('real_quantity')
                    ->label('الكمية الفعلية')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('variance')
                    ->label('الفرق')
                    ->getStateUsing(function ($record) {
                        return $record->real_quantity - $record->stock_quantity;
                    })
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->color(function ($state) {
                        if ($state > 0) return 'success';
                        if ($state < 0) return 'danger';
                        return 'gray';
                    })
                    ->formatStateUsing(function ($state) {
                        if ($state > 0) {
                            return '+' . number_format($state, 2);
                        }
                        return number_format($state, 2);
                    }),

                TextColumn::make('price')
                    ->label('سعر الوحدة')
                    ->money('EGP')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('total')
                    ->label('القيمة الإجمالية')
                    ->money('EGP')
                    ->sortable()
                    ->alignEnd()
                    ->color(function ($state) {
                        if ($state > 0) return 'success';
                        if ($state < 0) return 'danger';
                        return 'gray';
                    }),

                TextColumn::make('product.unit')
                    ->label('الوحدة')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('product.category')
                    ->label('الفئة')
                    ->relationship('product.category', 'name'),

                Filter::make('positive_variance')
                    ->label('فائض')
                    ->query(function (Builder $query) {
                        return $query->whereRaw('real_quantity > stock_quantity');
                    }),

                Filter::make('negative_variance')
                    ->label('عجز')
                    ->query(function (Builder $query) {
                        return $query->whereRaw('real_quantity < stock_quantity');
                    }),

                Filter::make('no_variance')
                    ->label('لا يوجد فرق')
                    ->query(function (Builder $query) {
                        return $query->whereRaw('real_quantity = stock_quantity');
                    }),
            ])
            ->headerActions([
                // No header actions for read-only relation manager
            ])
            ->recordActions([
                // No actions for read-only relation manager
            ])
            ->toolbarActions([
                // No bulk actions for read-only relation manager
            ])
            ->defaultSort('product.name', 'asc')
            ->emptyStateHeading('لا توجد أصناف في هذا الجرد')
            ->emptyStateDescription('لم يتم إضافة أي أصناف إلى هذا الجرد بعد.')
            ->emptyStateIcon('heroicon-o-cube');
    }
}
