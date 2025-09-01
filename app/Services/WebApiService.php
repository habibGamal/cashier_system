<?php

namespace App\Services;

use Exception;
use App\Enums\PaymentStatus;
use App\Events\Orders\WebOrderReceived;
use App\Models\Shift;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Models\DailySnapshot;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\SettingKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebApiService
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }
    /**
     * Check if we can accept new orders
     */
    public function canAcceptOrder(): bool
    {
        // Check if day is open (has active shift or recent activity)
        $activeShift = Shift::where('closed', false)->first();
        if ($activeShift) {
            return true;
        }

        // Check if there was recent activity today
        $todayActivity = DailySnapshot::where('date', today()->format('Y-m-d'))->exists();
        return $todayActivity;
    }

    /**
     * Get current shift ID
     */
    public function getShiftId(): ?int
    {
        $activeShift = Shift::where('closed', false)->first();
        return $activeShift?->id;
    }

    /**
     * Verify shift ID is valid
     */
    private function verifyShiftId(int $shiftId): void
    {
        $currentShiftId = $this->getShiftId();
        if ($shiftId !== $currentShiftId) {
            throw new Exception('الوردية غير صحيحة');
        }
    }

    /**
     * Create or get customer
     */
    private function createOrGetCustomer(array $customerData): Customer
    {
        $customer = Customer::firstOrCreate(
            ['phone' => $customerData['phone']],
            [
                'name' => $customerData['name'],
                'phone' => $customerData['phone'],
                'region' => $customerData['area'],
                'address' => $customerData['address'],
                'has_whatsapp' => false,
                'delivery_cost' => 0,
            ]
        );

        // Update address and region
        $customer->update([
            'address' => $customerData['address'],
            'region' => $customerData['area'],
        ]);

        return $customer;
    }

    /**
     * Fill order items from POS ref objects
     */
    private function fillOrderItems(Order $order, array $items): void
    {
        $orderItemsData = [];

        // Extract all product refs from items
        $productRefs = collect($items)
            ->flatMap(fn($item) => $item['posRefObj'])
            ->pluck('productRef')
            ->unique()
            ->toArray();

        // Get all products
        $products = Product::whereIn('product_ref', $productRefs)->get()->keyBy('product_ref');

        // Check if all products are found
        $notFoundProducts = collect($productRefs)
            ->reject(fn($ref) => $products->has($ref))
            ->toArray();

        if (!empty($notFoundProducts)) {
            throw new Exception('منتجات غير موجودة: ' . json_encode($notFoundProducts));
        }

        // Build order items
        foreach ($items as $item) {
            foreach ($item['posRefObj'] as $posRefObj) {
                $product = $products->get($posRefObj['productRef']);
                if ($product) {
                    $orderItemsData[] = [
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'] * $posRefObj['quantity'],
                        'price' => $product->price,
                        'cost' => $product->cost,
                        'total' => $product->price * $item['quantity'] * $posRefObj['quantity'],
                        'notes' => $item['notes'] ?? '',
                    ];
                }
            }
        }

        $order->items()->createMany($orderItemsData);
    }

    /**
     * Handle payments and calculate totals
     */
    private function handlePayments(Order $order, array $webOrder): void
    {
        $order->load('items');

        $order->sub_total = $order->items->sum('total');

        // Calculate POS subtotal
        // $order->web_pos_diff = $webOrder['subTotal'] - $order->sub_total;

        $order->tax = $webOrder['tax'];
        $order->service = $webOrder['service'];

        $order->total = $webOrder['total'];

        // pos total
        $realPosTotal = $order->sub_total + $order->tax + $order->service;

        $order->web_pos_diff = $order->total - $realPosTotal;
        $order->discount = $realPosTotal - $order->total;

        // Calculate profit
        $cost = $order->items->sum(fn($item) => $item->cost * $item->quantity);
        $profitValue = $order->total - $cost;

        // Update order with calculated values
        $order->save();

        // Update profit separately to avoid type issues
        $order->update(['profit' => $profitValue]);
    }

    /**
     * Place a new web order
     */
    public function placeOrder(array $data): void
    {
        $this->verifyShiftId($data['order']['shiftId']);

        DB::transaction(function () use ($data) {
            $customer = $this->createOrGetCustomer($data['user']);
            $customer->delivery_cost = $data['order']['service'] ?? 0;
            $customer->save();

            $order = $customer->orders()->create([
                'shift_id' => $data['order']['shiftId'],
                'status' => OrderStatus::PENDING,
                'type' => OrderType::from($data['order']['type']),
                'order_number' => $data['order']['orderNumber'],
                'order_notes' => $data['order']['note'] ?? '',
                'sub_total' => $data['order']['subTotal'],
                'tax' => $data['order']['tax'],
                'service' => $data['order']['service'],
                'discount' => $data['order']['discount'],
                'total' => $data['order']['total'],
                'temp_discount_percent' => 0,
                'profit' => 0, // Will be calculated later
                'payment_status' => PaymentStatus::PENDING,
            ]);

            $this->fillOrderItems($order, $data['order']['items']);
            $this->handlePayments($order, $data['order']);

            // Load the customer relationship for broadcasting
            $order->load('customer', 'items');

            // Broadcast web order received event
            WebOrderReceived::dispatch($order);

            Log::info('طلب ويب جديد تم استلامه', ['order_id' => $order->id, 'order_number' => $order->order_number]);
        });
    }

    /**
     * Accept an order
     */
    public function acceptOrder(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        $order->status = OrderStatus::PROCESSING;
        $order->save();

        $this->notifyWebOrderWithStatus($order);
    }

    /**
     * Reject an order
     */
    public function rejectOrder(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        $order->status = OrderStatus::CANCELLED;
        $order->save();

        // Delete any payments
        $order->payments()->delete();

        $this->notifyWebOrderWithStatus($order);
    }

    /**
     * Set order as out for delivery
     */
    public function outForDelivery(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        $order->status = OrderStatus::OUT_FOR_DELIVERY;
        $order->save();

        $this->notifyWebOrderWithStatus($order);
    }

    /**
     * Complete an order
     */
    public function completeOrder(int $orderId): Order
    {
        $order = Order::findOrFail($orderId);

        $order->status = OrderStatus::COMPLETED;
        $order->save();

        $this->notifyWebOrderWithStatus($order);

        return $order;
    }

    /**
     * Apply discount to order
     */
    public function applyDiscount(int $orderId, float $discount, string $discountType): void
    {
        $order = Order::findOrFail($orderId);

        $order->discount = $discountType === 'percent'
            ? $order->sub_total * ($discount / 100)
            : $discount;

        $order->total = $order->sub_total + $order->tax + $order->service - $order->discount;
        $order->save();
    }

    /**
     * Notify website about order status change
     */
    private function notifyWebOrderWithStatus(Order $order): void
    {
        try {
            $websiteUrl = setting(SettingKey::WEBSITE_URL);

            Http::post($websiteUrl . '/api/order-status', [
                'orderNumber' => $order->order_number,
                'status' => $order->status->value,
            ]);
            Log::info('إرسال تحديث الطلب إلى العميل', [
                'order_id' => $order->id,
                'url' => $websiteUrl . '/api/order-status',
                'orderNumber' => $order->order_number,
                'status' => $order->status->value,
            ]);
            Log::info('تم إرسال تحديث الطلب إلى العميل', ['order_id' => $order->id]);
        } catch (Exception $e) {
            Log::error('فشل في إرسال تحديث الطلب إلى العميل', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            throw new Exception('فشل في إرسال تحديث الطلب إلى العميل');
        }
    }
}
