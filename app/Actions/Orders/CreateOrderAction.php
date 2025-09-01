<?php

namespace App\Actions\Orders;

use App\DTOs\Orders\CreateOrderDTO;
use App\Models\Order;
use App\Services\Orders\OrderService;

class CreateOrderAction
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function execute(CreateOrderDTO $createOrderDTO): Order
    {
        return $this->orderService->createOrder($createOrderDTO);
    }
}
