@extends('layout.app')
@section('title', $page->title . ' — ' . ($storeName ?? ''))

@section('content')
<div class="wrap" style="padding:32px 0;max-width:800px">
    <h1>{{ $page->title }}</h1>
    <div style="margin-top:24px">{!! $page->content !!}</div>
</div>
@endsection
