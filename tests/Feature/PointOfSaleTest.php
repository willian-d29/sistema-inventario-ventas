<?php

use App\Exceptions\SaleCreateException;
use App\Exports\VentasExport;
use App\Enums\CashRegister\CashMovementTypeEnum;
use App\Models\Cart;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DocumentSequence;
use App\Models\DocumentPrintLog;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\UnitType;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\BusinessSettingsService;
use App\Services\CashRegisterService;
use App\Services\SaleService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function posProduct(): Product
{
    $category = Category::firstOrCreate(['name' => 'Bebidas']);
    $unit = UnitType::firstOrCreate(['name' => 'Unidad'], ['symbol' => 'und']);
    $suffix = Str::upper(Str::random(8));

    return Product::create([
        'category_id' => $category->id,
        'unit_type_id' => $unit->id,
        'name' => 'Agua mineral',
        'product_number' => 'P-'.$suffix,
        'product_code' => 'SKU-'.$suffix,
        'barcode' => '775'.Str::padLeft((string) random_int(1, 9999999999), 10, '0'),
        'buying_price' => 2,
        'selling_price' => 5,
        'quantity' => 10,
        'photo' => 'product.png',
        'status' => 'active',
    ]);
}

function posSaleFor(User $cashier)
{
    $product = posProduct();
    openPosRegister($cashier);
    Cart::create(['user_id' => $cashier->id, 'product_id' => $product->id, 'quantity' => 1]);

    return app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [['method' => 'cash', 'amount' => 5, 'received_amount' => 5]],
    ], $cashier->id);
}

function openPosRegister(User $cashier, float|int $openingAmount = 100): CashRegister
{
    return app(CashRegisterService::class)->open($cashier, [
        'opening_amount' => $openingAmount,
    ]);
}

function addProductToPosCart(User $cashier, ?Product $product = null, float|int $quantity = 1): Product
{
    $product ??= posProduct();
    Cart::create([
        'user_id' => $cashier->id,
        'product_id' => $product->id,
        'quantity' => $quantity,
    ]);

    return $product;
}

function posCustomer(array $overrides = []): Customer
{
    return Customer::create(array_merge([
        'name' => 'Cliente prueba',
        'name_or_business_name' => 'Cliente prueba',
    ], $overrides));
}

function createPosSale(User $cashier, array $payload = [], ?Product $product = null): Sale
{
    if (! CashRegister::where('user_id', $cashier->id)->where('status', 'open')->exists()) {
        openPosRegister($cashier);
    }

    if (! Cart::where('user_id', $cashier->id)->exists()) {
        addProductToPosCart($cashier, $product);
    }

    return app(SaleService::class)->createForUser(array_replace_recursive([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 5, 'received_amount' => 5],
        ],
    ], $payload), $cashier->id);
}

test('a sale cannot be registered without an open cash register', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $product = posProduct();
    Cart::create(['user_id' => $cashier->id, 'product_id' => $product->id, 'quantity' => 1]);

    expect(fn () => app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [['method' => 'cash', 'amount' => 5, 'received_amount' => 5]],
    ], $cashier->id))->toThrow(SaleCreateException::class);

    expect($product->refresh()->quantity)->toBe(10.0);
});

test('a mixed payment sale updates inventory and creates an audit trail', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $product = posProduct();
    $register = openPosRegister($cashier);
    Cart::create(['user_id' => $cashier->id, 'product_id' => $product->id, 'quantity' => 2]);

    $sale = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 4, 'received_amount' => 5],
            ['method' => 'yape', 'amount' => 6, 'reference' => 'YP-12345'],
        ],
    ], $cashier->id);

    expect($sale->cash_register_id)->toBe($register->id)
        ->and($sale->cashier_id)->toBe($cashier->id)
        ->and($sale->payments)->toHaveCount(2)
        ->and($sale->payments->firstWhere('payment_method', 'cash')->change_amount)->toBe('1.00')
        ->and($product->refresh()->quantity)->toBe(8.0)
        ->and(Cart::where('user_id', $cashier->id)->exists())->toBeFalse()
        ->and(StockMovement::where('reference_type', (new Sale())->getMorphClass())->where('reference_id', $sale->id)->exists())->toBeTrue();
});

test('the scanner adds a product by its exact barcode', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $product = posProduct();

    $response = $this->actingAs($cashier)->post('/sistema/carts/scan', [
        'code' => $product->barcode,
    ]);

    $response->assertRedirect('/sistema/pos');
    $this->assertDatabaseHas('carts', [
        'user_id' => $cashier->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);
});

test('a non exact scan becomes a product search', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);

    $response = $this->actingAs($cashier)->post('/sistema/carts/scan', [
        'code' => 'agua',
    ]);

    $response->assertRedirect('/sistema/pos?keyword=agua');
});

test('an unknown barcode opens the quick product registration flow', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);

    $response = $this->actingAs($cashier)->post('/sistema/carts/scan', [
        'code' => '7896004009582',
    ]);

    $response->assertRedirect('/sistema/pos?unknown_barcode=7896004009582');
});

test('a cashier can quickly register a scanned product and add it to the cart', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $category = Category::firstOrCreate(['name' => 'Snacks']);
    $unit = UnitType::firstOrCreate(['name' => 'Unidad'], ['symbol' => 'und']);

    $response = $this->actingAs($cashier)->post('/sistema/carts/products/quick', [
        'barcode' => '7896004009582',
        'name' => 'Pringles Original',
        'description' => 'Producto autocompletado desde barcode',
        'category_id' => $category->id,
        'unit_type_id' => $unit->id,
        'supplier_id' => null,
        'buying_price' => 4.5,
        'selling_price' => 8.9,
        'quantity' => 12,
    ]);

    $response->assertRedirect('/sistema/pos');

    $product = Product::where('barcode', '7896004009582')->first();

    expect($product)->not->toBeNull()
        ->and($product->name)->toBe('Pringles Original')
        ->and($product->quantity)->toBe(12.0);

    $this->assertDatabaseHas('carts', [
        'user_id' => $cashier->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);
});

test('cashiers can only list their own sales through the backend', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $otherCashier = User::factory()->create(['role' => 'cajero']);
    $ownSale = posSaleFor($cashier);
    $otherSale = posSaleFor($otherCashier);

    $response = $this->actingAs($cashier)->getJson('/sistema/sales?inertia=disabled');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownSale->id);

    expect(collect($response->json('data'))->pluck('id'))->not->toContain($otherSale->id);
});

test('sale payments are stored in payments instead of legacy transactions', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $sale = posSaleFor($cashier);

    expect(Payment::where('sale_id', $sale->id)->count())->toBe(1)
        ->and(DB::table('transactions')->count())->toBe(0);
});

test('a receipt creates a sale with sequence, payments and stock movement', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $product = posProduct();
    openPosRegister($cashier, 50);
    Cart::create(['user_id' => $cashier->id, 'product_id' => $product->id, 'quantity' => 2]);

    $sale = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 10, 'received_amount' => 20],
        ],
    ], $cashier->id);

    expect($sale->full_document_number)->toBe('B001-00000001')
        ->and($sale->document_type)->toBe('receipt')
        ->and($sale->issue_status)->toBe('pending')
        ->and($sale->items)->toHaveCount(1)
        ->and($sale->payments)->toHaveCount(1)
        ->and($sale->payments->first()->change_amount)->toBe('10.00')
        ->and($product->refresh()->quantity)->toBe(8.0)
        ->and(Cart::where('user_id', $cashier->id)->exists())->toBeFalse()
        ->and(StockMovement::where('reference_type', (new Sale())->getMorphClass())->where('reference_id', $sale->id)->exists())->toBeTrue();
});

test('an invoice can be registered without requiring customer data in the pos flow', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $product = posProduct();
    openPosRegister($cashier, 50);
    Cart::create(['user_id' => $cashier->id, 'product_id' => $product->id, 'quantity' => 1]);

    $sale = app(SaleService::class)->createForUser([
        'document_type' => 'invoice',
        'payments' => [
            ['method' => 'cash', 'amount' => 5, 'received_amount' => 5],
        ],
    ], $cashier->id);

    expect($sale->document_type)->toBe('invoice')
        ->and($sale->customer_id)->toBeNull();
});

test('receipts support every configured non cash payment method', function (string $method) {
    $cashier = User::factory()->create(['role' => 'cajero']);
    openPosRegister($cashier);
    addProductToPosCart($cashier);

    $sale = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => $method, 'amount' => 5, 'operation_number' => 'OP-'.$method],
        ],
    ], $cashier->id);

    expect($sale->payments)->toHaveCount(1)
        ->and($sale->payments->first()->payment_method)->toBe($method)
        ->and($sale->payments->first()->change_amount)->toBeNull();
})->with(['yape', 'plin', 'card', 'transfer']);

test('a mixed payment sale can include cash and a digital method', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $product = posProduct();
    openPosRegister($cashier);
    addProductToPosCart($cashier, $product, 2);

    $sale = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 4, 'received_amount' => 5],
            ['method' => 'yape', 'amount' => 6, 'operation_number' => 'YP-001'],
        ],
    ], $cashier->id);

    expect($sale->payments)->toHaveCount(2)
        ->and($sale->payments->firstWhere('payment_method', 'cash')->change_amount)->toBe('1.00')
        ->and($sale->payments->sum(fn ($payment) => (float) $payment->amount))->toBe(10.0)
        ->and($product->refresh()->quantity)->toBe(8.0);
});

test('sale validation rejects duplicate payment methods and more than two payments', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    openPosRegister($cashier);
    addProductToPosCart($cashier);

    expect(fn () => app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 2, 'received_amount' => 2],
            ['method' => 'cash', 'amount' => 3, 'received_amount' => 3],
        ],
    ], $cashier->id))->toThrow(\App\Exceptions\SaleCreateException::class);

    expect(fn () => app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 2, 'received_amount' => 2],
            ['method' => 'yape', 'amount' => 2],
            ['method' => 'plin', 'amount' => 1],
        ],
    ], $cashier->id))->toThrow(\App\Exceptions\SaleCreateException::class);
});

test('a discount sale stores discounted totals', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    openPosRegister($cashier);
    addProductToPosCart($cashier);

    $sale = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'custom_discount' => ['discount' => 1, 'discount_type' => 'fixed'],
        'payments' => [
            ['method' => 'cash', 'amount' => 4, 'received_amount' => 5],
        ],
    ], $cashier->id);

    expect($sale->subtotal)->toBe('5.00')
        ->and($sale->discount_total)->toBe('1.00')
        ->and($sale->total)->toBe('4.00')
        ->and($sale->payments->first()->change_amount)->toBe('1.00');
});

test('sale items store historical cost and gross profit', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $product = posProduct();
    $product->update(['buying_price' => 2.25]);
    openPosRegister($cashier);
    addProductToPosCart($cashier, $product, 2);

    $sale = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 10, 'received_amount' => 10],
        ],
    ], $cashier->id);
    $item = $sale->items->first();
    $product->update(['buying_price' => 9.99]);

    expect($item->unit_cost)->toBe('2.25')
        ->and($item->cost_subtotal)->toBe('4.50')
        ->and($item->gross_profit)->toBe('5.50')
        ->and($item->refresh()->unit_cost)->toBe('2.25');
});

test('general discount is allocated to sale items and reduces gross profit', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    openPosRegister($cashier);
    addProductToPosCart($cashier);

    $sale = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'custom_discount' => ['discount' => 1, 'discount_type' => 'fixed'],
        'payments' => [
            ['method' => 'cash', 'amount' => 4, 'received_amount' => 4],
        ],
    ], $cashier->id);
    $item = $sale->items->first();

    expect($item->discount)->toBe('1.00')
        ->and($item->cost_subtotal)->toBe('2.00')
        ->and($item->gross_profit)->toBe('2.00');
});

test('product without cost does not block sale and marks estimated cost', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $product = posProduct();
    $product->update(['buying_price' => 0]);
    openPosRegister($cashier);
    addProductToPosCart($cashier, $product);

    $sale = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 5, 'received_amount' => 5],
        ],
    ], $cashier->id);
    $item = $sale->items->first();

    expect($item->unit_cost)->toBe('0.00')
        ->and($item->cost_subtotal)->toBe('0.00')
        ->and($item->cost_is_estimated)->toBeTrue();
});

test('a sale can include multiple products and exact stock', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $first = posProduct();
    $second = posProduct();
    $first->update(['quantity' => 2]);
    openPosRegister($cashier);
    addProductToPosCart($cashier, $first, 2);
    addProductToPosCart($cashier, $second, 1);

    $sale = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 15, 'received_amount' => 20],
        ],
    ], $cashier->id);

    expect($sale->items)->toHaveCount(2)
        ->and($first->refresh()->quantity)->toBe(0.0)
        ->and($second->refresh()->quantity)->toBe(9.0);
});

test('receipt and invoice use independent sequences and issue status pending', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $rucCustomer = posCustomer([
        'document_type' => 'ruc',
        'document_number' => '20123456789',
        'name_or_business_name' => 'Empresa SAC',
    ]);

    $saleNote = createPosSale($cashier);
    addProductToPosCart($cashier);
    $receipt = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'plin', 'amount' => 5],
        ],
    ], $cashier->id);
    addProductToPosCart($cashier);
    $invoice = app(SaleService::class)->createForUser([
        'document_type' => 'invoice',
        'customer_id' => $rucCustomer->id,
        'payments' => [
            ['method' => 'transfer', 'amount' => 5, 'bank_name' => 'BCP'],
        ],
    ], $cashier->id);

    expect($saleNote->full_document_number)->toBe('B001-00000001')
        ->and($receipt->full_document_number)->toBe('B001-00000002')
        ->and($receipt->issue_status)->toBe('pending')
        ->and($invoice->full_document_number)->toBe('F001-00000001')
        ->and($invoice->issue_status)->toBe('pending');
});

test('receipt and invoice ignore customer payload after removing customers from the pos flow', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $dniCustomer = posCustomer([
        'document_type' => 'dni',
        'document_number' => '12345678',
    ]);
    openPosRegister($cashier);
    addProductToPosCart($cashier);

    $receiptWithoutCustomer = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 5, 'received_amount' => 5],
        ],
    ], $cashier->id);
    addProductToPosCart($cashier);
    $receiptWithCustomer = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'customer_id' => $dniCustomer->id,
        'payments' => [
            ['method' => 'card', 'amount' => 5],
        ],
    ], $cashier->id);
    addProductToPosCart($cashier);

    $invoiceWithCustomer = app(SaleService::class)->createForUser([
        'document_type' => 'invoice',
        'customer_id' => $dniCustomer->id,
        'payments' => [
            ['method' => 'cash', 'amount' => 5, 'received_amount' => 5],
        ],
    ], $cashier->id);

    expect($receiptWithoutCustomer->customer_id)->toBeNull()
        ->and($receiptWithCustomer->customer_id)->toBeNull()
        ->and($invoiceWithCustomer->customer_id)->toBeNull();
});

test('customer endpoints are removed from the operational flow', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cashier = User::factory()->create(['role' => 'cajero']);

    $this->actingAs($admin)->get('/sistema/customers')->assertNotFound();
    $this->actingAs($admin)->post('/sistema/customers', [
        'document_type' => 'dni',
        'document_number' => '12345678',
        'name' => 'Juan',
        'name_or_business_name' => 'Juan Perez',
    ])->assertNotFound();

    $product = posProduct();
    Cart::create(['user_id' => $cashier->id, 'product_id' => $product->id, 'quantity' => 1]);

    $this->actingAs($cashier)->getJson('/sistema/customers?inertia=disabled&keyword=12345678')->assertNotFound();
    $this->actingAs($cashier)->postJson('/sistema/customers/quick', [
        'document_type' => 'dni',
        'document_number' => '87654321',
        'name' => 'Maria Lopez',
        'name_or_business_name' => 'Maria Lopez',
        'phone' => '999888777',
    ])->assertNotFound();

    $this->assertDatabaseHas('carts', [
        'user_id' => $cashier->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);
});

test('invoice ignores optional customer data without blocking the pos sale', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $badRucCustomer = posCustomer([
        'document_type' => 'ruc',
        'document_number' => '123',
    ]);
    $validRucCustomer = posCustomer([
        'document_type' => 'ruc',
        'document_number' => '20123456789',
        'name_or_business_name' => 'Empresa Valida SAC',
    ]);
    openPosRegister($cashier);
    addProductToPosCart($cashier);

    $invoiceWithIncompleteCustomer = app(SaleService::class)->createForUser([
        'document_type' => 'invoice',
        'customer_id' => $badRucCustomer->id,
        'payments' => [
            ['method' => 'cash', 'amount' => 5, 'received_amount' => 5],
        ],
    ], $cashier->id);

    addProductToPosCart($cashier);

    $sale = app(SaleService::class)->createForUser([
        'document_type' => 'invoice',
        'customer_id' => $validRucCustomer->id,
        'payments' => [
            ['method' => 'cash', 'amount' => 5, 'received_amount' => 5],
        ],
    ], $cashier->id);

    expect($invoiceWithIncompleteCustomer->document_type)->toBe('invoice')
        ->and($invoiceWithIncompleteCustomer->customer_id)->toBeNull()
        ->and($sale->document_type)->toBe('invoice')
        ->and($sale->customer_id)->toBeNull();
});

test('dashboard metrics use sales payments and sale items as the source', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    openPosRegister($cashier);
    addProductToPosCart($cashier);

    app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'custom_discount' => ['discount' => 1, 'discount_type' => 'fixed'],
        'payments' => [
            ['method' => 'yape', 'amount' => 4, 'operation_number' => 'YP-100'],
        ],
    ], $cashier->id);

    $data = app(DashboardService::class)->getData(now()->format('Y-m'));

    expect($data['total_orders']['selected'])->toBe(1)
        ->and($data['total_profit']['selected'])->toBe(4.0)
        ->and($data['total_loss']['selected'])->toBe(2.0)
        ->and($data['cost_of_goods_sold']['selected'])->toBe(2.0)
        ->and($data['month_discounts']['selected'])->toBe(1.0)
        ->and($data['operating_result']['selected'])->toBe(2.0)
        ->and($data['payments_by_method'][0]['method'])->toBe('yape')
        ->and($data['top_products'][0]['name'])->toBe('Agua mineral')
        ->and($data['products_with_highest_profit'][0]['gross_profit'])->toBe(2.0);
});

test('dashboard keeps gross profit separate from expenses and operating result', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    openPosRegister($cashier);
    addProductToPosCart($cashier);
    app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 5, 'received_amount' => 5],
        ],
    ], $cashier->id);
    Expense::create([
        'name' => 'Bolsa de reparto',
        'amount' => 1,
        'expense_date' => now()->toDateString(),
    ]);

    $data = app(DashboardService::class)->getData(now()->format('Y-m'));

    expect($data['total_loss']['selected'])->toBe(3.0)
        ->and($data['total_expense']['selected'])->toBe(1.0)
        ->and($data['operating_result']['selected'])->toBe(2.0);
});

test('dashboard excludes cancelled sales from income cost and gross profit', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $sale = createPosSale($cashier);
    $sale->update(['status' => 'cancelled']);

    $data = app(DashboardService::class)->getData(now()->format('Y-m'));

    expect($data['total_orders']['selected'])->toBe(0)
        ->and($data['total_profit']['selected'])->toBe(0.0)
        ->and($data['cost_of_goods_sold']['selected'])->toBe(0.0)
        ->and($data['total_loss']['selected'])->toBe(0.0);
});

test('sales report export uses sales costs profits and supports payment method filters', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    openPosRegister($cashier);
    addProductToPosCart($cashier);
    $cashSale = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 5, 'received_amount' => 5],
        ],
    ], $cashier->id);

    addProductToPosCart($cashier);
    $yapeSale = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'yape', 'amount' => 5, 'operation_number' => 'YP-200'],
        ],
    ], $cashier->id);

    $allRows = (new VentasExport())->collection();
    $yapeRows = (new VentasExport(['payment_method' => 'yape']))->collection();

    expect($allRows)->toHaveCount(2)
        ->and($allRows->pluck('comprobante'))->toContain($cashSale->full_document_number)
        ->and($yapeRows)->toHaveCount(1)
        ->and($yapeRows->first()['comprobante'])->toBe($yapeSale->full_document_number)
        ->and($yapeRows->first()['costo'])->toBe(2.0)
        ->and($yapeRows->first()['utilidad_bruta'])->toBe(3.0)
        ->and($yapeRows->first()['margen_bruto'])->toBe(60.0);
});

test('reports page accepts product cashier payment and document filters', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cashier = User::factory()->create(['role' => 'cajero']);
    $product = posProduct();
    openPosRegister($cashier);
    addProductToPosCart($cashier, $product);
    app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 5, 'received_amount' => 5],
        ],
    ], $cashier->id);

    $this->actingAs($admin)
        ->get('/sistema/reports?product_id='.$product->id.'&cashier_id='.$cashier->id.'&payment_method=cash&document_type=receipt')
        ->assertOk();
});

test('sales report pdf view shows income cost profit and margin', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    openPosRegister($cashier);
    addProductToPosCart($cashier);
    $sale = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 5, 'received_amount' => 5],
        ],
    ], $cashier->id);

    $html = view('reports.ventas-pdf', [
        'ventas' => Sale::with(['customer', 'cashier', 'payments', 'items'])->whereKey($sale->id)->get(),
        'filters' => [],
        'paymentSummary' => [['label' => 'Efectivo', 'count' => 1, 'total' => 5]],
        'productSummary' => [['name' => 'Agua mineral', 'quantity' => 1, 'total' => 5, 'cost' => 2, 'gross_profit' => 3, 'margin' => 60, 'discount' => 0]],
        'categorySummary' => [['name' => 'Bebidas', 'quantity' => 1, 'total' => 5, 'cost' => 2, 'gross_profit' => 3, 'margin' => 60]],
        'cashierSummary' => [['name' => $cashier->name, 'count' => 1, 'total' => 5]],
        'documentSummary' => [['label' => 'Boleta', 'count' => 1, 'total' => 5]],
        'itemSummary' => ['income' => 5, 'cost' => 2, 'gross_profit' => 3, 'discount' => 0, 'quantity' => 1, 'margin' => 60],
    ])->render();

    expect($html)->toContain('Ingreso neto productos')
        ->and($html)->toContain('Costo vendido')
        ->and($html)->toContain('Utilidad bruta')
        ->and($html)->toContain('Margen');
});

test('cash register cannot be opened twice by the same cashier', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    openPosRegister($cashier);

    $this->actingAs($cashier)->post('/sistema/cash-registers/open', [
        'opening_amount' => 20,
    ])->assertSessionHasErrors('opening_amount');
});

test('opening creates a cash movement and is not counted twice', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $register = openPosRegister($cashier, 100);
    $summary = app(CashRegisterService::class)->summary($register);

    expect(CashMovement::where('cash_register_id', $register->id)->where('type', CashMovementTypeEnum::OPENING->value)->count())->toBe(1)
        ->and($summary['expected_cash'])->toBe(100.0);
});

test('mixed sale creates one cash movement per payment without duplicates', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $register = openPosRegister($cashier, 100);
    addProductToPosCart($cashier, quantity: 2);

    $sale = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 6, 'received_amount' => 10],
            ['method' => 'yape', 'amount' => 4, 'operation_number' => 'YP-1'],
        ],
    ], $cashier->id);

    foreach ($sale->payments as $payment) {
        app(CashRegisterService::class)->createSaleMovement($register, $sale, $payment, $cashier->id);
    }

    expect(CashMovement::where('sale_id', $sale->id)->where('type', 'sale')->count())->toBe(2)
        ->and(CashMovement::whereIn('payment_id', $sale->payments->pluck('id'))->count())->toBe(2)
        ->and(app(CashRegisterService::class)->summary($register)['expected_cash'])->toBe(106.0);
});

test('manual income withdrawal and adjustments change expected cash', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $register = openPosRegister($admin, 100);

    $this->actingAs($admin)->post("/sistema/cash-registers/{$register->id}/movements", [
        'type' => 'manual_income',
        'amount' => 20,
        'description' => 'Cambio adicional',
    ])->assertRedirect();
    $this->actingAs($admin)->post("/sistema/cash-registers/{$register->id}/movements", [
        'type' => 'withdrawal',
        'amount' => 10,
        'description' => 'Depósito parcial',
        'confirmed' => true,
    ])->assertRedirect();
    $this->actingAs($admin)->post("/sistema/cash-registers/{$register->id}/movements", [
        'type' => 'adjustment',
        'direction' => 'income',
        'amount' => 5,
        'description' => 'Ajuste positivo',
    ])->assertRedirect();
    $this->actingAs($admin)->post("/sistema/cash-registers/{$register->id}/movements", [
        'type' => 'adjustment',
        'direction' => 'expense',
        'amount' => 3,
        'description' => 'Ajuste negativo',
    ])->assertRedirect();

    expect(app(CashRegisterService::class)->summary($register)['expected_cash'])->toBe(112.0);
});

test('withdrawal requires explicit confirmation', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $register = openPosRegister($cashier, 50);

    $this->actingAs($cashier)->post("/sistema/cash-registers/{$register->id}/movements", [
        'type' => 'withdrawal',
        'amount' => 10,
        'description' => 'Retiro sin confirmar',
    ])->assertSessionHasErrors('confirmed');
});

test('cashier cannot withdraw more cash than available or create adjustments', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $register = openPosRegister($cashier, 50);

    $this->actingAs($cashier)->post("/sistema/cash-registers/{$register->id}/movements", [
        'type' => 'withdrawal',
        'amount' => 60,
        'description' => 'Retiro excesivo',
        'confirmed' => true,
    ])->assertSessionHasErrors('amount');

    $this->actingAs($cashier)->post("/sistema/cash-registers/{$register->id}/movements", [
        'type' => 'adjustment',
        'direction' => 'income',
        'amount' => 1,
        'description' => 'Ajuste no permitido',
    ])->assertForbidden();
});

test('digital payments do not change physical cash expected', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $register = openPosRegister($cashier, 100);
    addProductToPosCart($cashier);

    app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'yape', 'amount' => 5, 'operation_number' => 'YP-200'],
        ],
    ], $cashier->id);

    $summary = app(CashRegisterService::class)->summary($register);

    expect($summary['expected_cash'])->toBe(100.0)
        ->and($summary['system_amounts']['yape'])->toBe(5.0);
});

test('closing requires explicit confirmation', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $register = openPosRegister($cashier, 100);

    $this->actingAs($cashier)->put("/sistema/cash-registers/{$register->id}/close", [
        'declared_amounts' => ['cash' => 100, 'yape' => 0, 'plin' => 0, 'card' => 0, 'transfer' => 0],
    ])->assertSessionHasErrors('confirmed');
});

test('closing a cash register includes cash sales but not digital sales', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $register = openPosRegister($cashier, 100);
    addProductToPosCart($cashier);
    app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 5, 'received_amount' => 10],
        ],
    ], $cashier->id);
    addProductToPosCart($cashier);
    app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'yape', 'amount' => 5],
        ],
    ], $cashier->id);

    $this->actingAs($cashier)->put("/sistema/cash-registers/{$register->id}/close", [
        'declared_amounts' => [
            'cash' => 105,
            'yape' => 5,
            'plin' => 0,
            'card' => 0,
            'transfer' => 0,
        ],
        'confirmed' => true,
    ])->assertRedirect();

    expect($register->refresh()->expected_amount)->toBe('105.00')
        ->and($register->declared_amounts['cash'])->toEqual(105.0)
        ->and($register->system_amounts['yape'])->toEqual(5.0);
});

test('admin can close another employee open cash register', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cashier = User::factory()->create(['role' => 'cajero']);
    $register = openPosRegister($cashier, 80);

    $this->actingAs($admin)->put("/sistema/cash-registers/{$register->id}/close", [
        'declared_amounts' => ['cash' => 80],
        'confirmed' => true,
    ])->assertRedirect();

    expect($register->refresh()->status)->toBe('closed')
        ->and($register->closing_amount)->toBe('80.00');
});

test('cashier cannot close another employee cash register', function () {
    $owner = User::factory()->create(['role' => 'cajero']);
    $otherCashier = User::factory()->create(['role' => 'cajero']);
    $register = openPosRegister($owner, 80);

    $this->actingAs($otherCashier)->put("/sistema/cash-registers/{$register->id}/close", [
        'declared_amounts' => ['cash' => 80],
        'confirmed' => true,
    ])->assertForbidden();

    expect($register->refresh()->status)->toBe('open');
});

test('closing with differences is allowed and does not create a financial movement', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $register = openPosRegister($cashier, 100);
    $before = CashMovement::count();

    $this->actingAs($cashier)->put("/sistema/cash-registers/{$register->id}/close", [
        'declared_amounts' => ['cash' => 101, 'yape' => 0, 'plin' => 0, 'card' => 0, 'transfer' => 0],
        'confirmed' => true,
    ])->assertRedirect();

    expect(CashMovement::count())->toBe($before)
        ->and($register->refresh()->difference_status)->toBe('surplus')
        ->and($register->total_difference)->toBe('1.00');
});

test('closing supports shortage and denomination validation', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $register = openPosRegister($cashier, 100);

    $this->actingAs($cashier)->put("/sistema/cash-registers/{$register->id}/close", [
        'declared_amounts' => ['cash' => 90, 'yape' => 0, 'plin' => 0, 'card' => 0, 'transfer' => 0],
        'denominations' => ['50' => 1, '20' => 2],
        'closing_notes' => 'Faltante sustentado.',
        'confirmed' => true,
    ])->assertRedirect();

    expect($register->refresh()->difference_status)->toBe('shortage')
        ->and($register->denominations['50'])->toBe(1);
});

test('register cannot be closed twice and closed registers reject sales and movements', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $register = openPosRegister($cashier, 100);

    $this->actingAs($cashier)->put("/sistema/cash-registers/{$register->id}/close", [
        'declared_amounts' => ['cash' => 100, 'yape' => 0, 'plin' => 0, 'card' => 0, 'transfer' => 0],
        'confirmed' => true,
    ])->assertRedirect();
    $this->actingAs($cashier)->put("/sistema/cash-registers/{$register->id}/close", [
        'declared_amounts' => ['cash' => 100, 'yape' => 0, 'plin' => 0, 'card' => 0, 'transfer' => 0],
        'confirmed' => true,
    ])->assertSessionHasErrors('cash_register_id');

    $this->actingAs($cashier)->post("/sistema/cash-registers/{$register->id}/movements", [
        'type' => 'manual_income',
        'amount' => 1,
        'description' => 'No permitido',
    ])->assertSessionHasErrors('cash_register_id');

    addProductToPosCart($cashier);
    expect(fn () => app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [['method' => 'cash', 'amount' => 5, 'received_amount' => 5]],
    ], $cashier->id))->toThrow(SaleCreateException::class);
});

test('admin review preserves cashier declared amounts', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $admin = User::factory()->create(['role' => 'admin']);
    $register = openPosRegister($cashier, 100);

    $this->actingAs($cashier)->put("/sistema/cash-registers/{$register->id}/close", [
        'declared_amounts' => ['cash' => 100, 'yape' => 0, 'plin' => 0, 'card' => 0, 'transfer' => 0],
        'confirmed' => true,
    ])->assertRedirect();
    $declared = $register->refresh()->declared_amounts;

    $this->actingAs($admin)->put("/sistema/cash-registers/{$register->id}/review", [
        'review_notes' => 'Revisado sin cambios.',
    ])->assertRedirect();

    expect($register->refresh()->status)->toBe('reviewed')
        ->and($register->declared_amounts)->toBe($declared)
        ->and($register->reviewed_by)->toBe($admin->id);
});

test('cashier cannot access or close another cashier register but admin can view all', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $other = User::factory()->create(['role' => 'cajero']);
    $admin = User::factory()->create(['role' => 'admin']);
    $register = openPosRegister($other, 100);

    $this->actingAs($cashier)->put("/sistema/cash-registers/{$register->id}/close", [
        'declared_amounts' => ['cash' => 100, 'yape' => 0, 'plin' => 0, 'card' => 0, 'transfer' => 0],
        'confirmed' => true,
    ])->assertForbidden();
    $this->actingAs($admin)->getJson('/sistema/cash-registers?inertia=disabled')->assertOk();
});

test('cash register report detects payment and cash movement discrepancies', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $admin = User::factory()->create(['role' => 'admin']);
    openPosRegister($cashier, 100);
    addProductToPosCart($cashier);

    $sale = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [['method' => 'cash', 'amount' => 5, 'received_amount' => 5]],
    ], $cashier->id);

    CashMovement::where('payment_id', $sale->payments->first()->id)->delete();

    $this->actingAs($admin)
        ->getJson('/sistema/cash-registers?inertia=disabled')
        ->assertOk()
        ->assertJsonPath('saleMovementDiscrepancies', 1);
});

test('expense only creates cash movement when paid from current register', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/sistema/expenses', [
        'name' => 'Gasto administrativo',
        'amount' => 10,
        'expense_date' => now()->toDateString(),
    ])->assertRedirect();

    expect(CashMovement::where('type', 'expense')->count())->toBe(0);

    $register = openPosRegister($admin, 100);
    $this->actingAs($admin)->post('/sistema/expenses', [
        'name' => 'Compra desde caja',
        'amount' => 10,
        'expense_date' => now()->toDateString(),
        'paid_from_cash_register' => true,
    ])->assertRedirect();

    expect(CashMovement::where('type', 'expense')->count())->toBe(1)
        ->and(app(CashRegisterService::class)->summary($register)['expected_cash'])->toBe(90.0);
});

test('stock failures and duplicate submits do not create invalid sales', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $product = posProduct();
    $product->update(['quantity' => 1]);
    openPosRegister($cashier);
    addProductToPosCart($cashier, $product, 2);

    expect(fn () => app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 10, 'received_amount' => 10],
        ],
    ], $cashier->id))->toThrow(\App\Exceptions\SaleCreateException::class);

    expect(Sale::count())->toBe(0)
        ->and(SaleItem::count())->toBe(0)
        ->and($product->refresh()->quantity)->toBe(1.0)
        ->and(Cart::where('user_id', $cashier->id)->exists())->toBeTrue();

    Cart::where('user_id', $cashier->id)->delete();
    addProductToPosCart($cashier, $product, 1);
    app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 5, 'received_amount' => 5],
        ],
    ], $cashier->id);

    expect(fn () => app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 5, 'received_amount' => 5],
        ],
    ], $cashier->id))->toThrow(\App\Exceptions\SaleCreateException::class);

    expect(Sale::count())->toBe(1)
        ->and(SaleItem::count())->toBe(1);
});

test('failed sale creation rolls back sequence, stock and cart', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $product = posProduct();
    $register = openPosRegister($cashier);
    addProductToPosCart($cashier, $product);

    Sale::create([
        'cash_register_id' => $register->id,
        'cashier_id' => $cashier->id,
        'document_type' => 'receipt',
        'document_series' => 'B001',
        'document_number' => 1,
        'full_document_number' => 'B001-00000001',
        'issue_status' => 'pending',
        'subtotal' => 5,
        'discount_total' => 0,
        'taxable_amount' => 5,
        'igv' => 0,
        'total' => 5,
        'status' => 'paid',
        'sold_at' => now(),
    ]);

    expect(fn () => app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 5, 'received_amount' => 5],
        ],
    ], $cashier->id))->toThrow(QueryException::class);

    expect(DocumentSequence::where('document_type', 'receipt')->first()->current_number)->toBe(0)
        ->and(SaleItem::count())->toBe(0)
        ->and($product->refresh()->quantity)->toBe(10.0)
        ->and(Cart::where('user_id', $cashier->id)->exists())->toBeTrue();
});

test('legacy order and transaction urls redirect to the new sales domain', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);

    $this->actingAs($cashier)->get('/sistema/orders')->assertRedirect('/sistema/sales');
    $this->actingAs($cashier)->post('/sistema/orders')->assertRedirect('/sistema/sales');
    $this->actingAs($cashier)->get('/sistema/transactions')->assertRedirect('/sistema/reports');
});

test('dashboard and main reports no longer import orders', function () {
    $dashboard = file_get_contents(app_path('Services/DashboardService.php'));
    $reports = file_get_contents(app_path('Http/Controllers/ReportController.php'));
    $export = file_get_contents(app_path('Exports/VentasExport.php'));

    expect($dashboard)->not->toContain('App\\Models\\Order')
        ->and($dashboard)->not->toContain('Order::')
        ->and($reports)->not->toContain('App\\Models\\Order')
        ->and($reports)->not->toContain('Order::')
        ->and($export)->not->toContain('App\\Models\\Order')
        ->and($export)->not->toContain('Order::');
});

test('cashier permissions apply to sale detail, thermal and pdf documents', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $otherCashier = User::factory()->create(['role' => 'cajero']);
    $admin = User::factory()->create(['role' => 'admin']);
    $ownSale = createPosSale($cashier);
    $otherSale = createPosSale($otherCashier);

    $this->actingAs($cashier)->get("/sistema/sales/{$ownSale->id}")->assertOk();
    $this->actingAs($cashier)->get("/sistema/sales/{$otherSale->id}")->assertNotFound();
    $this->actingAs($cashier)->get("/sistema/sales/{$otherSale->id}/thermal")->assertNotFound();
    $this->actingAs($cashier)->get("/sistema/sales/{$otherSale->id}/pdf")->assertNotFound();
    $this->actingAs($admin)->get("/sistema/sales/{$otherSale->id}")->assertOk();
});

test('thermal reprint and pdf download are available for a completed sale', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $sale = createPosSale($cashier);

    $this->actingAs($cashier)
        ->get("/sistema/sales/{$sale->id}/thermal")
        ->assertOk()
        ->assertSee('BOLETA')
        ->assertSee('Documento interno. No válido como comprobante tributario');

    $this->actingAs($cashier)
        ->get("/sistema/sales/{$sale->id}/pdf")
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('a new sale after a completed sale gets the next number', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $first = createPosSale($cashier);
    addProductToPosCart($cashier);
    $second = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [
            ['method' => 'cash', 'amount' => 5, 'received_amount' => 5],
        ],
    ], $cashier->id);

    expect($first->full_document_number)->toBe('B001-00000001')
        ->and($second->full_document_number)->toBe('B001-00000002');
});

test('cash register has exactly one opening movement and ticket uses that movement', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $register = openPosRegister($cashier, 100);
    $opening = CashMovement::where('cash_register_id', $register->id)->where('type', 'opening')->first();
    $opening->update(['amount' => 123]);
    $register->update(['opening_amount' => 999]);

    expect(CashMovement::where('cash_register_id', $register->id)->where('type', 'opening')->count())->toBe(1);

    $this->actingAs($cashier)
        ->get("/sistema/cash-registers/{$register->id}/movements/{$opening->id}/thermal")
        ->assertOk()
        ->assertSee('APERTURA DE CAJA')
        ->assertSee('S/ 123.00')
        ->assertDontSee('S/ 999.00');
});

test('cash register document permissions protect foreign boxes and admin report', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $otherCashier = User::factory()->create(['role' => 'cajero']);
    $admin = User::factory()->create(['role' => 'admin']);
    $ownRegister = openPosRegister($cashier, 100);
    $otherRegister = openPosRegister($otherCashier, 80);

    $this->actingAs($cashier)->get("/sistema/cash-registers/{$ownRegister->id}/thermal")->assertOk();
    $this->actingAs($cashier)->get("/sistema/cash-registers/{$otherRegister->id}/thermal")->assertForbidden();
    $this->actingAs($cashier)->get("/sistema/cash-registers/{$ownRegister->id}/admin-report/pdf")->assertForbidden();
    $this->actingAs($admin)->get("/sistema/cash-registers/{$otherRegister->id}/admin-report/pdf")->assertOk();
});

test('movement document returns not found when it does not belong to the register', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $first = openPosRegister($cashier, 100);
    $other = User::factory()->create(['role' => 'cajero']);
    $second = openPosRegister($other, 100);
    $movement = CashMovement::where('cash_register_id', $second->id)->where('type', 'opening')->first();

    $this->actingAs($cashier)
        ->get("/sistema/cash-registers/{$first->id}/movements/{$movement->id}/thermal")
        ->assertNotFound();
});

test('closing thermal is summarized and administrative pdf includes detail', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $admin = User::factory()->create(['role' => 'admin']);
    $register = openPosRegister($cashier, 100);

    for ($i = 1; $i <= 12; $i++) {
        $this->actingAs($cashier)->post("/sistema/cash-registers/{$register->id}/movements", [
            'type' => 'manual_income',
            'amount' => 1,
            'description' => "Ingreso de prueba {$i}",
        ])->assertRedirect();
    }

    $this->actingAs($cashier)->put("/sistema/cash-registers/{$register->id}/close", [
        'declared_amounts' => ['cash' => 112, 'yape' => 0, 'plin' => 0, 'card' => 0, 'transfer' => 0],
        'confirmed' => true,
    ])->assertRedirect();

    $this->actingAs($cashier)
        ->get("/sistema/cash-registers/{$register->id}/thermal")
        ->assertOk()
        ->assertSee('CIERRE Y ARQUEO')
        ->assertSee('Total ventas')
        ->assertDontSee('Ingreso de prueba 12');

    $this->actingAs($admin)
        ->get("/sistema/cash-registers/{$register->id}/admin-report/pdf")
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('cash register timeline is paginated', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $register = openPosRegister($cashier, 100);

    for ($i = 1; $i <= 12; $i++) {
        $this->actingAs($cashier)->post("/sistema/cash-registers/{$register->id}/movements", [
            'type' => 'manual_income',
            'amount' => 1,
            'description' => "Ingreso {$i}",
        ])->assertRedirect();
    }

    $response = $this->actingAs($cashier)->getJson('/sistema/cash-registers?inertia=disabled')->assertOk();

    expect($response->json('timeline.total'))->toBe(13)
        ->and($response->json('timeline.data'))->toHaveCount(10);
});

test('cash reconciliation detects missing movement and missing payment', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $admin = User::factory()->create(['role' => 'admin']);
    $register = openPosRegister($cashier, 100);
    addProductToPosCart($cashier);

    $sale = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [['method' => 'cash', 'amount' => 5, 'received_amount' => 5]],
    ], $cashier->id);

    CashMovement::where('payment_id', $sale->payments->first()->id)->delete();
    CashMovement::create([
        'cash_register_id' => $register->id,
        'user_id' => $cashier->id,
        'sale_id' => $sale->id,
        'type' => 'sale',
        'direction' => 'income',
        'payment_method' => 'yape',
        'amount' => 8,
        'description' => 'Movimiento sin payment',
        'occurred_at' => now(),
    ]);

    $this->actingAs($admin)
        ->getJson('/sistema/cash-registers/reconciliation?inertia=disabled&status=missing_movement')
        ->assertOk()
        ->assertJsonPath('rows.0.status', 'missing_movement');

    $this->actingAs($admin)
        ->getJson('/sistema/cash-registers/reconciliation?inertia=disabled&status=missing_payment')
        ->assertOk()
        ->assertJsonPath('rows.0.status', 'missing_payment');
});

test('cash reconciliation filters by cashier and method', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $otherCashier = User::factory()->create(['role' => 'cajero']);
    $admin = User::factory()->create(['role' => 'admin']);
    createPosSale($cashier);
    openPosRegister($otherCashier, 100);
    addProductToPosCart($otherCashier);
    app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'payments' => [['method' => 'yape', 'amount' => 5]],
    ], $otherCashier->id);

    $this->actingAs($admin)
        ->getJson("/sistema/cash-registers/reconciliation?inertia=disabled&cashier_id={$otherCashier->id}&method=yape")
        ->assertOk()
        ->assertJsonPath('rows.0.cashier_id', $otherCashier->id)
        ->assertJsonPath('rows.0.method', 'yape');
});

test('cash documents keep internal message and movement impact texts', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $register = openPosRegister($admin, 100);

    $this->actingAs($admin)->post("/sistema/cash-registers/{$register->id}/movements", [
        'type' => 'manual_income',
        'amount' => 20,
        'description' => 'Ingreso para sencillo',
    ])->assertRedirect();
    $income = CashMovement::where('type', 'manual_income')->first();

    $this->actingAs($admin)->post("/sistema/cash-registers/{$register->id}/movements", [
        'type' => 'withdrawal',
        'amount' => 10,
        'description' => 'Retiro a bóveda',
        'confirmed' => true,
    ])->assertRedirect();
    $withdrawal = CashMovement::where('type', 'withdrawal')->first();

    $this->actingAs($admin)
        ->get("/sistema/cash-registers/{$register->id}/movements/{$income->id}/thermal")
        ->assertOk()
        ->assertSee('Incrementa el saldo esperado de caja')
        ->assertSee('Documento interno. No válido como comprobante tributario');

    $this->actingAs($admin)
        ->get("/sistema/cash-registers/{$register->id}/movements/{$withdrawal->id}/thermal")
        ->assertOk()
        ->assertSee('Reduce el saldo esperado de caja')
        ->assertSee('Retiro a bóveda');
});

test('adjustment document shows related movement and administrator responsible', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $register = openPosRegister($admin, 100);
    $opening = CashMovement::where('cash_register_id', $register->id)->where('type', 'opening')->first();

    $this->actingAs($admin)->post("/sistema/cash-registers/{$register->id}/movements", [
        'type' => 'adjustment',
        'direction' => 'expense',
        'amount' => 5,
        'description' => 'Corrección de apertura',
        'reference' => 'AJ-001',
        'reversed_movement_id' => $opening->id,
    ])->assertRedirect();

    $adjustment = CashMovement::where('type', 'adjustment')->first();

    $this->actingAs($admin)
        ->get("/sistema/cash-registers/{$register->id}/movements/{$adjustment->id}/pdf")
        ->assertOk()
        ->assertHeader('content-disposition');

    $this->actingAs($admin)
        ->get("/sistema/cash-registers/{$register->id}/movements/{$adjustment->id}/thermal")
        ->assertOk()
        ->assertSee('AJUSTE')
        ->assertSee('AJ-001')
        ->assertSee('#'.$opening->id)
        ->assertSee($admin->name);
});

test('cash register documents do not expose another cashier information', function () {
    $cashier = User::factory()->create(['role' => 'cajero', 'name' => 'Cajero Propio']);
    $otherCashier = User::factory()->create(['role' => 'cajero', 'name' => 'Cajero Ajeno']);
    $ownRegister = openPosRegister($cashier, 100);
    openPosRegister($otherCashier, 200);

    $this->actingAs($cashier)
        ->get("/sistema/cash-registers/{$ownRegister->id}/thermal")
        ->assertOk()
        ->assertSee('Cajero Propio')
        ->assertDontSee('Cajero Ajeno');
});

test('admin can open and update business settings but cashier cannot', function () {
    $this->withoutVite();
    $admin = User::factory()->create(['role' => 'admin']);
    $cashier = User::factory()->create(['role' => 'cajero']);

    $this->actingAs($admin)
        ->get('/sistema/settings')
        ->assertOk()
        ->assertSee('Settings\\/Business', false);
    $this->actingAs($cashier)->get('/sistema/settings')->assertForbidden();

    $payload = array_merge(app(BusinessSettingsService::class)->all(), [
        'business_name' => 'Minimarket Central',
        'currency_symbol' => 'S/.',
        'thermal_show_logo' => false,
        'thermal_show_customer' => true,
        'thermal_show_payment_refs' => false,
        'auto_open_print_dialog' => true,
        'return_to_pos_after_print' => false,
        'keep_sale_confirmation' => true,
    ]);
    unset($payload['logo_url'], $payload['date_time_format']);

    $this->actingAs($admin)->post('/sistema/settings', $payload)->assertRedirect('/sistema/settings');
    $this->actingAs($cashier)->post('/sistema/settings', $payload)->assertForbidden();

    expect(app(BusinessSettingsService::class)->getBusinessName())->toBe('Minimarket Central')
        ->and(app(BusinessSettingsService::class)->public()['thermal_show_logo'])->toBeFalse()
        ->and(app(BusinessSettingsService::class)->public()['auto_open_print_dialog'])->toBeTrue();
});

test('business settings validation rejects invalid timezone and paper width', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $payload = array_merge(app(BusinessSettingsService::class)->all(), [
        'timezone' => 'No/Existe',
        'thermal_paper_width' => 58,
    ]);
    unset($payload['logo_url'], $payload['date_time_format']);

    $this->actingAs($admin)
        ->from('/sistema/settings')
        ->post('/sistema/settings', $payload)
        ->assertRedirect('/sistema/settings')
        ->assertSessionHasErrors(['timezone', 'thermal_paper_width']);
});

test('business settings defaults are idempotent and do not overwrite existing values', function () {
    settings()->set('business_name', 'Bodega Guardada');

    app(BusinessSettingsService::class)->seedDefaults();
    app(BusinessSettingsService::class)->seedDefaults();

    expect(settings()->get('business_name'))->toBe('Bodega Guardada')
        ->and(settings()->get('currency_code'))->toBe('PEN')
        ->and(settings()->get('timezone'))->toBe('America/Lima');
});

test('valid logo is stored and replacing it removes previous logo safely', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    $service = app(BusinessSettingsService::class);
    $payload = array_merge($service->all(), ['business_name' => 'Con Logo']);
    unset($payload['logo_url'], $payload['date_time_format']);

    $this->actingAs($admin)
        ->post('/sistema/settings', array_merge($payload, [
            'logo_path' => UploadedFile::fake()->image('logo.png', 400, 200)->size(128),
        ]))
        ->assertRedirect('/sistema/settings');

    $firstLogo = $service->getLogoPath();
    Storage::disk('public')->assertExists('business/'.$firstLogo);

    $this->actingAs($admin)
        ->post('/sistema/settings', array_merge($payload, [
            'logo_path' => UploadedFile::fake()->image('nuevo.jpg', 400, 200)->size(128),
        ]))
        ->assertRedirect('/sistema/settings');

    Storage::disk('public')->assertMissing('business/'.$firstLogo);
    Storage::disk('public')->assertExists('business/'.$service->getLogoPath());
});

test('invalid logo files are rejected and deleting logo keeps text fallback', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    $service = app(BusinessSettingsService::class);
    $payload = array_merge($service->all(), ['business_name' => 'Sin Logo']);
    unset($payload['logo_url'], $payload['date_time_format']);

    $this->actingAs($admin)
        ->from('/sistema/settings')
        ->post('/sistema/settings', array_merge($payload, [
            'logo_path' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ]))
        ->assertRedirect('/sistema/settings')
        ->assertSessionHasErrors('logo_path');

    settings()->set('business_name', 'Sin Logo');
    $service->storeLogo(UploadedFile::fake()->image('logo.jpg', 200, 120));
    $this->actingAs($admin)->delete('/sistema/settings/logo')->assertRedirect('/sistema/settings');

    expect($service->getLogoPath())->toBeNull()
        ->and($service->getLogoUrl())->toBeNull()
        ->and($service->getBusinessName())->toBe('Sin Logo');
});

test('sale documents use business settings, currency symbol and configured timezone', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    settings()->set([
        'business_name' => 'Bodega Lima',
        'currency_symbol' => 'PEN',
        'timezone' => 'America/Lima',
        'date_format' => 'd/m/Y',
        'time_format' => 'H:i',
        'receipt_footer' => 'Vuelva pronto.',
    ]);
    $sale = createPosSale($cashier);
    $sale->update(['sold_at' => '2026-07-22 04:30:00']);

    $this->actingAs($cashier)
        ->get("/sistema/sales/{$sale->id}/thermal")
        ->assertOk()
        ->assertSee('Bodega Lima')
        ->assertSee('21/07/2026 23:30')
        ->assertSee('PEN 5.00')
        ->assertSee('Documento interno. No válido como comprobante tributario')
        ->assertSee('Vuelva pronto.')
        ->assertDontSee('Laravel');

    $this->actingAs($cashier)
        ->get("/sistema/sales/{$sale->id}/pdf")
        ->assertOk()
        ->assertHeader('content-disposition');

    expect(DocumentPrintLog::where('sale_id', $sale->id)->where('action_type', 'download_requested')->exists())->toBeTrue();
});

test('cash documents use central business settings and configured cash register name', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    settings()->set(['business_name' => 'Caja Norte', 'cash_register_name' => 'Turno']);
    $register = openPosRegister($cashier, 70);
    $opening = CashMovement::where('cash_register_id', $register->id)->where('type', 'opening')->first();

    $this->actingAs($cashier)
        ->get("/sistema/cash-registers/{$register->id}/movements/{$opening->id}/thermal")
        ->assertOk()
        ->assertSee('Caja Norte')
        ->assertSee('Turno:')
        ->assertSee('S/ 70.00')
        ->assertDontSee('Laravel');
});

test('sale post sale screen shows complete actions and respects confirmation preference', function () {
    $this->withoutVite();
    $cashier = User::factory()->create(['role' => 'cajero']);
    settings()->set('keep_sale_confirmation', '1');
    $sale = createPosSale($cashier, [
        'payments' => [['method' => 'cash', 'amount' => 5, 'received_amount' => 10]],
    ]);

    $this->actingAs($cashier)
        ->get("/sistema/sales/{$sale->id}")
        ->assertOk()
        ->assertSee('Sale\\/Show', false)
        ->assertSee($sale->full_document_number)
        ->assertSee('5.00');

    addProductToPosCart($cashier);
    settings()->set('keep_sale_confirmation', '0');
    $this->actingAs($cashier)
        ->post('/sistema/sales', [
            'document_type' => 'receipt',
            'payments' => [['method' => 'cash', 'amount' => 5, 'received_amount' => 5]],
        ])
        ->assertRedirect('/sistema/pos');
});

test('print requests create original and reprint logs without logging document open', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $sale = createPosSale($cashier);

    $this->actingAs($cashier)->get("/sistema/sales/{$sale->id}/thermal")->assertOk();
    expect(DocumentPrintLog::where('sale_id', $sale->id)->count())->toBe(0);

    $this->actingAs($cashier)->post("/sistema/sales/{$sale->id}/print-request")->assertOk()->assertJsonPath('is_reprint', false);
    $this->actingAs($cashier)->post("/sistema/sales/{$sale->id}/print-request")->assertOk()->assertJsonPath('is_reprint', true);

    expect(DocumentPrintLog::where('sale_id', $sale->id)->where('action_type', 'print_requested')->count())->toBe(2);
});

test('cashier cannot request print for another cashier document and admin sees global print history', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    $otherCashier = User::factory()->create(['role' => 'cajero']);
    $admin = User::factory()->create(['role' => 'admin']);
    $ownSale = createPosSale($cashier);
    $otherSale = createPosSale($otherCashier);

    $this->actingAs($cashier)->post("/sistema/sales/{$otherSale->id}/print-request")->assertNotFound();
    $this->actingAs($cashier)->post("/sistema/sales/{$ownSale->id}/print-request")->assertOk();
    $this->actingAs($otherCashier)->post("/sistema/sales/{$otherSale->id}/print-request")->assertOk();

    $this->actingAs($cashier)->getJson('/sistema/print-logs')->assertOk()->assertJsonCount(1, 'data');
    $this->actingAs($admin)->getJson('/sistema/print-logs')->assertOk()->assertJsonCount(2, 'data');
});

test('printer test is admin only, uses current settings and does not create sales or movements', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cashier = User::factory()->create(['role' => 'cajero']);
    settings()->set(['business_name' => 'Ticket Test', 'thermal_paper_width' => 80]);

    $this->actingAs($cashier)->get('/sistema/settings/printer-test')->assertForbidden();
    $this->actingAs($admin)
        ->get('/sistema/settings/printer-test')
        ->assertOk()
        ->assertSee('Ticket Test')
        ->assertSee('80 mm')
        ->assertSee('PRUEBA DE IMPRESORA');

    $sales = Sale::count();
    $movements = CashMovement::count();
    $this->actingAs($admin)->post('/sistema/settings/printer-test/print-request')->assertOk()->assertJsonPath('is_reprint', false);

    expect(Sale::count())->toBe($sales)
        ->and(CashMovement::count())->toBe($movements)
        ->and(DocumentPrintLog::where('document_type', 'printer_test')->exists())->toBeTrue();
});

test('advanced sales history filters by customer document cashier method date and amount', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cashier = User::factory()->create(['role' => 'cajero']);
    $otherCashier = User::factory()->create(['role' => 'cajero']);
    $customer = posCustomer([
        'document_type' => 'dni',
        'document_number' => '45678912',
        'name' => 'Rosa Cliente',
        'name_or_business_name' => 'Rosa Cliente',
    ]);

    openPosRegister($cashier);
    addProductToPosCart($cashier);
    $target = app(SaleService::class)->createForUser([
        'document_type' => 'receipt',
        'customer_id' => $customer->id,
        'payments' => [
            ['method' => 'yape', 'amount' => 5, 'operation_number' => 'YP-777'],
        ],
    ], $cashier->id);
    $target->update(['sold_at' => now()->subDay()]);

    $otherSale = posSaleFor($otherCashier);

    $query = http_build_query([
        'inertia' => 'disabled',
        'full_document_number' => $target->full_document_number,
        'cashier_id' => $cashier->id,
        'document_type' => 'receipt',
        'payment_method' => 'yape',
        'status' => 'paid',
        'date_from' => now()->subDays(2)->toDateString(),
        'date_to' => now()->toDateString(),
        'amount_min' => 4,
        'amount_max' => 6,
    ]);

    $response = $this->actingAs($admin)->getJson('/sistema/sales?'.$query);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $target->id);

    expect(collect($response->json('data'))->pluck('id'))->not->toContain($otherSale->id);
});

test('dashboard returns role specific data and cashier does not receive administrative metrics', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cashier = User::factory()->create(['role' => 'cajero']);
    openPosRegister($cashier);
    createPosSale($cashier);

    $adminData = app(DashboardService::class)->getForUser($admin, now()->format('Y-m'));
    $cashierData = app(DashboardService::class)->getForUser($cashier, now()->format('Y-m'));

    expect($adminData['dashboardRole'])->toBe('admin')
        ->and($adminData)->toHaveKey('total_loss')
        ->and($adminData)->toHaveKey('products_without_cost_count')
        ->and($cashierData['dashboardRole'])->toBe('cajero')
        ->and($cashierData)->toHaveKey('cashierDashboard')
        ->and($cashierData)->not->toHaveKey('total_loss')
        ->and($cashierData['cashierDashboard']['sales_count'])->toBe(1);
});

test('cashier dashboard route is available but administrative routes remain protected', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);

    $this->withoutVite();

    $this->actingAs($cashier)
        ->get('/sistema/dashboard')
        ->assertOk()
        ->assertSee('Dashboard', false);

    $this->actingAs($cashier)
        ->get('/sistema/reports')
        ->assertForbidden();
});

test('navigation is centralized by role and does not expose legacy modules', function () {
    $menu = file_get_contents(resource_path('js/Navigation/menu.js'));
    $layout = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.vue'));

    expect($menu)->toContain('menuForRole')
        ->and($menu)->toContain('navigation.point_of_sale')
        ->and($menu)->toContain('navigation.my_sales')
        ->and($menu)->not->toContain('orders.index')
        ->and($menu)->not->toContain('transactions.index')
        ->and($layout)->toContain('<Sidebar')
        ->and($layout)->not->toContain('SidebarVendedor');
});

test('pos and navigation include keyboard and accessibility affordances', function () {
    $pos = file_get_contents(resource_path('js/Pages/Cart/Pos.vue'));
    $sidebar = file_get_contents(resource_path('js/Components/Sidebar/Sidebar.vue'));
    $modal = file_get_contents(resource_path('js/Components/Modal.vue'));

    expect($pos)->toContain("event.key === 'F2'")
        ->and($pos)->toContain("event.key === 'F4'")
        ->and($pos)->toContain('isTextField')
        ->and($pos)->toContain('aria-describedby="product_query_help"')
        ->and($sidebar)->toContain("t('navigation.main')")
        ->and($sidebar)->toContain("event.key === 'Escape'")
        ->and($sidebar)->toContain('overflow-hidden')
        ->and($modal)->toContain('role="dialog"')
        ->and($modal)->toContain('aria-modal="true"');
});
