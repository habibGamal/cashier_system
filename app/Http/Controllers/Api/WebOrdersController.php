<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Http\Controllers\Controller;
use App\Services\WebApiService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class WebOrdersController extends Controller
{
    protected WebApiService $webApiService;

    public function __construct(WebApiService $webApiService)
    {
        $this->webApiService = $webApiService;
    }

    /**
     * Check if we can accept new orders
     */
    public function canAcceptOrder(): JsonResponse
    {
        $canAccept = $this->webApiService->canAcceptOrder();
        return response()->json(['can_accept' => $canAccept]);
    }

    /**
     * Get current shift ID
     */
    public function getShiftId(): JsonResponse
    {
        $shiftId = $this->webApiService->getShiftId();
        return response()->json(['shift_id' => $shiftId]);
    }

    /**
     * Create a new web order
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user' => 'required|array',
            'user.name' => 'required|string',
            'user.phone' => 'required|string',
            'user.area' => 'required|string',
            'user.address' => 'required|string',
            'order' => 'required|array',
            'order.type' => 'required|in:' . OrderType::WEB_DELIVERY->value . ',' . OrderType::WEB_TAKEAWAY->value,
            'order.shiftId' => 'required|integer',
            'order.orderNumber' => 'required|string',
            'order.subTotal' => 'required|numeric',
            'order.tax' => 'required|numeric',
            'order.service' => 'required|numeric',
            'order.discount' => 'required|numeric',
            'order.total' => 'required|numeric',
            'order.note' => 'nullable|string',
            'order.items' => 'required|array',
            'order.items.*.quantity' => 'required|numeric|min:0.001',
            'order.items.*.notes' => 'nullable|string',
            'order.items.*.posRefObj' => 'required|array',
            'order.items.*.posRefObj.*.productRef' => 'required|string',
            'order.items.*.posRefObj.*.quantity' => 'required|numeric|min:0.001',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $this->webApiService->placeOrder($request->all());

            return response()->json([
                'message' => 'تم إنشاء الطلب بنجاح',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'فشل في إنشاء الطلب',
                'error' => $e->getMessage(),
            ], 400);
        }
    }


}
