<?php

namespace App\Filament\Resources\ReturnPurchaseInvoices\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'أصناف المرتجع';

    protected static ?string $label = 'صنف';

    protected static ?string $pluralLabel = 'أصناف';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                TextColumn::make('product.name')
                    ->label('المنتج')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->sortable(),

                TextColumn::make('price')
                    ->label('سعر الوحدة')
                    ->money(currency_code())
                    ->sortable(),

                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->money(currency_code())
                    ->sortable(),
            ]);
    }

    public function isReadOnly(): bool
    {
        return ! is_null($this->ownerRecord->closed_at);
    }
}
