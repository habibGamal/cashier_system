<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WebOrderController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InvoicePrintController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->to('/admin');
});

Route::get('/dashboard', function () {
    return redirect()->to('/admin');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Shift Management Routes
    Route::prefix('shifts')->name('shifts.')->group(function () {
        Route::get('/start', [OrderController::class, 'showStartShift'])->name('start');
        Route::post('/start', [OrderController::class, 'startShift'])->name('store');
        Route::post('/end', [OrderController::class, 'endShift'])->name('end');
    });

    // Order Management Routes
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/manage/{order}', [OrderController::class, 'manage'])->name('manage');
        Route::post('/create', [OrderController::class, 'createOrder'])->name('store');
        Route::post('/save-order/{order}', [OrderController::class, 'saveOrder'])->name('save');
        Route::post('/complete-order/{order}', [OrderController::class, 'completeOrder'])->name('complete');
        Route::post('/cancel-order/{order}', [OrderController::class, 'cancelOrder'])->name('cancel');
        Route::post('/update-customer/{order}', [OrderController::class, 'updateCustomer'])->name('updateCustomer');
        Route::post('/update-driver/{order}', [OrderController::class, 'updateDriver'])->name('updateDriver');
        Route::post('/update-type/{order}', [OrderController::class, 'updateOrderType'])->name('updateType');
        Route::post('/update-notes/{order}', [OrderController::class, 'updateOrderNotes'])->name('updateNotes');
        Route::post('/apply-discount/{order}', [OrderController::class, 'applyDiscount'])->name('applyDiscount');
        Route::post('/link-customer/{order}', [OrderController::class, 'linkCustomer'])->name('linkCustomer');
        Route::post('/link-driver/{order}', [OrderController::class, 'linkDriver'])->name('linkDriver');
    })->middleware(['shift']);

    // Quick operations routes
    Route::post('/quick-customer', [OrderController::class, 'quickCustomer'])->name('quickCustomer')->middleware(['shift']);
    Route::post('/quick-driver', [OrderController::class, 'quickDriver'])->name('quickDriver')->middleware(['shift']);
    Route::post('/fetch-customer-info', [OrderController::class, 'fetchCustomerInfo'])->name('fetchCustomerInfo')->middleware(['shift']);
    Route::post('/fetch-driver-info', [OrderController::class, 'fetchDriverInfo'])->name('fetchDriverInfo')->middleware(['shift']);
    Route::get('/table-types', [OrderController::class, 'getTableTypes'])->name('tableTypes')->middleware(['shift']);

    // Web Order Management Routes
    Route::prefix('web-orders')->name('web-orders.')->group(function () {
        Route::get('/manage-web-order/{order}', [WebOrderController::class, 'manage'])->name('manage');
        Route::post('/accept-order/{order}', [WebOrderController::class, 'acceptOrder'])->name('accept');
        Route::post('/reject-order/{order}', [WebOrderController::class, 'rejectOrder'])->name('reject');
        Route::post('/complete-order/{order}', [WebOrderController::class, 'completeOrder'])->name('complete');
        Route::post('/out-for-delivery/{order}', [WebOrderController::class, 'outForDelivery'])->name('outForDelivery');
        Route::post('/apply-discount/{order}', [WebOrderController::class, 'applyDiscount'])->name('applyDiscount');
        Route::post('/save-order/{order}', [WebOrderController::class, 'saveOrder'])->name('saveOrder');
    });

    // Printer Management Routes
    Route::post('/orders/print/{order}', [OrderController::class, 'printReceipt'])->name('print');
    Route::post('/printers-of-products', [OrderController::class, 'getPrintersOfProducts'])->name('printers.products');
    Route::post('/print-in-kitchen', [OrderController::class, 'printInKitchen'])->name('print.kitchen');
    Route::post('/open-cashier-drawer', [OrderController::class, 'openCashierDrawer'])->name('cashier.openDrawer');

    // Admin Printer Management Routes
    Route::prefix('admin/printers')->name('admin.printers.')->group(function () {
        Route::post('/test', [PrinterController::class, 'testPrinter'])->name('test');
        Route::post('/scan', [PrinterController::class, 'scanNetwork'])->name('scan');
    });

    // Expense Management Routes
    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->name('index');
        Route::post('/', [ExpenseController::class, 'store'])->name('store');
        Route::put('/{expense}', [ExpenseController::class, 'update'])->name('update');
        Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->name('destroy');
    })->middleware(['shift']);

    // Inventory Management Routes
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/day-status', [InventoryController::class, 'dayStatus'])->name('dayStatus');
        Route::post('/toggle-day', [InventoryController::class, 'toggleDay'])->name('toggleDay');
    });

    // Invoice Print Routes
    Route::get('/print/invoice/{type}/{id}', [InvoicePrintController::class, 'show'])
        ->name('invoice.print')
        ->where(['type' => '(purchase_invoice|return_purchase_invoice|stocktaking|waste)', 'id' => '[0-9]+']);
});

require __DIR__ . '/auth.php';

// Test route for broadcasting
Route::get('/test-broadcast', function () {
    // Get the latest order or create a dummy one for testing
    $order = \App\Models\Order::with(['customer', 'items'])->latest()->first();

    if (!$order) {
        return response()->json(['error' => 'No orders found. Create an order first.']);
    }

    // Log for debugging
    \Log::info('Broadcasting test event for order: ' . $order->id);
    \Log::info('Broadcasting connection: ' . config('broadcasting.default'));
    \Log::info('Reverb config: ', config('broadcasting.connections.reverb'));

    // Dispatch the event
    $event = new \App\Events\Orders\WebOrderReceived($order);
    \Log::info('Event created: ' . get_class($event));
    \Log::info('Broadcast channels: ', $event->broadcastOn());
    \Log::info('Broadcast as: ' . $event->broadcastAs());
    \Log::info('Broadcast with: ', $event->broadcastWith());

    event($event);

    \Log::info('Event dispatched');

    return response()->json([
        'message' => 'Event dispatched successfully',
        'order_id' => $order->id,
        'order_number' => $order->order_number,
        'broadcast_connection' => config('broadcasting.default'),
        'reverb_config' => config('broadcasting.connections.reverb')
    ]);
});

// Simple test event
Route::get('/test-simple', function () {
    \Log::info('Dispatching simple test event');

    $event = new \App\Events\TestEvent('This is a test message');
    event($event);

    \Log::info('Simple test event dispatched');

    return response()->json([
        'message' => 'Simple test event dispatched',
        'timestamp' => now()->format('H:i:s')
    ]);
});
