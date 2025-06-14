<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UnitTypeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Product;
/*
|--------------------------------------------------------------------------
| Redirección por rol después del login
|--------------------------------------------------------------------------
*/




/*
|--------------------------------------------------------------------------
| Rutas Públicas (Frontend para clientes/visitantes)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
        $products = Product::with('category')
            ->where('status', 'active')
            ->take(20)
            ->get();

    return Inertia::render('Welcome', [
        'pageTitle' => 'Home',
        'products' => $products,
    ]);
})->name('home');

Route::get('/productos', [ProductController::class, 'index'])->name('productos.publico');
Route::get('/carrito', [CartController::class, 'showPublicCart'])->name('carrito.publico');

Route::post('contacts', [ContactController::class, 'store'])->name('contacts.store');
Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Rutas para Clientes logueados
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:cliente'])->group(function () {
    Route::get('/cliente/pedidos', fn () => Inertia::render('Cliente/Pedidos'))->name('cliente.pedidos');
});

/*
|--------------------------------------------------------------------------
| Rutas del sistema privado (solo admin y vendedor)
|--------------------------------------------------------------------------
*/
Route::prefix('sistema')->group(function () {
    // Login de admin/vendedor (separado)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('sistema.login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    });

    // Área protegida por autenticación y rol
    Route::middleware(['auth', 'role:admin,vendedor'])->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        // Perfil
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::post('/profile/image', [ProfileController::class, 'updateImage'])->name('profile.image');

        // Solo admin
        Route::middleware('role:admin')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::apiResource('/employees', EmployeeController::class);
            Route::apiResource('/salaries', SalaryController::class);
            Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        });

        // Gestión general
        Route::apiResource('/categories', CategoryController::class);
        Route::apiResource('/unit-types', UnitTypeController::class);
        Route::apiResource('/suppliers', SupplierController::class);
        Route::resource('/products', ProductController::class);
        Route::apiResource('/customers', CustomerController::class);
        Route::apiResource('/expenses', ExpenseController::class);

        // Órdenes
        Route::apiResource('/orders', OrderController::class);
        Route::put('/orders/{order}/settle', [OrderController::class, 'settle'])->name('orders.settle');
        Route::put('/orders/{order}/pay', [OrderController::class, 'pay'])->name('orders.pay');

        // Transacciones
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

        // Punto de Venta / Carts
        Route::get('/pos', [CartController::class, 'index'])->name('carts.index');
        Route::post('/carts/{productId}', [CartController::class, 'addToCart'])->name('carts.store');
        Route::put('/carts/{cart}', [CartController::class, 'updateQuantity'])->name('carts.update');
        Route::delete('/carts/{cart}', [CartController::class, 'delete'])->name('carts.delete');
        Route::delete('/carts/delete/all', [CartController::class, 'deleteForUser'])->name('carts.delete.all');
        Route::put('/carts/{cart}/increment', [CartController::class, 'incrementQuantity'])->name('carts.increment');
        Route::put('/carts/{cart}/decrement', [CartController::class, 'decrementQuantity'])->name('carts.decrement');
    });
});

require __DIR__ . '/auth.php';
