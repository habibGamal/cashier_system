<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ReturnItem;
use App\Models\ReturnOrder;
use App\Services\Orders\OrderStockConversionService;
use App\Services\ShiftService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ReturnOrderController extends Controller
{
    public function __construct(
        private readonly OrderStockConversionService $stockConversionService,
        private readonly ShiftService $shiftService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $returnOrders = ReturnOrder::with(['order', 'customer', 'user', 'shift', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($returnOrders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'reason' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.reason' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $currentShift = $this->shiftService->getCurrentShift();
            // Validate that the order exists and is completed
            $order = Order::with(['shift', 'items'])->findOrFail($request->order_id);

            if ($order->status !== \App\Enums\OrderStatus::COMPLETED) {
                return response()->json([
                    'message' => 'Cannot return items from an incomplete order.',
                ], 400);
            }

            // Validate return quantities against original order items
            $this->validateReturnQuantitiesAgainstOrderItems($order, $request->items);

            // Generate return number (unique per shift)
            $returnNumber = $this->generateReturnNumberForShift($order->shift_id);

            // Calculate order-level discount ratio for proper return price calculation
            $orderDiscountRatio = $this->calculateOrderDiscountRatio($order);

            // Index order items by ID for quick lookup
            $orderItemsById = $order->items->keyBy('id');

            // Calculate total refund amount with proper discount consideration
            $items = $request->items;
            $refundAmount = 0;

            // Create return order
            $returnOrder = ReturnOrder::create([
                'order_id' => $request->order_id,
                'customer_id' => $order->customer_id,
                'user_id' => Auth::id(),
                'shift_id' => $currentShift->id,
                'return_number' => $returnNumber,
                'status' => 'completed',
                'refund_amount' => 0, // Will be updated after calculating items
                'reason' => $request->reason,
                'notes' => $request->notes,
            ]);

            // Create return items with correct effective return prices
            foreach ($items as $item) {
                // Get original order item for cost and discount information
                $orderItem = $orderItemsById[$item['order_item_id']];

                // Calculate effective return price per unit considering discounts
                $effectiveReturnPrice = $this->calculateEffectiveReturnPricePerUnit($orderItem, $orderDiscountRatio);
                $itemTotal = $item['quantity'] * $effectiveReturnPrice;
                $refundAmount += $itemTotal;

                ReturnItem::create([
                    'return_order_id' => $returnOrder->id,
                    'order_item_id' => $item['order_item_id'],
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'original_price' => $orderItem->price,
                    'original_cost' => $orderItem->cost,
                    'return_price' => $effectiveReturnPrice,
                    'total' => $itemTotal,
                    'reason' => $item['reason'] ?? null,
                ]);
            }

            // Update return order with calculated refund amount
            $returnOrder->update(['refund_amount' => $refundAmount]);

            // Restore stock for returned items
            $this->restoreStockForReturnOrder($returnOrder);

            DB::commit();

            // Load relationships for response
            $returnOrder->load(['order', 'customer', 'user', 'shift', 'items.product', 'items.orderItem']);

            return back()->with('success', 'تم إنشاء طلب الإرجاع بنجاح');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create return order', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return back()
                ->withErrors(['error' => 'حدث خطأ أثناء معالجة طلب الإرجاع: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified return order.
     */
    public function show(ReturnOrder $returnOrder)
    {
        $returnOrder->load([
            'order:id,order_number,created_at,total',
            'customer:id,name,phone,address',
            'user:id,name,email',
            'items.product:id,name',
            'items.orderItem:id,product_id,quantity,price',
        ]);

        return Inertia::render('ReturnOrders/Show', [
            'returnOrder' => $returnOrder,
        ]);
    }

    /**
     * Get order details for return processing
     */
    public function getOrderForReturn(int $orderId): JsonResponse
    {
        try {
            $order = Order::with(['items.product', 'customer'])
                ->findOrFail($orderId);

            if ($order->status !== \App\Enums\OrderStatus::COMPLETED) {
                return response()->json([
                    'message' => 'Order must be completed to process returns.',
                ], 400);
            }

            // Get previously returned quantities for each order item
            $returnedQuantities = $this->getReturnedQuantitiesByOrderItem($orderId);

            // Calculate order-level discount ratio if applicable
            $orderDiscountRatio = $this->calculateOrderDiscountRatio($order);

            // Add available return quantities and effective return price to each item
            $order->items->transform(function ($item) use ($returnedQuantities, $orderDiscountRatio) {
                $alreadyReturned = $returnedQuantities[$item->id] ?? 0;
                $item->available_for_return = max(0, $item->quantity - $alreadyReturned);
                $item->already_returned = $alreadyReturned;

                // Calculate effective return price per unit considering discounts
                $item->effective_return_price = $this->calculateEffectiveReturnPricePerUnit($item, $orderDiscountRatio);

                return $item;
            });

            return response()->json($order);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Order not found or cannot be returned.',
            ], 404);
        }
    }

    /**
     * Calculate order-level discount ratio (discount per unit of subtotal)
     */
    private function calculateOrderDiscountRatio(Order $order): float
    {
        // Check if order has item-level discounts (mutual exclusivity)
        $hasItemDiscounts = $order->items->some(fn ($item) => ($item->item_discount ?? 0) > 0);

        if ($hasItemDiscounts) {
            // Item-level discounts are applied, no order-level discount ratio
            return 0;
        }

        // Calculate subtotal (sum of item totals before order-level discount)
        $subtotal = $order->items->sum(fn ($item) => $item->price * $item->quantity);

        if ($subtotal <= 0) {
            return 0;
        }

        // Calculate order-level discount amount
        $orderDiscount = 0;
        if ($order->temp_discount_percent > 0) {
            $orderDiscount = ($order->temp_discount_percent / 100) * $subtotal;
        } elseif ($order->discount > 0) {
            $orderDiscount = $order->discount;
        }

        // Return ratio: discount per unit of subtotal
        return $orderDiscount / $subtotal;
    }

    /**
     * Calculate effective return price per unit considering discounts
     */
    private function calculateEffectiveReturnPricePerUnit($item, float $orderDiscountRatio): float
    {
        $unitPrice = (float) $item->price;
        $itemSubtotal = $unitPrice * $item->quantity;

        // Check for item-level discount
        if (($item->item_discount ?? 0) > 0 || ($item->item_discount_percent ?? 0) > 0) {
            $itemDiscount = 0;

            if ($item->item_discount_type === 'percent' && $item->item_discount_percent > 0) {
                // Percentage discount on item
                $itemDiscount = $itemSubtotal * ($item->item_discount_percent / 100);
            } else {
                // Fixed value discount on item
                $itemDiscount = (float) ($item->item_discount ?? 0);
            }

            // Ensure discount doesn't exceed item subtotal
            $itemDiscount = min($itemDiscount, $itemSubtotal);

            // Effective price per unit = (subtotal - discount) / quantity
            $effectivePricePerUnit = ($itemSubtotal - $itemDiscount) / $item->quantity;

            return round($effectivePricePerUnit, 2);
        }

        // Apply order-level discount ratio if applicable
        if ($orderDiscountRatio > 0) {
            // Proportional discount per unit
            $discountPerUnit = $unitPrice * $orderDiscountRatio;
            $effectivePricePerUnit = $unitPrice - $discountPerUnit;

            return round($effectivePricePerUnit, 2);
        }

        // No discount, return original price
        return $unitPrice;
    }

    /**
     * Validate return quantities against original order items using order_item_id
     */
    private function validateReturnQuantitiesAgainstOrderItems(Order $order, array $returnItems): void
    {
        $orderItems = $order->items->keyBy('id');
        $returnedQuantities = $this->getReturnedQuantitiesByOrderItem($order->id);

        foreach ($returnItems as $returnItem) {
            $orderItemId = $returnItem['order_item_id'];
            $returnQuantity = $returnItem['quantity'];

            // Check if order item exists in original order
            if (! $orderItems->has($orderItemId)) {
                throw new Exception("Order item ID {$orderItemId} was not found in the original order.");
            }

            $orderItem = $orderItems[$orderItemId];
            $alreadyReturned = $returnedQuantities[$orderItemId] ?? 0;
            $availableForReturn = $orderItem->quantity - $alreadyReturned;

            if ($returnQuantity > $availableForReturn) {
                $productName = $orderItem->product->name;
                throw new Exception("Cannot return {$returnQuantity} of {$productName}. Only {$availableForReturn} available for return.");
            }
        }
    }

    /**
     * Get already returned quantities for an order by order_item_id
     */
    private function getReturnedQuantitiesByOrderItem(int $orderId): array
    {
        $returnedItems = ReturnItem::whereHas('returnOrder', function ($query) use ($orderId) {
            $query->where('order_id', $orderId);
        })->get();

        $quantities = [];
        foreach ($returnedItems as $item) {
            $quantities[$item->order_item_id] = ($quantities[$item->order_item_id] ?? 0) + $item->quantity;
        }

        return $quantities;
    }

    /**
     * Generate a unique return number for a specific shift
     */
    private function generateReturnNumberForShift(int $shiftId): int
    {
        $lastReturn = ReturnOrder::where('shift_id', $shiftId)
            ->orderBy('return_number', 'desc')
            ->first();

        return $lastReturn ? $lastReturn->return_number + 1 : 1;
    }

    /**
     * Restore stock for returned items (reverse of removeStockForCompletedOrder)
     */
    private function restoreStockForReturnOrder(ReturnOrder $returnOrder): bool
    {
        return $this->stockConversionService->addStockForReturnOrder($returnOrder);
    }
}
