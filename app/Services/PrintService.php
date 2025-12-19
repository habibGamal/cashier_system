<?php

namespace App\Services;

use App\Enums\OrderType;
use App\Models\Order;
use App\Enums\SettingKey;
use App\Jobs\PrintKitchenOrder;
use App\Jobs\PrintOrderReceipt;
use App\Services\PrintStrategies\PrintStrategyInterface;
use App\Services\PrintStrategies\PrintStrategyFactory;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

class PrintService
{
    private const USE_QUEUE = false;
    private PrintStrategyInterface $printStrategy;

    public function __construct()
    {
        $this->printStrategy = $this->createPrintStrategy();
    }

    /**
     * Create the appropriate print strategy
     * You can modify this method to switch between strategies programmatically
     */
    private function createPrintStrategy(): PrintStrategyInterface
    {
        // Use factory to get the best available strategy
        $strategy = PrintStrategyFactory::create('wkhtmltoimage');
        \Log::info("Using print strategy: " . $strategy->getName());
        return $strategy;
    }
    /**
     * Create appropriate print connector based on printer IP format
     */
    private function createConnector(string $printerIp): NetworkPrintConnector|WindowsPrintConnector
    {
        // Check if it's a UNC path (\\hostname\printername or \\ip\sharename)
        if (preg_match('/^\\\\\\\\[^\\\\]+\\\\[^\\\\]+/', $printerIp)) {
            return new WindowsPrintConnector($printerIp);
        }

        // Check if it's just a printer name (e.g., share_cash)
        if (!filter_var($printerIp, FILTER_VALIDATE_IP)) {
            return new WindowsPrintConnector($printerIp);
        }

        // Check if it's an IP address format (xxx.xxx.xxx.xxx)
        if (filter_var($printerIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return new NetworkPrintConnector($printerIp, 9100);
        }

        // Default to network connector if format is unclear
        return new NetworkPrintConnector($printerIp, 9100);
    }

    /**
     * Print order receipt
     */
    public function printOrderReceipt(Order $order): void
    {
        if (self::USE_QUEUE) {
            \Log::info("Dispatching order receipt to queue for order {$order->id}");
            PrintOrderReceipt::dispatch($order);
        } else {
            \Log::info("Printing order receipt directly for order {$order->id}");
            $this->printOrderProcess($order);
        }
    }

    /**
     * Print kitchen receipt
     */
    public function printKitchenReceipt($orderId, $items): void
    {
        if (self::USE_QUEUE) {
            \Log::info("Dispatching kitchen receipt to queue for order {$orderId}");
            $this->printKitchenQueued($orderId, $items);
        } else {
            \Log::info("Printing kitchen receipt directly for order {$orderId}");
            $this->printKitchenDirect($orderId, $items);
        }
    }

    /**
     * Open the cashier drawer
     */
    public function openCashierDrawer(): void
    {
        try {
            $printerIp = setting(SettingKey::CASHIER_PRINTER_IP);
            $connector = $this->createConnector($printerIp);
            $printer = new Printer($connector);

            \Log::info("Opening cashier drawer");

            // Send pulse to open the drawer
            $printer->pulse();

        } catch (\Exception $e) {
            \Log::error("Error opening cashier drawer: " . $e->getMessage());
            throw $e;
        } finally {
            if (isset($printer)) {
                $printer->close();
            }
        }
    }

    /**
     * Print kitchen order directly (synchronous)
     */
    private function printKitchenDirect($orderId, $items): void
    {
        try {
            \Log::info("Starting direct kitchen printing for order {$orderId}");

            // Load order with relationships
            $order = Order::with(['user', 'customer', 'driver', 'table'])->findOrFail($orderId);

            // Validate and prepare items data
            $preparedItems = $this->prepareKitchenItems($items);

            if (empty($preparedItems)) {
                throw new \Exception('لا توجد منتجات للطباعة');
            }

            // Get product IDs from items to find their printers
            $productIds = collect($preparedItems)->pluck('product_id')->unique()->values()->toArray();

            // Get products with their printers
            $products = \App\Models\Product::with('printers:id')
                ->whereIn('id', $productIds)
                ->get(['id']);

            // Map items to printers
            $itemsByPrinterMap = [];

            foreach ($preparedItems as $item) {
                $product = $products->firstWhere('id', $item['product_id']);

                if ($product && $product->printers->isNotEmpty()) {
                    foreach ($product->printers as $printer) {
                        if (!isset($itemsByPrinterMap[$printer->id])) {
                            $itemsByPrinterMap[$printer->id] = [];
                        }

                        // Add item to this printer's list
                        $itemsByPrinterMap[$printer->id][] = $item;
                    }
                }
            }

            // Print directly to each printer
            foreach ($itemsByPrinterMap as $printerId => $printerItems) {
                $this->printKitchenProcess($order, $printerItems, $printerId);
            }

            if (empty($itemsByPrinterMap)) {
                \Log::warning("No printers found for order {$orderId} items");
                throw new \Exception('لا توجد طابعات مخصصة للمنتجات المحددة');
            }

            \Log::info("Kitchen printing completed directly for order {$orderId}");

        } catch (\Exception $e) {
            \Log::error("Error in direct kitchen printing for order {$orderId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Print kitchen order via queue (asynchronous)
     */
    private function printKitchenQueued($orderId, $items): void
    {
        try {
            \Log::info("Starting kitchen printing via queue for order {$orderId}");

            // Load order with relationships
            $order = Order::with(['user', 'customer', 'driver', 'table'])->findOrFail($orderId);

            // Validate and prepare items data
            $preparedItems = $this->prepareKitchenItems($items);

            if (empty($preparedItems)) {
                throw new \Exception('لا توجد منتجات للطباعة');
            }

            // Get product IDs from items to find their printers
            $productIds = collect($preparedItems)->pluck('product_id')->unique()->values()->toArray();

            // Get products with their printers
            $products = \App\Models\Product::with('printers:id')
                ->whereIn('id', $productIds)
                ->get(['id']);

            // Map items to printers
            $itemsByPrinterMap = [];

            foreach ($preparedItems as $item) {
                $product = $products->firstWhere('id', $item['product_id']);

                if ($product && $product->printers->isNotEmpty()) {
                    foreach ($product->printers as $printer) {
                        if (!isset($itemsByPrinterMap[$printer->id])) {
                            $itemsByPrinterMap[$printer->id] = [];
                        }

                        // Add item to this printer's list
                        $itemsByPrinterMap[$printer->id][] = $item;
                    }
                }
            }

            // Dispatch print jobs to queue for each printer
            foreach ($itemsByPrinterMap as $printerId => $printerItems) {
                PrintKitchenOrder::dispatch($order, $printerItems, $printerId);
            }

            if (empty($itemsByPrinterMap)) {
                \Log::warning("No printers found for order {$orderId} items");
                throw new \Exception('لا توجد طابعات مخصصة للمنتجات المحددة');
            }

            \Log::info("Kitchen printing jobs dispatched successfully for order {$orderId}");

        } catch (\Exception $e) {
            \Log::error("Error dispatching kitchen printing jobs for order {$orderId}: " . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Prepare and validate kitchen items data
     */
    private function prepareKitchenItems(array $items): array
    {
        $preparedItems = [];

        foreach ($items as $item) {
            // Validate required fields
            if (!isset($item['product_id']) || !isset($item['quantity'])) {
                \Log::warning('Invalid item data: missing product_id or quantity', $item);
                continue;
            }

            $preparedItem = [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
                'notes' => $item['notes'] ?? null,
            ];

            // Get product name if not provided
            if (isset($item['name'])) {
                $preparedItem['name'] = $item['name'];
            } else {
                $product = \App\Models\Product::find($item['product_id']);
                $preparedItem['name'] = $product ? $product->name : "المنتج رقم {$item['product_id']}";
            }

            $preparedItems[] = $preparedItem;
        }

        return $preparedItems;
    }

    /**
     * Print kitchen order to a specific printer
     */
    public function printKitchenProcess(Order $order, array $orderItems, int $printerId): void
    {
        try {
            $printer = \App\Models\Printer::findOrFail($printerId);

            if (!$printer->ip_address) {
                \Log::warning("Printer {$printer->name} has no IP address configured");
                return;
            }
            \Log::info("Printing kitchen order to printer {$printer->name} ({$printer->ip_address}) using {$this->printStrategy->getName()}");

            // ---------- 1. Generate HTML content using kitchen template ----------
            $html = $this->generateKitchenHtml($order, $orderItems);

            // ---------- 2. Convert HTML to image using current strategy ----------
            $tempImagePath = $this->printStrategy->generateImageFromHtml($html, 572, 100);

            // ---------- 3. Print via escpos-php ----------
            $connector = $this->createConnector($printer->ip_address);
            $escposPrinter = new Printer($connector);
            $escposPrinter->setJustification(Printer::JUSTIFY_CENTER);

            // Load and print image
            $escposImage = EscposImage::load($tempImagePath);
            $escposPrinter->bitImage($escposImage);
            $escposPrinter->feed(3);
            $escposPrinter->cut();

            // Clean up
            unlink($tempImagePath);
            $escposPrinter->close();

            \Log::info("Kitchen order printed successfully to printer {$printer->name}");

        } catch (\Exception $e) {
            \Log::error("Error printing kitchen order to printer {$printerId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate HTML content for kitchen receipt printing
     */
    private function generateKitchenHtml(Order $order, array $orderItems): string
    {
        // Use Blade view to render the kitchen receipt
        return view('print.kitchen-template', [
            'order' => $order,
            'orderItems' => $orderItems,
        ])->render();
    }

    /**
     * Test cashier printer connection with sample text
     */
    public function testCashierPrinter(): void
    {
        try {
            $printerIp = setting(SettingKey::CASHIER_PRINTER_IP);
            $connector = $this->createConnector($printerIp);
            $printer = new Printer($connector);

            \Log::info("Testing cashier printer connection");

            // Print test text
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
            $printer->text("Printer Test\n");
            $printer->selectPrintMode();
            $printer->text("------------------------\n");
            $printer->text("Date: " . now()->format('Y-m-d H:i:s') . "\n");
            $printer->text("IP: {$printerIp}\n");
            $printer->text("------------------------\n");
            $printer->text("If you see this text, the printer is working correctly\n");
            $printer->feed(3);
            $printer->cut();

            \Log::info("Test print sent successfully to cashier printer");

        } catch (\Exception $e) {
            \Log::error("Error testing cashier printer: " . $e->getMessage());
            throw $e;
        } finally {
            if (isset($printer)) {
                $printer->close();
            }
        }
    }

    /**
     * Print order receipt using the current strategy
     */
    public function printOrderProcess(Order $order): void
    {
        try {

            $printerIp = setting(SettingKey::CASHIER_PRINTER_IP);
            $connector = $this->createConnector($printerIp);
            $printer = new Printer($connector);

            \Log::info("Printing order receipt for order {$order->id} using {$this->printStrategy->getName()}");

            // Load order with relationships
            $order->load(['user', 'customer', 'driver', 'items.product']);

            $itemCount = $order->items->count();

            // If order has 5 or fewer items, use existing single-image logic
            if ($itemCount <= 5) {
                \Log::info("Order {$order->id} has {$itemCount} items, using single receipt printing");

                // ---------- 1. Generate HTML content using generateReceiptHtml method ----------
                $html = $this->generateReceiptHtml($order);

                // ---------- 2. Convert HTML to image using current strategy ----------
                $tempImagePath = $this->printStrategy->generateImageFromHtml($html, 567, 1200);

                // ---------- 3. Print via escpos-php ----------
                $printer->setJustification(Printer::JUSTIFY_CENTER);

                // Load and print image
                $escposImage = EscposImage::load($tempImagePath);
                $printer->bitImage($escposImage);
                $printer->feed(3);
                $printer->cut();

                // Clean up
                unlink($tempImagePath);

            } else {
                // Split printing for orders with more than 5 items
                \Log::info("Order {$order->id} has {$itemCount} items, using split receipt printing");

                $printer->setJustification(Printer::JUSTIFY_CENTER);

                // ---------- 1. Print header with first 5 items ----------
                $headerItems = $order->items->take(5);
                $html = $this->generateReceiptHeaderHtml($order, $headerItems);
                $tempImagePath = $this->printStrategy->generateImageFromHtml($html, 567, 1200);
                $escposImage = EscposImage::load($tempImagePath);
                $printer->bitImage($escposImage);
                unlink($tempImagePath);
                \Log::info("Printed header with first 5 items for order {$order->id}");

                // ---------- 2. Print remaining items in chunks of 5 ----------
                $remainingItems = $order->items->skip(5);
                $chunks = $remainingItems->chunk(5);

                foreach ($chunks as $index => $chunk) {
                    $html = $this->generateReceiptItemsHtml($chunk);
                    $tempImagePath = $this->printStrategy->generateImageFromHtml($html, 567, 1200);
                    $escposImage = EscposImage::load($tempImagePath);
                    $printer->bitImage($escposImage);
                    unlink($tempImagePath);
                    \Log::info("Printed items chunk " . ($index + 1) . " for order {$order->id}");
                }

                // ---------- 3. Print footer with totals ----------
                $html = $this->generateReceiptFooterHtml($order);
                $tempImagePath = $this->printStrategy->generateImageFromHtml($html, 567, 1200);
                $escposImage = EscposImage::load($tempImagePath);
                $printer->bitImage($escposImage);
                $printer->feed(3);
                $printer->cut();
                unlink($tempImagePath);
                \Log::info("Printed footer for order {$order->id}");
            }

            \Log::info("Order receipt printing completed successfully for order {$order->id}");

        } catch (\Exception $e) {
            \Log::error("Error printing order receipt for order {$order->id}: " . $e->getMessage(), [
                $e->getTrace()
            ]);
            throw $e;
        } finally {
            if (isset($printer)) {
                $printer->close();
            }
        }
    }

    /**
     * Generate HTML content for receipt printing
     */
    private function generateReceiptHtml(Order $order): string
    {
        // Use Blade view to render the receipt with minimal data
        return view('print.receipt-template', [
            'order' => $order,
        ])->render();
    }

    /**
     * Set the print strategy programmatically by name
     */
    public function setPrintStrategyByName(string $strategyName): void
    {
        $this->printStrategy = PrintStrategyFactory::create($strategyName);
        \Log::info("Print strategy changed to: " . $this->printStrategy->getName());
    }

    /**
     * Set the print strategy programmatically
     */
    public function setPrintStrategy(PrintStrategyInterface $strategy): void
    {
        $this->printStrategy = $strategy;
        \Log::info("Print strategy changed to: " . $strategy->getName());
    }

    /**
     * Get the current print strategy
     */
    public function getPrintStrategy(): PrintStrategyInterface
    {
        return $this->printStrategy;
    }

    /**
     * Get all available print strategies
     */
    public function getAvailableStrategies(): array
    {
        return PrintStrategyFactory::getAvailableStrategies();
    }

    /**
     * Check if a specific strategy is available
     */
    public function isStrategyAvailable(string $strategyName): bool
    {
        try {
            $strategy = PrintStrategyFactory::create($strategyName);
            return $strategy->isAvailable();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the current queue usage setting
     */
    public function isUsingQueue(): bool
    {
        return self::USE_QUEUE;
    }

    /**
     * Generate HTML content for receipt header with specified items
     */
    private function generateReceiptHeaderHtml(Order $order, $items): string
    {
        return view('print.receipt-header', [
            'order' => $order,
            'items' => $items,
        ])->render();
    }

    /**
     * Generate HTML content for receipt items continuation
     */
    private function generateReceiptItemsHtml($items): string
    {
        return view('print.receipt-items', [
            'items' => $items,
        ])->render();
    }

    /**
     * Generate HTML content for receipt footer
     */
    private function generateReceiptFooterHtml(Order $order): string
    {
        return view('print.receipt-footer', [
            'order' => $order,
        ])->render();
    }

    /**
     * Get queue and strategy status information
     */
    public function getStatusInfo(): array
    {
        return [
            'using_queue' => self::USE_QUEUE,
            'current_strategy' => $this->printStrategy->getName(),
            'strategy_available' => $this->printStrategy->isAvailable(),
            'available_strategies' => array_keys($this->getAvailableStrategies()),
        ];
    }
}
