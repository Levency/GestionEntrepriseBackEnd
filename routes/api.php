<?php

use App\Models\Departement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\sales\SalesController;
use App\Http\Controllers\api\stock\ProductController;
use App\Http\Controllers\api\stock\CategoryController;
use App\Http\Controllers\api\personnel\PayrollController;
use App\Http\Controllers\api\personnel\EmployeeController;
use App\Http\Controllers\api\stock\StockMovementController;
use App\Http\Controllers\api\personnel\AttendanceController;
use App\Http\Controllers\api\personnel\DepartmentController;

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
    // Route::get('produuts/index', [ProductController::class, 'index']);
    // Route::post('products/store', [ProductController::class, 'store']);
    // Route::post('products/{id}/show', [ProductController::class, 'show']);
    // Route::post('products/{id}/update', [ProductController::class, 'update']);
    // Route::delete('products/{id}/destroy', [ProductController::class, 'destroy']);

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

    // Employees
    Route::apiResource('employees', EmployeeController::class);
    Route::post('employees/store', [EmployeeController::class, 'store']);
    Route::get('employees/{id}/show', [EmployeeController::class, 'show']);
    Route::post('employees/{id}/update', [EmployeeController::class, 'update']);
    Route::delete('employees/{id}/destroy', [EmployeeController::class, 'destroy']);
    Route::get('employees/{employee}/attendance-history', [EmployeeController::class, 'attendanceHistory']);
    Route::get('employees/{employee}/payroll-history', [EmployeeController::class, 'payrollHistory']);
    Route::post('employees/{employee}/activate', [EmployeeController::class, 'activate']);
    Route::post('employees/{employee}/deactivate', [EmployeeController::class, 'deactivate']);
    
    // Attendance
    Route::get('attendance', [AttendanceController::class, 'index']);
    Route::post('attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('attendance/check-out', [AttendanceController::class, 'checkOut']);
    Route::get('attendance/today', [AttendanceController::class, 'today']);
    Route::get('attendance/employee/{employee}', [AttendanceController::class, 'byEmployee']);
    Route::post('attendance/mark-absent', [AttendanceController::class, 'markAbsent']);
    Route::get('attendance/summary', [AttendanceController::class, 'summary']);
    
    // Payroll
    Route::apiResource('payrolls', PayrollController::class);
    Route::post('payrolls/store', [PayrollController::class, 'store']);
    
    Route::post('payrolls/generate', [PayrollController::class, 'generate']);
    Route::post('payrolls/{payroll}/pay', [PayrollController::class, 'markAsPaid']);
    Route::get('payrolls/period/{period}', [PayrollController::class, 'byPeriod']);
    Route::get('payrolls/employee/{employee}', [PayrollController::class, 'byEmployee']);
    
    
    // Departments
    Route::apiResource('departments', DepartmentController::class);
    Route::post('departments/store', [DepartmentController::class, 'store']);
    Route::post('departments/{id}/update', [DepartmentController::class, 'update']);
    Route::get('departments/{id}/show', [DepartmentController::class, 'show']);
    Route::delete('departments/{id}/destroy', [DepartmentController::class, 'destroy']);
    Route::get('departments/{department}/employees', [DepartmentController::class, 'employees']);


    