<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;

class OrderCalculationService
{
    private const TAX_RATE = 0.0; // 0% tax rate as per original system


    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {
    }

    public function calculateOrderTotals(Order $order): Order
    {
        $order->load(['items', 'customer']);

        // Calculate subtotal
        $subtotal = $order->items->sum(fn($item) => $item->total);

        // Calculate service charge
        $serviceCharge = $this->calculateServiceCharge($order, $subtotal);

        // Calculate tax
        $tax = $this->calculateTax($subtotal);

        // Apply discount if set
        $discount = $this->calculateDiscount($order, $subtotal);

        // Calculate total
        $total = ceil($subtotal + $serviceCharge + $tax - $discount);

        // Calculate profit
        $profit = $this->calculateProfit($order, $total);

        // Update order
        $this->orderRepository->update($order, [
            'sub_total' => $subtotal,
            'service' => $serviceCharge,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
            'profit' => $profit,
        ]);

        return $order->refresh();
    }

    public function applyDiscount(Order $order, float $discount, string $discountType): Order
    {
        $order->load('items');

        // Clear all item-level discounts when applying order-level discount (mutual exclusivity)
        $order->items()->update([
            'item_discount' => 0,
            'item_discount_type' => null,
            'item_discount_percent' => null,
        ]);

        // Reset existing discounts
        $updateData = [
            'discount' => 0,
            'temp_discount_percent' => 0,
        ];

        // Apply new discount
        if ($discountType === 'percent') {
            $updateData['temp_discount_percent'] = $discount;
        } else {
            $updateData['discount'] = $discount;
        }

        $this->orderRepository->update($order, $updateData);
        $order->refresh();

        // Recalculate totals
        return $this->calculateOrderTotals($order);
    }

    private function calculateServiceCharge(Order $order, float $subtotal): float
    {
        if ($order->type->hasDeliveryFee() && $order->customer) {
            // Delivery fee from customer
            return $order->customer->delivery_cost ?? 0;
        }

        return 0;
    }

    private function calculateTax(float $subtotal): float
    {
        return $subtotal * self::TAX_RATE;
    }

    private function calculateDiscount(Order $order, float $subtotal): float
    {
        // Check for item-level discounts first
        $hasItemDiscounts = $order->items->some(fn($item) => ($item->item_discount ?? 0) > 0);

        if ($hasItemDiscounts) {
            // Sum all item-level discounts
            return $order->items->sum(function ($item) {
                $itemSubtotal = $item->price * $item->quantity;

                if ($item->item_discount_type === 'percent' && $item->item_discount_percent) {
                    $discount = $itemSubtotal * ($item->item_discount_percent / 100);
                    return min($discount, $itemSubtotal);
                }

                return min($item->item_discount ?? 0, $itemSubtotal);
            });
        }

        // Fall back to order-level discount
        if ($order->temp_discount_percent > 0) {
            return ($order->temp_discount_percent / 100) * $subtotal;
        }

        return $order->discount ?? 0;
    }

    private function calculateProfit(Order $order, float $total): float
    {
        $totalCost = $order->items->sum(fn($item) => $item->cost * $item->quantity);
        return $total - $totalCost;
    }
}

