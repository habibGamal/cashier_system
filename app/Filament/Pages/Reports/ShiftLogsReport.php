<?php

namespace App\Filament\Pages\Reports;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Filament\Traits\AdminAccess;
use App\Filament\Traits\ViewerAccess;
use App\Models\Shift;
use App\Services\ShiftLoggingService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ShiftLogsReport extends Page implements HasForms
{
    use InteractsWithForms, ViewerAccess;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.reports.shift-logs-report';

    protected static string | \UnitEnum | null $navigationGroup = 'التقارير';

    protected static ?string $navigationLabel = 'سجل أنشطة الوردية';

    protected static ?string $title = 'سجل أنشطة الوردية';

    protected static ?int $navigationSort = 4;

    public ?array $data = [];
    public array $logEntries = [];
    public ?string $selectedShiftInfo = null;

    public function mount(): void
    {
        $this->form->fill();
        $this->loadLogs();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اختيار الوردية')
                    ->description('اختر الوردية لعرض سجل الأنشطة الخاص بها')
                    ->schema([
                        Select::make('shift_id')
                            ->label('الوردية')
                            ->options(function () {
                                return Shift::with('user')
                                    ->orderBy('start_at', 'desc')
                                    ->get()
                                    ->mapWithKeys(function ($shift) {
                                        $userLabel = $shift->user ? $shift->user->name : 'غير محدد';
                                        $startDate = $shift->start_at ? $shift->start_at->format('d/m/Y H:i') : 'غير محدد';
                                        $endDate = $shift->end_at ? $shift->end_at->format('d/m/Y H:i') : 'لم تنته';

                                        return [
                                            $shift->id => "وردية #{$shift->id} - {$userLabel} ({$startDate} - {$endDate})"
                                        ];
                                    });
                            })
                            ->searchable()
                            ->placeholder('اختر الوردية')
                            ->live()
                            ->afterStateUpdated(fn() => $this->loadLogs()),

                        Select::make('log_level')
                            ->label('مستوى السجل')
                            ->options([
                                'all' => 'جميع الأنشطة',
                                'info' => 'الأنشطة العادية فقط',
                                'error' => 'الأخطاء فقط',
                            ])
                            ->default('all')
                            ->live()
                            ->afterStateUpdated(fn() => $this->loadLogs()),

                        Select::make('action_filter')
                            ->label('نوع النشاط')
                            ->options([
                                'all' => 'جميع الأنشطة',
                                'order_save' => 'حفظ الطلبات',
                                'order_complete' => 'إتمام الطلبات',
                                'order_cancel' => 'إلغاء الطلبات',
                                'discount' => 'تطبيق الخصومات',
                                'customer' => 'أنشطة العملاء',
                                'driver' => 'أنشطة السائقين',
                                'expense' => 'المصروفات',
                                'web_order' => 'طلبات الويب',
                            ])
                            ->default('all')
                            ->live()
                            ->afterStateUpdated(fn() => $this->loadLogs()),

                        TextInput::make('order_id')
                            ->label('رقم الطلب')
                            ->placeholder('اختياري - اتركه فارغ لعرض جميع الطلبات')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn() => $this->loadLogs()),
                    ])
                    ->columns(4),
            ])
            ->statePath('data');
    }

    public function loadLogs(): void
    {
        $shiftId = $this->data['shift_id'] ?? null;
        $logLevel = $this->data['log_level'] ?? 'all';
        $actionFilter = $this->data['action_filter'] ?? 'all';
        $orderId = $this->data['order_id'] ?? null;

        if (!$shiftId) {
            $this->logEntries = [];
            $this->selectedShiftInfo = null;
            return;
        }

        $shift = Shift::with('user')->find($shiftId);
        if (!$shift) {
            $this->logEntries = [];
            $this->selectedShiftInfo = null;
            return;
        }

        // Set shift info
        $userLabel = $shift->user ? $shift->user->name : 'غير محدد';
        $startDate = $shift->start_at ? $shift->start_at->format('d/m/Y H:i') : 'غير محدد';
        $endDate = $shift->end_at ? $shift->end_at->format('d/m/Y H:i') : 'لم تنته';
        $this->selectedShiftInfo = "وردية #{$shift->id} - {$userLabel} ({$startDate} - {$endDate})";

        // Load log file
        $shiftDate = $shift->created_at->format('Y-m-d');
        $logPath = storage_path("logs/shifts/shift_{$shift->id}_{$shiftDate}.log");

        if (!file_exists($logPath)) {
            $this->logEntries = [];
            return;
        }

        $logContent = file_get_contents($logPath);
        $this->logEntries = $this->parseLogContent($logContent, $logLevel, $actionFilter, $orderId);
    }

    private function parseLogContent(string $content, string $logLevel, string $actionFilter = 'all', ?string $orderId = null): array
    {
        $lines = explode("\n", $content);
        $entries = [];

        foreach ($lines as $line) {
            if (empty(trim($line)))
                continue;

            // Parse Laravel log format: [timestamp] environment.level: message {context}
            if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(\w+)\.(\w+):\s*(.+)$/', $line, $matches)) {
                $timestamp = $matches[1];
                $environment = $matches[2];
                $level = $matches[3];
                $messageAndContext = $matches[4];

                // Filter by log level
                if ($logLevel !== 'all' && $level !== $logLevel) {
                    continue;
                }

                // Separate message from JSON context
                $message = $messageAndContext;
                $context = [];

                // Look for JSON pattern at the end - find the first { that starts a valid JSON
                if (preg_match('/^(.+?)\s*(\{.+\})\s*$/', $messageAndContext, $jsonMatches)) {
                    $possibleMessage = trim($jsonMatches[1]);
                    $possibleJson = trim($jsonMatches[2]);

                    // Try to decode the JSON part
                    $contextData = json_decode($possibleJson, true);
                    if (json_last_error() === JSON_ERROR_NONE && $contextData && is_array($contextData)) {
                        $message = $possibleMessage;
                        $context = $contextData;

                        // Extract details if nested
                        if (isset($contextData['details']) && is_array($contextData['details'])) {
                            $context = array_merge($context, $contextData['details']);
                        }
                    }
                } else {
                    // Fallback: try to find the last occurrence of { to split message and context
                    $lastBracePos = strrpos($messageAndContext, '{');
                    if ($lastBracePos !== false && $lastBracePos > 0) {
                        $possibleMessage = trim(substr($messageAndContext, 0, $lastBracePos));
                        $possibleJson = substr($messageAndContext, $lastBracePos);

                        // Try to decode the JSON part
                        $contextData = json_decode($possibleJson, true);
                        if (json_last_error() === JSON_ERROR_NONE && $contextData && is_array($contextData)) {
                            $message = $possibleMessage;
                            $context = $contextData;

                            // Extract details if nested
                            if (isset($contextData['details']) && is_array($contextData['details'])) {
                                $context = array_merge($context, $contextData['details']);
                            }
                        }
                    }
                }                // Filter by action type
                if ($actionFilter !== 'all') {
                    $shouldInclude = false;

                    switch ($actionFilter) {
                        case 'order_save':
                            $shouldInclude = str_contains($message, 'حفظ تعديلات الطلب');
                            break;
                        case 'order_complete':
                            $shouldInclude = str_contains($message, 'إتمام الطلب');
                            break;
                        case 'order_cancel':
                            $shouldInclude = str_contains($message, 'إلغاء الطلب');
                            break;
                        case 'discount':
                            $shouldInclude = str_contains($message, 'تطبيق خصم');
                            break;
                        case 'customer':
                            $shouldInclude = str_contains($message, 'عميل') || str_contains($message, 'العميل');
                            break;
                        case 'driver':
                            $shouldInclude = str_contains($message, 'سائق') || str_contains($message, 'السائق');
                            break;
                        case 'expense':
                            $shouldInclude = str_contains($message, 'مصروف') || str_contains($message, 'المصروف');
                            break;
                        case 'web_order':
                            $shouldInclude = str_contains($message, 'طلب ويب') || str_contains($message, 'web');
                            break;
                    }

                    if (!$shouldInclude) {
                        continue;
                    }
                }

                // Filter by order ID if specified
                if ($orderId !== null && $orderId !== '') {
                    $orderIdInContext = $context['order_id'] ?? null;
                    if ($orderIdInContext != $orderId) {
                        continue;
                    }
                }

                $entries[] = [
                    'timestamp' => $timestamp,
                    'level' => $level,
                    'message' => $message,
                    'context' => $context,
                    'formatted_time' => Carbon::parse($timestamp)->format('H:i:s'),
                    'formatted_date' => Carbon::parse($timestamp)->format('d/m/Y'),
                    // Temporary debug info
                    'debug_original' => $messageAndContext,
                    'debug_json_detected' => !empty($context),
                ];
            }
        }

        // Sort by timestamp descending (newest first)
        usort($entries, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return $entries;
    }

    public function getLogLevelClass(string $level): string
    {
        return match ($level) {
            'error' => 'bg-red-100 text-red-800 border-red-200',
            'warning' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            'info' => 'bg-blue-100 text-blue-800 border-blue-200',
            default => 'bg-gray-100 text-gray-800 border-gray-200',
        };
    }

    public function getLogLevelIcon(string $level): string
    {
        return match ($level) {
            'error' => 'heroicon-s-exclamation-triangle',
            'warning' => 'heroicon-s-exclamation-circle',
            'info' => 'heroicon-s-information-circle',
            default => 'heroicon-s-document',
        };
    }

    public function formatContextValue($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        return (string) $value;
    }

    /**
     * Get the Arabic label for a context key
     */
    public function getContextKeyLabel(string $key): string
    {
        return match ($key) {
            'order_id' => 'رقم الطلب',
            'expense_id' => 'رقم المصروف',
            'expense_type' => 'نوع المصروف',
            'order_type' => 'نوع الطلب',
            'table_number' => 'رقم الطاولة',
            'customer_name' => 'اسم العميل',
            'customer_phone' => 'هاتف العميل',
            'customer_address' => 'عنوان العميل',
            'driver_name' => 'اسم السائق',
            'driver_phone' => 'هاتف السائق',
            'total_amount' => 'المبلغ الإجمالي',
            'expense_type_name' => 'نوع المصروف',
            'amount' => 'المبلغ',
            'discount_amount' => 'قيمة الخصم',
            'discount_type' => 'نوع الخصم',
            'discount_display' => 'عرض الخصم',
            'start_cash' => 'نقدية البداية',
            'end_cash' => 'نقدية النهاية',
            'real_end_cash' => 'النقدية الفعلية',
            'total_changes' => 'عدد التغييرات',
            'changes_count' => 'عدد التحديثات',
            'description' => 'الوصف',
            'reason' => 'السبب',
            'error' => 'تفاصيل الخطأ',
            'delivery_cost' => 'تكلفة التوصيل',
            'payment_summary' => 'ملخص الدفع',
            'action_type' => 'نوع العملية',
            default => $key
        };
    }

    /**
     * Format monetary values
     */
    public function formatMoney($value): string
    {
        if (!is_numeric($value)) {
            return (string) $value;
        }

        return number_format((float) $value, 2) . ' جنيه';
    }

    /**
     * Check if a key represents a monetary value
     */
    public function isMonetaryKey(string $key): bool
    {
        return in_array($key, [
            'total_amount',
            'amount',
            'delivery_cost',
            'start_cash',
            'end_cash',
            'real_end_cash',
            'discount_amount'
        ]);
    }

    /**
     * Get the action type icon
     */
    public function getActionTypeIcon(string $type): string
    {
        return match ($type) {
            'added' => 'heroicon-s-plus-circle',
            'removed' => 'heroicon-s-minus-circle',
            'quantity_changed' => 'heroicon-s-arrow-path',
            'notes_changed' => 'heroicon-s-pencil',
            'price_changed' => 'heroicon-s-currency-dollar',
            default => 'heroicon-s-document'
        };
    }

    /**
     * Get the action type color
     */
    public function getActionTypeColor(string $type): string
    {
        return match ($type) {
            'added' => 'text-green-600',
            'removed' => 'text-red-600',
            'quantity_changed' => 'text-blue-600',
            'notes_changed' => 'text-yellow-600',
            'price_changed' => 'text-purple-600',
            default => 'text-gray-600'
        };
    }

    /**
     * Format payment method in Arabic
     */
    public function formatPaymentMethod(string $method): string
    {
        return match ($method) {
            'cash' => 'نقداً',
            'card' => 'بطاقة',
            'visa' => 'فيزا',
            'vodafone_cash' => 'فودافون كاش',
            'company' => 'شركة',
            default => $method
        };
    }

    /**
     * Debug method to test log parsing
     */
    public function debugLogParsing(): void
    {
        if (!isset($this->data['shift_id'])) {
            return;
        }

        $shift = Shift::find($this->data['shift_id']);
        if (!$shift) {
            return;
        }

        $shiftDate = $shift->created_at->format('Y-m-d');
        $logPath = storage_path("logs/shifts/shift_{$shift->id}_{$shiftDate}.log");

        if (!file_exists($logPath)) {
            dd('Log file not found: ' . $logPath);
        }

        $content = file_get_contents($logPath);
        $lines = explode("\n", $content);

        foreach ($lines as $index => $line) {
            if (empty(trim($line)))
                continue;

            echo "Line " . ($index + 1) . ":\n";
            echo "Raw: " . $line . "\n";

            if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(\w+)\.(\w+):\s*(.+?)(\s+\{.+\})?$/', $line, $matches)) {
                echo "Matches found:\n";
                echo "1. Timestamp: " . $matches[1] . "\n";
                echo "2. Environment: " . $matches[2] . "\n";
                echo "3. Level: " . $matches[3] . "\n";
                echo "4. Message: " . $matches[4] . "\n";
                echo "5. Context: " . ($matches[5] ?? 'none') . "\n";

                if (!empty($matches[5])) {
                    $context = json_decode(trim($matches[5]), true);
                    echo "Parsed context: " . print_r($context, true) . "\n";
                }
            } else {
                echo "No match found\n";
            }
            echo "---\n";

            if ($index > 2)
                break; // Just show first few lines for debugging
        }

        dd('Debug complete');
    }
}
