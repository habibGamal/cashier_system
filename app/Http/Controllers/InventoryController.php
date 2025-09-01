<?php

namespace App\Http\Controllers;

use Exception;
use App\Services\InventoryDailyAggregationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    protected InventoryDailyAggregationService $inventoryService;

    public function __construct(InventoryDailyAggregationService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get the current day status
     */
    public function dayStatus(): JsonResponse
    {
        $dayStatus = $this->inventoryService->dayStatus();

        return response()->json([
            'is_open' => $dayStatus !== null,
            'date' => $dayStatus?->toDateString(),
            'status' => $dayStatus ? 'مفتوح' : 'مغلق'
        ]);
    }

    /**
     * Toggle day status (open/close)
     */
    public function toggleDay(): JsonResponse
    {
        try {
            $currentStatus = $this->inventoryService->dayStatus();

            if ($currentStatus) {
                // Day is open, close it
                $closedCount = $this->inventoryService->closeDay();
                return response()->json([
                    'success' => true,
                    'action' => 'closed',
                    'message' => "تم إغلاق اليوم بنجاح. تم تحديث {$closedCount} منتج.",
                    'is_open' => false,
                    'status' => 'مغلق'
                ]);
            } else {
                // Day is closed, open it
                $openedCount = $this->inventoryService->openDay();
                return response()->json([
                    'success' => true,
                    'action' => 'opened',
                    'message' => "تم فتح اليوم بنجاح. تم إنشاء {$openedCount} سجل منتج.",
                    'is_open' => true,
                    'status' => 'مفتوح'
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }
}
