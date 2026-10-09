@extends('layout.app')
@section('title', 'Request Return — ' . ($storeName ?? ''))

@section('content')
<div class="wrap" style="padding:32px 0;max-width:720px">
    {{--
        The return / exchange form is a Livewire form shared by every theme: quantity stepper,
        refund or exchange (+ replacement option with stock), return method, reason, description,
        photos (required only for seller-fault reasons), video, notes and a live refund estimate.
        Including it is all a theme needs; restyle around it as you like.

        Variables: $order, $item, $reasons and (optional) $remaining, $returnMethods,
        $exchangeEnabled, $exchangeOptions, $selectedReason, $photosRequired,
        $descriptionRequired, $refundEstimate.
    --}}
    @include('livewire.tenant.storefront.partials.return-form-content')
</div>
@endsection
