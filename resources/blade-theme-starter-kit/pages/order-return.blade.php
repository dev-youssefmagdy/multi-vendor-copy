@extends('layout.app')
@section('title', 'Request Return — ' . ($storeName ?? ''))

@section('content')
<div class="wrap" style="padding:32px 0;max-width:600px">
    <h1>Request a Return</h1>
    <p>Item: {{ $item->product?->translationValue('name') ?? '' }}</p>

    <form method="POST" enctype="multipart/form-data">
        @csrf
        <label>Reason</label>
        <select name="reason" required style="width:100%;padding:10px;margin-bottom:12px">
            @foreach ($reasons as $reason)
                <option value="{{ $reason->value }}">{{ ucfirst(str_replace('_', ' ', $reason->value)) }}</option>
            @endforeach
        </select>

        <label>Description</label>
        <textarea name="description" style="width:100%;padding:10px;margin-bottom:12px"></textarea>

        <label>Photos (required)</label>
        <input type="file" name="photos[]" multiple accept="image/*" required style="margin-bottom:12px">

        <button type="submit" style="padding:12px 24px;background:#111;color:#fff;border:none;border-radius:8px">Submit Return Request</button>
    </form>
</div>
@endsection
