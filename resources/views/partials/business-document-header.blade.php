@php
    $locale = app()->getLocale();
    $headerLabels = $locale === 'en'
        ? ['tax_id' => 'Tax ID', 'phone' => 'Phone']
        : ['tax_id' => 'RUC', 'phone' => 'Tel'];
@endphp
<header class="{{ $class ?? 'center' }}">
    @if(($showLogo ?? true) && ($business['logo_url'] ?? null))
        <img src="{{ $business['logo_url'] }}" alt="Logo {{ $business['business_name'] }}" class="logo">
    @endif
    <h1>{{ $business['business_name'] }}</h1>
    @if($business['legal_name'] ?? null)
        <p>{{ $business['legal_name'] }}</p>
    @endif
    @if($business['tax_id'] ?? null)
        <p>{{ $headerLabels['tax_id'] }}: {{ $business['tax_id'] }}</p>
    @endif
    @if($business['address'] ?? null)
        <p class="muted">{{ $business['address'] }}</p>
    @endif
    @if($business['phone'] ?? null)
        <p class="muted">{{ $headerLabels['phone'] }}: {{ $business['phone'] }}</p>
    @endif
    @if($business['email'] ?? null)
        <p class="muted">{{ $business['email'] }}</p>
    @endif
    @isset($title)
        <h2 style="margin-top: 6px;">{{ $title }}</h2>
    @endisset
    @isset($subtitle)
        <p class="bold">{{ $subtitle }}</p>
    @endisset
</header>
