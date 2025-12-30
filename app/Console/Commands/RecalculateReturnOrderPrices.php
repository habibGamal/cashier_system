<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\ReturnOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateReturnOrderPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'returns:recalculate-prices
                            {--dry-run : Show what would be updated without making changes}
                            {--return-order= : Recalculate a specific return order by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate return order prices to account for item-level and order-level discounts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $specificReturnOrderId = $this->option('return-order');

        if ($dryRun) {
            $this->warn('🔍 Dry-run mode - no changes will be saved');
            $this->newLine();
        }

        // Get return orders to process
        $query = ReturnOrder::with([
            'items.orderItem',
            'order.items',
        ]);

        if ($specificReturnOrderId) {
            $query->where('id', $specificReturnOrderId);
        }

        $returnOrders = $query->get();

        if ($returnOrders->isEmpty()) {
            $this->info('No return orders to process.');

            return Command::SUCCESS;
        }

        $this->info("📦 Processing {$returnOrders->count()} return order(s)...");
        $this->newLine();

        $updatedCount = 0;
        $skippedCount = 0;
        $totalDifference = 0;

        $progressBar = $this->output->createProgressBar($returnOrders->count());
        $progressBar->start();

        foreach ($returnOrders as $returnOrder) {
            $result = $this->processReturnOrder($returnOrder, $dryRun);

            if ($result['updated']) {
                $updatedCount++;
                $totalDifference += $result['difference'];
            } else {
                $skippedCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('📊 Summary:');
        $this->table(
            ['Description', 'Value'],
            [
                ['Return orders processed', $returnOrders->count()],
                ['Orders updated', $updatedCount],
                ['Orders unchanged', $skippedCount],
                ['Total amount difference', number_format($totalDifference, 2).' EGP'],
            ]
        );

        if ($dryRun && $updatedCount > 0) {
            $this->newLine();
            $this->warn('⚠️ This was a dry-run. Run the command without --dry-run to apply changes.');
        }

        return Command::SUCCESS;
    }

    /**
     * Process a single return order
     */
    private function processReturnOrder(ReturnOrder $returnOrder, bool $dryRun): array
    {
        $order = $returnOrder->order;

        if (! $order) {
            $this->newLine();
            $this->error("Return order #{$returnOrder->id}: Original order not found");

            return ['updated' => false, 'difference' => 0];
        }

        // Calculate order-level discount ratio
        $orderDiscountRatio = $this->calculateOrderDiscountRatio($order);

        // Index order items by ID for quick lookup
        $orderItemsById = $order->items->keyBy('id');

        $oldRefundAmount = (float) $returnOrder->refund_amount;
        $newRefundAmount = 0;
        $itemsToUpdate = [];

        foreach ($returnOrder->items as $returnItem) {
            $orderItem = $orderItemsById[$returnItem->order_item_id] ?? null;

            if (! $orderItem) {
                // Try to get from the relation
                $orderItem = $returnItem->orderItem;
            }

            if (! $orderItem) {
                continue;
            }

            $oldReturnPrice = (float) $returnItem->return_price;
            $newReturnPrice = $this->calculateEffectiveReturnPricePerUnit($orderItem, $orderDiscountRatio);

            $oldTotal = (float) $returnItem->total;
            $newTotal = $returnItem->quantity * $newReturnPrice;
            $newRefundAmount += $newTotal;

            // Check if there's a difference
            if (abs($oldReturnPrice - $newReturnPrice) > 0.01) {
                $itemsToUpdate[] = [
                    'return_item' => $returnItem,
                    'old_price' => $oldReturnPrice,
                    'new_price' => $newReturnPrice,
                    'old_total' => $oldTotal,
                    'new_total' => $newTotal,
                ];
            }
        }

        $difference = $oldRefundAmount - $newRefundAmount;

        // Check if there's any change
        if (empty($itemsToUpdate) && abs($difference) < 0.01) {
            return ['updated' => false, 'difference' => 0];
        }

        // Log the changes
        if ($this->output->isVerbose() || $dryRun) {
            $this->newLine();
            $this->info("Return order #{$returnOrder->id} (Original order #{$order->order_number}):");

            if (! empty($itemsToUpdate)) {
                foreach ($itemsToUpdate as $item) {
                    $productName = $item['return_item']->product->name ?? 'Unknown product';
                    $this->line("  • {$productName}: {$item['old_price']} EGP → {$item['new_price']} EGP");
                }
            }

            $this->line("  Refund amount: {$oldRefundAmount} EGP → {$newRefundAmount} EGP (Difference: {$difference} EGP)");
        }

        // Apply changes if not dry run
        if (! $dryRun) {
            DB::transaction(function () use ($itemsToUpdate, $returnOrder, $newRefundAmount) {
                foreach ($itemsToUpdate as $item) {
                    $item['return_item']->update([
                        'return_price' => $item['new_price'],
                        'total' => $item['new_total'],
                    ]);
                }

                $returnOrder->update([
                    'refund_amount' => $newRefundAmount,
                ]);
            });
        }

        return ['updated' => true, 'difference' => $difference];
    }

    /**
     * Calculate order-level discount ratio (discount per unit of subtotal)
     */
    private function calculateOrderDiscountRatio(Order $order): float
    {
        // Check if order has item-level discounts (mutual exclusivity)
        $hasItemDiscounts = $order->items->some(fn ($item) => ($item->item_discount ?? 0) > 0);

        if ($hasItemDiscounts) {
            // Item-level discounts are applied, no order-level discount ratio
            return 0;
        }

        // Calculate subtotal (sum of item totals before order-level discount)
        $subtotal = $order->items->sum(fn ($item) => $item->price * $item->quantity);

        if ($subtotal <= 0) {
            return 0;
        }

        // Calculate order-level discount amount
        $orderDiscount = 0;
        if ($order->temp_discount_percent > 0) {
            $orderDiscount = ($order->temp_discount_percent / 100) * $subtotal;
        } elseif ($order->discount > 0) {
            $orderDiscount = $order->discount;
        }

        // Return ratio: discount per unit of subtotal
        return $orderDiscount / $subtotal;
    }

    /**
     * Calculate effective return price per unit considering discounts
     */
    private function calculateEffectiveReturnPricePerUnit($item, float $orderDiscountRatio): float
    {
        $unitPrice = (float) $item->price;
        $itemSubtotal = $unitPrice * $item->quantity;

        // Check for item-level discount
        if (($item->item_discount ?? 0) > 0 || ($item->item_discount_percent ?? 0) > 0) {
            $itemDiscount = 0;

            if ($item->item_discount_type === 'percent' && $item->item_discount_percent > 0) {
                // Percentage discount on item
                $itemDiscount = $itemSubtotal * ($item->item_discount_percent / 100);
            } else {
                // Fixed value discount on item
                $itemDiscount = (float) ($item->item_discount ?? 0);
            }

            // Ensure discount doesn't exceed item subtotal
            $itemDiscount = min($itemDiscount, $itemSubtotal);

            // Effective price per unit = (subtotal - discount) / quantity
            $effectivePricePerUnit = ($itemSubtotal - $itemDiscount) / $item->quantity;

            return round($effectivePricePerUnit, 2);
        }

        // Apply order-level discount ratio if applicable
        if ($orderDiscountRatio > 0) {
            // Proportional discount per unit
            $discountPerUnit = $unitPrice * $orderDiscountRatio;
            $effectivePricePerUnit = $unitPrice - $discountPerUnit;

            return round($effectivePricePerUnit, 2);
        }

        // No discount, return original price
        return $unitPrice;
    }
}
