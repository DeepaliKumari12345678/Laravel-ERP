@extends('admin.layouts.app')

@section('title', 'Carriers')

@section('content')
<style>
    .carrier-logo { width:44px;height:32px;object-fit:contain;border:1px solid var(--ps-line);border-radius:4px;background:#fff; }
    .carrier-placeholder { width:44px;height:32px;display:grid;place-items:center;border:1px dashed var(--ps-line);border-radius:4px;color:var(--ps-muted);font-size:.65rem; }
    .carrier-actions { display:flex;justify-content:flex-end;gap:.35rem; }
</style>

<div class="ps-breadcrumb">Improve &gt; Shipping &gt; Carriers</div>
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem;">
    <div>
        <h1 class="page-title" style="margin:0;">Carriers</h1>
        <p class="page-sub" style="margin:.25rem 0 0;">Configure delivery services, destinations, rates, tracking, and package limits.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.shipping.carriers.create') }}">+ Add new carrier</a>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <div class="card-head" style="padding:1rem;"><h3 style="margin:0;">Carriers ({{ $carriers->total() }})</h3></div>
    <form method="get" action="{{ route('admin.shipping.carriers') }}" style="display:grid;grid-template-columns:100px 1fr 180px auto auto;gap:.45rem;align-items:end;padding:.8rem 1rem;background:#fafbfc;border-bottom:1px solid var(--ps-line);">
        <label>ID<input type="number" name="id" value="{{ request('id') }}"></label>
        <label>Name<input name="name" value="{{ request('name') }}" placeholder="Search carrier"></label>
        <label>Status
            <select name="active">
                <option value="">All</option>
                <option value="1" @selected(request('active') === '1')>Enabled</option>
                <option value="0" @selected(request('active') === '0')>Disabled</option>
            </select>
        </label>
        <button class="btn btn-primary" type="submit">Search</button>
        @if(collect(request()->except('page'))->filter(fn ($v) => filled($v))->isNotEmpty())
            <a class="btn btn-ghost" href="{{ route('admin.shipping.carriers') }}">Reset</a>
        @endif
    </form>

    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Logo</th>
                <th>Name</th>
                <th>Transit time</th>
                <th>Pricing</th>
                <th>Status</th>
                <th>Free shipping</th>
                <th>Position</th>
                <th style="text-align:right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($carriers as $carrier)
                <tr>
                    <td>{{ $carrier->id }}</td>
                    <td>
                        @if($carrier->logo_url)
                            <img class="carrier-logo" src="{{ $carrier->logo_url }}" alt="{{ $carrier->name }}">
                        @else
                            <span class="carrier-placeholder">NO LOGO</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $carrier->name }}</strong>
                        <div style="font-size:.75rem;color:var(--ps-muted);">{{ $carrier->rate_ranges_count }} rate range{{ $carrier->rate_ranges_count === 1 ? '' : 's' }}</div>
                    </td>
                    <td>{{ $carrier->delay }}</td>
                    <td>
                        {{ ucfirst($carrier->billing_basis) }}
                        @if($carrier->rate_ranges_count === 0)
                            · {{ number_format((float) $carrier->price, 2) }} {{ $carrier->currency }}
                        @endif
                    </td>
                    <td><span class="badge {{ $carrier->active ? 'badge-on' : 'badge-off' }}">{{ $carrier->active ? 'Enabled' : 'Disabled' }}</span></td>
                    <td><span class="badge {{ $carrier->free_shipping ? 'badge-on' : 'badge-off' }}">{{ $carrier->free_shipping ? 'Yes' : 'No' }}</span></td>
                    <td>{{ $carrier->position }}</td>
                    <td>
                        <div class="carrier-actions">
                            <a class="btn btn-ghost" href="{{ route('admin.shipping.carriers.edit', $carrier) }}" style="padding:.35rem .55rem;">Edit</a>
                            <form method="post" action="{{ route('admin.shipping.carriers.destroy', $carrier) }}" onsubmit="return confirm('Delete this carrier and its rates?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger" type="submit" style="padding:.35rem .55rem;">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" style="color:var(--ps-muted);">No carriers found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:1rem;">{{ $carriers->links() }}</div>
</div>
@endsection
