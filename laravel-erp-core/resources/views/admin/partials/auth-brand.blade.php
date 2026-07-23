@php
    $brandName = shop_name();
    $brandLogo = shop_logo_url();
@endphp
@if($brandLogo)
    <div class="auth-brand">
        <img class="auth-logo" src="{{ $brandLogo }}" alt="{{ $brandName }}">
        <h1>{{ $brandName }}</h1>
    </div>
@else
    <h1>{{ $brandName }}</h1>
@endif
