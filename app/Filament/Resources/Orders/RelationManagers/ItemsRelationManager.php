<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'أصناف الطلب';

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

                TextColumn::make('price')
                    ->label('سعر الوحدة')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        return $record->quantity * $record->price;
                    }),

                TextColumn::make('cost')
                    ->label('التكلفة')
                    ->money('EGP')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->limit(50)
                    ->placeholder('لا توجد ملاحظات')
                    ->tooltip(function ($record) {
                        return $record->notes ?: 'لا توجد ملاحظات';
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
