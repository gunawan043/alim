<script>window.userId = "{{ Auth::user()->id }}";</script>
{{-- Pusher config (dibaca oleh notifications.init.js) --}}
<meta name="pusher-key" content="{{ config('broadcasting.connections.pusher.key') }}">
<meta name="pusher-cluster" content="{{ config('broadcasting.connections.pusher.options.cluster') }}">
<meta name="web-push-vapid-public-key" content="{{ \App\Support\WebPushConfig::vapidPublicKey() }}">
@php
    $portalTokenForPush = request()->routeIs('portal.*') ? request()->route('token') : null;
    $webPushSubscribeUrl = $portalTokenForPush
        ? route('portal.web-push.subscribe', ['token' => $portalTokenForPush])
        : (auth()->check() ? route('user.notifications.web-push.subscribe', ['userId' => auth()->id()]) : '');
    $webPushUnsubscribeUrl = $portalTokenForPush
        ? route('portal.web-push.unsubscribe', ['token' => $portalTokenForPush])
        : (auth()->check() ? route('user.notifications.web-push.unsubscribe', ['userId' => auth()->id()]) : '');
@endphp
<script>
    window.alimWebPushSubscribeUrl = @json($webPushSubscribeUrl);
    window.alimWebPushUnsubscribeUrl = @json($webPushUnsubscribeUrl);
</script>
@php
    $notifUnreadCount = 0;
    if (auth()->check()) {
        try {
            $notifUnreadCount = app(\App\Services\NotificationUniversalService::class)->getUnreadCount(auth()->id());
        } catch (\Throwable) { $notifUnreadCount = 0; }
    }
@endphp
<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO -->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="index" class="logo logo-dark">
                        <span class="logo-sm">
                            <img src="{{ URL::asset('build/images/alim-sm-light.png') }}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ URL::asset('build/images/alim-dark-name.png') }}" alt="" height="17">
                        </span>
                    </a>

                    <a href="index" class="logo logo-light">
                        <span class="logo-sm">
                            <img src="{{ URL::asset('build/images/alim-sm-light.png') }}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ URL::asset('build/images/alim-light-name.png') }}" alt="" height="17">
                        </span>
                    </a>
                </div>

                <button type="button"
                        class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger"
                        id="topnav-hamburger-icon">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>

                {{-- Pencarian global: menu, fitur, & santri --}}
                <div class="alim-search d-none d-md-block position-relative ms-2 mt-3 flex-grow-1" style="min-width:300px;">
                    <span class="alim-search-icon"><i class="ri-search-line"></i></span>
                    <input type="search" id="globalSearchInput" class="alim-search-input"
                           placeholder="Cari menu, fitur, atau santri…" autocomplete="off">
                    <div id="globalSearchResults" class="alim-search-panel d-none"></div>
                </div>

            </div>

            <div class="d-flex align-items-center">

                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-toggle="fullscreen">
                        <i class='bx bx-fullscreen fs-22'></i>
                    </button>
                </div>

                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode">
                        <i class='bx bx-moon fs-22'></i>
                    </button>
                </div>

                {{-- ============================================================
                     NOTIFICATION BELL
                     ============================================================ --}}
                <div class="dropdown topbar-head-dropdown ms-1 header-item" id="notificationDropdown">
                    <button type="button"
                            class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle position-relative"
                            id="page-header-notifications-dropdown"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                            aria-haspopup="true"
                            aria-expanded="false"
                            aria-label="Notifikasi">
                        <i class="bx bx-bell fs-22"></i>
                        <span class="position-absolute topbar-badge fs-10 translate-middle badge rounded-pill bg-danger {{ $notifUnreadCount > 0 ? '' : 'd-none' }}"
                            id="notif-badge-count">
                            {{ $notifUnreadCount }}
                            <span class="visually-hidden">pesan belum dibaca</span>
                        </span>
                    </button>

                    {{-- DROPDOWN PANEL --}}
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0 notif-dropdown-panel"
                        aria-labelledby="page-header-notifications-dropdown"
                        style="width: 400px;">

                        {{-- Header --}}
                        <div class="dropdown-head bg-primary bg-pattern rounded-top py-3 px-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="m-0 fs-16 fw-semibold text-white">Notifikasi</h6>
                                    <p class="mb-0 mt-1">
                                        <span class="text-white text-opacity-75 fw-semibold" id="notif-new-badge">{{ $notifUnreadCount }}</span>
                                        <span class="text-white text-opacity-75"> belum dibaca</span>
                                    </p>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button"
                                            class="btn btn-sm btn-ghost-light rounded-circle p-1 notif-mark-all-btn"
                                            title="Tandai semua dibaca"
                                            aria-label="Tandai semua dibaca"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="bottom">
                                        <i class="bx bx-check-double fs-16"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm btn-ghost-light rounded-circle p-1 d-none"
                                            id="web-push-enable"
                                            title="Aktifkan notifikasi browser"
                                            aria-label="Aktifkan notifikasi browser"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="bottom">
                                        <i class="bx bx-bell-plus fs-16"></i>
                                    </button>
                                    <a href="{{ route('user.notifications.page', ['userId' => $userId ?? auth()->id()]) }}"
                                    class="btn btn-sm btn-ghost-light rounded-circle p-1 notif-all-link"
                                    title="Lihat semua"
                                    aria-label="Lihat semua notifikasi"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="bottom">
                                        <i class="bx bx-arrow-from-right fs-16"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Tab Navigation --}}
                        <div class="px-2 pt-2">
                            <ul class="nav nav-tabs dropdown-tabs nav-tabs-custom nav-fill"
                                data-dropdown-tabs="true"
                                id="notifTab"
                                role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active"
                                    data-bs-toggle="tab"
                                    href="#notif-tab-all"
                                    role="tab"
                                    aria-selected="true">
                                        Semua
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link notif-tab-unread"
                                    data-bs-toggle="tab"
                                    href="#notif-tab-unread"
                                    role="tab"
                                    aria-selected="false">
                                        Belum Dibaca
                                    </a>
                                </li>
                            </ul>
                        </div>

                        {{-- Tab Content --}}
                        <div class="tab-content" id="notifTabContent">
                            {{-- All Tab --}}
                            <div class="tab-pane fade show active"
                                id="notif-tab-all"
                                role="tabpanel">
                                <div class="notif-list-scroll"
                                    id="notif-all-list"
                                    data-simplebar
                                    style="max-height: 340px;">
                                    <div class="text-center py-5 notif-empty-state d-none">
                                        <div class="mb-3">
                                            <i class="bx bx-bell-slash display-4 text-muted opacity-25"></i>
                                        </div>
                                        <p class="text-muted mb-1" style="font-size: 14px;">Tidak ada notifikasi</p>
                                        <small class="text-muted">Semua sudah dilihat</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Unread Tab --}}
                            <div class="tab-pane fade"
                                id="notif-tab-unread"
                                role="tabpanel">
                                <div class="notif-list-scroll"
                                    id="notif-unread-list"
                                    data-simplebar
                                    style="max-height: 340px;">
                                    <div class="text-center py-5 notif-empty-state d-none">
                                        <div class="mb-3">
                                            <i class="bx bx-bell-check display-4 text-success opacity-25"></i>
                                        </div>
                                        <p class="text-muted mb-1" style="font-size: 14px;">Semua sudah dibaca</p>
                                        <small class="text-muted">Tidak ada notifikasi baru</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="dropdown-footer py-2 px-3 border-top">
                            <a href="{{ route('user.notifications.page', ['userId' => $userId ?? auth()->id()]) }}"
                            class="notif-all-link d-block text-center py-1">
                                <small class="text-primary fw-medium">Lihat Semua Notifikasi</small>
                                <i class="bx bx-right-arrow-alt ms-1 text-primary"></i>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Only View As is available — non-destructive role preview.
                     The legacy "Login As" (impersonate) switcher is intentionally
                     disabled so the System Admin can preview any role from one
                     account without ever switching identities. --}}
                @if(canPermission('super-admin-only'))
                    @include('system._switcher')
                @endif

                <div class="dropdown ms-sm-3 header-item">
                    <button type="button" class="btn" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <img class="rounded-circle header-profile-user" src="@if (Auth::user()->avatar != '') {{ URL::asset('images/' . Auth::user()->avatar) }}@else{{ URL::asset('build/images/users/about.jpg') }} @endif" alt="Header Avatar">
                            <span class="text-start ms-xl-2">
                                <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{ Auth::user()->name }}</span>
                                <span class="d-none d-xl-block ms-1 fs-12 user-name-sub-text">{{ Auth::user()->getRoleNames()->first() ?? '-' }}</span>
                            </span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        @php
                            $topbarUser = Auth::user();
                            $canManageSecurity = (method_exists($topbarUser, 'isSystemAdmin') && $topbarUser->isSystemAdmin())
                                || $topbarUser->hasRole('Super Admin');
                            $settingsUrl = $canManageSecurity
                                ? route('user.sa.system-settings.index', ['userId' => $topbarUser->id, 'tab' => 'security'])
                                : route('user.profile.security', ['userId' => $topbarUser->id]);
                        @endphp
                        <h6 class="dropdown-header">Welcome {{ $topbarUser->name }}!</h6>
                        <a class="dropdown-item" href="{{ route('user.profile.my', ['userId' => $topbarUser->id]) }}"><i class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Profile</span></a>
                        <a class="dropdown-item" href="{{ route('user.profile.security', ['userId' => $topbarUser->id]) }}"><i class="mdi mdi-shield-key-outline text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Keamanan Akun</span></a>
                        <a class="dropdown-item" href="apps-tasks-kanban"><i class="mdi mdi-calendar-check-outline text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Taskboard</span></a>
                        <a class="dropdown-item" href="{{ $settingsUrl }}"><span class="badge bg-success-subtle text-success mt-1 float-end">New</span><i class="mdi mdi-cog-outline text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Settings</span></a>
                        <a class="dropdown-item" href="auth-lockscreen-basic"><i class="mdi mdi-lock text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Lock screen</span></a>
                        <a class="dropdown-item " href="javascript:void();" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="bx bx-power-off font-size-16 align-middle me-1"></i> <span key="t-logout">@lang('translation.logout')</span></a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('globalSearchInput');
        const results = document.getElementById('globalSearchResults');
        if (!input || !results) return;

        let timer = null;

        function render(groupTitle, icon, items) {
            if (!items || !items.length) return '';
            let html = '<div class="alim-search-group"><i class="' + icon + '"></i> ' + groupTitle + '</div>';
            items.forEach(function (item) {
                html += '<a class="alim-search-item" href="' + item.url + '">' +
                    '<div class="alim-search-label">' + item.label + '</div>' +
                    (item.meta ? '<div class="alim-search-meta">' + item.meta + '</div>' : '') +
                    '</a>';
            });
            return html;
        }

        input.addEventListener('input', function () {
            const q = input.value.trim();
            clearTimeout(timer);

            if (q.length < 2) { results.classList.add('d-none'); results.innerHTML = ''; return; }

            timer = setTimeout(function () {
                results.innerHTML = '<div class="alim-search-loading"><span class="spinner-border spinner-border-sm me-2"></span>Mencari…</div>';
                results.classList.remove('d-none');

                fetch("{{ route('user.search', ['userId' => auth()->id()]) }}?q=" + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(function (data) {
                    const total = (data.menus ? data.menus.length : 0) + (data.students ? data.students.length : 0);
                    let html = render('Menu & Fitur', 'ri-apps-2-line', data.menus) + render('Santri', 'ri-user-3-line', data.students);

                    if (!html) {
                        html = '<div class="alim-search-empty"><i class="ri-search-eye-line"></i> Tidak ada hasil untuk “' + q + '”.</div>';
                    } else {
                        html += '<div class="alim-search-footer">' + total + ' hasil · tekan Enter untuk membuka hasil pertama</div>';
                    }

                    results.innerHTML = html;
                    results.classList.remove('d-none');
                })
                .catch(function () {
                    results.innerHTML = '<div class="dropdown-item text-danger small">Pencarian gagal. Coba lagi.</div>';
                    results.classList.remove('d-none');
                });
            }, 250);
        });

        document.addEventListener('click', function (e) {
            if (!results.contains(e.target) && e.target !== input) { results.classList.add('d-none'); }
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                const first = results.querySelector('a.dropdown-item');
                if (first) { window.location = first.getAttribute('href'); }
            }
            if (e.key === 'Escape') { results.classList.add('d-none'); }
        });
    });
    </script>

    

    <style>
        .notif-item { transition: background .15s ease; border-left: 3px solid transparent; }
        .notif-item:hover { background: rgba(10,95,158,.06); }
        .notif-item.notif-unread { background: rgba(10,95,158,.05); border-left-color: #0a5f9e; }
        .notif-deleted { opacity: .55; }
        .notif-list-scroll { max-height: 380px; overflow-y: auto; }
    </style>

    <style>
        /* Warna memakai variabel Bootstrap agar ikut tema terang/gelap,
           tanpa border/outline — pemisah memakai bayangan lembut. */
        .alim-search-icon { position:absolute; left:12px; top:50%; transform:translateY(-80%); color:var(--bs-secondary-color); font-size:16px; }
        .alim-search-input { width:100%; height:38px; padding:0 14px 0 36px; border-radius:10px; font-size:.85rem; background-color:var(--bs-tertiary-bg); border:1px solid transparent; color:var(--bs-body-color); transition:all .15s ease; }
        .alim-search-input::placeholder { color:var(--bs-secondary-color); opacity:1; }
        .alim-search-input:focus { outline:none; border-color:transparent; background-color:var(--bs-body-bg); box-shadow:0 0 0 3px rgba(var(--bs-primary-rgb), .18); }
        .alim-search-panel { position:absolute; top:44px; left:0; right:0; background-color:var(--bs-body-bg); border:0; border-radius:12px; z-index:2000; max-height:420px; overflow:auto; padding:.4rem; box-shadow:0 10px 30px rgba(0,0,0,.14); }
        [data-bs-theme="dark"] .alim-search-panel { box-shadow:0 10px 30px rgba(0,0,0,.55); }
        .alim-search-group { padding:.5rem .7rem .25rem; font-size:.66rem; letter-spacing:.6px; text-transform:uppercase; color:var(--bs-secondary-color); font-weight:600; }
        .alim-search-group i { margin-right:4px; }
        .alim-search-item { display:block; padding:.5rem .7rem; border-radius:9px; color:var(--bs-body-color); text-decoration:none; transition:background .12s ease; }
        .alim-search-item:hover { background-color:var(--bs-tertiary-bg); color:var(--bs-body-color); }
        .alim-search-label { font-size:.82rem; font-weight:600; }
        .alim-search-meta { font-size:.72rem; color:var(--bs-secondary-color); }
        .alim-search-loading, .alim-search-empty { padding:.85rem .7rem; font-size:.8rem; color:var(--bs-secondary-color); text-align:center; }
        .alim-search-empty i { font-size:22px; display:block; margin-bottom:4px; }
        .alim-search-footer { border-top:1px solid var(--bs-border-color); margin-top:.35rem; padding:.45rem .7rem; font-size:.68rem; color:var(--bs-secondary-color); text-align:center; }
        .topbar-badge, .notif-badge-count { min-width:18px; height:18px; line-height:18px; font-size:10px; padding:0 5px; }
    </style>
</header>
