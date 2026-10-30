@extends('layouts.master')
@section('title') Kamar Asrama — {{ $dormitory->name ?? 'Asrama' }} @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('title') Kamar @endslot
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
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- ============================================================
         STATS CARDS
    ============================================================ --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded">
                                <i class="ri-door-open-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Total Kamar</p>
                            <h3 class="stat-value">{{ number_format($rooms->total() ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-home-4-line me-1"></i>Seluruh kamar asrama</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <i class="ri-user-location-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Terisi</p>
                            <h3 class="stat-value">{{ number_format($rooms->total() ? $occupied : 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-group-line me-1"></i>Santri menempati kamar</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded">
                                <i class="ri-checkbox-circle-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Kosong</p>
                            <h3 class="stat-value">{{ number_format($rooms->total() ? max(0, $totalCapacity - $occupied) : 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Kapasitas yang masih tersedia</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded">
                                <i class="ri-user-heart-line text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Kapasitas</p>
                            <h3 class="stat-value">{{ number_format($rooms->sum('capacity') ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-community-line me-1"></i>Total kapasitas kamar</p>
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
                            <h5 class="card-title mb-0">Kamar — {{ $dormitory->name ?? 'Asrama' }}</h5>
                            <p class="text-muted mb-0 small">Pengelolaan kamar, wali kamar, dan penempatan penghuni.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <a href="{{ route('user.asrama.room-supervisors.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-outline-primary btn-sm" title="Wali Kamar">
                                    <i class="ri-shield-user-line me-1"></i> Wali Kamar
                                </a>
                                <a href="{{ route('user.asrama.imports.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-outline-success btn-sm" title="Import Kamar">
                                    <i class="ri-upload-cloud-2-line me-1"></i> Import
                                </a>
                                <a href="{{ route('user.asrama.rooms.placement', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-outline-info btn-sm" title="Penempatan Kamar">
                                    <i class="ri-layout-grid-line me-1"></i> Penempatan
                                </a>
                                <button type="button" class="btn btn-primary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#modalTambahKamar">
                                    <i class="ri-add-line me-1"></i> Tambah Kamar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- ============================================================
                         FILTER CEPAT (inline, tanpa modal)
                    ============================================================ --}}
                    <form method="GET" class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <div class="flex-grow-1" style="min-width:200px;max-width:320px;">
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="Cari nama / kode kamar..." value="{{ request('search') }}">
                        </div>
                        <div style="min-width:160px;max-width:200px;">
                            <select name="wing_id" class="form-select form-select-sm">
                                <option value="">Semua Gedung</option>
                                @foreach($wings as $w)
                                    <option value="{{ $w->id }}" {{ request('wing_id') == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="min-width:140px;max-width:180px;">
                            <select name="is_active" class="form-select form-select-sm">
                                <option value="">Semua Status</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                            <i class="ri-search-line"></i> Cari
                        </button>
                        <a href="{{ route('user.asrama.rooms.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                           class="btn btn-light btn-sm flex-shrink-0" title="Reset filter">
                            <i class="ri-refresh-line"></i>
                        </a>
                    </form>

                    @if(request()->hasAny(['search','wing_id','is_active']) && (request('search') || request('wing_id') || (request('is_active') !== null && request('is_active') !== '')))
                        <div class="mb-3">
                            <span class="text-muted small">Filter aktif:</span>
                            @if(request('search'))
                                <span class="filter-badge active"><i class="ri-search-line"></i> "{{ request('search') }}"</span>
                            @endif
                            @if(request('wing_id'))
                                <span class="filter-badge active"><i class="ri-hotel-line"></i> {{ $wings->firstWhere('id', request('wing_id'))?->name ?? 'Gedung #'.request('wing_id') }}</span>
                            @endif
                            @if(request('is_active') !== null && request('is_active') !== '')
                                <span class="filter-badge active"><i class="ri-flag-line"></i> {{ request('is_active') === '1' ? 'Aktif' : 'Nonaktif' }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $rooms->count() }}</strong> dari
                            <strong>{{ $rooms->total() }}</strong> kamar
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
                                    <th>Kode</th>
                                    <th>Nama Kamar</th>
                                    <th>Sayap</th>
                                    <th>Wali Kamar</th>
                                    <th class="text-center">Lantai</th>
                                    <th class="text-center">Tipe</th>
                                    <th class="text-center">Kapasitas</th>
                                    <th class="text-center">Penghuni</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width:110px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rooms ?? [] as $i => $r)
                                    @php $activeCount = $r->residents->filter(fn($res) => $res->is_active)->count(); @endphp
                                    <tr>
                                        <td class="text-center text-muted">{{ $rooms->firstItem() + $i }}</td>
                                        <td>
                                            <span class="badge bg-dark-subtle text-dark">{{ $r->code }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('user.asrama.rooms.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => $r->id]) }}"
                                               class="text-decoration-none fw-semibold text-body">
                                                {{ $r->name ?? $r->code }}
                                            </a>
                                        </td>
                                        <td>{{ $r->wing?->name ?? '—' }}</td>
                                        <td>
                                            @if($r->activeSupervisor?->user)
                                                <span class="fw-semibold">{{ $r->activeSupervisor->user->name }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $r->floor ?? '—' }}</td>
                                        <td class="text-center">
                                            @if($r->room_type)
                                                <span class="badge bg-{{ $r->room_type === 'musyrif' ? 'warning' : 'info' }}-subtle text-{{ $r->room_type === 'musyrif' ? 'warning' : 'info' }}">
                                                    {{ ucfirst($r->room_type) }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ number_format($r->capacity) }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $activeCount >= $r->capacity ? 'danger' : 'success' }}-subtle text-{{ $activeCount >= $r->capacity ? 'danger' : 'success' }}">
                                                {{ $activeCount }}/{{ $r->capacity }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $r->is_active ? 'success' : 'secondary' }}-subtle text-{{ $r->is_active ? 'success' : 'secondary' }}">
                                                <i class="ri-{{ $r->is_active ? 'checkbox-circle-line' : 'pause-circle-line' }} me-1"></i>
                                                {{ $r->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('user.asrama.rooms.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => $r->id]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Detail">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" title="Aksi lain">
                                                        <i class="ri-more-2-fill"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('user.asrama.rooms.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => $r->id]) }}">
                                                                <i class="ri-edit-line me-2 text-warning"></i>Edit
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form action="{{ route('user.asrama.rooms.destroy', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => $r->id]) }}"
                                                                  method="POST"
                                                                  onsubmit="return confirm('Yakin hapus kamar ini?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="ri-delete-bin-line me-2"></i>Hapus
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="11" title="Belum ada kamar"
                                        message="Tambahkan kamar untuk mulai menempatkan penghuni.">
                                        <button type="button" class="btn btn-primary btn-sm mt-3"
                                                data-bs-toggle="modal" data-bs-target="#modalTambahKamar">
                                            <i class="ri-add-line me-1"></i> Tambah Kamar
                                        </button>
                                    </x-empty-state>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($rooms->hasPages())
                        @include('shared._pagination', ['paginator' => $rooms->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL TAMBAH KAMAR
    ============================================================ --}}
    <div class="modal fade" id="modalTambahKamar" tabindex="-1" aria-labelledby="modalTambahKamarLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.asrama.rooms.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTambahKamarLabel">
                            <i class="ri-door-open-line me-1"></i> Tambah Kamar
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        @if($errors->any())
                            <div class="alert alert-danger py-2">
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($waliKamarCandidates->isEmpty())
                            <div class="alert alert-warning py-2">
                                Belum ada GTK terdata pada satuan kerja
                                <strong>{{ $dormitory->workUnit?->name ?? '—' }}</strong>.
                                Tambahkan GTK ke satuan kerja ini terlebih dahulu di modul GTK.
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="kamar_code" class="form-label">Kode Kamar <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="kamar_code" maxlength="20" required
                                       class="form-control @error('code') is-invalid @enderror"
                                       value="{{ old('code') }}" placeholder="A-101">
                                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8">
                                <label for="kamar_name" class="form-label">Nama Kamar</label>
                                <input type="text" name="name" id="kamar_name" maxlength="100"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" placeholder="Kamar Reguler 101">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="kamar_wing" class="form-label">Blok <span class="text-danger">*</span></label>
                                <select name="wing_id" id="kamar_wing" required
                                        class="form-select @error('wing_id') is-invalid @enderror"
                                        onchange="var f = document.getElementById('kamar_floor'); if (f && !f.dataset.touched) { f.value = this.selectedOptions[0]?.dataset.floor || ''; }">
                                    <option value="">— Pilih Blok —</option>
                                    @foreach($wings as $w)
                                        <option value="{{ $w->id }}" data-floor="{{ $w->floor ?? 1 }}" {{ old('wing_id', request('wing_id')) == $w->id ? 'selected' : '' }}>
                                            {{ $w->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('wing_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="kamar_wali" class="form-label">Wali Kamar <span class="text-danger">*</span></label>
                                <select name="wali_kamar_user_id" id="kamar_wali" required
                                        class="form-select @error('wali_kamar_user_id') is-invalid @enderror"
                                        {{ $waliKamarCandidates->isEmpty() ? 'disabled' : '' }}>
                                    <option value="">— Pilih Wali Kamar —</option>
                                    @foreach($waliKamarCandidates as $u)
                                        <option value="{{ $u->id }}" {{ old('wali_kamar_user_id') == $u->id ? 'selected' : '' }}>
                                            {{ $u->name }}{{ $u->email ? ' · '.$u->email : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('wali_kamar_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">{{ $waliKamarCandidates->count() }} kandidat tersedia.</small>
                            </div>

                            <div class="col-md-4">
                                <label for="kamar_floor" class="form-label">Lantai</label>
                                <input type="number" name="floor" id="kamar_floor" min="1"
                                       class="form-control @error('floor') is-invalid @enderror"
                                       value="{{ old('floor') }}" placeholder="1"
                                       oninput="this.dataset.touched = '1'">
                                <small class="text-muted">Otomatis dari lantai blok.</small>
                                @error('floor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="kamar_type" class="form-label">Tipe Kamar <span class="text-danger">*</span></label>
                                <select name="room_type" id="kamar_type" required
                                        class="form-select @error('room_type') is-invalid @enderror">
                                    <option value="">— Pilih Tipe —</option>
                                    <option value="reguler" {{ old('room_type') === 'reguler' ? 'selected' : '' }}>Reguler</option>
                                    <option value="musyrif" {{ old('room_type') === 'musyrif' ? 'selected' : '' }}>Musyrif</option>
                                </select>
                                @error('room_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="kamar_capacity" class="form-label">Kapasitas <span class="text-danger">*</span></label>
                                <input type="number" name="capacity" id="kamar_capacity" min="1" required
                                       class="form-control @error('capacity') is-invalid @enderror"
                                       value="{{ old('capacity', 4) }}">
                                @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label for="kamar_facility" class="form-label">Fasilitas</label>
                                <textarea name="facility_notes" id="kamar_facility" rows="2"
                                          class="form-control @error('facility_notes') is-invalid @enderror"
                                          placeholder="AC, Kamar Mandi Dalam, dll">{{ old('facility_notes') }}</textarea>
                                @error('facility_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                           id="kamar_is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="kamar_is_active">Kamar aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" {{ $waliKamarCandidates->isEmpty() ? 'disabled' : '' }}>
                            <i class="ri-save-line me-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($errors->any())
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var el = document.getElementById('modalTambahKamar');
                    if (el) { new bootstrap.Modal(el).show(); }
                });
            </script>
        @endpush
    @endif
@endsection