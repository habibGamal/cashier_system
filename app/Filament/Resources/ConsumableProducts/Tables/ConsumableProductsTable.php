<?php

namespace App\Filament\Resources\ConsumableProducts\Tables;

use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ConsumableProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم المنتج الاستهلاكي')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('barcode')
                    ->label('الباركود')
                    ->searchable()
                    ->placeholder('غير محدد'),
                TextColumn::make('category.name')
                    ->label('الفئة')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('price')
                    ->label('السعر')
                    ->money(currency_code())
                    ->sortable(),
                TextColumn::make('cost')
                    ->label('التكلفة')
                    ->money(currency_code())
                    ->sortable(),
                TextColumn::make('min_stock')
                    ->label('الحد الأدنى للمخزون')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit')
                    ->label('الوحدة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'packet' => 'باكت',
                        'kg' => 'كيلوجرام',
                        'gram' => 'جرام',
                        'liter' => 'لتر',
                        'ml' => 'ميليلتر',
                        'piece' => 'قطعة',
                        'box' => 'صندوق',
                        'bag' => 'كيس',
                        'bottle' => 'زجاجة',
                        'can' => 'علبة',
                        'cup' => 'كوب',
                        'tablespoon' => 'ملعقة كبيرة',
                        'teaspoon' => 'ملعقة صغيرة',
                        'dozen' => 'دستة',
                        'meter' => 'متر',
                        'cm' => 'سنتيمتر',
                        'roll' => 'رول',
                        'sheet' => 'ورقة',
                        'slice' => 'شريحة',
                        'loaf' => 'رغيف',
                        default => $state,
                    }),
                TextColumn::make('printers.name')
                    ->label('الطابعات')
                    ->badge()
                    ->separator(',')
                    ->sortable(),
                TextColumn::make('inventoryItem.quantity')
                    ->label('المخزون')
                    ->sortable()
                    ->default('0'),
                IconColumn::make('legacy')
                    ->label('غير نشط')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('الفئة')
                    ->options(Category::all()->pluck('name', 'id')),
                SelectFilter::make('printers')
                    ->label('الطابعة')
                    ->relationship('printers', 'name')
                    ->multiple(),
                TernaryFilter::make('legacy')
                    ->label('غير نشط'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
