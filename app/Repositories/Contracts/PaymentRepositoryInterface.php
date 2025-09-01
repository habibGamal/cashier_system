<?php

namespace App\Repositories\Contracts;

use App\DTOs\Orders\PaymentDTO;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

interface PaymentRepositoryInterface
{
    public function create(PaymentDTO $paymentDTO): Payment;

    public function getOrderPayments(int $orderId): Collection;

    public function getTotalPaidForOrder(int $orderId): float;

    public function deleteOrderPayments(int $orderId): bool;
}
