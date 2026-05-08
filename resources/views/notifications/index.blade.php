@extends('layouts.master')
@section('title')
    Notifikasi
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
@endsection
@section('content')
    <meta name="user-id" content="{{ auth()->id() }}">
    @component('components.breadcrumb')
        @slot('li_1')
            Home
        @endslot
        @slot('title')
            Notifikasi
        @endslot
    @endcomponent

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Total Notifikasi</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="badge bg-primary-subtle text-primary">{{ $stats['total'] }}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                <span class="counter-value" data-target="{{ $stats['total'] }}">{{ $stats['total'] }}</span>
                            </h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-3">
                                <i class="bx bx-bell text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Belum Dibaca</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="badge bg-danger-subtle text-danger">{{ $stats['unread'] }}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                <span class="counter-value" data-target="{{ $stats['unread'] }}">{{ $stats['unread'] }}</span>
                            </h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-3">
                                <i class="bx bx-bell-off text-danger"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Sudah Dibaca</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="badge bg-success-subtle text-success">{{ $stats['total'] - $stats['unread'] }}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                <span class="counter-value" data-target="{{ $stats['total'] - $stats['unread'] }}">{{ $stats['total'] - $stats['unread'] }}</span>
                            </h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-3">
                                <i class="bx bx-check-circle text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-bell me-2"></i>Daftar Notifikasi
                    </h5>
                    <div class="d-flex gap-2">
                        <button type="button"
                                class="btn btn-sm btn-success notif-mark-all"
                                title="Tandai semua dibaca">
                            <i class="bx bx-check-double me-1"></i> Tandai Semua Dibaca
                        </button>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary notif-filter-toggle"
                                title="Filter">
                            <i class="bx bx-filter-alt"></i>
                        </button>
                    </div>
                </div>

                {{-- Filter Panel --}}
                <div class="notif-filter-panel border-top border-bottom py-3 px-4 bg-light d-none" id="notifFilterPanel">
                    <form method="GET" action="{{ route('user.notifications.index') }}"
                          class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small fw-medium">Modul</label>
                            <select name="module" class="form-select form-select-sm">
                                <option value="">Semua Modul</option>
                                @foreach(['recruitment','gtk','work_unit','career','approval','system','transfer','education','competency','training'] as $mod)
                                    <option value="{{ $mod }}" {{ ($filters['module'] ?? '') == $mod ? 'selected' : '' }}>
                                        {{ ucfirst($mod) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-medium">Tipe</label>
                            <select name="type" class="form-select form-select-sm">
                                <option value="">Semua Tipe</option>
                                @foreach(['info','success','warning','error'] as $type)
                                    <option value="{{ $type }}" {{ ($filters['type'] ?? '') == $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-medium">Prioritas</label>
                            <select name="priority" class="form-select form-select-sm">
                                <option value="">Semua Prioritas</option>
                                @foreach(['low','medium','high','urgent'] as $p)
                                    <option value="{{ $p }}" {{ ($filters['priority'] ?? '') == $p ? 'selected' : '' }}>
                                        {{ ucfirst($p) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-medium">Status</label>
                            <select name="is_read" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                <option value="false" {{ ($filters['is_read'] ?? '') == 'false' ? 'selected' : '' }}>Belum Dibaca</option>
                                <option value="true" {{ ($filters['is_read'] ?? '') == 'true' ? 'selected' : '' }}>Sudah Dibaca</option>
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bx bx-search me-1"></i> Filter
                            </button>
                            <a href="{{ route('user.notifications.index') }}"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-reset"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>

                <div class="card-body p-0">
                    {{-- Notification List --}}
                    <div class="notif-page-list" id="notifPageList">
                        @forelse($notifications as $notif)
                            <div class="notif-page-item d-flex align-items-start px-4 py-3 border-bottom {{ $notif->is_read ? '' : 'notif-unread' }}"
                                 data-id="{{ $notif->id }}"
                                 data-url="{{ $notif->action_url ?? '#' }}">
                                {{-- Icon --}}
                                <div class="flex-shrink-0 me-3">
                                    <span class="avatar-title rounded-circle fs-20 {{ $notif->type_badge_class }}">
                                        <i class="bx {{ $notif->module_icon }}"></i>
                                    </span>
                                </div>

                                {{-- Content --}}
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div class="w-75">
                                            <h6 class="mb-1 fs-14 fw-semibold text-dark">
                                                @if(in_array($notif->priority, ['urgent','high']))
                                                    <span class="badge bg-danger me-1" style="font-size:9px;">!</span>
                                                @endif
                                                {{ $notif->title }}
                                            </h6>
                                            <p class="mb-1 fs-12 text-muted"
                                               style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                                {{ $notif->message }}
                                            </p>
                                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                                <small class="text-muted">
                                                    <i class="bx bx-time me-1"></i>
                                                    {{ $notif->created_at->diffForHumans() }}
                                                </small>
                                                @if($notif->module)
                                                    <span class="badge bg-light text-dark">
                                                        <i class="{{ $notif->module_icon }} me-1"></i>
                                                        {{ $notif->module_label }}
                                                    </span>
                                                @endif
                                                @if($notif->reference_code)
                                                    <small class="text-muted">
                                                        <i class="bx bx-hash me-1"></i>{{ $notif->reference_code }}
                                                    </small>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Actions --}}
                                        <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                                            <div class="d-flex gap-1">
                                                @if(!$notif->is_read)
                                                    <button class="btn btn-sm btn-ghost-secondary p-1 notif-read-action"
                                                            data-id="{{ $notif->id }}"
                                                            title="Tandai dibaca">
                                                        <i class="bx bx-check fs-14"></i>
                                                    </button>
                                                @endif
                                                <button class="btn btn-sm btn-ghost-secondary p-1 notif-delete-action"
                                                        data-id="{{ $notif->id }}"
                                                        title="Hapus">
                                                    <i class="bx bx-trash fs-14 text-danger"></i>
                                                </button>
                                            </div>
                                            @if($notif->action_url)
                                                <a href="{{ $notif->action_url }}"
                                                   class="btn btn-sm btn-outline-primary btn-sm py-0 px-2 notif-action-link">
                                                    <i class="bx bx-arrow-forward fs-11"></i>
                                                    {{ $notif->action_text ?? 'Lihat' }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="bx bx-bell-slash display-4 text-muted opacity-25"></i>
                                </div>
                                <h5 class="text-muted">Tidak ada notifikasi</h5>
                                <p class="text-muted mb-0">Semua notifikasi sudah dilihat</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Pagination --}}
                    @if($notifications->hasPages())
                        <div class="px-4 py-3 border-top bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    Menampilkan {{ $notifications->firstItem() ?? 0 }} - {{ $notifications->lastItem() ?? 0 }}
                                    dari {{ $notifications->total() }} notifikasi
                                </div>
                                <div>
                                    {{ $notifications->withQueryString()->links('pagination.bootstrap5') }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
    (function () {
        'use strict';

        var userId = document.querySelector('meta[name="user-id"]')?.content || '{{ auth()->id() }}';

        function csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        }

        function getApiUrl(path) {
            return '/' + userId + '/' + path;
        }

        function fetchJSON(url, options = {}) {
            return fetch(url, {
                ...options,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                    ...(options.headers || {}),
                },
            }).then(r => r.json());
        }

        // ── Toggle filter panel ────────────────────────────────
        document.querySelector('.notif-filter-toggle')?.addEventListener('click', function () {
            const panel = document.getElementById('notifFilterPanel');
            panel.classList.toggle('d-none');
        });

        // ── Mark all read ─────────────────────────────────────
        document.querySelector('.notif-mark-all')?.addEventListener('click', function () {
            if (!confirm('Tandai semua notifikasi sebagai sudah dibaca?')) return;

            fetchJSON(getApiUrl('notifications/mark-all-read'), { method: 'POST' })
                .then(res => {
                    if (res.success) {
                        document.querySelectorAll('.notif-page-item').forEach(el => {
                            el.classList.remove('notif-unread');
                        });
                        // Update badge di topbar
                        const badge = document.getElementById('notif-badge-count');
                        if (badge) {
                            badge.classList.add('d-none');
                            badge.textContent = '0';
                        }
                        Toastify({
                            text: 'Semua notifikasi ditandai dibaca',
                            duration: 3000,
                            gravity: 'top',
                            position: 'right',
                            backgroundColor: '#198754',
                        }).showToast();
                    }
                });
        });

        // ── Single mark read ──────────────────────────────────
        document.querySelectorAll('.notif-read-action').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const id = this.dataset.id;
                fetchJSON(getApiUrl('notifications/' + id + '/mark-read'), { method: 'POST' })
                    .then(() => {
                        const item = this.closest('.notif-page-item');
                        if (item) item.classList.remove('notif-unread');
                        this.closest('.d-flex.gap-1')?.remove();
                    });
            });
        });

        // ── Delete notification ────────────────────────────────
        document.querySelectorAll('.notif-delete-action').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const id = this.dataset.id;
                const item = this.closest('.notif-page-item');

                Swal.fire({
                    title: 'Hapus Notifikasi?',
                    text: 'Notifikasi yang dihapus tidak dapat dikembalikan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f06548',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                }).then(result => {
                    if (!result.isConfirmed) return;
                    fetchJSON(getApiUrl('notifications/' + id), { method: 'DELETE' })
                        .then(() => {
                            if (item) {
                                item.style.transition = 'all 0.3s';
                                item.style.opacity = '0';
                                item.style.transform = 'translateX(20px)';
                                setTimeout(() => item.remove(), 300);
                            }
                        });
                });
            });
        });

        // ── Click on notification item ────────────────────────
        document.querySelectorAll('.notif-page-item').forEach(item => {
            item.addEventListener('click', function (e) {
                const isActionBtn = e.target.closest('button, a, .btn');
                if (isActionBtn) return;

                const url = this.dataset.url || '#';
                if (url !== '#') {
                    window.location.href = url;
                }
            });
        });

        // ── Init: show filter if any filter active ────────────
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.toString()) {
            document.getElementById('notifFilterPanel')?.classList.remove('d-none');
        }

    })();
    </script>
@endsection
