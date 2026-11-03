undefined

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Menu "Registrasi Tamu" di sidebar membuka halaman ini dengan ?register=1
    if (new URLSearchParams(window.location.search).get('register') === '1') {
        var modal = document.getElementById('guestModal');
        if (modal) {
            new bootstrap.Modal(modal).show();
        }
    }
});
</script>
@endpush
