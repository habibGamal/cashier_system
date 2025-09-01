<?php

namespace App\Services\Orders;

use App\DTOs\Orders\PaymentDTO;
use App\Enums\PaymentMethod;
use App\Enums\OrderStatus;
use App\Events\Orders\OrderCompleted;
use App\Models\Order;

class OrderCompletionService
{
    public function __construct(
        private readonly OrderPaymentService $orderPaymentService,
        private readonly TableManagementService $tableManagementService,
        private readonly OrderCalculationService $orderCalculationService,
    ) {}

    public function complete(Order $order, array $paymentsData, bool $shouldPrint = false): Order
    {
        $order->load('items');

        // Calculate final totals
        if (! $order->type->isWebOrder()) {
            $this->orderCalculationService->calculateOrderTotals($order);
        }

        // Process payments - use single or multiple payment based on array content
        $validPayments = array_filter($paymentsData, fn($amount) => $amount > 0);

        if (count($validPayments) === 1) {
            // Single payment - use processPayment method
            $method = array_key_first($validPayments);
            $amount = $validPayments[$method];

            $paymentDTO = new PaymentDTO(
                amount: $amount,
                method: PaymentMethod::from($method),
                orderId: $order->id,
                shiftId: $order->shift_id
            );

            $this->orderPaymentService->processPayment($order, $paymentDTO);
        } else {
            // Multiple payments - use processMultiplePayments method
            $this->orderPaymentService->processMultiplePayments(
                $order,
                $paymentsData,
                $order->shift_id
            );
        }

        // Free table if dine-in
        if ($order->type->requiresTable() && $order->dine_table_number) {
            $this->tableManagementService->freeTable($order->dine_table_number);
        }

        // Update order status
        $order->update(['status' => OrderStatus::COMPLETED]);
        $order->refresh();

        // Fire event
        OrderCompleted::dispatch($order);

        return $order;
    }
}
