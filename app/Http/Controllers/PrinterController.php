<?php

namespace App\Http\Controllers;

use Exception;
use App\Services\PrinterScanService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PrinterController extends Controller
{
    public function __construct(
        private PrinterScanService $printerScanService
    ) {}

    /**
     * Test a printer connection
     */
    public function testPrinter(Request $request): JsonResponse
    {
        $request->validate([
            'ip_address' => 'required|string|max:255'
        ]);

        try {
            $result = $this->printerScanService->testPrinter($request->ip_address);

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء اختبار الطابعة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Scan network for printers
     */
    public function scanNetwork(Request $request): JsonResponse
    {
        $request->validate([
            'network_range' => 'sometimes|string|max:255'
        ]);

        try {
            $networkRange = $request->input('network_range', '192.168.1.0/24');
            $printers = $this->printerScanService->scanNetworkForPrinters($networkRange);

            return response()->json([
                'success' => true,
                'printers' => $printers
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء البحث عن الطابعات: ' . $e->getMessage()
            ], 500);
        }
    }
}
