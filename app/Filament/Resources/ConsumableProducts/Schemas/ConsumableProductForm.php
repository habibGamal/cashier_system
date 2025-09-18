<?php

namespace App\Filament\Resources\ConsumableProducts\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ConsumableProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم المنتج الاستهلاكي')
                    ->required()
                    ->maxLength(255),
                TextInput::make('barcode')
                    ->label('الباركود')
                    ->maxLength(255)
                    ->placeholder('اختياري'),
                Select::make('category_id')
                    ->label('الفئة')
                    ->options(Category::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                TextInput::make('price')
                    ->label('السعر')
                    ->required()
                    ->numeric()
                    ->prefix('ج.م'),
                TextInput::make('cost')
                    ->label('التكلفة')
                    ->required()
                    ->numeric()
                    ->prefix('ج.م'),
                TextInput::make('min_stock')
                    ->label('الحد الأدنى للمخزون')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('avg_purchase_quantity')
                    ->label('متوسط كمية الشراء')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->helperText('الكمية المعتادة التي يتم شراؤها من هذا المنتج'),
                Select::make('unit')
                    ->label('الوحدة')
                    ->options([
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
                    ])
                    ->required(),
                Select::make('printers')
                    ->label('الطابعات')
                    ->relationship('printers', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Hidden::make('type')
                    ->default('consumable'),
                Toggle::make('legacy')
                    ->label('غير نشط')
                    ->default(false),
            ]);
    }
}
