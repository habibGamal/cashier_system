<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
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
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                Action::make('reportOrder')
                    ->label('إرسال للزكاة')
                    ->icon('heroicon-o-document-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (\App\Models\Order $record, \App\Services\Zatca\ZatcaReportingService $service) {
                        $result = $service->reportOrder($record);

                        if ($result['status'] === 'success') {
                            \Filament\Notifications\Notification::make()
                                ->title('تم الإرسال بنجاح')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('فشل الإرسال')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn(\App\Models\Order $record) => !in_array($record->zatca_status, ['REPORTED', 'CLEARED'])),
            ])
            ->toolbarActions([
                // No bulk actions for view-only resource
            ])
            ->defaultSort('created_at', 'desc');
    }
}
