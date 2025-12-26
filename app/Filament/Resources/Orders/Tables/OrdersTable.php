<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('الرقم المرجعي')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('order_number')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->placeholder('غير محدد'),

                TextColumn::make('customer.phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->placeholder('غير محدد'),

                TextColumn::make('type')
                    ->label('نوع الطلب')
                    ->badge()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('حالة الطلب')
                    ->badge()
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->badge()
                    ->sortable(),

                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->money(currency_code())
                    ->sortable(),

                TextColumn::make('payments_count')
                    ->label('عدد المدفوعات')
                    ->counts('payments')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('user.name')
                    ->label('الكاشير')
                    ->placeholder('غير محدد'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('نوع الطلب')
                    ->options(OrderType::class),

                SelectFilter::make('status')
                    ->label('حالة الطلب')
                    ->options(OrderStatus::class),

                SelectFilter::make('payment_status')
                    ->label('حالة الدفع')
                    ->options(PaymentStatus::class),

                Filter::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('من تاريخ'),
                        DatePicker::make('created_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
            ])
            ->toolbarActions([
                // No bulk actions for view-only resource
            ])
            ->defaultSort('created_at', 'desc');
    }
}
