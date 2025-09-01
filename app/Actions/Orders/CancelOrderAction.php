<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Services\Orders\OrderService;

class CancelOrderAction
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function execute(int $orderId, string $reason = ''): Order
    {
        return $this->orderService->cancelOrder($orderId, $reason);
    }
}
