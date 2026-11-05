@extends('tenant.layouts.app')

{{-- Preview of a platform product the tenant has not added yet (opened from "New products" / dashboard "New in"). --}}
@section('title', $card['title'])

@section('content')
    <div class="pm-page">
        @include('tenant.pages.catalog._module-nav', ['activeTab' => 'products'])

        <p style="margin:8px 0 0"><a href="{{ route('tenant.products.new') }}">&larr; Back to new products</a></p>

        <div class="pm-preview fu d1">
            <div class="pm-preview-gallery">
                @forelse($images as $src)
                    <img src="{{ $src }}" alt="{{ $card['title'] }}" loading="lazy">
                @empty
                    <p class="db-welcome-sub">No images yet.</p>
                @endforelse
            </div>
            <div class="pm-preview-info">
                <h1>{{ $card['title'] }}</h1>
                <p class="pm-preview-price">{{ $card['cost'] }}<small>Platform price, before your profit margin</small></p>
                <p class="pm-preview-desc">{{ $fullDescription !== '' ? $fullDescription : 'No description yet.' }}</p>
                <form method="POST" action="{{ route('tenant.products.sync-new') }}">
                    @csrf
                    <input type="hidden" name="product_ids[]" value="{{ $card['id'] }}">
                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%">Add to my store</button>
                </form>
            </div>
        </div>
    </div>
@endsection
