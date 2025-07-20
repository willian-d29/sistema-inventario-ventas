<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Product;
use App\Http\Controllers\{
    CartController,
    CategoryController,
    ContactController,
    CustomerController,
    DashboardController,
    EmployeeController,
    ExpenseController,
    OrderController,
    ProductController,
    ProfileController,
    SalaryController,
    SettingController,
    SupplierController,
    TransactionController,
    UnitTypeController,
    UserController,
    ReportController,
    PaymentController,
    Auth\AuthenticatedSessionController
};


Route::get('/debug-ping', fn () => 'pong')->name('debug.ping');

Route::get('/orders/last-id', function () {
    $last = \App\Models\Order::latest('id')->first();
    return response()->json(['id' => $last?->id]);
})->name('orders.last-id');




/*
|--------------------------------------------------------------------------
| Página pública (catálogo de productos)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    $products = Product::with('category')
        ->where('status', 'active')
        ->get();

        

    return Inertia::render('Welcome', [
        'pageTitle' => 'Home',
        'products'  => $products,
    ]);
})->name('home');

Route::get('/productos', [ProductController::class, 'index'])->name('productos.publico');
Route::get('/carrito', [CartController::class, 'showPublicCart'])->name('carrito.publico');
Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');

/*
|--------------------------------------------------------------------------
| Redirección automática post-login según rol
|--------------------------------------------------------------------------
*/
Route::get('/redirect', function () {
    $role = auth()->user()->role;
    return match ($role) {
        'admin'    => redirect('/sistema/dashboard'),
        'vendedor' => redirect('/sistema/pos'),
        'cliente'  => redirect('/'),
        default    => abort(403),
    };
})->middleware('auth')->name('redirect.by.role');

/*
|--------------------------------------------------------------------------
| Rutas para cliente autenticado
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:cliente'])->group(function () {
    Route::get('/cliente/pedidos', [OrderController::class, 'clientOrders'])->name('cliente.pedidos');
    Route::delete('/cliente/pedidos/{id}', [OrderController::class, 'destroy'])->middleware(['auth', 'role:cliente']);

});

/*
|--------------------------------------------------------------------------
| Autenticación para admin/vendedor (login externo)
|--------------------------------------------------------------------------
*/
Route::prefix('sistema')->middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('sistema.login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Admin y Vendedor autenticados
|--------------------------------------------------------------------------
*/
Route::prefix('sistema')->middleware(['auth', 'role:admin,vendedor'])->group(function () {
    // Perfil de usuario
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/image', [ProfileController::class, 'updateImage'])->name('profile.image');

    // Recursos administrativos
    Route::apiResource('/categories', CategoryController::class);
    Route::apiResource('/unit-types', UnitTypeController::class);
    Route::apiResource('/suppliers', SupplierController::class);
    Route::resource('/products', ProductController::class);
    Route::apiResource('/customers', CustomerController::class);
    Route::apiResource('/expenses', ExpenseController::class);
    Route::apiResource('/orders', OrderController::class);
    Route::put('/orders/{order}/settle', [OrderController::class, 'settle'])->name('orders.settle');
    Route::put('/orders/{order}/pay', [OrderController::class, 'pay'])->name('orders.pay');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    // Punto de venta (POS)
    Route::get('/pos', [CartController::class, 'index'])->name('carts.index');
    Route::post('/carts/{productId}', [CartController::class, 'addToCart'])->name('carts.store');
    Route::put('/carts/{cart}', [CartController::class, 'updateQuantity'])->name('carts.update');
    Route::delete('/carts/{cart}', [CartController::class, 'delete'])->name('carts.delete');
    Route::delete('/carts/delete/all', [CartController::class, 'deleteForUser'])->name('carts.delete.all');
    Route::put('/carts/{cart}/increment', [CartController::class, 'incrementQuantity'])->name('carts.increment');
    Route::put('/carts/{cart}/decrement', [CartController::class, 'decrementQuantity'])->name('carts.decrement');
});

/*
|--------------------------------------------------------------------------
| Panel exclusivo para Admin
|--------------------------------------------------------------------------
*/
Route::prefix('sistema')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::apiResource('/employees', EmployeeController::class);
    Route::apiResource('/salaries', SalaryController::class);
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    // Reportes
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/reports/ventas/pdf', [ReportController::class, 'ventasPDF'])->name('reports.ventas.pdf');
    Route::get('/reports/ventas/excel', [ReportController::class, 'ventasExcel'])->name('reports.ventas.excel');
});

/*
|--------------------------------------------------------------------------
| Panel exclusivo para Vendedor (opcional)
|--------------------------------------------------------------------------
*/
Route::prefix('sistema')->middleware(['auth', 'role:vendedor'])->group(function () {
    Route::get('/panel', fn () => Inertia::render('Panel'))->name('panel');
});

/*
|--------------------------------------------------------------------------
| Rutas de perfil (cliente autenticado)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/perfil', [CustomerController::class, 'editProfile'])->name('perfil');
    Route::put('/perfil', [CustomerController::class, 'updateProfile']);
    Route::post('/perfil', [CustomerController::class, 'updateProfile']); // doble vía opcional
});

/*
|--------------------------------------------------------------------------
| Rutas de pagos con Stripe
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/checkout', fn () => Inertia::render('Checkout'))->name('checkout');
    Route::post('/checkout/stripe', [PaymentController::class, 'checkout'])->name('checkout.stripe');
    Route::get('/checkout/success', [OrderController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/cancel', fn () => Inertia::render('Checkout/Cancel'))->name('checkout.cancel');
});



Route::get('/order/{id}/pdf', [OrderController::class, 'downloadPDF'])
    ->middleware(['auth'])
    ->name('orders.pdf');

    Route::get('/orders/{id}/receipt', [OrderController::class, 'receipt'])->name('orders.receipt');



/*
|--------------------------------------------------------------------------
| Rutas de autenticación Fortify/Breeze
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';


