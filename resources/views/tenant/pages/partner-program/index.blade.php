@extends('tenant.layouts.app')

@section('title', 'Partner Program')

@section('content')
    <x-tenant::page-header title="Partner Program" badge="Partners" description="Share your referral link, track conversions, and withdraw the rewards you earn." />

    @include('tenant.pages.partner-program._content')
@endsection
