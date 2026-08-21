@extends('layout.app')
@section('title', '404 — ' . ($storeName ?? ''))

@section('content')
<div class="wrap" style="padding:100px 0;text-align:center">
    <h1 style="font-size:48px">404</h1>
    <p>Page not found.</p>
    <a href="/" style="display:inline-block;margin-top:20px;padding:12px 32px;background:#111;color:#fff;border-radius:8px">Back to Home</a>
</div>
@endsection
