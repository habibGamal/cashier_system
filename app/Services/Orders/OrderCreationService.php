<?php

namespace App\Services\Orders;

use App\DTOs\Orders\CreateOrderDTO;
use App\DTOs\Orders\OrderItemDTO;
use App\Events\Orders\OrderCreated;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\OrderItemRepositoryInterface;

class OrderCreationService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderItemRepositoryInterface $orderItemRepository,
        private readonly OrderCalculationService $orderCalculationService,
    ) {
    }

    public function create(CreateOrderDTO $createOrderDTO): Order
    {
        // Create order
        $order = $this->orderRepository->create($createOrderDTO);

        // Fire event
        OrderCreated::dispatch($order);

        return $order;
    }

    public function updateOrderItems(Order $order, array $itemDTOs): Order
    {
        // Update order items
        $this->orderItemRepository->updateOrderItems($order, $itemDTOs);

        // Recalculate order totals
        $order->refresh();
        $order->load('items');
        $this->orderCalculationService->calculateOrderTotals($order);

        return $order;
    }
}
