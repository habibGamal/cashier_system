<?php

namespace App\Filament\Resources\Wastes\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Tables\Filters\SelectFilter;
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

    protected static ?string $title = 'الأصناف التالفة';

    protected static ?string $modelLabel = 'صنف تالف';

    protected static ?string $pluralModelLabel = 'أصناف تالفة';

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

                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('price')
                    ->label('سعر الوحدة')
                    ->money('EGP')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('product.unit')
                    ->label('الوحدة')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('product.category')
                    ->label('الفئة')
                    ->relationship('product.category', 'name'),
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
            ->emptyStateHeading('لا توجد أصناف تالفة في هذا السجل')
            ->emptyStateDescription('لم يتم إضافة أي أصناف تالفة إلى هذا السجل بعد.')
            ->emptyStateIcon('heroicon-o-trash');
    }


}
