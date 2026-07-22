<?php

use App\Models\User;
use App\Services\BusinessSettingsService;
use App\Services\UserPreferenceService;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

function validPreferences(array $overrides = []): array
{
    return [
        ...UserPreferenceService::DEFAULTS,
        ...$overrides,
    ];
}

test('preferences nullable no rompe usuarios existentes y aplica defaults', function () {
    $user = User::factory()->create(['preferences' => null]);

    $preferences = app(UserPreferenceService::class)->forUser($user);

    expect($preferences)->toBe(UserPreferenceService::DEFAULTS);
});

test('usuario actualiza sus preferencias personales', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.preferences.update'), validPreferences([
            'theme' => 'dark',
            'reduced_motion' => true,
            'font_scale' => '125',
            'compact_mode' => true,
        ]));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $preferences = $user->refresh()->preferences;

    expect($preferences['theme'])->toBe('dark');
    expect($preferences['reduced_motion'])->toBeTrue();
    expect($preferences['font_scale'])->toBe('125');
    expect($preferences['compact_mode'])->toBeTrue();
});

test('usuario no modifica preferencias ajenas', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create(['preferences' => validPreferences(['theme' => 'light'])]);

    $this
        ->actingAs($user)
        ->patch(route('profile.preferences.update'), validPreferences([
            'theme' => 'dark',
            'user_id' => $otherUser->id,
        ]))
        ->assertSessionHasErrors('preferences');

    expect($otherUser->refresh()->preferences['theme'])->toBe('light');
});

test('theme invalido es rechazado', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch(route('profile.preferences.update'), validPreferences(['theme' => 'neon']))
        ->assertSessionHasErrors('theme');
});

test('font scale invalido es rechazado', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch(route('profile.preferences.update'), validPreferences(['font_scale' => '175']))
        ->assertSessionHasErrors('font_scale');
});

test('claves desconocidas no se guardan', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch(route('profile.preferences.update'), validPreferences(['unexpected' => true]))
        ->assertSessionHasErrors('preferences');

    expect($user->refresh()->preferences)->toBeNull();
});

test('preferencias se comparten por inertia', function () {
    $user = User::factory()->create([
        'preferences' => validPreferences(['theme' => 'color_accessible']),
    ]);

    $this
        ->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Edit')
            ->where('auth.user.preferences.theme', 'color_accessible')
            ->where('auth.user.preferences.locale', 'es')
            ->where('userPreferences.theme', 'color_accessible')
            ->where('userPreferences.locale', 'es'));
});

test('tema se restaura al iniciar sesion desde el html inicial', function () {
    $user = User::factory()->create([
        'preferences' => validPreferences(['theme' => 'dark', 'font_scale' => '150']),
    ]);

    $this
        ->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('data-theme="dark"', false)
        ->assertSee('data-font-scale="150"', false);
});

test('sidebar colapsado persiste', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch(route('profile.preferences.update'), validPreferences(['sidebar_collapsed' => true]))
        ->assertSessionHasNoErrors();

    expect($user->refresh()->preferences['sidebar_collapsed'])->toBeTrue();
});

test('reduced motion alto contraste y tema accesible se aplican en el html', function () {
    $user = User::factory()->create([
        'preferences' => validPreferences([
            'theme' => 'high_contrast',
            'high_contrast' => true,
            'reduced_motion' => true,
        ]),
    ]);

    $this
        ->actingAs($user)
        ->get(route('profile.edit'))
        ->assertSee('data-theme="high_contrast"', false)
        ->assertSee('data-contrast="high"', false)
        ->assertSee('data-motion="reduced"', false);

    $user->forceFill([
        'preferences' => validPreferences(['theme' => 'color_accessible']),
    ])->save();

    $this
        ->actingAs($user)
        ->get(route('profile.edit'))
        ->assertSee('data-theme="color_accessible"', false);
});

test('configuracion global del negocio no cambia al guardar preferencias', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $businessSettings = app(BusinessSettingsService::class)->public();

    $this
        ->actingAs($user)
        ->patch(route('profile.preferences.update'), validPreferences(['theme' => 'dark']))
        ->assertSessionHasNoErrors();

    expect(app(BusinessSettingsService::class)->public())->toBe($businessSettings);
});

test('cajero puede cambiar sus preferencias personales', function () {
    $cashier = User::factory()->create(['role' => 'cajero']);

    $this
        ->actingAs($cashier)
        ->patch(route('profile.preferences.update'), validPreferences([
            'theme' => 'light',
            'font_scale' => '112',
        ]))
        ->assertSessionHasNoErrors();

    expect($cashier->refresh()->preferences['font_scale'])->toBe('112');
});

test('pdf y termico no reciben tema oscuro de la app', function () {
    $documentViews = [
        resource_path('views/cash-registers/admin-report-pdf.blade.php'),
        resource_path('views/cash-registers/closing-pdf.blade.php'),
        resource_path('views/cash-registers/closing-thermal.blade.php'),
        resource_path('views/cash-registers/movement-pdf.blade.php'),
        resource_path('views/cash-registers/movement-thermal.blade.php'),
        resource_path('views/cash-registers/opening-pdf.blade.php'),
        resource_path('views/cash-registers/opening-thermal.blade.php'),
        resource_path('views/reports/ventas-pdf.blade.php'),
        resource_path('views/sales/pdf-note.blade.php'),
        resource_path('views/sales/thermal-note.blade.php'),
    ];

    foreach ($documentViews as $view) {
        expect(File::get($view))
            ->not->toContain('@extends(\'app\')')
            ->not->toContain('data-theme')
            ->not->toContain('data-theme-effective');
    }
});
