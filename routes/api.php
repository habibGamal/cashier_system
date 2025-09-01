<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\WebOrdersController;
use App\Http\Controllers\Api\ManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check endpoints
Route::get('/check', [ApiController::class, 'check']);
Route::post('/check', [ApiController::class, 'check']);

// Management endpoints
Route::prefix('management')->name('management.')->group(function () {
    Route::post('/deploy', [ManagementController::class, 'deploy'])->name('deploy');
    Route::post('/stop', [ManagementController::class, 'stop'])->name('stop');
    Route::post('/start', [ManagementController::class, 'start'])->name('start');
    Route::post('/custom-script', [ManagementController::class, 'customScript'])->name('custom-script');
    Route::get('/status', [ManagementController::class, 'status'])->name('status');
});

// Product search and management
Route::get('/products/product_search', [ApiController::class, 'productSearch']);
Route::post('/validate_products', [ApiController::class, 'validateProducts']);
Route::get('/all-products', [ApiController::class, 'allProducts']);
Route::get('/all-products-refs-master', [ApiController::class, 'allProductsRefsMaster']);
Route::get('/all-products-prices-master', [ApiController::class, 'allProductsPricesMaster']);
Route::get('/all-products-recipes-master', [ApiController::class, 'allProductsRecipesMaster']);
Route::get('/get-products-master', [ApiController::class, 'getProductsMaster']);
Route::get('/get-products-master-by-refs', [ApiController::class, 'getProductsMasterByRefs']);
Route::get('/get-products-prices-master', [ApiController::class, 'getProductsPricesMaster']);

// Web order endpoints
Route::post('/web-orders/place-order', [WebOrdersController::class, 'createOrder']);
Route::get('/can-accept-order', [WebOrdersController::class, 'canAcceptOrder']);
Route::get('/get-shift-id', [WebOrdersController::class, 'getShiftId']);
