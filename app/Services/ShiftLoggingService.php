<?php

namespace App\Services;

use App\Models\Shift;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ShiftLoggingService
{
    private $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

    /**
     * Log an action for the current shift
     */
    public function logAction(string $action, array $details = [], string $level = 'info'): void
    {
        $currentShift = $this->shiftService->getCurrentShift();

        if (!$currentShift) {
            // Log to default channel if no shift is active
            Log::channel('daily')->log($level, $action, $details);
            return;
        }

        $logData = [
            'timestamp' => Carbon::now()->format('Y-m-d H:i:s'),
            'shift_id' => $currentShift->id,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
            'action' => $action,
            'details' => $details,
        ];

        // Create shift-specific log channel
        $channelName = 'shift_' . $currentShift->id;
        $this->createShiftLogChannel($channelName, $currentShift);

        Log::channel($channelName)->log($level, $action, $logData);
    }

    /**
     * Log order save action with item differences
     */
    public function logOrderSave(int $orderId, array $oldItems, array $newItems): void
    {
        $differences = $this->calculateItemDifferences($oldItems, $newItems);

        $action = "حفظ تعديلات الطلب رقم #{$orderId}";
        $details = [
            'order_id' => $orderId,
            'differences' => $differences,
            'total_changes' => count($differences),
        ];

        $this->logAction($action, $details);
    }

    /**
     * Log order creation
     */
    public function logOrderCreation(int $orderId, string $orderType, ?string $tableNumber = null): void
    {
        $action = "إنشاء طلب جديد رقم #{$orderId}";

        $orderTypeMap = [
            'dine_in' => 'صالة',
            'takeaway' => 'تيك أواي',
            'delivery' => 'توصيل',
            'companies' => 'شركات',
            'talabat' => 'طلبات',
        ];

        $details = [
            'order_id' => $orderId,
            'order_type' => $orderTypeMap[$orderType] ?? $orderType,
            'table_number' => $tableNumber,
        ];

        $this->logAction($action, $details);
    }

    /**
     * Log order completion
     */
    public function logOrderCompletion(int $orderId, array $paymentData, float $totalAmount): void
    {
        $paymentDetails = [];
        foreach ($paymentData as $method => $amount) {
            if ($amount > 0) {
                $paymentDetails[] = $this->translatePaymentMethod($method) . ": {$amount} جنيه";
            }
        }

        $action = "إتمام الطلب رقم #{$orderId}";
        $details = [
            'order_id' => $orderId,
            'total_amount' => $totalAmount,
            'payment_methods' => $paymentDetails,
            'payment_summary' => implode(', ', $paymentDetails),
        ];

        $this->logAction($action, $details);
    }

    /**
     * Log order cancellation
     */
    public function logOrderCancellation(int $orderId, string $reason = null): void
    {
        $action = "إلغاء الطلب رقم #{$orderId}";
        $details = [
            'order_id' => $orderId,
            'reason' => $reason ?: 'لم يتم تحديد السبب',
        ];

        $this->logAction($action, $details);
    }

    /**
     * Log customer creation/update
     */
    public function logCustomerAction(string $actionType, array $customerData, int $orderId = null): void
    {
        $actionMap = [
            'create' => 'إنشاء عميل جديد',
            'update' => 'تحديث بيانات العميل',
            'link' => 'ربط عميل بالطلب',
        ];

        $action = $actionMap[$actionType] ?? $actionType;
        if ($orderId) {
            $action .= " للطلب رقم #{$orderId}";
        }

        $details = [
            'customer_name' => $customerData['name'] ?? null,
            'customer_phone' => $customerData['phone'] ?? null,
            'customer_address' => $customerData['address'] ?? null,
            'delivery_cost' => $customerData['delivery_cost'] ?? null,
            'order_id' => $orderId,
        ];

        $this->logAction($action, $details);
    }

    /**
     * Log driver actions
     */
    public function logDriverAction(string $actionType, array $driverData, int $orderId = null): void
    {
        $actionMap = [
            'create' => 'إنشاء سائق جديد',
            'update' => 'تحديث بيانات السائق',
            'link' => 'ربط سائق بالطلب',
        ];

        $action = $actionMap[$actionType] ?? $actionType;
        if ($orderId) {
            $action .= " للطلب رقم #{$orderId}";
        }

        $details = [
            'driver_name' => $driverData['name'] ?? null,
            'driver_phone' => $driverData['phone'] ?? null,
            'order_id' => $orderId,
        ];

        $this->logAction($action, $details);
    }

    /**
     * Log discount application
     */
    public function logDiscountApplication(int $orderId, float $discount, string $type): void
    {
        $discountText = $type === 'percent'
            ? "{$discount}%"
            : "{$discount} جنيه";

        $action = "تطبيق خصم على الطلب رقم #{$orderId}";
        $details = [
            'order_id' => $orderId,
            'discount_amount' => $discount,
            'discount_type' => $type === 'percent' ? 'نسبة مئوية' : 'مبلغ ثابت',
            'discount_display' => $discountText,
        ];

        $this->logAction($action, $details);
    }

    /**
     * Log expense actions
     */
    public function logExpenseAction(string $actionType, array $expenseData): void
    {
        $actionMap = [
            'create' => 'إضافة مصروف جديد',
            'update' => 'تعديل مصروف',
            'delete' => 'حذف مصروف',
        ];

        $action = $actionMap[$actionType] ?? $actionType;
        $details = [
            'expense_id' => $expenseData['id'] ?? null,
            'expense_type' => $expenseData['expense_type_name'] ?? null,
            'amount' => $expenseData['amount'] ?? null,
            'description' => $expenseData['notes'] ?? $expenseData['description'] ?? null,
        ];

        $this->logAction($action, $details);
    }

    /**
     * Log web order actions
     */
    public function logWebOrderAction(string $actionType, int $orderId, array $additionalData = []): void
    {
        $actionMap = [
            'accept' => 'قبول طلب ويب',
            'reject' => 'رفض طلب ويب',
            'cancel' => 'إلغاء طلب ويب',
            'out_for_delivery' => 'تحديد طلب ويب كخارج للتوصيل',
            'complete' => 'إتمام طلب ويب',
            'apply_discount' => 'تطبيق خصم على طلب ويب',
            'save' => 'حفظ طلب ويب',
        ];

        $action = ($actionMap[$actionType] ?? $actionType) . " رقم #{$orderId}";
        $details = array_merge([
            'order_id' => $orderId,
            'action_type' => $actionType,
        ], $additionalData);

        $this->logAction($action, $details);
    }

    /**
     * Log shift actions
     */
    public function logShiftAction(string $actionType, array $shiftData): void
    {
        $actionMap = [
            'start' => 'بدء وردية جديدة',
            'end' => 'إنهاء الوردية',
        ];

        $action = $actionMap[$actionType] ?? $actionType;
        $details = [
            'shift_id' => $shiftData['id'] ?? null,
            'start_cash' => $shiftData['start_cash'] ?? null,
            'end_cash' => $shiftData['end_cash'] ?? null,
            'real_end_cash' => $shiftData['real_end_cash'] ?? null,
            'shift_date' => $shiftData['date'] ?? null,
        ];

        $this->logAction($action, $details);
    }

    /**
     * Calculate differences between old and new order items
     */
    private function calculateItemDifferences(array $oldItems, array $newItems): array
    {
        $differences = [];

        // Create lookup arrays
        $oldItemsLookup = [];
        foreach ($oldItems as $item) {
            $oldItemsLookup[$item['product_id']] = $item;
        }

        $newItemsLookup = [];
        foreach ($newItems as $item) {
            $newItemsLookup[$item['product_id']] = $item;
        }

        // Check for new items and quantity changes
        foreach ($newItems as $newItem) {
            $productId = $newItem['product_id'];
            $productName = $newItem['product_name'] ?? "المنتج رقم {$productId}";

            if (!isset($oldItemsLookup[$productId])) {
                // New item added
                $differences[] = [
                    'type' => 'added',
                    'product_name' => $productName,
                    'quantity' => $newItem['quantity'],
                    'description' => "تم إضافة {$newItem['quantity']} من {$productName}",
                ];
            } else {
                // Check for quantity changes
                $oldQuantity = $oldItemsLookup[$productId]['quantity'];
                $newQuantity = $newItem['quantity'];

                if ($oldQuantity != $newQuantity) {
                    $differences[] = [
                        'type' => 'quantity_changed',
                        'product_name' => $productName,
                        'old_quantity' => $oldQuantity,
                        'new_quantity' => $newQuantity,
                        'difference' => $newQuantity - $oldQuantity,
                        'description' => "تم تغيير كمية {$productName} من {$oldQuantity} إلى {$newQuantity}",
                    ];
                }

                // Check for notes changes
                $oldNotes = $oldItemsLookup[$productId]['notes'] ?? '';
                $newNotes = $newItem['notes'] ?? '';

                if ($oldNotes != $newNotes) {
                    $differences[] = [
                        'type' => 'notes_changed',
                        'product_name' => $productName,
                        'old_notes' => $oldNotes,
                        'new_notes' => $newNotes,
                        'description' => "تم تغيير ملاحظات {$productName}",
                    ];
                }
            }
        }

        // Check for removed items
        foreach ($oldItems as $oldItem) {
            $productId = $oldItem['product_id'];
            if (!isset($newItemsLookup[$productId])) {
                $productName = $oldItem['product_name'] ?? "المنتج رقم {$productId}";
                $differences[] = [
                    'type' => 'removed',
                    'product_name' => $productName,
                    'quantity' => $oldItem['quantity'],
                    'description' => "تم حذف {$oldItem['quantity']} من {$productName}",
                ];
            }
        }

        return $differences;
    }

    /**
     * Translate payment method to Arabic
     */
    private function translatePaymentMethod(string $method): string
    {
        $translations = [
            'cash' => 'نقدي',
            'card' => 'كارت',
            'talabat_card' => 'كارت طلبات',
        ];

        return $translations[$method] ?? $method;
    }

    /**
     * Create a shift-specific log channel
     */
    private function createShiftLogChannel(string $channelName, Shift $shift): void
    {
        $shiftDate = $shift->created_at->format('Y-m-d');
        $logPath = storage_path("logs/shifts/shift_{$shift->id}_{$shiftDate}.log");

        // Ensure directory exists
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        config([
            "logging.channels.{$channelName}" => [
                'driver' => 'single',
                'path' => $logPath,
                'level' => 'info',
                'replace_placeholders' => true,
            ]
        ]);
    }
}
