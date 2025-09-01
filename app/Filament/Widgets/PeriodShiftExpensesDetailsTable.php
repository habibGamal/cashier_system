<?php

namespace App\Filament\Widgets;

use Filament\Actions\ExportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use App\Services\ShiftsReportService;
use App\Models\Expense;
use App\Filament\Exports\PeriodShiftExpensesDetailedExporter;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class PeriodShiftExpensesDetailsTable extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'تفاصيل المصاريف';

    protected ShiftsReportService $shiftsReportService;

    public function boot(): void
    {
        $this->shiftsReportService = app(ShiftsReportService::class);
    }

    public function table(Table $table): Table
    {
        $filterType = $this->pageFilters['filterType'] ?? 'period';

        if ($filterType === 'shifts') {
            $shiftIds = $this->pageFilters['shifts'] ?? [];
            $expenseQuery = Expense::query()
                ->when(!empty($shiftIds), function (Builder $query) use ($shiftIds) {
                    return $query->whereIn('shift_id', $shiftIds);
                })
                ->with(['expenceType', 'shift.user'])
                ->orderBy('created_at', 'desc');
        } else {
            $startDate = $this->pageFilters['startDate'];
            $endDate = $this->pageFilters['endDate'];
            $expenseQuery = Expense::query()
                ->whereHas('shift', function (Builder $query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay()
                    ]);
                })
                ->with(['expenceType', 'shift.user'])
                ->orderBy('created_at', 'desc');
        }

        return $table
            ->query($expenseQuery)
            ->headerActions([
                ExportAction::make()
                    ->label('تصدير تفاصيل المصروفات')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->extraAttributes([
                        'id' => 'expenses_table',
                    ])
                    ->exporter(PeriodShiftExpensesDetailedExporter::class)
                    ->fileName(fn() => 'period-shift-expenses-detailed-' . now()->format('Y-m-d-H-i-s') . '.xlsx'),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('رقم المصروف')
                    ->sortable()
                    ->searchable()
                    ->weight('medium')
                    ->color('primary'),

                TextColumn::make('expenceType.name')
                    ->label('نوع المصروف')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->color('info'),

                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('EGP')
                    ->alignCenter()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('shift.id')
                    ->label('رقم الشفت')
                    ->sortable()
                    ->alignCenter()
                    ->color('warning')
                    ->formatStateUsing(fn ($state) => "شفت #{$state}"),

                TextColumn::make('shift.user.name')
                    ->label('المستخدم')
                    ->searchable()
                    ->sortable()
                    ->color('secondary')
                    ->default('غير محدد'),

                TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->notes;
                    })
                    ->placeholder('لا توجد ملاحظات')
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->alignCenter()
                    ->color('gray'),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('لا توجد مصروفات')
            ->emptyStateDescription('لم يتم العثور على أي مصروفات في الفترة المحددة.')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->recordAction(null)
            ->recordUrl(null)
            ->toolbarActions([])
            ->filters([
                SelectFilter::make('expenceType')
                    ->label('نوع المصروف')
                    ->relationship('expenceType', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('amount_range')
                    ->label('نطاق المبلغ')
                    ->schema([
                        TextInput::make('min_amount')
                            ->label('أقل مبلغ')
                            ->numeric()
                            ->placeholder('0.00'),
                        TextInput::make('max_amount')
                            ->label('أعلى مبلغ')
                            ->numeric()
                            ->placeholder('1000.00'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn (Builder $query, $minAmount): Builder => $query->where('amount', '>=', $minAmount),
                            )
                            ->when(
                                $data['max_amount'],
                                fn (Builder $query, $maxAmount): Builder => $query->where('amount', '<=', $maxAmount),
                            );
                    }),
            ])
        ;
    }
}
