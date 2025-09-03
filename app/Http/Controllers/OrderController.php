<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Product;
use InvalidArgumentException;
use App\Enums\ProductType;
use App\Models\Order;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Region;
use App\Models\Payment;
use App\Models\OrderItem;
use App\Models\Expense;
use App\Models\ExpenceType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\DTOs\Orders\CreateOrderDTO;
use App\DTOs\Orders\PaymentDTO;
use App\Services\Orders\OrderService;
use App\Services\PrintService;
use App\Services\ShiftService;
use App\Services\ShiftLoggingService;
use App\Http\Requests\SaveOrderRequest;
use App\Http\Requests\CompleteOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class OrderController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private OrderService $orderService,
        private PrintService $printService,
        private ShiftService $shiftService,
        private ShiftLoggingService $loggingService
    ) {
    }

    /**
     * Display the orders index page
     */
    public function index()
    {
        $currentShift = $this->shiftService->getCurrentShift();

        if (!$currentShift) {
            return redirect()->route('shifts.start');
        }

        $orders = $this->orderService->getShiftOrders($currentShift->id);

        // Get current shift expenses
        $expenses = Expense::with('expenceType')
            ->where('shift_id', $currentShift->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get expense types
        $expenseTypes = ExpenceType::all();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'currentShift' => $currentShift,
            'expenses' => $expenses,
            'expenseTypes' => $expenseTypes,
        ]);
    }

    /**
     * Show start shift page
     */
    public function showStartShift()
    {
        if (!$this->shiftService->canStartShift()) {
            return redirect()->route('orders.index');
        }

        return Inertia::render('Shifts/StartShift');
    }

    /**
     * Start a new shift
     */
    public function startShift(Request $request)
    {
        $validated = $request->validate([
            'start_cash' => 'required|numeric|min:0',
        ]);
        try {

            if (!$this->shiftService->canStartShift()) {
                return redirect()->route('orders.index')->with('info', 'لديك وردية نشطة بالفعل');
            }

            $shift = $this->shiftService->startShift($validated['start_cash']);

            // Log shift start
            $this->loggingService->logShiftAction('start', [
                'id' => $shift->id,
                'start_cash' => $validated['start_cash'],
                'date' => $shift->created_at->format('Y-m-d H:i:s'),
            ]);

            return redirect()->route('orders.index')->with('success', 'تم بدء الوردية بنجاح');
        } catch (Exception $e) {
            $this->loggingService->logAction('فشل في بدء الوردية', [
                'error' => $e->getMessage(),
                'start_cash' => $validated['start_cash'],
            ], 'error');

            return back()->withErrors(['error' => 'حدث خطأ أثناء بدء الوردية: ' . $e->getMessage()]);
        }
    }


    /**
     * Display the order management interface
     */
    public function manage(Order $order)
    {
        $order = $this->orderService->getOrderDetails($order->id);

        $categories = Category::with([
            'products' => function ($query) {
                $query
                    ->whereNot('type', ProductType::RawMaterial)
                    ->where('legacy', false)->orderBy('name');
            }
        ])->orderBy('name')->get();

        // Get all drivers for the dropdown
        $drivers = Driver::orderBy('name')->get();

        // Get all regions for the dropdown
        $regions = Region::orderBy('name')->get();

        return Inertia::render('Orders/ManageOrder', [
            'order' => $order,
            'categories' => $categories,
            'drivers' => $drivers,
            'regions' => $regions,
        ]);
    }

    /**
     * Save order items
     */
    public function saveOrder(SaveOrderRequest $request, Order $order)
    {
        try {
            // Get current order items for comparison
            $oldItems = $order->items()->with('product')->get()->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'notes' => $item->notes,
                ];
            })->toArray();

            $itemsData = $request->validated('items') ?: [];

            // Add product names to new items for logging
            $newItems = collect($itemsData)->map(function ($item) {
                $product = Product::find($item['product_id']);
                return array_merge($item, [
                    'product_name' => $product?->name ?? "المنتج رقم {$item['product_id']}",
                ]);
            })->toArray();

            $this->orderService->updateOrderItems($order->id, $itemsData);

            // Log the changes
            $this->loggingService->logOrderSave($order->id, $oldItems, $newItems);

            return back()->with('success', 'تم حفظ الطلب بنجاح');
        } catch (Exception $e) {
            $this->loggingService->logAction('فشل في حفظ الطلب', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ], 'error');

            return back()->withErrors(['error' => 'حدث خطأ أثناء حفظ الطلب: ' . $e->getMessage()]);
        }
    }

    /**
     * Complete order with payment
     */
    public function completeOrder(CompleteOrderRequest $request, Order $order)
    {
        try {
            $validatedData = $request->validated();
            $shouldPrint = $request->boolean('print');

            // Remove print from payments data
            $paymentsData = collect($validatedData)->except('print')->toArray();

            $completedOrder = $this->orderService->completeOrder($order->id, $paymentsData, $shouldPrint);

            // Log order completion
            $this->loggingService->logOrderCompletion($order->id, $paymentsData, (float) $completedOrder->total);

            return redirect()->to(route('orders.index') .'#' . $order->type->value)
                ->with('success', 'تم إنهاء الطلب بنجاح');
        } catch (Exception $e) {
            $this->loggingService->logAction('فشل في إتمام الطلب', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'payment_data' => $request->validated(),
            ], 'error');

            return back()->withErrors(['error' => 'حدث خطأ أثناء إنهاء الطلب: ' . $e->getMessage()]);
        }
    }

    /**
     * Cancel a completed order (admin only)
     */
    public function cancelOrder(Order $order)
    {
        if (!auth()->user()->canCancelOrders()) {
            abort(403, 'غير مسموح لك بإلغاء الطلبات');
        }

        try {
            $this->orderService->cancelOrder($order->id);

            // Log order cancellation
            $this->loggingService->logOrderCancellation($order->id, 'إلغاء من قبل المدير');

            return back()->with('success', 'تم إلغاء الطلب بنجاح');
        } catch (Exception $e) {
            $this->loggingService->logAction('فشل في إلغاء الطلب', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ], 'error');

            return back()->withErrors(['error' => 'حدث خطأ أثناء إلغاء الطلب: ' . $e->getMessage()]);
        }
    }

    /**
     * Update customer information
     */
    public function updateCustomer(Request $request, Order $order)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'delivery_cost' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            if ($order->customer) {
                $order->customer->update($validated);
                $customer = $order->customer;
                $actionType = 'update';
            } else {
                $customer = Customer::create($validated);
                $this->orderService->linkCustomer($order->id, $customer->id);
                $actionType = 'create';
            }

            // Log customer action
            $this->loggingService->logCustomerAction($actionType, $validated, $order->id);

            DB::commit();

            return back()->with('success', 'تم حفظ بيانات العميل بنجاح');
        } catch (Exception $e) {
            DB::rollBack();

            $this->loggingService->logAction('فشل في حفظ بيانات العميل', [
                'order_id' => $order->id,
                'customer_data' => $validated,
                'error' => $e->getMessage(),
            ], 'error');

            return back()->withErrors(['error' => 'حدث خطأ أثناء حفظ بيانات العميل']);
        }
    }

    /**
     * Update driver information
     */
    public function updateDriver(Request $request, Order $order)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        try {
            DB::beginTransaction();

            if ($order->driver) {
                $order->driver->update($validated);
                $driver = $order->driver;
                $actionType = 'update';
            } else {
                $driver = Driver::create($validated);
                $this->orderService->linkDriver($order->id, $driver->id);
                $actionType = 'create';
            }

            // Log driver action
            $this->loggingService->logDriverAction($actionType, $validated, $order->id);

            DB::commit();

            return back()->with('success', 'تم حفظ بيانات السائق بنجاح');
        } catch (Exception $e) {
            DB::rollBack();

            $this->loggingService->logAction('فشل في حفظ بيانات السائق', [
                'order_id' => $order->id,
                'driver_data' => $validated,
                'error' => $e->getMessage(),
            ], 'error');

            return back()->withErrors(['error' => 'حدث خطأ أثناء حفظ بيانات السائق']);
        }
    }

    /**
     * Quick create customer
     */
    public function quickCustomer(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'hasWhatsapp' => 'nullable|string|in:0,1',
            'region' => 'nullable|string|max:255',
            'deliveryCost' => 'nullable|numeric|min:0',
        ]);

        try {
            $customer = Customer::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'address' => $validated['address'] ?? null,
                'has_whatsapp' => $validated['hasWhatsapp'] === '1',
                'region' => $validated['region'] ?? null,
                'delivery_cost' => $validated['deliveryCost'] ?? null,
            ]);

            // Log customer creation
            $this->loggingService->logCustomerAction('create', [
                'name' => $customer->name,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'delivery_cost' => $customer->delivery_cost,
            ]);

            return response()->json($customer);
        } catch (Exception $e) {
            $this->loggingService->logAction('فشل في إنشاء عميل جديد', [
                'customer_data' => $validated,
                'error' => $e->getMessage(),
            ], 'error');

            return response()->json(['error' => 'حدث خطأ أثناء إنشاء العميل'], 500);
        }
    }

    /**
     * Quick create driver
     */
    public function quickDriver(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        try {
            $driver = Driver::create($validated);

            // Log driver creation
            $this->loggingService->logDriverAction('create', [
                'name' => $driver->name,
                'phone' => $driver->phone,
            ]);

            return response()->json($driver);
        } catch (Exception $e) {
            $this->loggingService->logAction('فشل في إنشاء سائق جديد', [
                'driver_data' => $validated,
                'error' => $e->getMessage(),
            ], 'error');

            return response()->json(['error' => 'حدث خطأ أثناء إنشاء السائق'], 500);
        }
    }

    /**
     * Fetch customer info by phone
     */
    public function fetchCustomerInfo(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        try {
            $customer = Customer::where('phone', $validated['phone'])->first();

            if (!$customer) {
                return response()->json(['error' => 'لم يتم العثور على العميل'], 404);
            }

            return response()->json($customer);
        } catch (Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء البحث عن العميل'], 500);
        }
    }

    /**
     * Fetch driver info by phone
     */
    public function fetchDriverInfo(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        try {
            $driver = Driver::where('phone', $validated['phone'])->first();

            if (!$driver) {
                return response()->json(['error' => 'لم يتم العثور على السائق'], 404);
            }

            return response()->json($driver);
        } catch (Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء البحث عن السائق'], 500);
        }
    }

    /**
     * Link customer to order
     */
    public function linkCustomer(Request $request, Order $order)
    {
        $validated = $request->validate([
            'customerId' => 'required|integer|exists:customers,id',
        ]);

        try {
            $customer = Customer::find($validated['customerId']);
            $this->orderService->linkCustomer($order->id, $validated['customerId']);

            // Log customer linking
            $this->loggingService->logCustomerAction('link', [
                'name' => $customer->name,
                'phone' => $customer->phone,
                'address' => $customer->address,
            ], $order->id);

            // Return in Inertia page props format
            $updatedOrder = $this->orderService->getOrderDetails($order->id);

            return back()->with([
                'success' => 'تم ربط العميل بالطلب بنجاح',
                'order' => $updatedOrder
            ]);
        } catch (Exception $e) {
            $this->loggingService->logAction('فشل في ربط العميل بالطلب', [
                'order_id' => $order->id,
                'customer_id' => $validated['customerId'],
                'error' => $e->getMessage(),
            ], 'error');

            return back()->withErrors(['error' => 'حدث خطأ أثناء ربط العميل']);
        }
    }

    /**
     * Link driver to order
     */
    public function linkDriver(Request $request, Order $order)
    {
        $validated = $request->validate([
            'driverId' => 'required|integer|exists:drivers,id',
        ]);

        try {
            $driver = Driver::find($validated['driverId']);
            $this->orderService->linkDriver($order->id, $validated['driverId']);

            // Log driver linking
            $this->loggingService->logDriverAction('link', [
                'name' => $driver->name,
                'phone' => $driver->phone,
            ], $order->id);

            // Return in Inertia page props format
            $updatedOrder = $this->orderService->getOrderDetails($order->id);

            return back()->with([
                'success' => 'تم ربط السائق بالطلب بنجاح',
                'order' => $updatedOrder
            ]);
        } catch (Exception $e) {
            $this->loggingService->logAction('فشل في ربط السائق بالطلب', [
                'order_id' => $order->id,
                'driver_id' => $validated['driverId'],
                'error' => $e->getMessage(),
            ], 'error');

            return back()->withErrors(['error' => 'حدث خطأ أثناء ربط السائق']);
        }
    }

    /**
     * Update order type
     */
    public function updateOrderType(Request $request, Order $order)
    {
        $validated = $request->validate([
            'type' => 'required|in:takeaway,delivery,web_delivery,web_takeaway',
        ]);

        try {
            $this->orderService->changeOrderType(
                $order->id,
                $validated['type']
            );

            return back()->with('success', 'تم تغيير نوع الطلب بنجاح');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'حدث خطأ أثناء تغيير نوع الطلب: ' . $e->getMessage()]);
        }
    }

    /**
     * Update order notes
     */
    public function updateOrderNotes(Request $request, Order $order)
    {
        $validated = $request->validate([
            'order_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->orderService->updateNotes($order->id, $validated['order_notes']);

            return back()->with('success', 'تم حفظ ملاحظات الطلب بنجاح');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'حدث خطأ أثناء حفظ ملاحظات الطلب: ' . $e->getMessage()]);
        }
    }

    /**
     * Apply discount to order
     */
    public function applyDiscount(Request $request, Order $order)
    {
        if (!auth()->user()->canApplyDiscounts()) {
            abort(403, 'غير مسموح لك بتطبيق خصم');
        }

        $validated = $request->validate([
            'discount' => 'required|numeric|min:0',
            'discount_type' => 'required|in:percent,value',
        ]);

        try {
            $this->orderService->applyDiscount($order->id, $validated['discount'], $validated['discount_type']);

            // Log discount application
            $this->loggingService->logDiscountApplication($order->id, $validated['discount'], $validated['discount_type']);

            return back()->with('success', 'تم تطبيق الخصم بنجاح');
        } catch (Exception $e) {
            $this->loggingService->logAction('فشل في تطبيق الخصم', [
                'order_id' => $order->id,
                'discount' => $validated['discount'],
                'discount_type' => $validated['discount_type'],
                'error' => $e->getMessage(),
            ], 'error');

            return back()->withErrors(['error' => 'حدث خطأ أثناء تطبيق الخصم: ' . $e->getMessage()]);
        }
    }

    /**
     * Print order receipt
     */
    public function printReceipt(Request $request, Order $order)
    {
        $order = $this->orderService->getOrderDetails($order->id);

        try {
            $this->printService->printOrderReceipt($order, $request->input('images'));

            return back()->with('success', 'تم إرسال طباعة الطلب بنجاح');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'حدث خطأ أثناء طباعة طلب ']);
        }

    }

    /**
     * Get printers for products
     */
    public function getPrintersOfProducts(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:products,id',
        ]);

        try {
            $products = Product::with('printers:id')
                ->whereIn('id', $validated['ids'])
                ->get(['id']);

            $result = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'printers' => $product->printers->map(function ($printer) {
                        return ['id' => $printer->id];
                    })->toArray(),
                ];
            });

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء جلب بيانات الطابعات'], 500);
        }
    }

    /**
     * Open the cashier drawer
     */
    public function openCashierDrawer()
    {
        try {
            $this->printService->openCashierDrawer();

            return back()->with('success', 'تم فتح درج الكاشير بنجاح');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'حدث خطأ أثناء فتح درج الكاشير: ' . $e->getMessage()]);
        }
    }


    /**
     * Create a new order
     */
    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:' . implode(',', array_column(OrderType::cases(), 'value')),
        ]);

        try {
            $currentShift = $this->shiftService->getCurrentShift();

            if (!$currentShift) {
                return back()->withErrors(['error' => 'لا يوجد شيفت نشط']);
            }

            // Map order type to enum
            $orderType = match ($validated['type']) {
                'takeaway' => OrderType::TAKEAWAY,
                'delivery' => OrderType::DELIVERY,
                'web_delivery' => OrderType::WEB_DELIVERY,
                'web_takeaway' => OrderType::WEB_TAKEAWAY,
                default => throw new InvalidArgumentException('Invalid order type'),
            };

            $createOrderDTO = new CreateOrderDTO(
                type: $orderType,
                shiftId: $currentShift->id,
                userId: auth()->id(),
            );

            $order = $this->orderService->createOrder($createOrderDTO);

            // Log order creation
            $this->loggingService->logOrderCreation(
                $order->id,
                $validated['type']
            );

            return redirect()->route('orders.manage', $order);
        } catch (Exception $e) {
            logger()->error('Error creating order: ' . $e->getMessage());
            return back()->withErrors(['error' => 'حدث خطأ أثناء إنشاء الطلب: ' . $e->getMessage()]);
        }
    }

    /**
     * End current shift
     */
    public function endShift(Request $request)
    {
        if (!auth()->user()->canManageOrders()) {
            abort(403, 'غير مسموح لك بإنهاء الشيفت');
        }

        $validated = $request->validate([
            'real_end_cash' => 'required|numeric|min:0',
        ]);

        try {
            if (!$this->shiftService->canEndShift()) {
                return back()->withErrors(['error' => 'لا يوجد شيفت نشط']);
            }

            $currentShift = $this->shiftService->getCurrentShift();
            $endedShift = $this->shiftService->endShift($validated['real_end_cash']);

            // Log shift end
            $this->loggingService->logShiftAction('end', [
                'id' => $endedShift->id,
                'start_cash' => $endedShift->start_cash,
                'end_cash' => $endedShift->end_cash,
                'real_end_cash' => $validated['real_end_cash'],
                'date' => $endedShift->updated_at->format('Y-m-d H:i:s'),
            ]);

            return redirect()->route('shifts.start')->with('success', 'تم إنهاء الشيفت بنجاح');
        } catch (Exception $e) {
            $this->loggingService->logAction('فشل في إنهاء الوردية', [
                'real_end_cash' => $validated['real_end_cash'],
                'error' => $e->getMessage(),
            ], 'error');

            return back()->withErrors(['error' => 'حدث خطأ أثناء إنهاء الشيفت: ' . $e->getMessage()]);
        }
    }

}
