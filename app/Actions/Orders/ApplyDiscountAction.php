<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Services\Orders\OrderService;

class ApplyDiscountAction
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function execute(int $orderId, float $discount, string $discountType): Order
    {
        return $this->orderService->applyDiscount($orderId, $discount, $discountType);
    }
}
