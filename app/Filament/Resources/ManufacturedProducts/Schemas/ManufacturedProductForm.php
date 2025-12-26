<?php

namespace App\Filament\Resources\ManufacturedProducts\Schemas;

use App\Filament\Actions\Forms\ProductComponentsImporterAction;
use App\Models\Category;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManufacturedProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات المنتج')
                    ->columnSpan('full')
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم المنتج')
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
                            ->prefix(currency_symbol()),
                        TextInput::make('cost')
                            ->label('التكلفة')
                            ->required()
                            ->numeric()
                            ->prefix(currency_symbol()),
                        TextInput::make('min_stock')
                            ->label('الحد الأدنى للمخزون')
                            ->required()
                            ->numeric()
                            ->default(0),
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
                            ->default('manufactured'),
                        Toggle::make('legacy')
                            ->label('غير نشط')
                            ->default(false),
                    ])
                    ->columns(3),

                Section::make('مكونات المنتج')
                    ->columnSpan('full')
                    ->extraAttributes([
                        'x-init' => <<<'JS'
                                const updateTotal = () => {
                                    let items = $wire.data.productComponents;
                                    if (!Array.isArray(items)) {
                                        items = Object.values(items);
                                    }
                                    $wire.data.cost = items.reduce((total, item) => total + (item.quantity * item.cost || 0), 0);
                                    items.forEach(item => {
                                        item.total = item.quantity * item.cost || 0;
                                    });
                                };
                                $watch('$wire.data', value => {
                                    updateTotal();
                                })
                                updateTotal();
                            JS
                    ])
                    ->schema([
                        Actions::make([
                            ProductComponentsImporterAction::make('importComponents'),
                        ])
                            ->alignStart(),

                        Repeater::make('productComponents')
                            ->label('المكونات')
                            ->relationship('productComponents', fn ($query) => $query->with('component.category'))
                            ->table([
                                TableColumn::make('المكون')->width('300px'),
                                TableColumn::make('الكمية')->width('120px'),
                                TableColumn::make('التكلفة')->width('120px'),
                                TableColumn::make('الوحدة')->width('120px'),
                                TableColumn::make('الإجمالي')->width('120px'),
                                TableColumn::make('الفئة')->width('200px'),

                            ])
                            ->schema([
                                Hidden::make('component_id'),
                                TextInput::make('component_name')
                                    ->label('اسم المكون')
                                    ->formatStateUsing(
                                        fn ($record, $state) => $state ?? $record->component->name
                                    )
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('quantity')
                                    ->label('الكمية')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0),

                                TextInput::make('cost')
                                    ->label('التكلفة')
                                    ->formatStateUsing(
                                        fn ($record, $state) => $state ?? $record->component->cost
                                    )
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('unit')
                                    ->label('الوحدة')
                                    ->formatStateUsing(
                                        fn ($record, $state) => $state ?? $record->component->unit
                                    )
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('total')
                                    ->label('الإجمالي')
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('category_name')
                                    ->label('التكلفة')
                                    ->formatStateUsing(
                                        fn ($record, $state) => $state ?? $record->component->category->name
                                    )
                                    ->disabled()
                                    ->dehydrated(false),
                            ])
                            // ->columns(3)
                            ->defaultItems(0)
                            ->reorderableWithButtons()
                            ->collapsible(),
                    ]),
            ]);
    }
}
