<?php

namespace App\Filament\Resources\Wastes\Schemas;

use App\Filament\Actions\Forms\ProductImporterAction;
use App\Filament\Components\Forms\ProductSelector;
use App\Models\Product;
use App\Models\User;
use App\Services\Resources\WasteCalculatorService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WasteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات سجل التالف')
                    ->schema([
                        Select::make('user_id')
                            ->label('المستخدم')
                            ->options(User::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->default(auth()->id()),

                        TextInput::make('total')
                            ->label('إجمالي قيمة التالف (ج.م)')
                            ->numeric()
                            ->prefix('ج.م')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(0),

                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->required(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('الأصناف التالفة')
                    ->extraAttributes([
                        'x-init' => WasteCalculatorService::getJavaScriptCalculation(),
                    ])
                    ->schema([
                        Actions::make([
                            ProductImporterAction::make('importProducts')
                                ->additionalProps(function (Product $product) {
                                    return [
                                        'stock_quantity' => $product->inventoryItem?->quantity ?? 0,
                                    ];
                                }),
                        ])
                            ->alignStart(),

                        ProductSelector::make()
                            ->columnSpanFull(),
                        Repeater::make('items')
                            ->label('الأصناف')
                            ->relationship('items', fn ($query) => $query->with('product.inventoryItem'))
                            ->table([
                                TableColumn::make('المنتج')->width('200px'),
                                TableColumn::make('الكمية الحالية')->width('100px'),
                                TableColumn::make('الكمية')->width('100px'),
                                TableColumn::make('سعر الوحدة (ج.م)')->width('120px'),
                                TableColumn::make('الإجمالي (ج.م)')->width('120px'),
                            ])
                            ->schema([
                                Hidden::make('product_id'),

                                TextInput::make('product_name')
                                    ->label('المنتج')
                                    ->formatStateUsing(function ($record, $state) {
                                        return $state ?? ($record->product?->name ?? 'غير محدد');
                                    })
                                    ->dehydrated(false)
                                    ->disabled(),

                                TextInput::make('stock_quantity')
                                    ->label('الكمية')
                                    ->formatStateUsing(function ($record) {
                                        return $record->product?->inventoryItem->quantity ?? 'غير محدد';
                                    })
                                    ->disabled(),

                                TextInput::make('quantity')
                                    ->label('الكمية')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1),

                                TextInput::make('price')
                                    ->label('سعر الوحدة (ج.م)')
                                    ->numeric()
                                    ->prefix('ج.م')
                                    ->disabled()
                                    ->dehydrated(condition: true),

                                TextInput::make('total')
                                    ->label('الإجمالي (ج.م)')
                                    ->numeric()
                                    ->prefix('ج.م')
                                    ->disabled()
                                    ->dehydrated(false),
                            ])
                            ->defaultItems(0)
                            ->reorderableWithButtons()
                            ->dehydrated(true)
                            ->mutateRelationshipDataBeforeCreateUsing(function ($data) {
                                return WasteCalculatorService::prepareItemData($data);
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function ($data) {
                                return WasteCalculatorService::prepareItemData($data);
                            })
                            ->collapsible(),
                    ]),
            ])->columns(1);
    }
}
