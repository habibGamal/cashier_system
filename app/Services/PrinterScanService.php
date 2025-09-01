<?php

namespace App\Services;

use Log;
use RuntimeException;
use Exception;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;

class PrinterScanService
{
    /**
     * Scan for available printers on the network
     */
    public function scanNetworkForPrinters(string $networkRange = '192.168.1.0/24'): array
    {
        $command = "nmap -sS -p 9100 {$networkRange} -T4 -n -Pn --max-retries 2 --min-rate 5000";

        Log::info("Scanning for printers with command: {$command}");

        try {
            $output = shell_exec($command . ' 2>&1');

            if ($output === null) {
                throw new RuntimeException('Failed to execute nmap command');
            }

            return $this->parseNmapOutput($output);
        } catch (Exception $e) {
            Log::error("Error scanning for printers: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Parse nmap output to extract printer IP addresses
     */
    private function parseNmapOutput(string $output): array
    {
        $printers = [];
        $lines = explode("\n", $output);
        $currentIp = null;
        $portOpen = false;

        foreach ($lines as $line) {
            $line = trim($line);

            // Look for IP address lines
            if (preg_match('/Nmap scan report for (\d+\.\d+\.\d+\.\d+)/', $line, $matches)) {
                // If we had a previous IP with open port, add it
                if ($currentIp && $portOpen) {
                    $printers[] = [
                        'ip' => $currentIp,
                        'status' => 'detected'
                    ];
                }

                $currentIp = $matches[1];
                $portOpen = false;
            }

            // Check if port 9100 is open or filtered (both indicate potential printer)
            if ($currentIp && preg_match('/9100\/tcp\s+(open|filtered)\s+/', $line)) {
                $portOpen = true;
            }
        }

        // Handle the last IP if it has an open port
        if ($currentIp && $portOpen) {
            $printers[] = [
                'ip' => $currentIp,
                'status' => 'detected'
            ];
        }

        Log::info("Found " . count($printers) . " potential printers", $printers);

        return $printers;
    }

    /**
     * Test a printer by sending a test message
     */
    public function testPrinter(string $ipAddress): array
    {
        try {
            $connector = new NetworkPrintConnector($ipAddress, 9100, 5); // 5 second timeout
            $printer = new Printer($connector);

            // Send test message
            $printer->text("=== اختبار الطابعة ===\n");
            $printer->text("عنوان IP: {$ipAddress}\n");
            $printer->text("التاريخ: " . now()->format('Y-m-d H:i:s') . "\n");
            $printer->text("هذه رسالة اختبار للطابعة\n");
            $printer->text("========================\n\n");
            $printer->cut();

            $printer->close();

            return [
                'success' => true,
                'message' => 'تم إرسال رسالة الاختبار بنجاح'
            ];
        } catch (Exception $e) {
            Log::error("Error testing printer {$ipAddress}: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'فشل في الاتصال بالطابعة: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if nmap is available on the system
     */
    public function isNmapAvailable(): bool
    {
        $output = shell_exec('nmap --version 2>&1');
        return $output !== null && strpos($output, 'Nmap version') !== false;
    }
}
