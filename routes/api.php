<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\sales\SalesController;
use App\Http\Controllers\api\stock\ProductController;
use App\Http\Controllers\api\stock\CategoryController;
use App\Http\Controllers\api\stock\StockMovementController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Products
    Route::apiResource('products', ProductController::class);
    Route::get('produuts/index', [ProductController::class, 'index']);
    Route::post('products/store', [ProductController::class, 'store']);
    Route::post('products/{id}/show', [ProductController::class, 'show']);
    Route::post('products/{id}/update', [ProductController::class, 'update']);
    Route::delete('products/{id}/destroy', [ProductController::class, 'destroy']);

    Route::post('products/bulk-import', [ProductController::class, 'bulkImport']);
    Route::get('products/low-stock/alert', [ProductController::class, 'lowStockAlert']);
    Route::post('products/{product}/adjust-stock', [ProductController::class, 'adjustStock']);
    Route::get('products/search/{query}', [ProductController::class, 'search']);
    Route::get('products/barcode/{barcode}', [ProductController::class, 'findByBarcode']);
    
    // Categories
    Route::apiResource('categories', CategoryController::class);
    Route::post('categories/store', [CategoryController::class, 'store']);
    Route::post('categories/{id}/update', [CategoryController::class, 'update']);
    Route::post('categories/{id}/show', [CategoryController::class, 'show']);
    Route::post('categories/{id}/destroy', [CategoryController::class, 'destroy']);

    Route::get('categories/{category}/products', [CategoryController::class, 'products']);
    
    // Stock Movements
    Route::get('stock-movements', [StockMovementController::class, 'index']);
    Route::post('stock-movements/store', [StockMovementController::class, 'store']);
    Route::post('stock-movemnts/{id}/update', [StockMovementController::class, 'update']);
    Route::delete('stock-movements/{id}/destroy', [StockMovementController::class, 'destroy']);
    Route::get('stock-movements/product/{product}', [StockMovementController::class, 'byProduct']);
    Route::get('stock-movements/report', [StockMovementController::class, 'report']);


// Sales
    Route::apiResource('sales', SalesController::class);
    Route::get('sales', [SalesController::class, 'index']);
    Route::post('sales/store', [SalesController::class, 'store']);
    Route::get('sales/{sale}/show', [SalesController::class, 'show']);
    Route::post('sales/{sale}/update', [SalesController::class, 'update']);
    Route::delete('sales/{sale}/destroy', [SalesController::class, 'destroy']);

    Route::post('sales/{sale}/refund', [SalesController::class, 'refund']);
    Route::post('sales/{sale}/cancel', [SalesController::class, 'cancel']);
    Route::post('sales/{sale}/pending', [SalesController::class, 'pending']);
    Route::get('sales/{sale}/print', [SalesController::class, 'print']);
    Route::get('sales/today/summary', [SalesController::class, 'todaySummary']);
    Route::post('sales/quick-sale', [SalesController::class, 'quickSale']);


    