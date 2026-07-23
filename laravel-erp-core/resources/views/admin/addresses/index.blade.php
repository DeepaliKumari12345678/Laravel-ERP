@extends('admin.layouts.app')

@section('title', 'Addresses')

@section('content')
<style>
    .ad-actions { display:flex; gap:0.3rem; align-items:center; }
    .ad-icon-btn {
        width:30px; height:30px; border:1px solid var(--ps-line); border-radius:3px;
        background:#fff; display:inline-grid; place-items:center; color:var(--ps-ink); cursor:pointer;
    }
    .ad-icon-btn:hover { border-color:var(--ps-blue); color:var(--ps-blue-dark); }
    .ad-menu { position:relative; display:inline-block; }
    .ad-menu-panel {
        display:none; position:absolute; right:0; top:110%; z-index:20;
        background:#fff; border:1px solid var(--ps-line); border-radius:4px;
        box-shadow:0 6px 18px rgba(0,0,0,.08); min-width:130px; padding:0.25rem 0;
    }
    .ad-menu.open .ad-menu-panel { display:block; }
    .ad-menu-panel a, .ad-menu-panel button {
        display:flex; align-items:center; gap:0.45rem; width:100%;
        padding:0.45rem 0.75rem; background:none; border:0; font:inherit; color:var(--ps-ink);
        text-align:left; cursor:pointer;
    }
    .ad-menu-panel a:hover, .ad-menu-panel button:hover { background:#f3f5f6; }
    .ad-filters {
        display:grid;
        grid-template-columns:70px 1fr 1fr 1.4fr 110px 1fr 1.1fr auto auto;
        gap:0.4rem; align-items:end; margin-bottom:0.85rem;
    }
    @media (max-width:1100px) {
        .ad-filters { grid-template-columns:repeat(2, minmax(0,1fr)); }
    }
</style>

<div class="ps-breadcrumb">Customers &gt; Addresses</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">Addresses</h1>
    <div class="actions">
        <a href="{{ route('admin.addresses.create') }}" class="btn btn-primary">+ Add new address</a>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h3>Addresses ({{ $addresses->total() }})</h3>
    </div>

    <form method="get" action="{{ route('admin.addresses.index') }}" class="ad-filters">
        <label>ID<input type="number" name="id" value="{{ request('id') }}"></label>
        <label>First name<input name="first_name" value="{{ request('first_name') }}"></label>
        <label>Last name<input name="last_name" value="{{ request('last_name') }}"></label>
        <label>Address<input name="address" value="{{ request('address') }}"></label>
        <label>Zip/Postal<input name="postcode" value="{{ request('postcode') }}"></label>
        <label>City<input name="city" value="{{ request('city') }}"></label>
        <label>Country
            <select name="country">
                @include('admin.partials.country-options', ['selectedCountry' => request('country')])
            </select>
        </label>
        <button class="btn btn-primary" type="submit">Search</button>
        @if(collect(request()->except('page'))->filter(fn ($v) => filled($v))->isNotEmpty())
            <a href="{{ route('admin.addresses.index') }}" class="btn btn-ghost">Reset</a>
        @endif
    </form>

    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>First name</th>
                <th>Last name</th>
                <th>Address</th>
                <th>Zip/Postal code</th>
                <th>City</th>
                <th>Country</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($addresses as $address)
                <tr>
                    <td>{{ $address->id }}</td>
                    <td>{{ $address->first_name ?: '—' }}</td>
                    <td>{{ $address->last_name ?: '—' }}</td>
                    <td>
                        {{ $address->address1 }}
                        @if($address->alias)
                            <div style="color:var(--ps-muted);font-size:0.75rem;">{{ $address->alias }}</div>
                        @endif
                    </td>
                    <td>{{ $address->postcode ?: '—' }}</td>
                    <td>{{ $address->city ?: '—' }}</td>
                    <td>{{ $address->country ?: '—' }}</td>
                    <td>
                        <div class="ad-actions">
                            <a href="{{ route('admin.addresses.edit', $address) }}" class="ad-icon-btn" title="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </a>
                            <div class="ad-menu">
                                <button type="button" class="ad-icon-btn ad-menu-toggle" title="More">⋮</button>
                                <div class="ad-menu-panel">
                                    <form method="post" action="{{ route('admin.addresses.destroy', $address) }}" onsubmit="return confirm('Delete this address?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="color:var(--danger);">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" style="color:var(--ps-muted);">No addresses found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:0.85rem;">{{ $addresses->links() }}</div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.ad-menu-toggle').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        document.querySelectorAll('.ad-menu').forEach(m => { if (m !== btn.parentElement) m.classList.remove('open'); });
        btn.parentElement.classList.toggle('open');
    });
});
document.addEventListener('click', () => document.querySelectorAll('.ad-menu').forEach(m => m.classList.remove('open')));
</script>
@endpush
@endsection
