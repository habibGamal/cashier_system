<?php

namespace App\Filament\Resources\PurchaseInvoices\Schemas;

use App\Filament\Actions\Forms\LowStockImporterAction;
use App\Filament\Actions\Forms\ProductImporterAction;
use App\Filament\Components\Forms\ProductSelector;
use App\Models\User;
use App\Services\Resources\PurchaseInvoiceCalculatorService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الفاتورة')
                    ->schema([
                        Select::make('user_id')
                            ->label('المستخدم')
                            ->options(User::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->default(auth()->id()),

                        Select::make('supplier_id')
                            ->label('المورد')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->required()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('اسم المورد')
                                    ->required(),
                                TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->tel(),
                                TextInput::make('address')
                                    ->label('العنوان'),
                            ]),

                        TextInput::make('total')
                            ->label('إجمالي الفاتورة ('.currency_symbol().')')
                            ->numeric()
                            ->prefix(currency_symbol())
                            ->disabled()
                            ->dehydrated(false)
                            ->default(0),
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->required(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('أصناف الفاتورة')
                    ->extraAttributes([
                        'x-init' => PurchaseInvoiceCalculatorService::getJavaScriptCalculation(),
                    ])
                    ->schema([
                        Actions::make([
                            ProductImporterAction::make('importProducts'),
                            LowStockImporterAction::make('importLowStock'),
                        ])
                            ->alignStart(),
                        ProductSelector::make()
                            ->columnSpanFull(),

                        Repeater::make('items')
                            ->label('الأصناف')
                            ->relationship('items', fn ($query) => $query->with('product'))
                            ->table([
                                TableColumn::make('المنتج')->width('200px'),
                                TableColumn::make('الكمية')->width('100px'),
                                TableColumn::make('سعر الوحدة ('.currency_symbol().')')->width('120px'),
                                TableColumn::make('الإجمالي ('.currency_symbol().')')->width('120px'),
                            ])
                            ->schema([
                                Hidden::make('product_id'),
                                TextInput::make('product_name')
                                    ->label('المنتج')
                                    ->formatStateUsing(function ($record) {
                                        if (! $record) {
                                            return 'غير محدد';
                                        }

                                        return $record->product_name != null ? $record->product_name : $record->product->name;
                                    })
                                    ->dehydrated(false)
                                    ->disabled(),

                                TextInput::make('quantity')
                                    ->label('الكمية')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1),

                                TextInput::make('price')
                                    ->label('سعر الوحدة ('.currency_symbol().')')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->prefix(currency_symbol()),

                                TextInput::make('total')
                                    ->label('الإجمالي ('.currency_symbol().')')
                                    ->numeric()
                                    ->prefix(currency_symbol())
                                    ->disabled()
                                    ->dehydrated(false),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->reorderableWithButtons()
                            ->dehydrated(true)
                            ->mutateRelationshipDataBeforeCreateUsing(function ($data) {
                                return PurchaseInvoiceCalculatorService::prepareItemData($data);
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function ($data) {
                                return PurchaseInvoiceCalculatorService::prepareItemData($data);
                            })
                            ->collapsible(),
                    ]),
            ])->columns(1);
    }
}
