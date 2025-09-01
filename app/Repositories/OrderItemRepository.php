<?php

namespace App\Repositories;

use App\DTOs\Orders\OrderItemDTO;
use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\Contracts\OrderItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class OrderItemRepository implements OrderItemRepositoryInterface
{
    public function createForOrder(Order $order, OrderItemDTO $itemDTO): OrderItem
    {
        return $order->items()->create($itemDTO->toArray());
    }

    public function createManyForOrder(Order $order, array $itemDTOs): Collection
    {
        $itemsData = array_map(fn($itemDTO) => $itemDTO->toArray(), $itemDTOs);
        return $order->items()->createMany($itemsData);
    }

    public function updateOrderItems(Order $order, array $itemDTOs): Collection
    {
        // Delete existing items
        $this->deleteOrderItems($order);

        // Create new items
        return $this->createManyForOrder($order, $itemDTOs);
    }

    public function deleteOrderItems(Order $order): bool
    {
        return $order->items()->delete() >= 0;
    }

    public function getOrderItems(int $orderId): Collection
    {
        return OrderItem::where('order_id', $orderId)
            ->with('product')
            ->get();
    }
}
