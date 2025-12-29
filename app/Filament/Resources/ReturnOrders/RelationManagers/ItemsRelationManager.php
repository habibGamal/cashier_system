<?php

namespace App\Filament\Resources\ReturnOrders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'أصناف المرتجع';

    protected static ?string $modelLabel = 'صنف';

    protected static ?string $pluralModelLabel = 'الأصناف';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // This is view-only, no form needed
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                TextColumn::make('product.name')
                    ->label('اسم المنتج')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product.category.name')
                    ->label('الفئة')
                    ->searchable()
                    ->placeholder('غير محدد'),

                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('original_price')
                    ->label('السعر الأصلي')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('return_price')
                    ->label('سعر الإرجاع')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->sortable()
                    ->color('danger'),

                TextColumn::make('original_cost')
                    ->label('التكلفة الأصلية')
                    ->money('EGP')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reason')
                    ->label('سبب الإرجاع')
                    ->limit(50)
                    ->placeholder('لا يوجد سبب')
                    ->tooltip(function ($record) {
                        return $record->reason ?: 'لا يوجد سبب';
                    }),
            ])
            ->filters([
                SelectFilter::make('product.category_id')
                    ->label('الفئة')
                    ->relationship('product.category', 'name')
                    ->preload(),
            ])
            ->headerActions([
                // View-only: no create action
            ])
            ->recordActions([
                // View-only: no edit or delete actions
            ])
            ->toolbarActions([
                // View-only: no bulk actions
            ])
            ->defaultSort('id');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
