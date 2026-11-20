<!doctype html >
<html lang="id" data-layout="vertical" data-sidebar-size="lg" data-bs-theme="light"
    data-sidebar="light" data-topbar="light"
    data-theme="galaxy" data-theme-colors="default">
<head>
    <meta charset="utf-8" />
    @include('layouts.partials.seo')
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('build/images/alim-sm-light.png')}}">
    <link rel="apple-touch-icon" href="{{ URL::asset('build/images/alim-sm-light.png') }}">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="preconnect" href="https://js.pusher.com" crossorigin>
    <!-- jQuery — loaded early so inline scripts in page content/script sections can use $ -->
    <script src="{{ URL::asset('build/vendor/jquery/jquery.min.js') }}"></script>
    <!-- Select2 -->
    <link href="{{ URL::asset('build/vendor/select2/css/select2.min.css') }}" rel="stylesheet" />
    <script src="{{ URL::asset('build/vendor/select2/js/select2.min.js') }}"></script>
    @include('layouts.head-css')
    @if(request()->routeIs('user.asrama.*'))
        @include('dormitory._ui-styles')
    @endif
    @include('layouts.partials.theme-fixes')
    <style>
        /* Logo ALIM: paksa logo yang tepat untuk mode terang/gelap. */
        [data-bs-theme="light"] .logo.logo-light { display: none !important; }
        [data-bs-theme="light"] .logo.logo-dark { display: inline-block !important; }
        [data-bs-theme="dark"] .logo.logo-dark { display: none !important; }
        [data-bs-theme="dark"] .logo.logo-light { display: inline-block !important; }
        .navbar-brand-box .logo-sm img { object-fit: contain; }
        .app-menu .logo-lg img, .navbar-brand-box .logo-lg img { object-fit: contain; }
    </style>
    @if(request()->routeIs('user.dashboard', 'user.payroll.*', 'user.payroll-slip.*', 'user.cuti.*', 'user.kontrak.*', 'user.kinerja.*', 'user.pelatihan.*', 'user.kesejahteraan.*', 'user.peraturan.*', 'user.jam-kerja.*', 'user.absensi-gtk.*', 'user.kehadiran.*', 'user.personalia.*', 'user.analisis-gtk.*', 'user.rapor-gtk.*'))
        @include('personalia._gtk-ui')
    @endif
    <link rel="stylesheet" href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/libs/toastify-js/src/toastify.css') }}">

    {{-- Pusher + Laravel Echo (Real-time Notification) --}}
    <script src="{{ URL::asset('build/vendor/pusher/pusher.min.js') }}"></script>
    <script src="{{ URL::asset('build/vendor/echo/echo.iife.min.js') }}"></script>
</head>

@section('body')
    @include('layouts.body')
@show
    <!-- Begin page -->
    <div id="layout-wrapper">
        @include('layouts.topbar')
        @include('layouts.sidebar')
        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                @include('components.impersonate-banner')
                <div class="container-fluid @if(request()->routeIs('user.asrama.*')) asrama-page @endif">
                    @yield('content')
                    @include('components.delete-modal')
                </div>
                <!-- container-fluid -->
                @yield('modal')

                @include('layouts.partials.permission-setup')
                @include('layouts.partials.flash-modals')
                @include('layouts.partials.confirm-interceptor')
            </div>
            <!-- End Page-content -->
            @include('layouts.footer')
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    {{-- Dummy Elements untuk Mencegah Error app.js Velzon --}}
    <div id="two-column-menu" style="display: none;"></div>
    <div id="scrollbar" style="display: none;"></div>

    {{-- @include('layouts.customizer') --}}

    <!-- JAVASCRIPT -->
    @include('layouts.vendor-scripts')
    @include('layouts.partials.searchable-selects')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/toastify-js/src/toastify.js') }}"></script>
    <script src="{{ URL::asset('build/js/sidebar-scroll.js') }}"></script>
    @unless(request()->routeIs('user.sa.ai-chat.*') || request()->routeIs('user.ai.*') || (auth()->check() && auth()->user()->isStudentOperator()))
        @include('layouts.partials.ai-chat-widget')
    @endunless
    {{-- Skrip khusus halaman: @yield('script') dirender di vendor-scripts (jangan ganda!) --}}
    @stack('script')
    @stack('scripts')
    @yield('js')

    {{-- Cegah roda mouse mengubah nilai input angka (leger, buku nilai, dsb.).
         Input angka bertambah/berkurang saat roda mouse digeser di atasnya.
         Listener dipasang di fase capture agar tidak kalah oleh handler lain
         yang memanggil stopPropagation, lalu gulir diteruskan manual ke
         container terdekat supaya halaman tetap bisa di-scroll. --}}
    <script>
        (function () {
            'use strict';

            function scrollContainer(el) {
                var node = el.parentElement;

                while (node && node !== document.body && node !== document.documentElement) {
                    var style = window.getComputedStyle(node);

                    if ((style.overflowY === 'auto' || style.overflowY === 'scroll')
                        && node.scrollHeight > node.clientHeight) {
                        return node;
                    }

                    node = node.parentElement;
                }

                return null;
            }

            document.addEventListener('wheel', function (event) {
                var target = event.target;

                if (! target || target.tagName !== 'INPUT' || target.type !== 'number') {
                    return;
                }

                event.preventDefault();

                if (document.activeElement === target) {
                    target.blur();
                }

                var deltaY = event.deltaY;
                var deltaX = event.deltaX;

                if (event.deltaMode === 1) {
                    deltaY *= 16;
                    deltaX *= 16;
                } else if (event.deltaMode === 2) {
                    deltaY *= window.innerHeight;
                    deltaX *= window.innerWidth;
                }

                var container = scrollContainer(target);

                if (container) {
                    container.scrollTop += deltaY;
                    container.scrollLeft += deltaX;
                } else {
                    window.scrollBy(deltaX, deltaY);
                }
            }, { passive: false, capture: true });

            {{-- Panah atas/bawah jangan menambah atau mengurangi nilai pada sel
                 tabel nilai. Navigasi pindah baris tetap jalan karena handler
                 tabel berjalan setelah ini dan memindahkan fokus. --}}
            document.addEventListener('keydown', function (event) {
                if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') {
                    return;
                }

                var target = event.target;

                if (! target || target.tagName !== 'INPUT' || target.type !== 'number') {
                    return;
                }

                // Hanya sel tabel nilai; input lain (mis. bobot NR di luar tabel)
                // dibiarkan memakai panah seperti biasa.
                var inNilaiGrid = target.classList.contains('sheet-input')
                    || target.closest('table.js-tools-table')
                    || target.closest('table[data-paste-grid]');

                if (! inNilaiGrid) {
                    return;
                }

                event.preventDefault();
            }, { capture: true });
        })();
    </script>
</body>

</html>