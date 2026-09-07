@extends('layout.app')
@section('title', 'Sign In — ' . ($storeName ?? ''))

{{--
    NOTE: /account/login and /account/register are not plain POST routes —
    AuthPage is a Livewire component (see routes/tenant.php). The form
    actions below are placeholders; wire this page to the Livewire
    component or ask the platform team for dedicated POST endpoints
    matching this form shape before shipping.
--}}

@section('content')
<div class="wrap" style="padding:60px 0;max-width:420px">
    <div style="display:flex;gap:16px;margin-bottom:24px">
        <button type="button" onclick="showTab('login')" id="tab-login-btn">Sign In</button>
        <button type="button" onclick="showTab('register')" id="tab-register-btn">Register</button>
    </div>

    <form id="tab-login" method="POST" action="/account/login">
        @csrf
        <input type="email" name="loginEmail" placeholder="Email" required style="width:100%;padding:10px;margin-bottom:10px">
        <input type="password" name="loginPassword" placeholder="Password" required style="width:100%;padding:10px;margin-bottom:10px">
        <button type="submit" style="width:100%;padding:12px;background:#111;color:#fff;border:none;border-radius:8px">Sign In</button>
    </form>

    <form id="tab-register" method="POST" action="/account/register" style="display:none">
        @csrf
        <input type="text" name="regName" placeholder="Full Name" required style="width:100%;padding:10px;margin-bottom:10px">
        <input type="email" name="regEmail" placeholder="Email" required style="width:100%;padding:10px;margin-bottom:10px">
        <input type="password" name="regPassword" placeholder="Password" required style="width:100%;padding:10px;margin-bottom:10px">
        <input type="password" name="regConfirm" placeholder="Confirm Password" required style="width:100%;padding:10px;margin-bottom:10px">
        <button type="submit" style="width:100%;padding:12px;background:#111;color:#fff;border:none;border-radius:8px">Create Account</button>
    </form>
</div>

<script>
function showTab(tab) {
    document.getElementById('tab-login').style.display = tab === 'login' ? 'block' : 'none';
    document.getElementById('tab-register').style.display = tab === 'register' ? 'block' : 'none';
}
</script>
@endsection
