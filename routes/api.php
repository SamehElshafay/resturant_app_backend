<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\UserController;

Route::post('/login-pin', [PosController::class, 'loginByPin']);
Route::post('/register-device', [PosController::class, 'registerDeviceByCode']);

Route::middleware(['auth:sanctum', \App\Http\Middleware\TokenExpirationMiddleware::class])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // POS Routes
    Route::prefix('pos')->group(function () {
        Route::get('/initial-data', [PosController::class, 'getInitialData']);
        Route::get('/categories', [PosController::class, 'getCategories']);
        Route::get('/products/{category_id?}', [PosController::class, 'getProducts']);
        Route::get('/tables', [PosController::class, 'getTables']);
        Route::get('/drivers', [PosController::class, 'getDrivers']);
        Route::post('/orders', [PosController::class, 'storeOrder']);
        Route::get('/orders', [PosController::class, 'getOrders']);
        Route::get('/orders/{id}', [PosController::class, 'getOrder']);
        Route::post('/orders/{id}/add-items', [PosController::class, 'addItemsToOrder']);
        Route::delete('/orders/{id}/items/{item_id}', [PosController::class, 'removeItemFromOrder']);
        Route::put('/orders/{id}/items/{item_id}/quantity', [PosController::class, 'updateItemQuantity']);
        Route::post('/orders/{id}/pay', [PosController::class, 'payOrder']);
        Route::get('/printer-configs', [PosController::class, 'getPrinterConfigs']);
        Route::post('/printer-configs', [PosController::class, 'updatePrinterConfig']);
        Route::get('/inventory', [PosController::class, 'getInventory']);
        Route::post('/close-session', [PosController::class, 'closeSession']);
        Route::post('/logout', [PosController::class, 'logout']);
    });

    // Admin User Management Routes
    Route::prefix('admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::post('users/{user}/documents', [UserController::class, 'uploadDocument']);
    });
});
