<?php

namespace App\Filament\Widgets;

use App\Enums\ReturnOrderStatus;
use App\Models\ReturnOrder;
use App\Models\Shift;
use App\Services\ShiftsReportService;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CurrentShiftReturnOrdersTable extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'المرتجعات';

    protected ShiftsReportService $shiftsReportService;

    public function boot(): void
    {
        $this->shiftsReportService = app(ShiftsReportService::class);
    }

    public function table(Table $table): Table
    {
        $currentShift = $this->getCurrentShift();

        if (! $currentShift) {
            $query = ReturnOrder::query()->where('id', 0); // Empty query
        } else {
            $query = ReturnOrder::query()
                ->where('shift_id', $currentShift->id)
                ->with(['customer', 'user', 'order', 'items']);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('id')
                    ->label('الرقم المرجعي')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('return_number')
                    ->label('رقم المرتجع')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->color('primary'),

                TextColumn::make('order.order_number')
                    ->label('رقم الطلب الأصلي')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->order ? route('filament.admin.resources.orders.view', $record->order) : null)
                    ->color('gray'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),

                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->default('غير محدد')
                    ->color('gray'),

                TextColumn::make('refund_amount')
                    ->label('مبلغ الاسترداد')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' جنيه')
                    ->color('danger')
                    ->weight('bold')
                    ->alignCenter(),

                TextColumn::make('items_count')
                    ->label('عدد الأصناف')
                    ->counts('items')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('reason')
                    ->label('السبب')
                    ->limit(30)
                    ->placeholder('لا يوجد سبب')
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label('الموظف')
                    ->searchable()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('وقت الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->color('gray')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(ReturnOrderStatus::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->poll('30s')
            ->emptyStateHeading('لا توجد مرتجعات')
            ->emptyStateDescription('لم يتم العثور على أي مرتجعات في الشفت الحالي.')
            ->emptyStateIcon('heroicon-o-arrow-uturn-left')
            ->recordActions([
                ViewAction::make()
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ReturnOrder $record): string => route('filament.admin.resources.return-orders.view', $record))
                    ->openUrlInNewTab(),
            ])
            ->recordAction(ViewAction::class)
            ->recordUrl(fn (ReturnOrder $record): string => route('filament.admin.resources.return-orders.view', $record))
            ->toolbarActions([]);
    }

    private function getCurrentShift(): ?Shift
    {
        return $this->shiftsReportService->getCurrentShift();
    }
}
