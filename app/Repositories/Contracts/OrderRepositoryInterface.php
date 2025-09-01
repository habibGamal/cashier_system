<?php

namespace App\Repositories\Contracts;

use App\DTOs\Orders\CreateOrderDTO;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function create(CreateOrderDTO $orderDTO): Order;

    public function findById(int $id): ?Order;

    public function findByIdOrFail(int $id): Order;

    public function getShiftOrders(int $shiftId): Collection;

    public function getProcessingOrders(int $shiftId): Collection;

    public function getNextOrderNumber(int $shiftId): int;

    public function update(Order $order, array $data): bool;

    public function delete(Order $order): bool;

    public function getOrdersWithPaymentStatus(string $paymentStatus): Collection;
}
