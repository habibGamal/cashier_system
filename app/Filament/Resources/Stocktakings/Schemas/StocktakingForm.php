<?php

namespace App\Filament\Resources\Stocktakings\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Actions;
use App\Filament\Actions\Forms\StocktakingProductImporterAction;
use App\Filament\Components\Forms\StocktakingProductSelector;
use App\Models\User;
use App\Services\Resources\StocktakingCalculatorService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class StocktakingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الجرد')
                    ->schema([
                        Select::make('user_id')
                            ->label('المستخدم')
                            ->options(User::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->default(auth()->id()),

                        TextInput::make('total')
                            ->label('إجمالي الفرق (ج.م)')
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

                Section::make('أصناف الجرد')
                    ->extraAttributes([
                        "x-init" => StocktakingCalculatorService::getJavaScriptCalculation(),
                    ])
                    ->schema([
                        Actions::make([
                            StocktakingProductImporterAction::make('importProducts'),
                        ])
                            ->alignStart(),
                        StocktakingProductSelector::make()
                            ->columnSpanFull(),
                        Repeater::make('items')
                            ->label('الأصناف')
                            ->relationship('items', fn($query) => $query->with('product'))
                            ->table([
                                TableColumn::make('المنتج')->width('150px'),
                                TableColumn::make('الكمية الفعلية')->width('120px'),
                                TableColumn::make('سعر الوحدة (ج.م)')->width('120px'),
                                TableColumn::make('الفرق (ج.م)')->width('120px'),
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

                                Hidden::make('stock_quantity')
                                    ->label('الكمية المخزنة')
                                    ->required()
                                    ->default(0)
                                    ->dehydrated(true),

                                TextInput::make('real_quantity')
                                    ->label('الكمية الفعلية')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0),

                                TextInput::make('price')
                                    ->label('سعر الوحدة (ج.م)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->prefix('ج.م')
                                    ->disabled()
                                    ->dehydrated(true),

                                TextInput::make('total')
                                    ->label('الفرق (ج.م)')
                                    ->numeric()
                                    ->prefix('ج.م')
                                    ->disabled()
                                    ->dehydrated(false),
                            ])
                            ->columns(5)
                            ->defaultItems(0)
                            ->reorderableWithButtons()
                            ->dehydrated(true)
                            ->mutateRelationshipDataBeforeCreateUsing(function ($data) {
                                return StocktakingCalculatorService::prepareItemData($data);
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function ($data) {
                                return StocktakingCalculatorService::prepareItemData($data);
                            })
                            ->collapsible(),
                    ]),
            ]);
    }
}
