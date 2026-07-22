<?php

use App\Models\User;
use App\Services\UserPreferenceService;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

function frontendSourceFiles(): array
{
    return array_map(
        fn (SplFileInfo $file) => $file->getRealPath(),
        File::allFiles(resource_path('js'))
    );
}

function frontendSource(): string
{
    return collect(frontendSourceFiles())
        ->map(fn (string $file) => File::get($file))
        ->implode("\n");
}

function inertiaPropsFromResponse($response): array
{
    return $response->viewData('page')['props'];
}

test('rutas principales cargan despues de la limpieza legacy', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cashier = User::factory()->create(['role' => 'cajero']);

    $this->withoutVite();

    $this
        ->get(route('sistema.login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));

    foreach ([
        'profile.edit',
        'dashboard',
        'carts.index',
        'sales.index',
        'cash-registers.index',
        'reports.index',
        'settings.edit',
    ] as $routeName) {
        $this->actingAs($admin)->get(route($routeName))->assertOk();
    }

    foreach (['dashboard', 'carts.index', 'sales.index', 'cash-registers.index'] as $routeName) {
        $this->actingAs($cashier)->get(route($routeName))->assertOk();
    }
});

test('archivos legacy eliminados no tienen referencias ni imports activos', function () {
    $source = frontendSource();

    $deletedNames = [
        'Welcome',
        'MapExample',
        'BuyNow',
        'AdminNavbar',
        'IndexNavbar',
        'SidebarVendedor',
        'HeaderStats',
        'CardLineChart',
        'CardBarChart',
        'CardPageVisits',
        'CardSocialTraffic',
        'CardStats',
        'NotificationDropdown',
        'IndexDropdown',
        'PagesDropdown',
        'TableDropdown',
        'UserDropdown',
        'GuestLayout',
        'ContactForm',
    ];

    foreach ($deletedNames as $name) {
        expect($source)->not->toContain($name);
    }
});

test('manifest de vite no incluye welcome ni assets demo eliminados', function () {
    $manifestPath = public_path('build/manifest.json');

    if (! File::exists($manifestPath)) {
        $this->markTestSkipped('El manifest se valida despues de ejecutar npm run build.');
    }

    $manifest = File::get($manifestPath);

    foreach ([
        'Welcome',
        'angular.jpg',
        'react.jpg',
        'vue.jpg',
        'sketch.jpg',
        'component-btn',
        'component-info',
        'team-1',
        'team2',
        'landing.jpg',
        'banner.avif',
    ] as $asset) {
        expect($manifest)->not->toContain($asset);
    }
});

test('frontend no conserva storage ni patrones inseguros heredados', function () {
    foreach (frontendSourceFiles() as $file) {
        $content = File::get($file);

        expect($content, $file)
            ->not->toContain('v-html')
            ->not->toContain('javascript:')
            ->not->toContain('window.confirm')
            ->not->toContain('localStorage')
            ->not->toContain('sessionStorage')
            ->not->toContain('document.cookie')
            ->not->toContain('console.log')
            ->not->toContain('console.error');
    }
});

test('usuario global de inertia expone solo campos minimos', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'preferences' => ['theme' => 'dark'],
    ]);

    $response = $this->actingAs($admin)->get(route('profile.edit'))->assertOk();
    $user = inertiaPropsFromResponse($response)['auth']['user'];

    expect(array_keys($user))->toMatchArray([
        'id',
        'name',
        'email',
        'role',
        'photo',
        'email_verified_at',
        'preferences',
    ]);

    expect($user['preferences'])
        ->toHaveKey('theme', 'dark');

    expect(array_keys($user['preferences']))->toMatchArray(array_keys(UserPreferenceService::DEFAULTS));

    expect($user)
        ->not->toHaveKey('password')
        ->not->toHaveKey('remember_token')
        ->not->toHaveKey('created_at')
        ->not->toHaveKey('updated_at');
});

test('cajero no recibe listas administrativas por inertia', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);
    User::factory()->create(['role' => 'admin', 'name' => 'Admin privado']);

    $this
        ->actingAs($cashier)
        ->get(route('sales.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Sale/Index')
            ->where('cashiers', []));

    $this
        ->actingAs($cashier)
        ->get(route('cash-registers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CashRegister/Index')
            ->where('cashiers', [])
            ->where('registers', null)
            ->where('saleMovementDiscrepancies', 0));
});

test('sidebar oficial y auth layout permanecen sin navegacion demo', function () {
    $layout = File::get(resource_path('js/Layouts/AuthenticatedLayout.vue'));
    $sidebar = File::get(resource_path('js/Components/Sidebar/Sidebar.vue'));
    $authLayout = File::get(resource_path('js/Layouts/AuthLayout.vue'));
    $guestNavbar = File::get(resource_path('js/Components/Navbars/GuestNavbar.vue'));

    expect($layout)
        ->toContain('<Sidebar')
        ->not->toContain('AdminNavbar')
        ->not->toContain('SidebarVendedor');

    expect($sidebar)
        ->toContain('menuForRole')
        ->toContain("t('navigation.main')");

    expect($authLayout)->toContain('GuestNavbar');
    expect($guestNavbar)
        ->not->toContain('PagesDropdown')
        ->not->toContain('Get Started')
        ->not->toContain('Docs');
});

test('cardtable queda justificado y sin html o dropdowns inseguros', function () {
    $cardTable = File::get(resource_path('js/Components/Cards/CardTable.vue'));

    expect($cardTable)
        ->toContain('Datepicker')
        ->toContain('AsyncVueSelect')
        ->toContain('Pagination')
        ->not->toContain('v-html')
        ->not->toContain('TableDropdown')
        ->not->toContain('console.log')
        ->not->toContain('javascript:');
});
