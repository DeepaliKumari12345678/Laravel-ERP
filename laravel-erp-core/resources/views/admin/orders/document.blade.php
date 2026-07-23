@extends('admin.layouts.app')

@section('title', $definition['title'].' '.$document->number)

@section('content')
    <div class="ps-breadcrumb">
        <a href="{{ route('admin.orders.index') }}">Orders</a> &gt;
        <a href="{{ route($definition['indexRoute']) }}">{{ $definition['title'] }}s</a> &gt;
        {{ $document->number }}
    </div>

    <div class="card-head no-print" style="margin-bottom:1rem;">
        <div>
            <h1 class="page-title" style="margin-bottom:.2rem;">{{ $definition['title'] }} {{ $document->number }}</h1>
            <p class="page-sub" style="margin:0;">View, print, or download this generated document.</p>
        </div>
        <div style="display:flex;gap:.5rem;">
            @if($order)
                <a class="btn btn-ghost" href="{{ route('admin.orders.show', ['order' => $order, 'tab' => 'documents']) }}">View order</a>
            @endif
            <button class="btn btn-ghost" type="button" onclick="window.print()">Print</button>
            <a class="btn btn-primary" href="{{ route($definition['downloadRoute'], $document) }}">Download PDF</a>
        </div>
    </div>

    <div class="card" style="max-width:900px;margin:0 auto;">
        @include('admin.orders.partials.document-content')
    </div>
@endsection
