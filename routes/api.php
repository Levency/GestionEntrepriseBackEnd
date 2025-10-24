<?php

use App\Models\Departement;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\auth\AuthController;
use App\Http\Controllers\api\auth\LoginController;
use App\Http\Controllers\api\taxes\TaxeController;
use App\Http\Controllers\api\access\RoleController;
use App\Http\Controllers\api\sales\SalesController;
use App\Http\Controllers\api\auth\RegisterController;
use App\Http\Controllers\api\stock\ProductController;
use App\Http\Controllers\api\stock\CategoryController;
use App\Http\Controllers\api\access\UserRoleController;
use App\Http\Controllers\api\access\PermissionController;
use App\Http\Controllers\api\personnel\PayrollController;
use App\Http\Controllers\api\personnel\EmployeeController;
use App\Http\Controllers\api\stock\StockMovementController;
use App\Http\Controllers\api\personnel\AttendanceController;
use App\Http\Controllers\api\personnel\DepartmentController;
use App\Http\Controllers\api\access\RolePermissionController;

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


// Auth user
    // Route::post('user/register', [RegisterController::class, 'register']);
    // Route::post('user/login', [LoginController::class, 'login']);
    // Route::post('user/forgot-password', [\App\Http\Controllers\api\auth\FogetPassword::class, 'sendResetLinkEmail']);

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('logout', [AuthController::class, 'logout']);

// users

Route::middleware('auth:sanctum')->group(function () {

    Route::delete('users/{user}', [\App\Http\Controllers\api\auth\UserController::class, 'deleteUser']);
    Route::put('users/{user}', [\App\Http\Controllers\api\auth\UserController::class, 'updateUser']);
    Route::get('users', [\App\Http\Controllers\api\auth\UserController::class, 'getAllUsers']);
    Route::get('users/{user}', [\App\Http\Controllers\api\auth\UserController::class, 'getUser']);
    Route::get('users/{user}/show', [\App\Http\Controllers\api\auth\UserController::class, 'showUser']);

// Products
    Route::apiResource('products', ProductController::class);
    Route::post('products/bulk-import', [ProductController::class, 'bulkImport']);
    Route::get('products/low-stock/alert', [ProductController::class, 'lowStockAlert']);
    Route::post('products/{product}/adjust-stock', [ProductController::class, 'adjustStock']);
    Route::get('products/search/{query}', [ProductController::class, 'search']);
    Route::get('products/barcode/{barcode}', [ProductController::class, 'findByBarcode']);
    
    // Categories
    Route::apiResource('categories', CategoryController::class);
    Route::get('categories/{category}/products', [CategoryController::class, 'products']);
    
    // Stock Movements
    Route::get('stock-movements', [StockMovementController::class, 'index']);
    Route::get('stock-movements/product/{product}', [StockMovementController::class, 'byProduct']);
    Route::get('stock-movements/report', [StockMovementController::class, 'report']);


// Sales
    Route::apiResource('sales', SalesController::class);
    Route::post('sales/{sale}/refund', [SalesController::class, 'refund']);
    Route::post('sales/{sale}/cancel', [SalesController::class, 'cancel']);
    Route::post('sales/{sale}/pending', [SalesController::class, 'pending']);
    Route::get('sales/{sale}/print', [SalesController::class, 'print']);
    Route::get('sales/today/summary', [SalesController::class, 'todaySummary']);
    Route::post('sales/quick-sale', [SalesController::class, 'quickSale']);

    // Employees
    Route::apiResource('employees', EmployeeController::class);
    Route::get('employees/{employee}/attendance-history', [EmployeeController::class, 'attendanceHistory']);
    Route::get('employees/{employee}/payroll-history', [EmployeeController::class, 'payrollHistory']);
    Route::post('employees/{employee}/activate', [EmployeeController::class, 'activate']);
    Route::post('employees/{employee}/deactivate', [EmployeeController::class, 'deactivate']);
    
    // Attendance
    Route::get('attendance', [AttendanceController::class, 'index']);
    Route::post('attendance/{emplyee}/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('attendance/{employee}/check-out', [AttendanceController::class, 'checkOut']);
    Route::get('attendance/today', [AttendanceController::class, 'today']);
    Route::get('attendance/employee/{employee}', [AttendanceController::class, 'byEmployee']);
    Route::post('attendance/{employee}/mark-absent', [AttendanceController::class, 'markAbsent']);
    Route::get('attendance/summary', [AttendanceController::class, 'summary']);
    
    // Payroll
    Route::apiResource('payrolls', PayrollController::class);
    Route::post('payrolls/generate', [PayrollController::class, 'generate']);
    Route::post('payrolls/{payroll}/pay', [PayrollController::class, 'markAsPaid']);
    Route::get('payrolls/period/{period}', [PayrollController::class, 'byPeriod']);
    Route::get('payrolls/employee/{employee}', [PayrollController::class, 'byEmployee']);
    
    
    // Departments
    Route::apiResource('departments', DepartmentController::class);
    Route::post('departments/store', [DepartmentController::class, 'store']);
    Route::get('departments/{department}/employees', [DepartmentController::class, 'employees']);

     // Taxes
    Route::apiResource('taxes', TaxeController::class);
    Route::put('taxes/{tax}/activate', [TaxeController::class, 'activate']);
    Route::put('taxes/{tax}/deactivate', [TaxeController::class, 'deactivate']);

     // 🔹 Gestion des rôles
    Route::apiResource('roles', RoleController::class);

    // 🔹 Gestion des permissions
    Route::apiResource('permissions', PermissionController::class);

    // 🔹 Attribution de rôles et permissions aux utilisateurs
    Route::post('users/{user}/assign-role', [UserRoleController::class, 'assignRole']);
    Route::post('users/{user}/remove-role', [UserRoleController::class, 'removeRole']);
    Route::post('users/{user}/give-permission', [UserRoleController::class, 'givePermission']);
    Route::post('users/{user}/revoke-permission', [UserRoleController::class, 'revokePermission']);

    //
    Route::post('roles/{role}/give-permissions', [RolePermissionController::class, 'givePermissions']);
    Route::post('roles/{role}/revoke-permissions', [RolePermissionController::class, 'revokePermissions']);


});


    