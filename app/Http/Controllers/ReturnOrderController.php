<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnOrder;
use App\Models\ReturnItem;
use App\Services\Orders\OrderStockConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class ReturnOrderController extends Controller
{
    public function __construct(
        private readonly OrderStockConversionService $stockConversionService
    ) {
    }

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
            'items.*.return_price' => 'required|numeric|min:0',
            'items.*.reason' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Validate that the order exists and is completed
            $order = Order::with(['shift'])->findOrFail($request->order_id);

            if ($order->status !== \App\Enums\OrderStatus::COMPLETED) {
                return response()->json([
                    'message' => 'Cannot return items from an incomplete order.'
                ], 400);
            }

            // Validate return quantities against original order items
            $this->validateReturnQuantitiesAgainstOrderItems($order, $request->items);

            // Generate return number (unique per shift)
            $returnNumber = $this->generateReturnNumberForShift($order->shift_id);

            // Calculate total refund amount
            $items = $request->items;
            $refundAmount = collect($items)->sum(fn($item) => $item['quantity'] * $item['return_price']);

            // Create return order
            $returnOrder = ReturnOrder::create([
                'order_id' => $request->order_id,
                'customer_id' => $order->customer_id,
                'user_id' => Auth::id(),
                'shift_id' => $order->shift_id,
                'return_number' => $returnNumber,
                'status' => 'completed',
                'refund_amount' => $refundAmount,
                'reason' => $request->reason,
                'notes' => $request->notes,
            ]);

            // Create return items
            foreach ($items as $item) {
                // Get original order item for cost information
                $orderItem = \App\Models\OrderItem::findOrFail($item['order_item_id']);
                $itemTotal = $item['quantity'] * $item['return_price'];

                ReturnItem::create([
                    'return_order_id' => $returnOrder->id,
                    'order_item_id' => $item['order_item_id'],
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'original_price' => $orderItem->price,
                    'original_cost' => $orderItem->cost,
                    'return_price' => $item['return_price'],
                    'total' => $itemTotal,
                    'reason' => $item['reason'] ?? null,
                ]);
            }

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
                'request' => $request->all()
            ]);

            return back()
                ->withErrors(['error' => 'حدث خطأ أثناء معالجة طلب الإرجاع: ' . $e->getMessage()])
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
            'items.orderItem:id,product_id,quantity,price'
        ]);

        return Inertia::render('ReturnOrders/Show', [
            'returnOrder' => $returnOrder
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
                    'message' => 'Order must be completed to process returns.'
                ], 400);
            }

            // Get previously returned quantities for each order item
            $returnedQuantities = $this->getReturnedQuantitiesByOrderItem($orderId);

            // Add available return quantities to each item
            $order->items->transform(function ($item) use ($returnedQuantities) {
                $alreadyReturned = $returnedQuantities[$item->id] ?? 0;
                $item->available_for_return = max(0, $item->quantity - $alreadyReturned);
                $item->already_returned = $alreadyReturned;
                return $item;
            });

            return response()->json($order);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Order not found or cannot be returned.'
            ], 404);
        }
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
            if (!$orderItems->has($orderItemId)) {
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
