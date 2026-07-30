<?php

use App\Models\Cart;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DocumentPrintLog;
use App\Models\Product;
use App\Models\UnitType;
use App\Models\User;
use App\Services\CashRegisterService;
use App\Services\SaleService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function simplificationProduct(array $overrides = []): Product
{
    $category = Category::firstOrCreate(['name' => 'Abarrotes']);
    $unit = UnitType::firstOrCreate(['name' => 'Unidad'], ['symbol' => 'und']);
    $suffix = Str::upper(Str::random(6));

    return Product::create(array_merge([
        'category_id' => $category->id,
        'unit_type_id' => $unit->id,
        'name' => 'Producto consulta '.$suffix,
        'product_number' => 'P-'.$suffix,
        'product_code' => 'SKU-'.$suffix,
        'barcode' => '779'.Str::padLeft((string) random_int(1, 999999999), 9, '0'),
        'buying_price' => 2,
        'selling_price' => 5,
        'quantity' => 10,
        'photo' => 'product.png',
        'status' => 'active',
    ], $overrides));
}

function simplificationOpenRegister(User $user, float|int $openingAmount = 100): void
{
    app(CashRegisterService::class)->open($user, ['opening_amount' => $openingAmount]);
}

function simplificationSale(User $cashier, array $payload = []): mixed
{
    if (! \App\Models\CashRegister::where('user_id', $cashier->id)->where('status', 'open')->exists()) {
        simplificationOpenRegister($cashier);
    }

    $product = simplificationProduct();
    Cart::create(['user_id' => $cashier->id, 'product_id' => $product->id, 'quantity' => 1]);

    return app(SaleService::class)->createForUser(array_replace_recursive([
        'document_type' => 'receipt',
        'payments' => [['method' => 'cash', 'amount' => 5, 'received_amount' => 5]],
    ], $payload), $cashier->id);
}

test('gastos queda oculto del menu y dashboard visible', function () {
    $menu = File::get(resource_path('js/Navigation/menu.js'));
    $dashboard = File::get(resource_path('js/Pages/Dashboard.vue'));

    expect($menu)
        ->not->toContain('navigation.expenses')
        ->toContain('navigation.cash_shifts')
        ->toContain('navigation.products')
        ->and($dashboard)
        ->not->toContain('total_expense')
        ->not->toContain('operating_result');
});

test('caja muestra estado de empleados y apertura funcional del cajero', function () {
    $cash = File::get(resource_path('js/Pages/CashRegister/Index.vue'));
    $layout = File::get(resource_path('js/Layouts/AuthenticatedLayout.vue'));
    $shortcuts = File::get(resource_path('js/Composables/useAppShortcuts.js'));

    expect($cash)
        ->toContain('<AppButton type="submit"')
        ->toContain("t('cash.open_register')")
        ->toContain('employeeCashStates')
        ->toContain('filteredEmployeeStates')
        ->toContain("t('cash.status_board_description')")
        ->toContain("route('carts.index')")
        ->toContain("route('cash-registers.close'")
        ->and($layout)
        ->toContain("isPrimaryShortcut(event, '/')")
        ->toContain("event.key.toLowerCase() === 'g'")
        ->toContain('awaitingRouteShortcut')
        ->toContain('handleGlobalCommands')
        ->toContain('commandPaletteOpen')
        ->and($shortcuts)
        ->toContain('g ${key.toLowerCase()}')
        ->toContain("commandPaletteShortcut = computed(() => '/')")
        ->toContain("sidebarShortcut = computed(() => '[')");
});

test('cajero conserva caja cerrada al entrar a caja y pos', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);

    $this->actingAs($cashier)
        ->get(route('cash-registers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CashRegister/Index')
            ->where('currentRegister', null));

    $this->actingAs($cashier)
        ->get(route('carts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cart/Pos')
            ->where('cashRegister', null));

    expect(CashRegister::where('user_id', $cashier->id)->exists())->toBeFalse();
});

test('campana muestra alertas operativas por rol', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);

    $this->actingAs($cashier)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('appNotifications.items.0.id', 'cash-closed')
            ->where('appNotifications.items.0.title_key', 'notifications.cash_closed_title'));

    simplificationOpenRegister($cashier, 50);

    $this->actingAs($cashier)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('appNotifications.count', 0)
            ->where('appNotifications.items', []));

    $admin = User::factory()->create(['role' => 'admin']);
    simplificationProduct(['quantity' => 3]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('appNotifications.items')
            ->where('appNotifications.items.0.id', 'low-stock'));
});

test('administrador recibe estado de cajas por empleado', function () {
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Z Admin']);
    $cashier = User::factory()->create(['role' => 'cajero', 'name' => 'A Caja Demo']);
    simplificationOpenRegister($cashier, 75);

    $this->actingAs($admin)
        ->get(route('cash-registers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CashRegister/Index')
            ->has('employeeCashStates')
            ->where('employeeCashStates.0.status', 'open'));
});

test('cajero lista y busca productos sin ver costo ni acciones de mantenimiento', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $product = simplificationProduct(['name' => 'Aceite Primor']);

    $this->actingAs($cashier)
        ->get(route('products.index', ['keyword' => 'Aceite']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Product/Index')
            ->where('canManageProducts', false)
            ->where('products.data.0.id', $product->id)
            ->missing('products.data.0.buying_price'));

    $this->actingAs($cashier)->get(route('products.create'))->assertForbidden();
    $this->actingAs($cashier)->post(route('products.store'), [])->assertForbidden();
    $this->actingAs($cashier)->get(route('products.edit', $product))->assertForbidden();
    $this->actingAs($cashier)->put(route('products.update', $product), [])->assertForbidden();
    $this->actingAs($cashier)->delete(route('products.destroy', $product))->assertForbidden();
});

test('administrador conserva acceso completo a productos', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $product = simplificationProduct(['buying_price' => 3.5]);

    $this->actingAs($admin)
        ->get(route('products.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Product/Index')
            ->where('canManageProducts', true)
            ->where('products.data.0.id', $product->id)
            ->where('products.data.0.buying_price', 3.5));

    $this->actingAs($admin)->get(route('products.create'))->assertRedirect(route('products.index'));
    $this->actingAs($admin)->get(route('products.edit', $product))->assertRedirect(route('products.index'));
});

test('administrador puede crear producto con datos minimos de inventario', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = Category::firstOrCreate(['name' => 'Bebidas']);
    $unit = UnitType::firstOrCreate(['name' => 'Unidad'], ['symbol' => 'und']);

    $response = $this->actingAs($admin)->post(route('products.store'), [
        'category_id' => $category->id,
        'supplier_id' => null,
        'name' => 'Agua mineral 625 ml',
        'description' => null,
        'product_code' => null,
        'barcode' => '7751234599999',
        'root' => null,
        'buying_date' => null,
        'buying_price' => 0.8,
        'selling_price' => 1.5,
        'unit_type_id' => $unit->id,
        'quantity' => 24,
        'photo' => null,
        'status' => 'active',
    ]);

    $response->assertRedirect(route('products.index'));

    $this->assertDatabaseHas('products', [
        'name' => 'Agua mineral 625 ml',
        'barcode' => '7751234599999',
        'supplier_id' => null,
        'root' => 'Agua mineral 625 ml',
        'photo' => 'default-image.jpg',
    ]);
});

test('filtros de productos combinan busqueda categoria stock y codigo', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $abarrotes = Category::firstOrCreate(['name' => 'Abarrotes']);
    $bebidas = Category::firstOrCreate(['name' => 'Bebidas']);

    $target = simplificationProduct([
        'category_id' => $abarrotes->id,
        'name' => 'Aceite Primor 1 L',
        'product_code' => 'SKU-ACEITE-PRIMOR',
        'barcode' => '775123450001',
        'quantity' => 6,
    ]);
    simplificationProduct([
        'category_id' => $bebidas->id,
        'name' => 'Aceite de prueba otra categoria',
        'product_code' => 'SKU-ACEITE-OTRO',
        'barcode' => '775123450002',
        'quantity' => 80,
    ]);

    $this->actingAs($admin)
        ->get(route('products.index', [
            'keyword' => 'Aceite',
            'category_id' => $abarrotes->id,
            'quantities' => [1, 9],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Product/Index')
            ->where('products.data.0.id', $target->id)
            ->where('products.total', 1));

    $this->actingAs($admin)
        ->get(route('products.index', ['keyword' => '775123450001']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Product/Index')
            ->where('products.data.0.id', $target->id));
});

test('logs de impresion registran el tipo real del documento', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $customer = Customer::create([
        'document_type' => 'ruc',
        'document_number' => '20123456789',
        'name' => 'Empresa Demo SAC',
        'name_or_business_name' => 'Empresa Demo SAC',
    ]);

    $saleNote = simplificationSale($cashier);
    $receipt = simplificationSale($cashier, [
        'document_type' => 'receipt',
        'payments' => [['method' => 'yape', 'amount' => 5]],
    ]);
    $invoice = simplificationSale($cashier, [
        'document_type' => 'invoice',
        'customer_id' => $customer->id,
        'payments' => [['method' => 'transfer', 'amount' => 5]],
    ]);

    foreach ([$saleNote, $receipt, $invoice] as $sale) {
        $this->actingAs($cashier)->post(route('sales.print-request', $sale))->assertOk();
    }

    expect(DocumentPrintLog::where('sale_id', $saleNote->id)->value('document_type'))->toBe('receipt')
        ->and(DocumentPrintLog::where('sale_id', $receipt->id)->value('document_type'))->toBe('receipt')
        ->and(DocumentPrintLog::where('sale_id', $invoice->id)->value('document_type'))->toBe('invoice');
});

test('endpoints de clientes y consulta documental quedan retirados del flujo', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);

    $this->actingAs($cashier)
        ->getJson('/sistema/customers/document-lookup?type=dni&number=12345678')
        ->assertNotFound();

    $this->actingAs($cashier)
        ->postJson('/sistema/customers/quick', [
            'document_type' => 'dni',
            'document_number' => '87654321',
            'name' => 'Cliente Manual',
            'name_or_business_name' => 'Cliente Manual',
        ])
        ->assertNotFound();
});
