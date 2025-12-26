<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\Orders\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Filament\Traits\AdminAccess;
use App\Models\Order;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    use AdminAccess;

    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'الطلبات';

    protected static ?string $modelLabel = 'طلب';

    protected static ?string $pluralModelLabel = 'الطلبات';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة المطعم';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // This is a view-only resource, no form fields needed
            ]);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الطلب')
                    ->schema([
                        TextEntry::make('order_number')
                            ->label('رقم الطلب'),

                        TextEntry::make('type')
                            ->label('نوع الطلب')
                            ->badge(),

                        TextEntry::make('status')
                            ->label('حالة الطلب')
                            ->badge(),

                        TextEntry::make('payment_status')
                            ->label('حالة الدفع')
                            ->badge(),

                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime('Y-m-d H:i:s'),
                    ])
                    ->columns(2),

                Section::make('معلومات العميل')
                    ->schema([
                        TextEntry::make('customer.name')
                            ->label('اسم العميل')
                            ->placeholder('غير محدد'),

                        TextEntry::make('customer.phone')
                            ->label('رقم الهاتف')
                            ->placeholder('غير محدد'),

                        TextEntry::make('customer.address')
                            ->label('العنوان')
                            ->placeholder('غير محدد'),

                        TextEntry::make('customer.region')
                            ->label('المنطقة')
                            ->placeholder('غير محدد'),

                        IconEntry::make('customer.has_whatsapp')
                            ->label('واتساب')
                            ->boolean()
                            ->placeholder('غير محدد'),

                        TextEntry::make('customer.delivery_cost')
                            ->label('تكلفة التوصيل')
                            ->money(currency_code())
                            ->placeholder('غير محدد'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('التفاصيل المالية')
                    ->schema([
                        TextEntry::make('sub_total')
                            ->label('المجموع الفرعي')
                            ->money(currency_code()),

                        TextEntry::make('tax')
                            ->label('الضرائب')
                            ->money(currency_code()),

                        TextEntry::make('service')
                            ->label('رسوم الخدمة')
                            ->money(currency_code()),

                        TextEntry::make('discount')
                            ->label('الخصم')
                            ->money(currency_code()),

                        TextEntry::make('total')
                            ->label('الإجمالي')
                            ->money(currency_code())
                            ->weight('bold'),

                        TextEntry::make('total_paid')
                            ->label('المبلغ المدفوع')
                            ->money(currency_code())
                            ->color('success'),

                        TextEntry::make('remaining_amount')
                            ->label('المبلغ المتبقي')
                            ->money(currency_code())
                            ->color(fn ($record) => $record->remaining_amount > 0 ? 'danger' : 'success'),

                        TextEntry::make('profit')
                            ->label('الربح')
                            ->money(currency_code()),
                    ])
                    ->columns(3),

                Section::make('ملاحظات')
                    ->schema([
                        TextEntry::make('order_notes')
                            ->label('ملاحظات الطلب')
                            ->placeholder('لا توجد ملاحظات'),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('معلومات إضافية')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('الكاشير'),

                        TextEntry::make('driver.name')
                            ->label('السائق')
                            ->placeholder('غير محدد'),

                        TextEntry::make('shift.id')
                            ->label('رقم الوردية')
                            ->placeholder('غير محدد'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('معلومات الفوترة الإلكترونية (ZATCA)')
                    ->schema([
                        TextEntry::make('zatca_status')
                            ->label('حالة الإرسال')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'REPORTED', 'CLEARED' => 'success',
                                'FAILED' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('zatca_uuid')
                            ->label('UUID')
                            ->copyable()
                            ->columnSpanFull(),

                        TextEntry::make('zatca_submitted_at')
                            ->label('تاريخ الإرسال')
                            ->dateTime('Y-m-d H:i:s'),

                        TextEntry::make('zatca_xml_path')
                            ->label('مسار XML'),

                        TextEntry::make('zatca_qr_base64')
                            ->label('رمز الاستجابة السريعة (QR)')
                            ->formatStateUsing(fn($state) => $state ? '<img src="data:image/png;base64,' . $state . '" style="width: 150px; height: 150px; border-radius: 8px; border: 1px solid #ddd; padding: 5px;" />' : 'لا يوجد QR')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'user', 'driver', 'shift', 'payments', 'items']);
    }
}
