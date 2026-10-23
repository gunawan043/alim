@extends('layouts.master')
@section('title') Penghargaan @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('title') Penghargaan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- ============================================================
         STATS CARDS (2 kartu)
    ============================================================ --}}
    <div class="row">
        <div class="col-xl-6 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded">
                                <i class="ri-medal-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Total Penghargaan</p>
                            <h3 class="stat-value">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-trophy-line me-1"></i>Seluruh penghargaan tercatat</p>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <i class="ri-calendar-check-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Bulan Ini</p>
                            <h3 class="stat-value">{{ number_format($stats['thisMonth'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-calendar-event-line me-1"></i>Penghargaan bulan berjalan</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Penghargaan</h5>
                            <p class="text-muted mb-0 small">Apresiasi santri berprestasi di {{ $dormitory->name ?? 'asrama' }}.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <button type="button" class="btn btn-primary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#rewardCreateModal">
                                    <i class="ri-add-line me-1"></i> Tambah
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- ============================================================
                         TOOLBAR PENCARIAN + FILTER (pola template)
                    ============================================================ --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width:520px;">
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="Cari santri..." value="{{ request('search') }}">
                            @foreach(['category', 'level'] as $filterKey)
                                @if(request($filterKey))
                                    <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                                @endif
                            @endforeach
                            <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                                <i class="ri-search-line"></i> Cari
                            </button>
                        </form>

                        <div class="toolbar-actions">
                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#rewardFilterModal">
                                <i class="ri-filter-3-line me-1"></i> Filter Lanjutan
                                @if(request()->hasAny(['category', 'level']))
                                    <span class="badge bg-light text-dark ms-1">Aktif</span>
                                @endif
                            </button>
                            <a href="{{ route('user.asrama.rewards.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light btn-sm" title="Reset filter">
                                <i class="ri-refresh-line"></i>
                            </a>
                        </div>
                    </div>

                    @if(request()->hasAny(['search','category','level']))
                        <div class="mb-3">
                            <span class="text-muted small">Filter aktif:</span>
                            @if(request('search'))
                                <span class="filter-badge active"><i class="ri-search-line"></i> "{{ request('search') }}"</span>
                            @endif
                            @if(request('category'))
                                <span class="filter-badge active"><i class="ri-price-tag-3-line"></i> {{ \App\Models\DormitoryReward::categories()[request('category')] ?? request('category') }}</span>
                            @endif
                            @if(request('level'))
                                <span class="filter-badge active"><i class="ri-bar-chart-line"></i> {{ \App\Models\DormitoryReward::levels()[request('level')] ?? request('level') }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $rewards->count() }}</strong> dari
                            <strong>{{ $rewards->total() }}</strong> penghargaan
                        </small>
                    </div>

                    {{-- ============================================================
                         TABEL
                    ============================================================ --}}
                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Santri</th>
                                    <th>Judul</th>
                                    <th>Kategori</th>
                                    <th>Level</th>
                                    <th>Tanggal</th>
                                    <th>Diberikan Oleh</th>
                                    <th class="text-center" style="width:110px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rewards ?? [] as $i => $reward)
                                    <tr>
                                        <td class="text-center text-muted">{{ $rewards->firstItem() + $i }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $reward->student->name ?? '—' }}</div>
                                            <small class="text-muted">{{ $reward->student->nisn ?? '' }}</small>
                                        </td>
                                        <td>{{ $reward->title }}</td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">{{ $reward->category_text }}</span>
                                        </td>
                                        <td>
                                            @if($reward->level === 'unggulan')
                                                <span class="badge bg-warning-subtle text-warning">{{ $reward->level_text }}</span>
                                            @elseif($reward->level === 'istimewa')
                                                <span class="badge bg-danger-subtle text-danger">{{ $reward->level_text }}</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $reward->level_text }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $reward->awarded_date->format('d/m/Y') }}</td>
                                        <td>{{ $reward->givenBy->name ?? '—' }}</td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('user.asrama.rewards.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'rewardUuid' => $reward->id]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Detail">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                <a href="{{ route('user.asrama.rewards.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'rewardUuid' => $reward->id]) }}"
                                                   class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="ri-pencil-line"></i>
                                                </a>
                                                <form action="{{ route('user.asrama.rewards.destroy', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'rewardUuid' => $reward->id]) }}"
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Yakin hapus penghargaan ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="8" title="Belum ada penghargaan"
                                        message="Belum ada penghargaan yang tercatat untuk asrama ini.">
                                        <button type="button" class="btn btn-primary btn-sm mt-3"
                                                data-bs-toggle="modal" data-bs-target="#rewardCreateModal">
                                            <i class="ri-add-line me-1"></i> Tambah Penghargaan
                                        </button>
                                    </x-empty-state>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($rewards->hasPages())
                        @include('shared._pagination', ['paginator' => $rewards->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL FILTER LANJUTAN
    ============================================================ --}}
    <div class="modal fade" id="rewardFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('user.asrama.rewards.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-filter-3-line me-2 text-primary"></i>Filter Penghargaan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-search-line"></i>Pencarian</div>
                                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama santri...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-price-tag-3-line"></i>Kategori</div>
                                    <select name="category" class="form-select">
                                        <option value="">Semua Kategori</option>
                                        @foreach(\App\Models\DormitoryReward::categories() as $key => $label)
                                            <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-bar-chart-line"></i>Level</div>
                                    <select name="level" class="form-select">
                                        <option value="">Semua Level</option>
                                        @foreach(\App\Models\DormitoryReward::levels() as $key => $label)
                                            <option value="{{ $key }}" {{ request('level') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('user.asrama.rewards.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">
                            <i class="ri-refresh-line me-1"></i> Reset
                        </a>
                        <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line me-1"></i> Terapkan Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL TAMBAH PENGHARGAAN
    ============================================================ --}}
    <div class="modal fade" id="rewardCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('user.asrama.rewards.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                      method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-medal-line me-2 text-warning"></i>Tambah Penghargaan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        @if($errors->any())
                            <div class="alert alert-danger py-2">
                                <i class="ri-error-warning-line me-1"></i> Periksa kembali input yang ditandai.
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Katalog Penghargaan <span class="text-danger">*</span></label>
                                <select name="point_catalog_id" id="modal_reward_catalog" class="form-select" required
                                        onchange="window.syncRewardModal && window.syncRewardModal()">
                                    <option value="">-- Cari dan pilih katalog penghargaan --</option>
                                    @foreach($catalogs as $catalog)
                                        <option value="{{ $catalog->id }}"
                                                data-title="{{ $catalog->name }}"
                                                data-category="{{ $catalog->rewardCategoryValue() }}"
                                                data-level="{{ $catalog->rewardLevelValue() }}"
                                                data-points="{{ $catalog->points_min }}"
                                                @selected(old('point_catalog_id') === $catalog->id)>
                                            {{ $catalog->code }} — {{ $catalog->name }} ({{ $catalog->points_min }} poin)
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Judul, kategori, level, dan poin terisi otomatis dari katalog.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Santri <span class="text-danger">*</span></label>
                                <select name="student_id" id="modal_reward_student" class="form-select" required>
                                    <option value="">-- Cari nama santri --</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}" @selected(old('student_id') === $student->id)>
                                            {{ $student->name }} — {{ $student->nisn ?? 'N/A' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tanggal Pemberian <span class="text-danger">*</span></label>
                                <input type="date" name="awarded_date" class="form-control"
                                       value="{{ old('awarded_date', today()->format('Y-m-d')) }}" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Judul <span class="text-muted fw-normal">(otomatis)</span></label>
                                <input type="text" name="title" id="modal_reward_title" class="form-control"
                                       value="{{ old('title') }}" readonly placeholder="Pilih katalog penghargaan">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Kategori <span class="text-muted fw-normal">(otomatis)</span></label>
                                <select name="category" id="modal_reward_category" class="form-select" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach(\App\Models\DormitoryReward::categories() as $key => $label)
                                        <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Level <span class="text-muted fw-normal">(otomatis)</span></label>
                                <select name="level" id="modal_reward_level" class="form-select" required>
                                    <option value="">-- Pilih Level --</option>
                                    @foreach(\App\Models\DormitoryReward::levels() as $key => $label)
                                        <option value="{{ $key }}" @selected(old('level') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Poin <span class="text-muted fw-normal">(otomatis)</span></label>
                                <input type="number" name="points" id="modal_reward_points" class="form-control"
                                       value="{{ old('points') }}" readonly placeholder="Pilih katalog">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Deskripsi</label>
                                <textarea name="description" class="form-control" rows="2"
                                          placeholder="Deskripsi pencapaian atau alasan penghargaan...">{{ old('description') }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Dokumen Pendukung</label>
                                <input type="file" name="proof_path" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">Foto/surat dukungan (maks 2MB)</small>
                            </div>

                            <div class="col-md-6 d-flex align-items-center">
                                <div class="w-100">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="send_notification" value="0">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               id="modal_send_notification" name="send_notification" value="1"
                                               {{ old('send_notification', '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="modal_send_notification">
                                            Kirim notifikasi ke wali santri
                                        </label>
                                    </div>
                                    <div class="form-text">WA ke wali/terpusat. Bisa dimatikan per input.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan Penghargaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
(function () {
    'use strict';

    const modalEl = document.getElementById('rewardCreateModal');
    if (!modalEl) return;

    const catalog = document.getElementById('modal_reward_catalog');
    const title = document.getElementById('modal_reward_title');
    const category = document.getElementById('modal_reward_category');
    const level = document.getElementById('modal_reward_level');
    const points = document.getElementById('modal_reward_points');

    function syncRewardModal() {
        if (!catalog) return;
        const option = catalog.options[catalog.selectedIndex];
        if (!option || !option.value) return;

        title.value = option.getAttribute('data-title') || '';
        category.value = option.getAttribute('data-category') || '';
        level.value = option.getAttribute('data-level') || '';
        points.value = option.getAttribute('data-points') || '0';
    }

    window.syncRewardModal = syncRewardModal;
    catalog.addEventListener('change', syncRewardModal);
    syncRewardModal();

    if (window.jQuery && jQuery.fn.select2) {
        const student = document.getElementById('modal_reward_student');
        jQuery(catalog).select2({
            width: '100%',
            dropdownParent: jQuery(modalEl),
            placeholder: 'Cari kode atau nama penghargaan...',
            allowClear: true,
        });
        jQuery(catalog).on('select2:select select2:clear', syncRewardModal);

        if (student) {
            jQuery(student).select2({
                width: '100%',
                dropdownParent: jQuery(modalEl),
                placeholder: 'Cari nama santri...',
                allowClear: true,
            });
        }
    }

    @if($errors->any())
        new bootstrap.Modal(modalEl).show();
    @endif
})();
</script>
@endsection
