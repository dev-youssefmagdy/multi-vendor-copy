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
                @if(!empty($categories))
                    <p class="pm-preview-cats">@foreach($categories as $cat)<span class="badge">{{ $cat }}</span> @endforeach</p>
                @endif
                @if($summary !== '')
                    <p class="pm-preview-desc"><strong>{{ $summary }}</strong></p>
                @endif
                <p class="pm-preview-desc">{{ $fullDescription !== '' ? $fullDescription : 'No description yet.' }}</p>
                @if(!empty($details))
                    <dl class="pm-preview-details">
                        @foreach($details as $label => $value)
                            <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                @endif
                @if(!empty($variants))
                    <h3 style="margin:0 0 8px">Variants ({{ count($variants) }})</h3>
                    <table class="pm-preview-variants">
                        <thead><tr><th>Option</th><th>SKU</th><th>Platform price</th><th>Stock</th></tr></thead>
                        <tbody>
                            @foreach($variants as $v)
                                <tr><td>{{ $v['options'] ?: '—' }}</td><td>{{ $v['sku'] ?: '—' }}</td><td>{{ $v['price'] }}</td><td>{{ $v['stock'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
                <form method="POST" action="{{ route('tenant.products.sync-new') }}">
                    @csrf
                    <input type="hidden" name="product_ids[]" value="{{ $card['id'] }}">
                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%">Add to my store</button>
                </form>
            </div>
        </div>
    </div>
@endsection
