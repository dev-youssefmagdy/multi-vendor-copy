<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { theme: { extend: { colors: { primary: '#FF4B2B', 'primary-dark': '#e83a1a' }, fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] } } } }</script>
<script src="{{ asset('central-website/assets/app.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@if(request('error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: @json(request('error')),
            confirmButtonColor: '#FF4B2B',
        });
    </script>
@endif
