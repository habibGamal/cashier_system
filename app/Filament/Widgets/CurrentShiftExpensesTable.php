<?php

namespace App\Filament\Widgets;

use Filament\Actions\ExportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Shift;
use App\Models\Expense;
use App\Models\ExpenceType;
use App\Services\ShiftsReportService;
use App\Filament\Exports\CurrentShiftExpensesExporter;
use App\Filament\Exports\CurrentShiftExpensesDetailedExporter;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class CurrentShiftExpensesTable extends BaseWidget
{
    protected static bool $isLazy = false;
    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'اجمالي المصاريف';

    protected ShiftsReportService $shiftsReportService;

    public function boot(): void
    {
        $this->shiftsReportService = app(ShiftsReportService::class);
    }

    public function table(Table $table): Table
    {
        $currentShift = $this->getCurrentShift();

        if (!$currentShift) {
            $query = ExpenceType::query()->where('id', 0); // Empty query
        } else {
            // Use ExpenceType as base and join with expenses
            $query = ExpenceType::query()
                ->select([
                    'expence_types.id',
                    'expence_types.name',
                    'expence_types.avg_month_rate',
                    DB::raw('COUNT(expenses.id) as expense_count'),
                    DB::raw('COALESCE(SUM(expenses.amount), 0) as total_amount'),
                ])
                ->leftJoin('expenses', function($join) use ($currentShift) {
                    $join->on('expence_types.id', '=', 'expenses.expence_type_id')
                         ->where('expenses.shift_id', '=', $currentShift->id);
                })
                ->groupBy('expence_types.id', 'expence_types.name', 'expence_types.avg_month_rate')
                ->havingRaw('COUNT(expenses.id) > 0'); // Only show types that have expenses
        }

        return $table
            ->query($query)
            ->headerActions([
                ExportAction::make()
                    ->label('تصدير ملخص المصروفات')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->exporter(CurrentShiftExpensesExporter::class)
                    ->modifyQueryUsing(function (Builder $query) {
                        $currentShift = $this->getCurrentShift();
                        if ($currentShift) {
                            return ExpenceType::query()
                                ->select([
                                    'expence_types.id',
                                    'expence_types.name',
                                    'expence_types.avg_month_rate',
                                    DB::raw('COUNT(expenses.id) as expense_count'),
                                    DB::raw('COALESCE(SUM(expenses.amount), 0) as total_amount'),
                                ])
                                ->leftJoin('expenses', function($join) use ($currentShift) {
                                    $join->on('expence_types.id', '=', 'expenses.expence_type_id')
                                         ->where('expenses.shift_id', '=', $currentShift->id);
                                })
                                ->groupBy('expence_types.id', 'expence_types.name', 'expence_types.avg_month_rate')
                                ->havingRaw('COUNT(expenses.id) > 0');
                        }
                        return ExpenceType::query()->where('id', 0);
                    })
                    ->fileName(fn () => 'current-shift-expenses-summary-' . now()->format('Y-m-d-H-i-s') . '.xlsx')
                    ->visible(fn () => $this->getCurrentShift() !== null),

                ExportAction::make('detailed_export')
                    ->label('تصدير تفاصيل المصروفات')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->exporter(CurrentShiftExpensesDetailedExporter::class)
                    ->modifyQueryUsing(function (Builder $query) {
                        $currentShift = $this->getCurrentShift();
                        if ($currentShift) {
                            return Expense::query()
                                ->where('shift_id', $currentShift->id)
                                ->with(['expenceType', 'shift'])
                                ->orderBy('created_at', 'desc');
                        }
                        return Expense::query()->where('id', 0);
                    })
                    ->fileName(fn () => 'current-shift-expenses-detailed-' . now()->format('Y-m-d-H-i-s') . '.xlsx')
                    ->visible(fn () => $this->getCurrentShift() !== null),
            ])
            ->columns([
                TextColumn::make('name')
                    ->label('نوع المصروف')
                    ->searchable()
                    ->weight('medium')
                    ->color('primary'),

                TextColumn::make('expense_count')
                    ->label('عدد المصروفات')
                    ->numeric()
                    ->alignCenter()
                    ->color('info'),

                TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' جنيه')
                    ->weight('bold')
                    ->alignCenter()
                    ->color(function ($record) {
                        $current = $record->total_amount ?? 0;
                        $monthlyRate = $record->avg_month_rate ?? 0;
                        if ($monthlyRate > 0 && $current > $monthlyRate) {
                            return 'danger';
                        }
                        return 'success';
                    }),

                TextColumn::make('avg_month_rate')
                    ->label('الميزانية الشهرية')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' جنيه')
                    ->alignCenter()
                    ->placeholder('غير محدد')
                    ->color('info'),

                TextColumn::make('budget_status')
                    ->label('حالة الميزانية')
                    ->state(function ($record) {
                        $current = $record->total_amount ?? 0;
                        $monthlyRate = $record->avg_month_rate ?? 0;

                        if ($monthlyRate == 0) {
                            return 'غير محدد';
                        }

                        // For current shift, we compare against monthly budget
                        // since expenses should be tracked daily/per shift against monthly budget
                        if ($current > $monthlyRate) {
                            $excess = $current - $monthlyRate;
                            return 'تجاوز بـ ' . number_format($excess, 2) . ' ج';
                        } else {
                            $remaining = $monthlyRate - $current;
                            return 'متبقي ' . number_format($remaining, 2) . ' ج';
                        }
                    })
                    ->color(function ($record) {
                        $current = $record->total_amount ?? 0;
                        $monthlyRate = $record->avg_month_rate ?? 0;

                        if ($monthlyRate == 0) {
                            return 'gray';
                        }

                        return $current > $monthlyRate ? 'danger' : 'success';
                    })
                    ->alignCenter()
                    ->icon(function ($record) {
                        $current = $record->total_amount ?? 0;
                        $monthlyRate = $record->avg_month_rate ?? 0;

                        if ($monthlyRate == 0) {
                            return 'heroicon-o-question-mark-circle';
                        }

                        return $current > $monthlyRate ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle';
                    }),
            ])
            ->defaultSort('total_amount', 'desc')
            ->striped()
            ->paginated([10, 25])
            ->emptyStateHeading('لا توجد مصروفات')
            ->emptyStateDescription('لم يتم تسجيل أي مصروفات في الشفت الحالي.')
            ->emptyStateIcon('heroicon-o-currency-dollar')
            ->recordActions([
                Action::make('view_expenses')
                    ->label('عرض التفاصيل')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn ($record) => 'مصروفات نوع: ' . $record->name)
                    ->modalWidth(Width::FourExtraLarge)
                    ->modalContent(fn ($record) =>
                        view('filament.modals.shift-details', [
                            'shiftId' => $currentShift->id,
                            'expenceTypeId' => $record->id,
                        ])
                    )
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق'),
            ])
            ->recordAction(null)
            ->recordUrl(null)
            ->toolbarActions([]);
    }

    private function getCurrentShift(): ?Shift
    {
        return $this->shiftsReportService->getCurrentShift();
    }

    private function getExpensesForType(int $expenseTypeId): Collection
    {
        $currentShift = $this->getCurrentShift();

        if (!$currentShift) {
            return collect();
        }

        return Expense::where('shift_id', $currentShift->id)
            ->where('expence_type_id', $expenseTypeId)
            ->with(['expenceType', 'shift'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function getExpensesTableContent($record): HtmlString
    {
        $expenses = $this->getExpensesForType($record->id);

        if ($expenses->isEmpty()) {
            return new HtmlString('
                <div class="text-center py-8">
                    <div class="mx-auto h-12 w-12 text-gray-400 mb-4">
                        <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">لا توجد مصروفات</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">لم يتم العثور على أي مصروفات لهذا النوع.</p>
                </div>
            ');
        }

        $totalAmount = $expenses->sum('amount');
        $expenseCount = $expenses->count();

        $html = '
        <div class="space-y-4">
            <!-- Summary Header -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">عدد المصروفات</h3>
                        <p class="text-xl font-bold text-primary-600 dark:text-primary-400">' . $expenseCount . '</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">إجمالي المبلغ</h3>
                        <p class="text-xl font-bold text-danger-600 dark:text-danger-400">' . number_format($totalAmount, 2) . ' جنيه</p>
                    </div>
                </div>
            </div>

            <!-- Expenses Table -->
            <div class="overflow-hidden shadow-sm ring-1 ring-black ring-opacity-5 rounded-lg">
                <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">المبلغ</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">الملاحظات</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">تاريخ الإنشاء</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">';

        foreach ($expenses as $index => $expense) {
            $bgClass = $index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700';
            $html .= '
                        <tr class="' . $bgClass . '">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-sm font-semibold text-danger-600 dark:text-danger-400">' . number_format($expense->amount, 2) . ' جنيه</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900 dark:text-white max-w-xs">
                                    ' . ($expense->notes ? htmlspecialchars($expense->notes) : '<span class="text-gray-400 italic">لا توجد ملاحظات</span>') . '
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-500 dark:text-gray-300">
                                    <div>' . $expense->created_at->format('Y-m-d') . '</div>
                                    <div class="text-xs">' . $expense->created_at->format('H:i:s') . '</div>
                                </div>
                            </td>
                        </tr>';
        }

        $html .= '
                    </tbody>
                </table>
            </div>
        </div>';

        return new HtmlString($html);
    }
}
