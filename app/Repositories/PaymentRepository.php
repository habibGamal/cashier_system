<?php

namespace App\Repositories;

use App\DTOs\Orders\PaymentDTO;
use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function create(PaymentDTO $paymentDTO): Payment
    {
        return Payment::create($paymentDTO->toArray());
    }

    public function getOrderPayments(int $orderId): Collection
    {
        return Payment::where('order_id', $orderId)->get();
    }

    public function getTotalPaidForOrder(int $orderId): float
    {
        return Payment::where('order_id', $orderId)
            ->sum('amount');
    }

    public function deleteOrderPayments(int $orderId): bool
    {
        return Payment::where('order_id', $orderId)->delete() >= 0;
    }
}
