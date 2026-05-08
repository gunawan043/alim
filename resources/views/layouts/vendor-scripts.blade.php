<script src="{{ URL::asset('build/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/node-waves/waves.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/feather-icons/feather.min.js') }}"></script>
<script src="{{ URL::asset('build/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>
{{-- Choices.js — digunakan oleh elemen dengan attribute data-choices --}}
<script src="{{ URL::asset('build/libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>
{{-- Flatpickr — digunakan oleh elemen dengan attribute data-provider="flatpickr" --}}
<script src="{{ URL::asset('build/libs/flatpickr/flatpickr.min.js') }}"></script>
{{-- Sync data-sidebar dengan data-bs-theme agar dark mode juga menerangi sidebar (template CSS default) --}}
<script>
(function(){
    var html=document.documentElement;
    new MutationObserver(function(){
        if(html.getAttribute('data-bs-theme')==='dark'){
            html.setAttribute('data-sidebar','dark');
        } else {
            html.setAttribute('data-sidebar','light');
        }
    }).observe(html,{attributes:true,attributeFilter:['data-bs-theme']});
})();
</script>

{{-- app.js HARUS dimuat terakhir — berisi init simplebar, hamburger toggle, sidebar logic, dark mode, dll --}}
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script src="{{ URL::asset('build/js/pages/notifications.init.js') }}"></script>

@yield('script')
@yield('script-bottom')
@stack('scripts')
