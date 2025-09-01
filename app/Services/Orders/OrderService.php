<?php

namespace App\Services\Orders;

use Illuminate\Support\Facades\Log;
use App\Enums\OrderType;
use App\DTOs\Orders\CreateOrderDTO;
use App\DTOs\Orders\OrderItemDTO;
use App\DTOs\Orders\PaymentDTO;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Events\Orders\OrderCancelled;
use App\Events\Orders\OrderCompleted;
use App\Events\Orders\OrderCreated;
use App\Exceptions\OrderException;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\Orders\OrderCalculationService;
use App\Services\Orders\OrderPaymentService;
use App\Services\Orders\TableManagementService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Setting to allow or disallow operations with insufficient stock
     * Set to false to prevent orders from being completed when stock is insufficient
     */
    public const ALLOW_INSUFFICIENT_STOCK = false;

    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderCreationService $orderCreationService,
        private readonly OrderPaymentService $orderPaymentService,
        private readonly OrderCalculationService $orderCalculationService,
        private readonly TableManagementService $tableManagementService,
        private readonly OrderCompletionService $orderCompletionService,
        private readonly OrderStockConversionService $orderStockConversionService,
    ) {
    }

    public function createOrder(CreateOrderDTO $createOrderDTO): Order
    {
        return DB::transaction(function () use ($createOrderDTO) {
            return $this->orderCreationService->create($createOrderDTO);
        });
    }

    public function getShiftOrders(int $shiftId): Collection
    {
        return $this->orderRepository->getShiftOrders($shiftId);
    }

    public function getOrderDetails(int $orderId): Order
    {
        $order = $this->orderRepository->findByIdOrFail($orderId);
        $order->load(['items.product', 'user', 'customer', 'driver', 'table', 'payments']);

        return $order;
    }

    public function updateOrderItems(int $orderId, array $itemsData): Order
    {
        return DB::transaction(function () use ($orderId, $itemsData) {
            $order = $this->orderRepository->findByIdOrFail($orderId);

            if (!$order->status->canBeModified()) {
                throw new OrderException('لا يمكن تعديل الطلب في هذه المرحلة');
            }

            $productIds = array_column($itemsData, 'product_id');
            $products = Product::select(['id', 'cost', 'price'])->whereIn('id', $productIds)->get();
            $itemsData = array_map(function ($item) use ($products) {
                $product = $products->firstWhere('id', $item['product_id']);
                if (!$product) {
                    throw new OrderException('المنتج غير موجود');
                }
                return [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => (float) $product->price,
                    'cost' => (float) $product->cost,
                    'notes' => $item['notes'] ?? null,
                ];
            }, $itemsData);

            $itemDTOs = array_map(fn($item) => OrderItemDTO::fromArray($item), $itemsData);

            return $this->orderCreationService->updateOrderItems($order, $itemDTOs);
        });
    }

    public function linkCustomer(int $orderId, ?int $customerId): Order
    {
        $order = $this->orderRepository->findByIdOrFail($orderId);

        if (!$order->status->canBeModified()) {
            throw new OrderException('لا يمكن ربط العميل في هذه المرحلة');
        }

        $this->orderRepository->update($order, ['customer_id' => $customerId]);
        $order->refresh();

        // Recalculate totals as delivery cost might change
        $this->orderCalculationService->calculateOrderTotals($order);

        return $order;
    }

    public function linkDriver(int $orderId, int $driverId): Order
    {
        $order = $this->orderRepository->findByIdOrFail($orderId);

        if (!$order->status->canBeModified()) {
            throw new OrderException('لا يمكن ربط السائق في هذه المرحلة');
        }

        $this->orderRepository->update($order, ['driver_id' => $driverId]);
        $order->refresh();

        return $order;
    }

    public function updateNotes(int $orderId, ?string $kitchenNotes = null, ?string $orderNotes = null): Order
    {
        $order = $this->orderRepository->findByIdOrFail($orderId);

        $updateData = [];
        if ($kitchenNotes !== null) {
            $updateData['kitchen_notes'] = $kitchenNotes;
        }
        if ($orderNotes !== null) {
            $updateData['order_notes'] = $orderNotes;
        }

        if (!empty($updateData)) {
            $this->orderRepository->update($order, $updateData);
            $order->refresh();
        }

        return $order;
    }

    public function applyDiscount(int $orderId, float $discount, string $discountType): Order
    {
        return DB::transaction(function () use ($orderId, $discount, $discountType) {
            $order = $this->orderRepository->findByIdOrFail($orderId);

            if (!$order->status->canBeModified()) {
                throw new OrderException('لا يمكن تطبيق الخصم في هذه المرحلة');
            }

            return $this->orderCalculationService->applyDiscount($order, $discount, $discountType);
        });
    }

    public function completeOrder(int $orderId, array $paymentsData, bool $shouldPrint = false): Order
    {
        return DB::transaction(function () use ($orderId, $paymentsData, $shouldPrint) {
            $order = $this->orderRepository->findByIdOrFail($orderId);
            $order = Order::where('id', $orderId)->lockForUpdate()->firstOrFail();

            if (!in_array($order->status, [OrderStatus::PROCESSING, OrderStatus::OUT_FOR_DELIVERY])) {
                throw new OrderException('الطلب غير متاح للإكمال');
            }

            // Validate stock availability before completing the order
            // $insufficientItems = $this->orderStockConversionService->validateOrderStockAvailability($order);
            // if (!empty($insufficientItems) && !self::ALLOW_INSUFFICIENT_STOCK) {
            //     $itemNames = array_column($insufficientItems, 'product_name');
            //     throw new OrderException('مخزون غير كافي للمنتجات: ' . implode(', ', $itemNames));
            // }

            // Complete the order
            $completedOrder = $this->orderCompletionService->complete($order, $paymentsData, $shouldPrint);

            // Remove stock items after successful completion
            $stockRemoved = $this->orderStockConversionService->removeStockForCompletedOrder($completedOrder);

            if (!$stockRemoved) {
                // Log warning but don't fail the transaction as order is already completed
                Log::warning('Failed to remove stock for completed order', [
                    'order_id' => $completedOrder->id
                ]);
            }

            return $completedOrder;
        });
    }

    public function cancelOrder(int $orderId, string $reason = ''): Order
    {
        return DB::transaction(function () use ($orderId, $reason) {
            $order = $this->orderRepository->findByIdOrFail($orderId);

            if (!$order->status->canBeCancelled()) {
                throw new OrderException('لا يمكن إلغاء الطلب في هذه المرحلة');
            }

            // Store the original status to check if we need to restore stock
            $wasCompleted = $order->status === OrderStatus::COMPLETED;

            // Free table if dine-in
            if ($order->type->requiresTable() && $order->dine_table_number) {
                $this->tableManagementService->freeTable($order->dine_table_number);
            }

            // Update order status
            $this->orderRepository->update($order, [
                'status' => OrderStatus::CANCELLED,
                'profit' => 0,
            ]);

            // Delete any payments
            $order->payments()->delete();

            $order->refresh();

            // If order was completed, add stock back
            if ($wasCompleted) {
                $stockRestored = $this->orderStockConversionService->addStockForCancelledOrder($order);

                if (!$stockRestored) {
                    Log::warning('Failed to restore stock for cancelled order', [
                        'order_id' => $order->id
                    ]);
                }
            }

            // Fire event
            OrderCancelled::dispatch($order, $reason);

            return $order;
        });
    }

    public function processOldOrderPayment(int $orderId, PaymentDTO $paymentDTO): Order
    {
        return DB::transaction(function () use ($orderId, $paymentDTO) {
            $order = $this->orderRepository->findByIdOrFail($orderId);

            return $this->orderPaymentService->processPayment($order, $paymentDTO);
        });
    }

    public function changeOrderType(int $orderId, string $newType, ?string $tableNumber = null): Order
    {
        return DB::transaction(function () use ($orderId, $newType, $tableNumber) {
            $order = $this->orderRepository->findByIdOrFail($orderId);

            if (!$order->status->canBeModified()) {
                throw new OrderException('لا يمكن تغيير نوع الطلب في هذه المرحلة');
            }

            // Handle table management
            $oldType = $order->type;
            $newOrderType = OrderType::from($newType);

            // Free old table if switching from dine-in
            if ($oldType->requiresTable() && $order->dine_table_number) {
                $this->tableManagementService->freeTable($order->dine_table_number);
            }

            // Reserve new table if switching to dine-in
            if ($newOrderType->requiresTable()) {
                if (!$tableNumber) {
                    throw new OrderException('رقم الطاولة مطلوب للطلبات الداخلية');
                }
                $this->tableManagementService->reserveTable($tableNumber, $order->id);
            }

            // Update order
            $this->orderRepository->update($order, [
                'type' => $newOrderType,
                'dine_table_number' => $newOrderType->requiresTable() ? $tableNumber : null,
            ]);

            $order->refresh();

            // Recalculate totals as service charges might change
            $this->orderCalculationService->calculateOrderTotals($order);

            return $order;
        });
    }

    public function getOrderStockRequirements(int $orderId): array
    {
        $order = $this->orderRepository->findByIdOrFail($orderId);
        $order->load('items.product');

        return $this->orderStockConversionService->getOrderStockRequirements($order);
    }

    public function validateOrderStockAvailability(int $orderId): array
    {
        $order = $this->orderRepository->findByIdOrFail($orderId);
        $order->load('items.product');

        return $this->orderStockConversionService->validateOrderStockAvailability($order);
    }
}
