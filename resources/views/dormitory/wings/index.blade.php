@extends('layouts.master')
@section('title') Blok Asrama — {{ $dormitory->name ?? 'Asrama' }} @endsection
@php
    $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null);
    $activeResidents = $wings->sum(function ($wing) {
        return $wing->rooms->sum(function ($room) {
            return $room->residents->where('is_active', true)->count();
        });
    });
@endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('title') Blok @endslot
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
         STATS CARDS (3 kartu)
    ============================================================ --}}
    <div class="row">
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded">
                                <i class="ri-hotel-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Total Blok</p>
                            <h3 class="stat-value">{{ number_format($wings->total() ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-building-4-line me-1"></i>Blok asrama terdaftar</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <i class="ri-door-open-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Total Kamar</p>
                            <h3 class="stat-value">{{ number_format($wings->total() ? $wings->sum('total_rooms') : 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-home-4-line me-1"></i>Kamar pada seluruh blok</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded">
                                <i class="ri-user-location-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Penghuni Aktif</p>
                            <h3 class="stat-value">{{ number_format($activeResidents) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-group-line me-1"></i>Santri yang sedang menetap</p>
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
                            <h5 class="card-title mb-0">Blok — {{ $dormitory->name ?? 'Asrama' }}</h5>
                            <p class="text-muted mb-0 small">Pengelolaan blok asrama per gedung Sarpras.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <button type="button" class="btn btn-primary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#modalTambahBlok">
                                    <i class="ri-add-line me-1"></i> Tambah Blok
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
                                   placeholder="Cari nama / kode blok..." value="{{ request('search') }}">
                        </div>
                        <div style="min-width:150px;max-width:190px;">
                            <select name="is_active" class="form-select form-select-sm">
                                <option value="">Semua Status</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                            <i class="ri-search-line"></i> Cari
                        </button>
                        <a href="{{ route('user.asrama.wings.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                           class="btn btn-light btn-sm flex-shrink-0" title="Reset filter">
                            <i class="ri-refresh-line"></i>
                        </a>
                    </form>

                    @if((request('search')) || (request('is_active') !== null && request('is_active') !== ''))
                        <div class="mb-3">
                            <span class="text-muted small">Filter aktif:</span>
                            @if(request('search'))
                                <span class="filter-badge active"><i class="ri-search-line"></i> "{{ request('search') }}"</span>
                            @endif
                            @if(request('is_active') !== null && request('is_active') !== '')
                                <span class="filter-badge active"><i class="ri-flag-line"></i> {{ request('is_active') === '1' ? 'Aktif' : 'Nonaktif' }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $wings->count() }}</strong> dari
                            <strong>{{ $wings->total() }}</strong> blok
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
                                    <th>Nama Blok</th>
                                    <th class="text-center">Lantai</th>
                                    <th class="text-center">Kapasitas</th>
                                    <th class="text-center">Kamar</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width:110px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($wings ?? [] as $i => $w)
                                    <tr>
                                        <td class="text-center text-muted">{{ $wings->firstItem() + $i }}</td>
                                        <td>
                                            <span class="badge bg-dark-subtle text-dark">{{ $w->code }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('user.asrama.wings.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'wingUuid' => $w->id]) }}"
                                               class="text-decoration-none fw-semibold text-body">
                                                {{ $w->sarprasBuilding?->name ?? '—' }}
                                            </a>
                                        </td>
                                        <td class="text-center">Lantai {{ $w->floor ?? '—' }}</td>
                                        <td class="text-center">{{ number_format($w->capacity) }}</td>
                                        <td class="text-center">{{ number_format($w->rooms->count()) }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $w->is_active ? 'success' : 'secondary' }}-subtle text-{{ $w->is_active ? 'success' : 'secondary' }}">
                                                <i class="ri-{{ $w->is_active ? 'checkbox-circle-line' : 'pause-circle-line' }} me-1"></i>
                                                {{ $w->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('user.asrama.wings.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'wingUuid' => $w->id]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Detail">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" title="Aksi lain">
                                                        <i class="ri-more-2-fill"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('user.asrama.wings.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'wingUuid' => $w->id]) }}">
                                                                <i class="ri-edit-line me-2 text-warning"></i>Edit
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form action="{{ route('user.asrama.wings.destroy', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'wingUuid' => $w->id]) }}"
                                                                  method="POST"
                                                                  onsubmit="return confirm('Yakin hapus blok ini?');">
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
                                    <x-empty-state colspan="8" title="Belum ada gedung/wing"
                                        message="Tambahkan gedung untuk mengelompokkan kamar asrama.">
                                        <button type="button" class="btn btn-primary btn-sm mt-3"
                                                data-bs-toggle="modal" data-bs-target="#modalTambahBlok">
                                            <i class="ri-add-line me-1"></i> Tambah Gedung
                                        </button>
                                    </x-empty-state>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($wings->hasPages())
                        @include('shared._pagination', ['paginator' => $wings->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL TAMBAH BLOK
    ============================================================ --}}
    <div class="modal fade" id="modalTambahBlok" tabindex="-1" aria-labelledby="modalTambahBlokLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.asrama.wings.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTambahBlokLabel">
                            <i class="ri-building-line me-1"></i> Tambah Blok
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

                        @if($buildings->isEmpty())
                            <div class="alert alert-warning py-2">
                                Belum ada gedung Sarpras. Tambahkan gedung terlebih dahulu di
                                <a href="{{ route('user.asrama.wings.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="alert-link">halaman Tambah Blok</a>.
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="blok_building" class="form-label">Gedung <span class="text-danger">*</span></label>
                                <select name="sarpras_building_id" id="blok_building"
                                        class="form-select @error('sarpras_building_id') is-invalid @enderror" required>
                                    <option value="">— Pilih Gedung —</option>
                                    @foreach($buildings as $b)
                                        <option value="{{ $b->id }}" {{ old('sarpras_building_id') == $b->id ? 'selected' : '' }}>
                                            {{ $b->name }} ({{ $b->gender ?? '-' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('sarpras_building_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="blok_floor" class="form-label">Lantai <span class="text-danger">*</span></label>
                                <input type="number" name="floor" id="blok_floor" min="1" required
                                       class="form-control @error('floor') is-invalid @enderror"
                                       value="{{ old('floor', 1) }}">
                                @error('floor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="blok_capacity" class="form-label">Kapasitas</label>
                                <input type="number" name="capacity" id="blok_capacity" min="0"
                                       class="form-control @error('capacity') is-invalid @enderror"
                                       value="{{ old('capacity') }}" placeholder="0">
                                @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="blok_code" class="form-label">Kode Blok</label>
                                <input type="text" name="code" id="blok_code" maxlength="20"
                                       class="form-control @error('code') is-invalid @enderror"
                                       value="{{ old('code') }}" placeholder="Otomatis dari kode gedung">
                                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                           id="blok_is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="blok_is_active">Blok aktif</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="blok_notes" class="form-label">Catatan</label>
                                <textarea name="notes" id="blok_notes" rows="2" maxlength="191"
                                          class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <small class="text-muted">
                                    <i class="ri-information-line me-1"></i>
                                    Nama blok dibuat otomatis: <em>Nama Gedung — Lantai N</em>.
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" {{ $buildings->isEmpty() ? 'disabled' : '' }}>
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
                    var el = document.getElementById('modalTambahBlok');
                    if (el) { new bootstrap.Modal(el).show(); }
                });
            </script>
        @endpush
    @endif
@endsection