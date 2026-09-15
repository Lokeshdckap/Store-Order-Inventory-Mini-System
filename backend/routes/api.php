<?php

use App\Http\Controllers\Api\CustomerOrderHistoryController;
use App\Http\Controllers\Api\OrderCreateController;
use App\Http\Controllers\Api\OrderDetailController;
use App\Http\Controllers\Api\ProductCreateController;
use App\Http\Controllers\Api\ProductDeleteController;
use App\Http\Controllers\Api\ProductListController;
use App\Http\Controllers\Api\ProductLowStockController;
use App\Http\Controllers\Api\ProductUpdateController;
use App\Http\Controllers\Api\ProductUpdateStockController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\MeController;
use Illuminate\Support\Facades\Route;

// Public Health Check
Route::get('/ping', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]);
});

// Admin Authentication (Public Login)
Route::post('/login', LoginController::class)->name('login');

// Admin Protected API Routes
Route::middleware('auth:sanctum')->group(function () {
    // Current Authenticated Admin User Profile
    Route::get('/user', MeController::class)->name('user.me');
    Route::post('/logout', LogoutController::class)->name('logout');

    // Product & Inventory Endpoints (Single-Action Invokable Controllers)
    Route::get('/products', ProductListController::class)->name('products.index');
    Route::post('/products', ProductCreateController::class)->name('products.store');
    Route::get('/products/low-stock', ProductLowStockController::class)->name('products.low-stock');
    Route::put('/products/{product}', ProductUpdateController::class)->name('products.update');
    Route::patch('/products/{product}/stock', ProductUpdateStockController::class)->name('products.update-stock');
    Route::delete('/products/{product}', ProductDeleteController::class)->name('products.destroy');

    // Order Endpoints (Single-Action Invokable Controllers)
    Route::post('/orders', OrderCreateController::class)->name('orders.store');
    Route::get('/orders/{order}', OrderDetailController::class)->name('orders.show');

    // Customer Order History Endpoint (Single-Action Invokable Controller)
    Route::get('/customers/{email}/orders', CustomerOrderHistoryController::class)
        ->where('email', '.*')
        ->name('customers.orders');
});
