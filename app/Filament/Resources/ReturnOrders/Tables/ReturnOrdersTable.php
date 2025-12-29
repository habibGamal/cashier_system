<?php

namespace App\Filament\Resources\ReturnOrders\Tables;

use App\Enums\ReturnOrderStatus;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReturnOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('الرقم المرجعي')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('return_number')
                    ->label('رقم المرتجع')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('order.order_number')
                    ->label('رقم الطلب الأصلي')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->order ? route('filament.admin.resources.orders.view', $record->order) : null),

                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->placeholder('غير محدد'),

                TextColumn::make('customer.phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->placeholder('غير محدد'),

                TextColumn::make('status')
                    ->label('حالة المرتجع')
                    ->badge()
                    ->sortable(),

                TextColumn::make('refund_amount')
                    ->label('مبلغ الاسترداد')
                    ->money('EGP')
                    ->sortable()
                    ->color('danger'),

                TextColumn::make('items_count')
                    ->label('عدد الأصناف')
                    ->counts('items')
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
                SelectFilter::make('status')
                    ->label('حالة المرتجع')
                    ->options(ReturnOrderStatus::class),

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
