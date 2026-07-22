<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\CashRegisterDocumentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentPrintLogController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleDocumentController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitTypeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('sistema.login');
    }

    return redirect()->route('dashboard');
})->name('home');

Route::get('/redirect', function () {
    return redirect()->route('dashboard');
})->middleware('auth')->name('redirect.by.role');

Route::prefix('sistema')->middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('sistema.login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::prefix('sistema')->middleware(['auth', 'role:admin,cajero'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/image', [ProfileController::class, 'updateImage'])->name('profile.image');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/pos', [CartController::class, 'index'])->name('carts.index');
    Route::post('/carts/scan', [CartController::class, 'scan'])->name('carts.scan');
    Route::post('/carts/{productId}', [CartController::class, 'addToCart'])->name('carts.store');
    Route::put('/carts/{cartId}', [CartController::class, 'updateQuantity'])->name('carts.update');
    Route::delete('/carts/all', [CartController::class, 'deleteForUser'])->name('carts.delete.all');
    Route::delete('/carts/{cartId}', [CartController::class, 'delete'])->name('carts.delete');
    Route::put('/carts/{cartId}/increment', [CartController::class, 'incrementQuantity'])->name('carts.increment');
    Route::put('/carts/{cartId}/decrement', [CartController::class, 'decrementQuantity'])->name('carts.decrement');

    Route::post('/cash-registers/open', [CashRegisterController::class, 'open'])->name('cash-registers.open');
    Route::get('/cash-registers', [CashRegisterController::class, 'index'])->name('cash-registers.index');
    Route::get('/cash-registers/reconciliation', [CashRegisterDocumentController::class, 'reconciliation'])->name('cash-registers.reconciliation');
    Route::get('/cash-registers/{cashRegister}/thermal', [CashRegisterDocumentController::class, 'thermal'])->name('cash-registers.thermal');
    Route::get('/cash-registers/{cashRegister}/pdf', [CashRegisterDocumentController::class, 'pdf'])->name('cash-registers.pdf');
    Route::get('/cash-registers/{cashRegister}/admin-report/pdf', [CashRegisterDocumentController::class, 'adminReportPdf'])->name('cash-registers.admin-report.pdf');
    Route::get('/cash-registers/{cashRegister}/movements/{cashMovement}/thermal', [CashRegisterDocumentController::class, 'movementThermal'])->name('cash-registers.movements.thermal');
    Route::get('/cash-registers/{cashRegister}/movements/{cashMovement}/pdf', [CashRegisterDocumentController::class, 'movementPdf'])->name('cash-registers.movements.pdf');
    Route::post('/cash-registers/{cashRegister}/print-request', [CashRegisterDocumentController::class, 'requestPrint'])->name('cash-registers.print-request');
    Route::post('/cash-registers/{cashRegister}/movements/{cashMovement}/print-request', [CashRegisterDocumentController::class, 'requestMovementPrint'])->name('cash-registers.movements.print-request');
    Route::post('/cash-registers/{cashRegister}/movements', [CashRegisterController::class, 'movement'])->name('cash-registers.movements.store');
    Route::put('/cash-registers/{cashRegister}/close', [CashRegisterController::class, 'close'])->name('cash-registers.close');
    Route::put('/cash-registers/{cashRegister}/review', [CashRegisterController::class, 'review'])->name('cash-registers.review');

    Route::get('/orders', fn () => redirect()->route('sales.index'))->name('orders.index');
    Route::post('/orders', fn () => redirect()->route('sales.index'))->name('orders.store');
    Route::get('/transactions', fn () => redirect()->route('reports.index'))->name('transactions.index');

    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::get('/sales/{sale}/thermal', [SaleDocumentController::class, 'thermal'])->name('sales.thermal');
    Route::get('/sales/{sale}/pdf', [SaleDocumentController::class, 'pdf'])->name('sales.pdf');
    Route::post('/sales/{sale}/print-request', [SaleDocumentController::class, 'requestPrint'])->name('sales.print-request');

    Route::get('/print-logs', DocumentPrintLogController::class)->name('print-logs.index');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
});

Route::prefix('sistema')->middleware(['auth', 'role:admin'])->group(function () {
    Route::apiResource('/categories', CategoryController::class)->except('show');
    Route::apiResource('/unit-types', UnitTypeController::class)->except('show');
    Route::apiResource('/suppliers', SupplierController::class)->except('show');
    Route::resource('/products', ProductController::class)->except(['index', 'show']);
    Route::apiResource('/expenses', ExpenseController::class)->except('show');
    Route::apiResource('/employees', EmployeeController::class)->except('show');
    Route::apiResource('/salaries', SalaryController::class)->except('show');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::match(['put', 'post'], '/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::delete('/settings/logo', [SettingController::class, 'destroyLogo'])->name('settings.logo.destroy');
    Route::get('/settings/printer-test', [SettingController::class, 'printerTest'])->name('settings.printer-test');
    Route::post('/settings/printer-test/print-request', [SettingController::class, 'requestPrinterTestPrint'])->name('settings.printer-test.print-request');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/ventas/pdf', [ReportController::class, 'ventasPDF'])->name('reports.ventas.pdf');
    Route::get('/reports/ventas/excel', [ReportController::class, 'ventasExcel'])->name('reports.ventas.excel');
});

require __DIR__.'/auth.php';
