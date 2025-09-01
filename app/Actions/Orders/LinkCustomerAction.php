<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Services\Orders\OrderService;

class LinkCustomerAction
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function execute(int $orderId, int $customerId): Order
    {
        return $this->orderService->linkCustomer($orderId, $customerId);
    }
}
