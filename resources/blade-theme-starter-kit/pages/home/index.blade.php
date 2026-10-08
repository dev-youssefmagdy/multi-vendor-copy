@extends('layout.app')

@section('content')
    @foreach($homeSections as $sectionKey)
        @includeIf('pages.home.sections.' . $sectionKey)
    @endforeach
@endsection
