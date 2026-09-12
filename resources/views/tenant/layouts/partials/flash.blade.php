@if(session('status'))
    <div id="flash-status" data-message="{{ session('status') }}" data-type="{{ session('status_type', 'success') }}" hidden></div>
@endif
@if(session('setup_warning'))
    <div id="flash-setup-warning" data-message="{{ session('setup_warning') }}" data-type="warning" hidden></div>
@endif
@if(session('setup_error'))
    <div id="flash-setup-error" data-message="{{ session('setup_error') }}" data-type="error" hidden></div>
@endif
