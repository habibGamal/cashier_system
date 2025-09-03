<?php

namespace App\Services;

use Log;
use Exception;
use App\Models\Product;
use App\Enums\OrderType;
use App\Models\Order;
use App\Enums\SettingKey;
use App\Jobs\PrintOrderReceipt;
use Illuminate\Support\Facades\Storage;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use Spatie\Browsershot\Browsershot;

class PrintService
{
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
    public function printOrderReceipt(Order $order, array $images): void
    {
        PrintOrderReceipt::dispatch($order);
    }

    /**
     * Print kitchen receipt - Disabled (kitchen functionality removed)
     */
    public function printKitchenReceipt($orderId, $items): void
    {
        // Kitchen printing functionality has been removed
        Log::info("Kitchen printing is disabled for order {$orderId}");
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

            Log::info("Opening cashier drawer");

            // Send pulse to open the drawer
            $printer->pulse();

        } catch (Exception $e) {
            Log::error("Error opening cashier drawer: " . $e->getMessage());
            throw $e;
        } finally {
            if (isset($printer)) {
                $printer->close();
            }
        }
    }

    /**
     * Prepare and validate kitchen items data (deprecated)
     */
    private function prepareKitchenItems(array $items): array
    {
        $preparedItems = [];

        foreach ($items as $item) {
            // Validate required fields
            if (!isset($item['product_id']) || !isset($item['quantity'])) {
                Log::warning('Invalid item data: missing product_id or quantity', $item);
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
                $product = Product::find($item['product_id']);
                $preparedItem['name'] = $product ? $product->name : "المنتج رقم {$item['product_id']}";
            }

            $preparedItems[] = $preparedItem;
        }

        return $preparedItems;
    }

    /**
     * Print kitchen order to a specific printer
     */
    public function printKitchenToPrinter(Order $order, array $orderItems, int $printerId): void
    {
        try {
            $printer = \App\Models\Printer::findOrFail($printerId);

            if (!$printer->ip_address) {
                Log::warning("Printer {$printer->name} has no IP address configured");
                return;
            }
            Log::info("Printing kitchen order to printer {$printer->name} ({$printer->ip_address})");

            // ---------- 1. Generate HTML content using kitchen template ----------
            $html = $this->generateKitchenHtml($order, $orderItems);
            // ---------- 2. Convert HTML to image using Browsershot ----------
            $tempImagePath = tempnam(sys_get_temp_dir(), 'kitchen_browsershot_') . '.png';


            Browsershot::html($html)
                ->windowSize(572, 100) // Kitchen receipt width
                ->setOption('executablePath', '/usr/bin/chromium-browser')
                ->setEnvironmentOptions([
                    'XDG_CONFIG_HOME' => base_path('.puppeteer'), // custom cache dir
                    'HOME' => base_path('.puppeteer')             // fallback
                ])
                ->setRemoteInstance('127.0.0.1', 9222)
                ->dismissDialogs()
                ->ignoreHttpsErrors()
                ->fullPage()
                ->save($tempImagePath);
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

            Log::info("Kitchen order printed successfully to printer {$printer->name}");

        } catch (Exception $e) {
            Log::error("Error printing kitchen order to printer {$printerId}: " . $e->getMessage());
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

            Log::info("Testing cashier printer connection");

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

            Log::info("Test print sent successfully to cashier printer");

        } catch (Exception $e) {
            Log::error("Error testing cashier printer: " . $e->getMessage());
            throw $e;
        } finally {
            if (isset($printer)) {
                $printer->close();
            }
        }
    }

    /**
     * Test direct Arabic text printing using Browsershot and escpos-php
     * Generates Arabic receipt HTML then converts to image
     */
    public function printOrderViaBrowsershot(Order $order): void
    {
        try {

            $printerIp = setting(SettingKey::CASHIER_PRINTER_IP);
            $connector = $this->createConnector($printerIp);
            $printer = new Printer($connector);

            Log::info("Testing Browsershot Arabic text printing for order {$order->id}");

            // Load order with relationships
            $order->load(['user', 'customer', 'driver', 'items.product']);


            // ---------- 1. Generate HTML content using generateReceiptHtml method ----------
            $html = $this->generateReceiptHtml($order);

            // ---------- 2. Convert HTML to image using Browsershot ----------
            $tempImagePath = tempnam(sys_get_temp_dir(), 'receipt_browsershot_') . '.png';
            $start = microtime(true);
            Browsershot::html($html)
                ->windowSize(567, 1200) // Thermal printer width (72mm ≈ 576px at 203dpi)
                // ->setOption('executablePath', '/usr/bin/chromium-browser')
                // ->setEnvironmentOptions([
                //     'XDG_CONFIG_HOME' => base_path('.puppeteer'), // custom cache dir
                //     'HOME' => base_path('.puppeteer')             // fallback
                // ])
                ->setRemoteInstance('127.0.0.1', 9222) // Use remote instance for Puppeteer
                ->dismissDialogs()
                ->ignoreHttpsErrors()
                ->fullPage()
                ->save($tempImagePath);
            $end = microtime(true);
            Log::info("Browsershot processing time: " . ($end - $start) . " seconds");
            // ---------- 3. Print via escpos-php ----------
            $printer->setJustification(Printer::JUSTIFY_CENTER);

            // Load and print image
            $escposImage = EscposImage::load($tempImagePath);
            $printer->bitImage($escposImage);
            $printer->feed(3);
            $printer->cut();

            // Clean up
            unlink($tempImagePath);

            Log::info("Browsershot Arabic text printing completed successfully for order {$order->id}");

        } catch (Exception $e) {
            Log::error("Error in Browsershot Arabic text printing for order {$order->id}: " . $e->getMessage());
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


}
