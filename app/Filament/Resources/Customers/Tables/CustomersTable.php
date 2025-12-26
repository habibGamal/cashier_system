<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Region;
use Exception;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم العميل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                IconColumn::make('has_whatsapp')
                    ->label('واتساب')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('region')
                    ->label('المنطقة')
                    ->searchable()
                    ->sortable()
                    ->placeholder('غير محدد'),

                TextColumn::make('delivery_cost')
                    ->label('تكلفة التوصيل')
                    ->money(currency_code())
                    ->sortable(),

                TextColumn::make('address')
                    ->label('العنوان')
                    ->limit(50)
                    ->placeholder('غير محدد')
                    ->tooltip(function ($record) {
                        return $record->address ?: 'غير محدد';
                    }),

                TextColumn::make('orders_count')
                    ->label('عدد الطلبات')
                    ->counts('orders')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('has_whatsapp')
                    ->label('لديه واتساب')
                    ->boolean()
                    ->trueLabel('نعم')
                    ->falseLabel('لا')
                    ->placeholder('الكل'),

                SelectFilter::make('region')
                    ->label('المنطقة')
                    ->options(function () {
                        return Region::pluck('name', 'name');
                    })
                    ->placeholder('جميع المناطق'),

                Filter::make('delivery_cost')
                    ->label('تكلفة التوصيل')
                    ->schema([
                        TextInput::make('delivery_cost_from')
                            ->label('من')
                            ->numeric()
                            ->prefix(currency_symbol()),
                        TextInput::make('delivery_cost_to')
                            ->label('إلى')
                            ->numeric()
                            ->prefix(currency_symbol()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['delivery_cost_from'],
                                fn (Builder $query, $cost): Builder => $query->where('delivery_cost', '>=', $cost),
                            )
                            ->when(
                                $data['delivery_cost_to'],
                                fn (Builder $query, $cost): Builder => $query->where('delivery_cost', '<=', $cost),
                            );
                    }),

                Filter::make('orders_count')
                    ->label('عدد الطلبات')
                    ->schema([
                        TextInput::make('orders_count_from')
                            ->label('أكثر من')
                            ->numeric(),
                        TextInput::make('orders_count_to')
                            ->label('أقل من')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->withCount('orders')
                            ->when(
                                $data['orders_count_from'],
                                fn (Builder $query, $count): Builder => $query->having('orders_count', '>=', $count),
                            )
                            ->when(
                                $data['orders_count_to'],
                                fn (Builder $query, $count): Builder => $query->having('orders_count', '<=', $count),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                DeleteAction::make()
                    ->label('حذف')
                    ->requiresConfirmation()
                    ->modalHeading('حذف العميل')
                    ->modalDescription('هل أنت متأكد من حذف هذا العميل؟ لن تتمكن من التراجع عن هذا الإجراء.')
                    ->modalSubmitActionLabel('نعم، احذف')
                    ->modalCancelActionLabel('إلغاء')
                    ->before(function ($record) {
                        // Check if customer has orders
                        if ($record->orders()->exists()) {
                            throw new Exception('لا يمكن حذف العميل لوجود طلبات مرتبطة به');
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف المحدد')
                        ->requiresConfirmation()
                        ->modalHeading('حذف العملاء المحددين')
                        ->modalDescription('هل أنت متأكد من حذف العملاء المحددين؟ لن تتمكن من التراجع عن هذا الإجراء.')
                        ->modalSubmitActionLabel('نعم، احذف')
                        ->modalCancelActionLabel('إلغاء')
                        ->before(function ($records) {
                            // Check if any customer has orders
                            $customersWithOrders = $records->filter(function ($customer) {
                                return $customer->orders()->exists();
                            });

                            if ($customersWithOrders->count() > 0) {
                                throw new Exception('لا يمكن حذف بعض العملاء لوجود طلبات مرتبطة بهم');
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
