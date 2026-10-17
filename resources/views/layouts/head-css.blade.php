{{-- Variabel CSS Bootstrap — build tema ini tidak menyertakannya, sehingga var(--bs-*) gagal.
     Blok ini mengembalikan variabel standar Bootstrap 5.3 (terang & gelap). --}}
<style id="bs-theme-vars">
:root {
    --bs-blue:#0d6efd; --bs-indigo:#6610f2; --bs-purple:#6f42c1; --bs-pink:#d63384; --bs-red:#dc3545;
    --bs-orange:#fd7e14; --bs-yellow:#ffc107; --bs-green:#198754; --bs-teal:#20c997; --bs-cyan:#0dcaf0;
    --bs-black:#000; --bs-white:#fff; --bs-gray:#6c757d; --bs-gray-dark:#343a40;
    --bs-gray-100:#f8f9fa; --bs-gray-200:#e9ecef; --bs-gray-300:#dee2e6; --bs-gray-400:#ced4da;
    --bs-gray-500:#adb5bd; --bs-gray-600:#6c757d; --bs-gray-700:#495057; --bs-gray-800:#343a40; --bs-gray-900:#212529;
    --bs-primary:#0d6efd; --bs-secondary:#6c757d; --bs-success:#198754; --bs-info:#0dcaf0; --bs-warning:#ffc107; --bs-danger:#dc3545;
    --bs-primary-rgb:13,110,253; --bs-secondary-rgb:108,117,125; --bs-success-rgb:25,135,84;
    --bs-info-rgb:13,202,240; --bs-warning-rgb:255,193,7; --bs-danger-rgb:220,53,69;
    --bs-body-color:#212529; --bs-body-color-rgb:33,37,41;
    --bs-body-bg:#fff; --bs-body-bg-rgb:255,255,255;
    --bs-emphasis-color:#000; --bs-secondary-color:rgba(33,37,41,.75); --bs-secondary-bg:#e9ecef;
    --bs-tertiary-color:rgba(33,37,41,.5); --bs-tertiary-bg:#f8f9fa;
    --bs-border-color:#dee2e6; --bs-border-color-translucent:rgba(0,0,0,.175);
    --bs-primary-text-emphasis:#052c65; --bs-primary-bg-subtle:#cfe2ff; --bs-primary-border-subtle:#9ec5fe;
    --bs-secondary-text-emphasis:#2b2f32; --bs-secondary-bg-subtle:#e2e3e5; --bs-secondary-border-subtle:#c4c8cb;
    --bs-success-text-emphasis:#0a3622; --bs-success-bg-subtle:#d1e7dd; --bs-success-border-subtle:#a3cfbb;
    --bs-warning-text-emphasis:#664d03; --bs-warning-bg-subtle:#fff3cd; --bs-warning-border-subtle:#ffe69c;
    --bs-danger-text-emphasis:#58151c; --bs-danger-bg-subtle:#f8d7da; --bs-danger-border-subtle:#f1aeb5;
    --bs-info-text-emphasis:#055160; --bs-info-bg-subtle:#cff4fc; --bs-info-border-subtle:#9eeaf9;
    --bs-light-border-subtle:#e9ecef; --bs-dark-bg-subtle:#ced4da;
    --bs-border-radius:.375rem; --bs-border-radius-sm:.25rem; --bs-border-radius-lg:.5rem;
}
[data-bs-theme="dark"] {
    --bs-body-color:#dee2e6; --bs-body-color-rgb:222,226,230;
    --bs-body-bg:#212529; --bs-body-bg-rgb:33,37,41;
    --bs-emphasis-color:#fff; --bs-secondary-color:rgba(222,226,230,.75); --bs-secondary-bg:#343a40;
    --bs-tertiary-color:rgba(222,226,230,.5); --bs-tertiary-bg:#2b3035;
    --bs-border-color:#495057; --bs-border-color-translucent:rgba(255,255,255,.15);
    --bs-primary-text-emphasis:#6ea8fe; --bs-primary-bg-subtle:#031633; --bs-primary-border-subtle:#084298;
    --bs-secondary-text-emphasis:#a7acb1; --bs-secondary-bg-subtle:#161719; --bs-secondary-border-subtle:#41464b;
    --bs-success-text-emphasis:#75b798; --bs-success-bg-subtle:#051b11; --bs-success-border-subtle:#0f5132;
    --bs-warning-text-emphasis:#ffda6a; --bs-warning-bg-subtle:#332701; --bs-warning-border-subtle:#664d03;
    --bs-danger-text-emphasis:#ea868f; --bs-danger-bg-subtle:#2c0b0e; --bs-danger-border-subtle:#842029;
    --bs-info-text-emphasis:#6edff6; --bs-info-bg-subtle:#032830; --bs-info-border-subtle:#087990;
}
</style>
<!-- Layout config Js -->
<script src="{{ URL::asset('build/js/layout.js') }}"></script>
<!-- Bootstrap Css -->
<link href="{{ URL::asset('build/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
<!-- Icons Css -->
<link href="{{ URL::asset('build/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
<!-- App Css-->
<link href="{{ URL::asset('build/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
<!-- custom Css-->
<link href="{{ URL::asset('build/css/custom.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#405189">
{{-- Gaya khusus halaman — dimuat SETELAH CSS tema agar tidak tertimpa --}}
@yield('css')
@stack('css')
@stack('style')
@stack('styles')
