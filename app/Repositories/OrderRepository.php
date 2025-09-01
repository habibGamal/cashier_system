<?php

namespace App\Repositories;

use App\DTOs\Orders\CreateOrderDTO;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository implements OrderRepositoryInterface
{
    public function create(CreateOrderDTO $orderDTO): Order
    {
        $orderNumber = $this->getNextOrderNumber($orderDTO->shiftId);

        return Order::create([
            'type' => $orderDTO->type,
            'shift_id' => $orderDTO->shiftId,
            'user_id' => $orderDTO->userId,
            'dine_table_number' => $orderDTO->tableNumber,
            'customer_id' => $orderDTO->customerId,
            'driver_id' => $orderDTO->driverId,
            'kitchen_notes' => $orderDTO->kitchenNotes,
            'order_notes' => $orderDTO->orderNotes,
            'order_number' => $orderNumber,
            'status' => OrderStatus::PROCESSING,
            'payment_status' => PaymentStatus::PENDING,
            'sub_total' => 0,
            'tax' => 0,
            'service' => 0,
            'discount' => 0,
            'temp_discount_percent' => 0,
            'total' => 0,
            'profit' => 0,
        ]);
    }

    public function findById(int $id): ?Order
    {
        return Order::find($id);
    }

    public function findByIdOrFail(int $id): Order
    {
        return Order::findOrFail($id);
    }

    public function getShiftOrders(int $shiftId): Collection
    {
        return Order::where('shift_id', $shiftId)
            ->with(['customer', 'driver', 'table', 'items.product'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getProcessingOrders(int $shiftId): Collection
    {
        return Order::where('shift_id', $shiftId)
            ->where('status', OrderStatus::PROCESSING)
            ->with(['customer', 'driver', 'table', 'items.product'])
            ->get();
    }

    public function getNextOrderNumber(int $shiftId): int
    {
        $lastOrder = Order::where('shift_id', $shiftId)
            ->whereNotIn('type', [OrderType::WEB_DELIVERY, OrderType::WEB_TAKEAWAY])
            ->orderBy('order_number', 'desc')
            ->first();
        $nextNumber = $lastOrder ? $lastOrder->order_number + 1 : 1;
        while (Order::where('shift_id', $shiftId)->where('order_number', $nextNumber)->exists()) {
            $nextNumber++;
        }
        return $nextNumber;
    }

    public function update(Order $order, array $data): bool
    {
        return $order->update($data);
    }

    public function delete(Order $order): bool
    {
        return $order->delete();
    }

    public function getOrdersWithPaymentStatus(string $paymentStatus): Collection
    {
        return Order::where('payment_status', $paymentStatus)
            ->with(['customer', 'payments'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
