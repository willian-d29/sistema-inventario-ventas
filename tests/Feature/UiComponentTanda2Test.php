<?php

use App\Models\Expense;
use App\Models\Product;
use App\Models\User;
use App\Services\BusinessSettingsService;
use App\Services\ExpenseService;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

function resourceFile(string $path): string
{
    return File::get(resource_path($path));
}

test('paginacion no usa html inseguro', function () {
    expect(resourceFile('js/Components/UI/AppPagination.vue'))
        ->not->toContain('v-html')
        ->toContain('normalizedLabel');

    expect(resourceFile('js/Components/Pagination.vue'))->not->toContain('v-html');
    expect(resourceFile('js/Pages/Sale/Index.vue'))->not->toContain('v-html');
    expect(resourceFile('js/Pages/CashRegister/Index.vue'))->not->toContain('v-html');
});

test('no quedan usos de v-html en frontend', function () {
    $files = File::allFiles(resource_path('js'));

    foreach ($files as $file) {
        expect(File::get($file->getRealPath()))
            ->not->toContain('v-html');
    }
});

test('enlaces target blank incluyen rel seguro', function () {
    $files = File::allFiles(resource_path('js'));

    foreach ($files as $file) {
        $content = File::get($file->getRealPath());
        if (! str_contains($content, 'target="_blank"')) {
            continue;
        }

        preg_match_all('/<[^>]+target="_blank"[^>]*>/m', $content, $matches);
        foreach ($matches[0] as $tag) {
            expect($tag, $file->getRelativePathname())->toContain('rel="noopener noreferrer"');
        }
    }
});

test('componentes base declaran estados accesibles', function () {
    expect(resourceFile('js/Components/UI/AppButton.vue'))
        ->toContain(':disabled="isDisabled"')
        ->toContain('loadingText')
        ->toContain('ariaLabel');

    expect(resourceFile('js/Components/UI/AppInput.vue'))
        ->toContain(':aria-describedby="describedBy"')
        ->toContain(':aria-invalid="error ?');

    expect(resourceFile('js/Components/UI/AppModal.vue'))
        ->toContain('role="dialog"')
        ->toContain('aria-modal="true"')
        ->toContain('focusableElements');

    expect(resourceFile('js/Components/UI/ConfirmDialog.vue'))
        ->toContain('action:')
        ->toContain('consequence:')
        ->toContain("defineEmits(['cancel', 'confirm'])");

    expect(resourceFile('js/Components/UI/AppBadge.vue'))
        ->toContain('<slot />')
        ->toContain('icon');
});

test('perfil conserva datos si falla validacion', function () {
    $user = User::factory()->create(['role' => 'admin', 'name' => 'Nombre original', 'email' => 'original.a@laratory.pe']);

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Nuevo nombre',
            'email_local' => 'correo inválido',
        ])
        ->assertSessionHasErrors('email_local');

    $user->refresh();
    expect($user->name)->toBe('Nombre original');
    expect($user->email)->toBe('original.a@laratory.pe');
});

test('configuracion conserva datos si falla validacion', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $settings = app(BusinessSettingsService::class)->public();

    $payload = [
        ...$settings,
        'business_name' => 'Nombre nuevo',
        'timezone' => 'Zona/Falsa',
        'thermal_show_logo' => true,
        'thermal_show_customer' => true,
        'thermal_show_payment_refs' => true,
        'auto_open_print_dialog' => false,
        'return_to_pos_after_print' => false,
        'keep_sale_confirmation' => true,
        'print_copies' => 1,
        'decimal_point' => 2,
        'discount' => 0,
        'tax' => 0,
        'thermal_paper_width' => 80,
        'default_sale_document' => 'receipt',
        'cash_register_name' => 'Caja',
    ];

    $this
        ->actingAs($admin)
        ->post(route('settings.update'), $payload)
        ->assertSessionHasErrors('timezone');

    expect(app(BusinessSettingsService::class)->public()['business_name'])->toBe($settings['business_name']);
});

test('producto usa modal y muestra etiqueta nueva sin raiz', function () {
    expect(resourceFile('js/Pages/Product/Index.vue'))
        ->toContain('<AppModal')
        ->toContain('<ProductForm')
        ->not->toContain("route('products.create')")
        ->not->toContain("route('products.edit')");

    expect(resourceFile('js/Pages/Product/Partials/ProductForm.vue'))
        ->toContain("t('products.root_product')")
        ->toContain("t('common.barcode')")
        ->not->toContain(":label=\"t('common.code')\"")
        ->not->toContain('Raíz');
});

test('clientes queda retirado de las rutas operativas', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get('/sistema/customers')->assertNotFound();
    $this->actingAs($admin)->post('/sistema/customers', [])->assertNotFound();
});

test('proveedor conserva datos si falla validacion', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this
        ->actingAs($admin)
        ->post(route('suppliers.store'), [
            'name' => '',
            'email' => 'correo-invalido',
        ])
        ->assertSessionHasErrors(['name', 'email']);
});

test('empleado respeta permisos de administrador', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);

    $this
        ->actingAs($cashier)
        ->get(route('employees.index'))
        ->assertForbidden();
});

test('gasto desde caja muestra validaciones cuando no hay caja abierta', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    expect(fn () => app(ExpenseService::class)->create([
            'name' => 'Compra de bolsas',
            'amount' => 20,
            'expense_date' => now()->toDateString(),
            'paid_from_cash_register' => true,
        ]))->toThrow(ValidationException::class);
});

test('tablas prioritarias usan patron comun y estados textuales', function () {
    expect(resourceFile('js/Pages/Sale/Index.vue'))
        ->toContain('PageHeader')
        ->toContain('AppPagination')
        ->toContain('paymentsText');

    expect(resourceFile('js/Pages/Product/Index.vue'))
        ->toContain("t('common.low_stock')")
        ->toContain("t('common.exhausted')")
        ->toContain("t('common.available')");

    expect(resourceFile('js/Navigation/menu.js'))
        ->not->toContain('customers.index');

    expect(resourceFile('js/Pages/CashRegister/Index.vue'))
        ->toContain("t('states.balanced')")
        ->toContain("t('states.missing')")
        ->toContain("t('states.surplus')");

    expect(resourceFile('js/Pages/Expense/Index.vue'))
        ->toContain("t('expenses.paid_from_cash')")
        ->toContain("t('expenses.administrative_expense')");
});

test('gastos pagina correctamente', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    for ($index = 1; $index <= 16; $index++) {
        Expense::query()->create([
            'name' => "Gasto {$index}",
            'amount' => 10 + $index,
            'expense_date' => now()->toDateString(),
            'description' => 'Gasto de prueba',
        ]);
    }

    $this
        ->actingAs($admin)
        ->get(route('expenses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Expense/Index')
            ->where('expenses.total', 16));

    expect(resourceFile('js/Pages/Expense/Index.vue'))->toContain("t('pagination.label')");
});
