<?php

use App\Models\User;
use App\Services\UserPreferenceService;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

function extraVisualResource(string $path): string
{
    return File::get(resource_path($path));
}

function extraVisualPreferences(array $overrides = []): array
{
    return [
        ...UserPreferenceService::DEFAULTS,
        ...$overrides,
    ];
}

test('navegacion principal queda traducible y sin movimientos de stock como modulo', function () {
    $menu = extraVisualResource('js/Navigation/menu.js');

    expect($menu)
        ->toContain('labelKey')
        ->toContain('navigation.point_of_sale')
        ->toContain('navigation.cash_register')
        ->not->toContain('Movimientos de stock')
        ->not->toContain('Pedidos')
        ->not->toContain('Ganancias');
});

test('preferencias solo permiten espanol e ingles y rechazan quechua en ui', function () {
    $service = app(UserPreferenceService::class);

    expect(UserPreferenceService::ALLOWED_LOCALES)->toBe(['es', 'en']);
    expect($service->normalize(['locale' => 'qu'])['locale'])->toBe('es');

    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch(route('profile.preferences.update'), extraVisualPreferences(['locale' => 'qu']))
        ->assertSessionHasErrors('locale');
});

test('tema claro explicito persiste en html inicial y no se convierte en oscuro', function () {
    $this->withoutVite();

    $user = User::factory()->create([
        'preferences' => extraVisualPreferences(['theme' => 'light', 'locale' => 'en']),
    ]);

    foreach (['profile.edit', 'dashboard'] as $routeName) {
        $this
            ->actingAs($user)
            ->get(route($routeName))
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('data-theme="light"', false)
            ->assertSee('data-theme-effective="light"', false)
            ->assertDontSee('data-theme-effective="dark"', false);
    }
});

test('tema system es el unico que consulta preferencia del sistema operativo', function () {
    expect(extraVisualResource('views/app.blade.php'))
        ->toContain("selectedTheme === 'system'")
        ->toContain("darkQuery && darkQuery.matches ? 'dark' : 'light'");

    expect(extraVisualResource('js/Composables/useAppearance.js'))
        ->toContain("normalized.theme === 'system'")
        ->toContain('root.lang = normalized.locale');
});

test('panel de apariencia usa paleta accesible y selector de idioma', function () {
    $panel = extraVisualResource('js/Pages/Profile/Partials/AppearancePanel.vue');
    $preferences = extraVisualResource('js/I18n/preferences.js');

    expect($panel)
        ->toContain("t('preferences.accessible_palette')")
        ->toContain("v-model=\"form.locale\"")
        ->not->toContain('Daltonismo');

    expect($preferences)
        ->toContain('Paleta accesible')
        ->toContain('Accessible palette')
        ->not->toContain('Daltonismo');
});

test('productos usa resumen, estados textuales e iconos accesibles de accion', function () {
    $products = extraVisualResource('js/Pages/Product/Index.vue');

    expect($products)
        ->toContain('StatCard')
        ->toContain('fa-pencil-alt')
        ->toContain('fa-trash-alt')
        ->toContain(':title="t(\'actions.edit\')"')
        ->toContain(':title="t(\'actions.delete\')"')
        ->toContain('sr-only')
        ->toContain("t('common.low_stock')")
        ->toContain("t('common.exhausted')")
        ->toContain("t('common.available')");
});

test('gastos explica diferencia entre administrativo y pagado desde caja', function () {
    $expenses = extraVisualResource('js/Pages/Expense/Index.vue');

    expect($expenses)
        ->toContain('StatCard')
        ->toContain("t('expenses.paid_question')")
        ->toContain("t('expenses.paid_help')")
        ->toContain("t('expenses.admin_help')")
        ->toContain("t('expenses.cash_impact_message')");
});

test('caja se enfoca en estado de empleados y cajas abiertas', function () {
    $cash = extraVisualResource('js/Pages/CashRegister/Index.vue');

    expect($cash)
        ->toContain('employeeCashStates')
        ->toContain('filteredEmployeeStates')
        ->toContain("t('cash.status_board_description')")
        ->toContain("t('cash.open_boxes')")
        ->toContain("t('cash.closed_boxes')")
        ->toContain("t('states.open')")
        ->toContain("t('states.closed')");
});

test('dashboard separa experiencia de administrador y cajero', function () {
    $dashboard = extraVisualResource('js/Pages/Dashboard.vue');

    expect($dashboard)
        ->toContain("t('dashboard.admin_title')")
        ->toContain("t('dashboard.cashier_title')")
        ->toContain("t('dashboard.today_gross_profit')")
        ->toContain("t('dashboard.expected_cash')")
        ->not->toContain('Pedidos')
        ->not->toContain('Ganancias');
});

test('locale elegido se comparte por inertia', function () {
    $user = User::factory()->create([
        'preferences' => extraVisualPreferences(['locale' => 'en']),
    ]);

    $this
        ->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Edit')
            ->where('userPreferences.locale', 'en'));
});

test('tailwind solo escanea rutas acotadas del proyecto', function () {
    $tailwind = File::get(base_path('tailwind.config.js'));

    expect($tailwind)
        ->toContain('./resources/js/**/*.vue')
        ->toContain('./resources/js/**/*.js')
        ->not->toContain('./**/*.js')
        ->not->toContain('./**/*.vue')
        ->not->toContain('./**/*.html')
        ->not->toContain('./*.js')
        ->not->toContain('./*.vue')
        ->not->toContain('./*.html');
});

test('pantallas criticas usan diccionario i18n en lugar de texto operativo fijo', function () {
    $files = [
        'js/Pages/Auth/Login.vue',
        'js/Pages/Sale/Index.vue',
        'js/Pages/Sale/Show.vue',
        'js/Pages/Reports/Index.vue',
        'js/Pages/Cart/Pos.vue',
        'js/Components/Customers/QuickCustomerSelector.vue',
        'js/Pages/Settings/Business.vue',
    ];

    foreach ($files as $file) {
        expect(extraVisualResource($file), $file)
            ->toContain('useI18n')
            ->toContain("t('");
    }
});

test('documentos imprimibles no muestran enums internos de caja ni ventas', function () {
    $views = [
        'views/cash-registers/opening-pdf.blade.php',
        'views/cash-registers/movement-pdf.blade.php',
        'views/cash-registers/movement-thermal.blade.php',
        'views/cash-registers/closing-pdf.blade.php',
        'views/cash-registers/closing-thermal.blade.php',
        'views/cash-registers/admin-report-pdf.blade.php',
        'views/reports/ventas-pdf.blade.php',
    ];

    foreach ($views as $view) {
        expect(extraVisualResource($view), $view)
            ->toContain('app()->getLocale()')
            ->toContain('<html lang="{{ $locale }}">');
    }

    expect(extraVisualResource('views/cash-registers/admin-report-pdf.blade.php'))
        ->toContain('$reconciliationLabels[$row[\'status\']]');

    expect(extraVisualResource('views/reports/ventas-pdf.blade.php'))
        ->toContain('$documentLabels[$venta->document_type]')
        ->toContain('$paymentLabels[$method]');
});

test('validaciones de preferencias responden en espanol e ingles', function () {
    $spanishUser = User::factory()->create([
        'preferences' => extraVisualPreferences(['locale' => 'es']),
    ]);

    $this
        ->actingAs($spanishUser)
        ->patch(route('profile.preferences.update'), extraVisualPreferences(['locale' => 'qu']))
        ->assertSessionHasErrors(['locale' => 'El valor seleccionado en idioma no es válido.']);

    $englishUser = User::factory()->create([
        'preferences' => extraVisualPreferences(['locale' => 'en']),
    ]);

    $this
        ->actingAs($englishUser)
        ->patch(route('profile.preferences.update'), extraVisualPreferences(['locale' => 'qu']))
        ->assertSessionHasErrors(['locale' => 'The selected language is invalid.']);
});

test('temas de alto contraste y paleta accesible persisten en html inicial', function (string $theme) {
    $this->withoutVite();

    $user = User::factory()->create([
        'role' => 'admin',
        'preferences' => extraVisualPreferences(['theme' => $theme, 'locale' => 'es']),
    ]);

    foreach (['profile.edit', 'dashboard', 'products.index', 'sales.index', 'cash-registers.index'] as $routeName) {
        $this
            ->actingAs($user)
            ->get(route($routeName))
            ->assertOk()
            ->assertSee('lang="es"', false)
            ->assertSee('data-theme="'.$theme.'"', false)
            ->assertSee('data-theme-effective="'.$theme.'"', false);
    }
})->with(['high_contrast', 'color_accessible']);

test('pantallas administrativas secundarias usan componentes comunes, i18n y acciones accesibles', function () {
    $pages = [
        'Clientes' => 'js/Pages/Customer/Index.vue',
        'Categorias' => 'js/Pages/Category/Index.vue',
        'Unidades' => 'js/Pages/UnitType/Index.vue',
        'Proveedores' => 'js/Pages/Supplier/Index.vue',
        'Empleados' => 'js/Pages/Employee/Index.vue',
        'Salarios' => 'js/Pages/Salary/Index.vue',
    ];

    foreach ($pages as $name => $page) {
        $content = extraVisualResource($page);

        expect($content, $name)
            ->toContain('useI18n')
            ->toContain('PageHeader')
            ->toContain('FilterPanel')
            ->toContain('AppPagination')
            ->toContain('AppModal')
            ->toContain('ConfirmDialog')
            ->toContain('fa-pencil-alt')
            ->toContain('fa-trash-alt')
            ->toContain(':title="t(\'actions.edit\')"')
            ->toContain(':title="t(\'actions.delete\')"')
            ->toContain(':aria-label=')
            ->toContain('AppEmptyState')
            ->toContain('hidden overflow-x-auto');

        expect(str_contains($content, 'lg:hidden') || str_contains($content, 'xl:hidden'), $name)->toBeTrue();
    }
});

test('diccionario administrativo cubre pantallas secundarias en espanol e ingles', function () {
    $dictionary = extraVisualResource('js/I18n/admin.js');

    foreach (['customers', 'categories', 'units', 'suppliers', 'employees', 'salaries', 'profile'] as $section) {
        expect($dictionary)->toContain($section);
    }

    expect($dictionary)
        ->toContain('Clientes')
        ->toContain('Customers')
        ->toContain('Categorías')
        ->toContain('Categories')
        ->toContain('Unidades')
        ->toContain('Units')
        ->toContain('Proveedores')
        ->toContain('Suppliers')
        ->toContain('Empleados')
        ->toContain('Employees')
        ->toContain('Salarios')
        ->toContain('Salaries')
        ->toContain('Habilitado')
        ->toContain('Enabled');
});

test('cajero no accede a mantenimiento administrativo secundario', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);

    foreach (['categories.index', 'unit-types.index', 'suppliers.index', 'employees.index', 'salaries.index'] as $routeName) {
        $this
            ->actingAs($cashier)
            ->get(route($routeName))
            ->assertForbidden();
    }
});

test('cliente mantiene acceso operativo pero borrado queda reservado al administrador', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);

    $this
        ->actingAs($cashier)
        ->get(route('customers.index'))
        ->assertOk();

    $this
        ->actingAs($cashier)
        ->delete(route('customers.destroy', 1))
        ->assertForbidden();
});

test('mensajes administrativos y validaciones de cliente responden en espanol e ingles', function () {
    expect(__('messages.categories.created', [], 'es'))->toBe('Categoría creada correctamente.');
    expect(__('messages.categories.created', [], 'en'))->toBe('Category created successfully.');
    expect(__('messages.employees.create_failed', [], 'es'))->toBe('No se pudo crear el cajero.');
    expect(__('messages.employees.create_failed', [], 'en'))->toBe('Could not create the cashier.');

    $spanishUser = User::factory()->create([
        'preferences' => extraVisualPreferences(['locale' => 'es']),
    ]);
    $englishUser = User::factory()->create([
        'preferences' => extraVisualPreferences(['locale' => 'en']),
    ]);

    $this
        ->actingAs($spanishUser)
        ->post(route('customers.store'), [
            'document_type' => 'dni',
            'document_number' => '123',
            'name' => 'Cliente prueba',
        ])
        ->assertSessionHasErrors(['document_number' => 'El DNI debe tener exactamente 8 dígitos.']);

    $this
        ->actingAs($englishUser)
        ->post(route('customers.store'), [
            'document_type' => 'ruc',
            'document_number' => '',
            'name' => 'Test customer',
        ])
        ->assertSessionHasErrors(['document_number' => 'Enter the document number.']);
});

test('documentos impresos declaran aviso interno bilingue exacto y respetan locale', function () {
    $views = [
        'views/sales/pdf-note.blade.php',
        'views/sales/thermal-note.blade.php',
        'views/settings/printer-test.blade.php',
        'views/cash-registers/opening-pdf.blade.php',
        'views/cash-registers/opening-thermal.blade.php',
        'views/cash-registers/movement-pdf.blade.php',
        'views/cash-registers/movement-thermal.blade.php',
        'views/cash-registers/closing-pdf.blade.php',
        'views/cash-registers/closing-thermal.blade.php',
        'views/cash-registers/admin-report-pdf.blade.php',
        'views/reports/ventas-pdf.blade.php',
    ];

    foreach ($views as $view) {
        $content = extraVisualResource($view);

        expect($content, $view)
            ->toContain('app()->getLocale()')
            ->toContain('Internal document. Not valid as a tax receipt')
            ->toContain('Documento interno. No válido como comprobante tributario')
            ->not->toContain('Documento operativo interno. No válido como comprobante tributario.');
    }
});

test('tema claro e ingles persisten en pantallas administrativas secundarias', function () {
    $this->withoutVite();

    $admin = User::factory()->create([
        'role' => 'admin',
        'preferences' => extraVisualPreferences(['theme' => 'light', 'locale' => 'en']),
    ]);

    foreach (['customers.index', 'categories.index', 'unit-types.index', 'suppliers.index', 'employees.index', 'salaries.index', 'profile.edit'] as $routeName) {
        $this
            ->actingAs($admin)
            ->get(route($routeName))
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('data-theme="light"', false)
            ->assertSee('data-theme-effective="light"', false);
    }
});
