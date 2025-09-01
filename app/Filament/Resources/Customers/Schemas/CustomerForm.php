<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use App\Models\Customer;
use App\Models\Region;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم العميل')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('أدخل اسم العميل'),

                        TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            ->placeholder('أدخل رقم الهاتف')
                            ->unique(Customer::class, 'phone', ignoreRecord: true)
                            ->helperText('يجب أن يكون رقم الهاتف فريداً'),

                        Toggle::make('has_whatsapp')
                            ->label('لديه واتساب')
                            ->default(false)
                            ->inline(false),

                        Select::make('region')
                            ->label('المنطقة')
                            ->options(function () {
                                return Region::pluck('name', 'name');
                            })
                            ->searchable()
                            ->placeholder('اختر المنطقة')
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $region = Region::where('name', $state)->first();
                                    if ($region) {
                                        $set('delivery_cost', $region->delivery_cost);
                                    }
                                }
                            }),

                        TextInput::make('delivery_cost')
                            ->label('تكلفة التوصيل')
                            ->numeric()
                            ->step(0.01)
                            ->placeholder('0.00')
                            ->prefix('ج.م')
                            ->helperText('سيتم تحديثها تلقائياً عند اختيار المنطقة'),
                    ]),

                Textarea::make('address')
                    ->label('العنوان')
                    ->maxLength(500)
                    ->rows(3)
                    ->placeholder('أدخل عنوان العميل'),
            ]);
    }
}
