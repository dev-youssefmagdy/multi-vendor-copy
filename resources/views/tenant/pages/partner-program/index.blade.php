@extends('tenant.layouts.app')

@section('title', __('Partner Program'))

@section('content')
    <x-tenant::page-header :title="__('Partner Program')" :badge="__('Partners')" :description="__('Share your referral link, track conversions, and withdraw the rewards you earn.')" />

    @include('tenant.pages.partner-program._content')
@endsection
