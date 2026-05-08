<script>window.userId = "{{ Auth::user()->id }}";</script>
{{-- Pusher config (dibaca oleh notifications.init.js) --}}
<meta name="pusher-key" content="{{ config('broadcasting.connections.pusher.key') }}">
<meta name="pusher-cluster" content="{{ config('broadcasting.connections.pusher.options.cluster') }}">
<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO -->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="index" class="logo logo-dark">
                        <span class="logo-sm">
                            <img src="{{ URL::asset('build/images/logo-sm.png') }}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ URL::asset('build/images/logo-dark.png') }}" alt="" height="17">
                        </span>
                    </a>

                    <a href="index" class="logo logo-light">
                        <span class="logo-sm">
                            <img src="{{ URL::asset('build/images/logo-sm.png') }}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ URL::asset('build/images/logo-light.png') }}" alt="" height="17">
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
                            aria-expanded="false">
                        <i class='bx bx-bell fs-22'></i>
                        <span class="position-absolute topbar-badge translate-middle rounded-pill bg-danger notif-badge-count d-none"
                              id="notif-badge-count">
                            0
                            <span class="visually-hidden">unread messages</span>
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
                                        <span class="text-white text-opacity-75 notif-new-badge" id="notif-new-badge">0</span>
                                        <span class="text-white text-opacity-75"> belum dibaca</span>
                                    </p>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button"
                                            class="btn btn-sm btn-ghost-light rounded-circle p-1 notif-mark-all-btn"
                                            title="Tandai semua dibaca"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="bottom">
                                        <i class='bx bx-check-double fs-16'></i>
                                    </button>
                                    <a href="#"
                                       class="btn btn-sm btn-ghost-light rounded-circle p-1 notif-all-link"
                                       title="Lihat semua"
                                       data-bs-toggle="tooltip"
                                       data-bs-placement="bottom">
                                        <i class='bx bx-arrow-from-right fs-16'></i>
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
                                            <i class='bx bx-bell-slash display-4 text-muted opacity-25'></i>
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
                                            <i class='bx bx-bell-check display-4 text-success opacity-25'></i>
                                        </div>
                                        <p class="text-muted mb-1" style="font-size: 14px;">Semua sudah dibaca</p>
                                        <small class="text-muted">Tidak ada notifikasi baru</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="dropdown-footer py-2 px-3 border-top">
                            <a href="#" class="notif-all-link d-block text-center py-1">
                                <small class="text-primary fw-medium">Lihat Semua Notifikasi</small>
                                <i class='bx bx-right-arrow-alt ms-1 text-primary'></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="dropdown ms-sm-3 header-item">
                    <button type="button" class="btn" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <img class="rounded-circle header-profile-user" src="@if (Auth::user()->avatar != '') {{ URL::asset('images/' . Auth::user()->avatar) }}@else{{ URL::asset('build/images/users/about.jpg') }} @endif" alt="Header Avatar">
                            <span class="text-start ms-xl-2">
                                <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{ Auth::user()->name }}</span>
                                <span class="d-none d-xl-block ms-1 fs-12 user-name-sub-text">Founder</span>
                            </span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <h6 class="dropdown-header">Welcome {{ Auth::user()->name }}!</h6>
                        <a class="dropdown-item" href="pages-profile"><i class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Profile</span></a>
                        <a class="dropdown-item" href="apps-tasks-kanban"><i class="mdi mdi-calendar-check-outline text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Taskboard</span></a>
                        <a class="dropdown-item" href="pages-profile-settings"><span class="badge bg-success-subtle text-success mt-1 float-end">New</span><i class="mdi mdi-cog-outline text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Settings</span></a>
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
</header>
