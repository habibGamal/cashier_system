<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Services\Orders\OrderService;

class CompleteOrderAction
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function execute(int $orderId, array $paymentsData, bool $shouldPrint = false): Order
    {
        return $this->orderService->completeOrder($orderId, $paymentsData, $shouldPrint);
    }
}
