<?php

namespace App\Repositories\Contracts;

use App\DTOs\Orders\OrderItemDTO;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Collection;

interface OrderItemRepositoryInterface
{
    public function createForOrder(Order $order, OrderItemDTO $itemDTO): OrderItem;

    public function createManyForOrder(Order $order, array $itemDTOs): Collection;

    public function updateOrderItems(Order $order, array $itemDTOs): Collection;

    public function deleteOrderItems(Order $order): bool;

    public function getOrderItems(int $orderId): Collection;
}
