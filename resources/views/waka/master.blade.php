<!doctype html >
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-layout="vertical" data-bs-theme="light" data-topbar="light" data-sidebar="light"
    data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable" data-theme="galaxy"
    data-theme-colors="default">
<head>
    <meta charset="utf-8" />
    <title>@yield('title') | Alim - Academic Learning & Information Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Alim - Academic Learning & Information Management" name="description" />
    <meta content="gunawan_wawan43" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('build/images/alim-sm-light.png')}}">
    @include('layouts.head-css')
    @include('layouts.partials.theme-fixes')
    @include('shared._styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Instead of document.write(), use: -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
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
                <div class="container-fluid">
                    @yield('content')
                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->
            @include('layouts.footer')
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    {{-- @include('layouts.customizer') --}}

    <!-- JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('layouts.partials.flash-modals')
    @include('layouts.partials.confirm-interceptor')
    @include('layouts.vendor-scripts')
    {{-- @yield('script') & @stack('scripts') sudah dirender vendor-scripts (jangan ganda!) --}}
    @stack('script')
</body>

</html>
