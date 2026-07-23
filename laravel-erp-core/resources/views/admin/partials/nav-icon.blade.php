@php
    $stroke = 'currentColor';
@endphp
@switch($icon ?? '')
    @case('orders')
        <svg viewBox="0 0 24 24" fill="none" stroke="{{ $stroke }}" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 7h15l-1.5 9h-12z"/>
            <path d="M6 7 5 3H2"/>
            <circle cx="9" cy="20" r="1.2"/>
            <circle cx="17" cy="20" r="1.2"/>
        </svg>
        @break
    @case('customers')
        <svg viewBox="0 0 24 24" fill="none" stroke="{{ $stroke }}" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/>
            <circle cx="12" cy="10" r="3"/>
            <path d="M6.5 18.2c1.4-2 3.3-3 5.5-3s4.1 1 5.5 3"/>
        </svg>
        @break
    @case('catalog')
        <svg viewBox="0 0 24 24" fill="none" stroke="{{ $stroke }}" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 10h16v10H4z"/>
            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
            <path d="M4 14h16"/>
        </svg>
        @break
    @case('employees')
        <svg viewBox="0 0 24 24" fill="none" stroke="{{ $stroke }}" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="9" cy="8" r="3"/>
            <circle cx="17" cy="9" r="2.5"/>
            <path d="M2.5 19c.8-3 3-5 6.5-5s5.7 2 6.5 5"/>
            <path d="M14.5 14.2c2 .3 3.7 1.5 4.5 3.8"/>
        </svg>
        @break
    @case('stats')
        <svg viewBox="0 0 24 24" fill="none" stroke="{{ $stroke }}" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 19V5"/>
            <path d="M4 19h16"/>
            <path d="M8 16v-5"/>
            <path d="M12 16V8"/>
            <path d="M16 16v-8"/>
        </svg>
        @break
    @case('shipping')
        <svg viewBox="0 0 24 24" fill="none" stroke="{{ $stroke }}" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="1" y="7" width="13" height="10" rx="1"/>
            <path d="M14 10h4l3 3v4h-7"/>
            <circle cx="5.5" cy="18.5" r="1.5"/>
            <circle cx="17.5" cy="18.5" r="1.5"/>
        </svg>
        @break
    @case('payment')
        <svg viewBox="0 0 24 24" fill="none" stroke="{{ $stroke }}" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="2" y="5" width="20" height="14" rx="2"/>
            <line x1="2" y1="10" x2="22" y2="10"/>
        </svg>
        @break
    @case('settings')
        <svg viewBox="0 0 24 24" fill="none" stroke="{{ $stroke }}" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="3"/>
            <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>
        </svg>
        @break
    @case('globe')
        <svg viewBox="0 0 24 24" fill="none" stroke="{{ $stroke }}" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/>
            <path d="M3 12h18"/>
            <path d="M12 3a14 14 0 0 1 0 18"/>
            <path d="M12 3a14 14 0 0 0 0 18"/>
        </svg>
        @break
    @case('advanced')
        <svg viewBox="0 0 24 24" fill="none" stroke="{{ $stroke }}" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <circle cx="12" cy="12" r="3"/>
            <path d="M12 7v2M12 15v2M7 12h2M15 12h2"/>
        </svg>
        @break
    @default
        <svg viewBox="0 0 24 24" fill="none" stroke="{{ $stroke }}" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="4" y="4" width="16" height="16" rx="2"/>
        </svg>
@endswitch
