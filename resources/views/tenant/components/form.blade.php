@props([
    'action' => '',
    'method' => 'POST',
    'validate' => null,
    'success' => 'none',
    'confirm' => null,
    'confirmDanger' => false,
    'files' => false,
    'id' => null,
])

@php
    $httpMethod = strtoupper($method);
    $isSpoofed = !in_array($httpMethod, ['GET', 'POST'], true);
@endphp

<form
    @if($id) id="{{ $id }}" @endif
    action="{{ $action }}"
    method="{{ $isSpoofed ? 'POST' : $httpMethod }}"
    @if($isSpoofed) data-method="{{ $httpMethod }}" @endif
    data-tenant-form
    @if($validate) data-validate-url="{{ $validate }}" @endif
    data-success="{{ $success }}"
    @if($confirm) data-confirm="{{ $confirm }}" @endif
    @if($confirmDanger) data-confirm-danger @endif
    novalidate
    @if($files) enctype="multipart/form-data" @endif
    {{ $attributes }}
>
    @csrf
    <div class="t-form-errors" data-form-errors hidden></div>

    {{ $slot }}
</form>
