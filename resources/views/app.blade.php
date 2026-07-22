<!DOCTYPE html>
@php
    $initialPreferences = app(\App\Services\UserPreferenceService::class)->forUser(auth()->user());
    $initialTheme = $initialPreferences['theme'] === 'system' ? 'light' : $initialPreferences['theme'];
@endphp
<html
    lang="{{ str_replace('_', '-', $initialPreferences['locale'] ?? app()->getLocale()) }}"
    data-theme="{{ $initialPreferences['theme'] }}"
    data-theme-effective="{{ $initialTheme }}"
    data-contrast="{{ ($initialPreferences['high_contrast'] || $initialPreferences['theme'] === 'high_contrast') ? 'high' : 'normal' }}"
    data-font-scale="{{ $initialPreferences['font_scale'] }}"
    data-density="{{ $initialPreferences['compact_mode'] ? 'compact' : 'comfortable' }}"
    data-motion="{{ $initialPreferences['reduced_motion'] ? 'reduced' : 'normal' }}"
>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset("assets/img/favicon.png") }}">
    <meta name="description" content=""/>

    <title inertia>{{ config('app.name', 'Inventory Management System') }}</title>

    <!-- Scripts -->
    <script>
        (function () {
            var preferences = @json($initialPreferences);
            var root = document.documentElement;
            var darkQuery = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
            var motionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

            function applyAppearance() {
                var selectedTheme = preferences.theme || 'system';
                var effectiveTheme = selectedTheme === 'system'
                    ? (darkQuery && darkQuery.matches ? 'dark' : 'light')
                    : selectedTheme;
                var reducedMotion = Boolean(preferences.reduced_motion) || Boolean(motionQuery && motionQuery.matches);

                root.dataset.theme = selectedTheme;
                root.dataset.themeEffective = effectiveTheme;
                root.dataset.contrast = (preferences.high_contrast || selectedTheme === 'high_contrast') ? 'high' : 'normal';
                root.dataset.fontScale = String(preferences.font_scale || '100');
                root.dataset.density = preferences.compact_mode ? 'compact' : 'comfortable';
                root.dataset.motion = reducedMotion ? 'reduced' : 'normal';
                root.lang = preferences.locale || 'es';
            }

            applyAppearance();
            darkQuery && darkQuery.addEventListener && darkQuery.addEventListener('change', applyAppearance);
            motionQuery && motionQuery.addEventListener && motionQuery.addEventListener('change', applyAppearance);
            window.__laratoryApplyAppearance = function (nextPreferences) {
                preferences = Object.assign({}, preferences, nextPreferences || {});
                applyAppearance();
            };
        })();
    </script>
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>
<body class="text-slate-700 antialiased">
@inertia
</body>
</html>
