<?php

namespace App\Services\Orders;

use InvalidArgumentException;
use App\Enums\PaymentMethod;
use App\DTOs\Orders\PaymentDTO;
use App\Enums\PaymentStatus;
use App\Events\Orders\PaymentProcessed;
use App\Models\Order;
use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;

class OrderPaymentService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
    ) {}

    public function processPayment(Order $order, PaymentDTO $paymentDTO): Order
    {
        // Calculate remaining balance
        $totalPaid = $this->paymentRepository->getTotalPaidForOrder($order->id);
        $remainingBalance = $order->total - $totalPaid;

        // Cap payment amount to remaining balance
        $actualPaymentAmount = min($paymentDTO->amount, $remainingBalance);

        // Only process payment if there's a remaining balance
        if ($actualPaymentAmount > 0) {
            // Create adjusted payment DTO
            $adjustedPaymentDTO = new PaymentDTO(
                amount: $actualPaymentAmount,
                method: $paymentDTO->method,
                orderId: $paymentDTO->orderId,
                shiftId: $paymentDTO->shiftId
            );

            // Create payment record
            $payment = $this->paymentRepository->create($adjustedPaymentDTO);

            // Update order payment status
            $this->updateOrderPaymentStatus($order);

            // Fire event
            PaymentProcessed::dispatch($payment, $order);
        }

        return $order->refresh();
    }

    public function processMultiplePayments(Order $order, array $paymentsData, int $shiftId): array
    {
        $payments = [];
        $totalPaid = $this->paymentRepository->getTotalPaidForOrder($order->id);
        $remainingBalance = $order->total - $totalPaid;

        // Calculate total of new payments
        $totalNewPayments = array_sum(array_filter($paymentsData, fn($amount) => $amount > 0));

        // Validate that total payments don't exceed order total
        if (($totalPaid + $totalNewPayments) > $order->total) {
            throw new InvalidArgumentException(
                "إجمالي المدفوعات ({$totalPaid} + {$totalNewPayments} = " . ($totalPaid + $totalNewPayments) .
                ") يتجاوز إجمالي الطلب (" . $order->total . ")"
            );
        }

        foreach ($paymentsData as $method => $amount) {
            if ($amount > 0 && $remainingBalance > 0) {
                // Cap payment amount to remaining balance
                $actualPaymentAmount = min($amount, $remainingBalance);

                $paymentDTO = new PaymentDTO(
                    amount: $actualPaymentAmount,
                    method: PaymentMethod::from($method),
                    orderId: $order->id,
                    shiftId: $shiftId
                );

                $payment = $this->paymentRepository->create($paymentDTO);
                $payments[] = $payment;

                // Update remaining balance for next payment
                $remainingBalance -= $actualPaymentAmount;

                // Fire event for each payment
                PaymentProcessed::dispatch($payment, $order);

                // Break if order is fully paid
                if ($remainingBalance <= 0) {
                    break;
                }
            }
        }

        // Update order payment status
        $this->updateOrderPaymentStatus($order);

        return $payments;
    }

    private function updateOrderPaymentStatus(Order $order): void
    {
        $totalPaid = $this->paymentRepository->getTotalPaidForOrder($order->id);
        if ($totalPaid >= $order->total) {
            $paymentStatus = PaymentStatus::FULL_PAID;
        } elseif ($totalPaid > 0) {
            $paymentStatus = PaymentStatus::PARTIAL_PAID;
        } else {
            $paymentStatus = PaymentStatus::PENDING;
        }

        $order->update(['payment_status' => $paymentStatus]);
    }
}
