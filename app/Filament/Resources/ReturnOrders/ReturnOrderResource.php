<?php

namespace App\Filament\Resources\ReturnOrders;

use App\Filament\Resources\ReturnOrders\Pages\ListReturnOrders;
use App\Filament\Resources\ReturnOrders\Pages\ViewReturnOrder;
use App\Filament\Resources\ReturnOrders\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\ReturnOrders\Tables\ReturnOrdersTable;
use App\Filament\Traits\AdminAccess;
use App\Models\ReturnOrder;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReturnOrderResource extends Resource
{
    use AdminAccess;

    protected static ?string $model = ReturnOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationLabel = 'مرتجعات الطلبات';

    protected static ?string $modelLabel = 'مرتجع';

    protected static ?string $pluralModelLabel = 'المرتجعات';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الشركة';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // This is a view-only resource, no form fields needed
            ]);
    }

    public static function table(Table $table): Table
    {
        return ReturnOrdersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات المرتجع')
                    ->schema([
                        TextEntry::make('return_number')
                            ->label('رقم المرتجع'),

                        TextEntry::make('order.order_number')
                            ->label('رقم الطلب الأصلي')
                            ->url(fn ($record) => $record->order ? route('filament.admin.resources.orders.view', $record->order) : null),

                        TextEntry::make('status')
                            ->label('حالة المرتجع')
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
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('التفاصيل المالية')
                    ->schema([
                        TextEntry::make('refund_amount')
                            ->label('مبلغ الاسترداد')
                            ->money('EGP')
                            ->weight('bold')
                            ->color('danger'),

                        TextEntry::make('total_items')
                            ->label('إجمالي الأصناف')
                            ->numeric(),
                    ])
                    ->columns(2),

                Section::make('السبب والملاحظات')
                    ->schema([
                        TextEntry::make('reason')
                            ->label('سبب الإرجاع')
                            ->placeholder('لا يوجد سبب'),

                        TextEntry::make('notes')
                            ->label('ملاحظات')
                            ->placeholder('لا توجد ملاحظات'),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('معلومات إضافية')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('الكاشير'),

                        TextEntry::make('shift.id')
                            ->label('رقم الوردية')
                            ->placeholder('غير محدد'),

                        TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime('Y-m-d H:i:s'),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReturnOrders::route('/'),
            'view' => ViewReturnOrder::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'user', 'shift', 'order', 'items']);
    }
}
