@extends('layouts.master')
@section('title') Inventaris Kamar — Asrama @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.index', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') {{ $dormitory->name ?? 'Asrama' }} @endslot
        @slot('title') Inventaris Kamar @endslot
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
            <i class="ri-error-warning-line me-1"></i> Terjadi kesalahan pada formulir. Silakan perbaiki input Anda.
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
                                <i class="ri-file-list-3-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Total Item</p>
                            <h3 class="stat-value">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Inventaris kamar</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <i class="ri-checkbox-circle-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Kondisi Baik</p>
                            <h3 class="stat-value">{{ number_format($stats['baik'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-shield-check-line me-1"></i>Dapat digunakan</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded">
                                <i class="ri-alert-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Kondisi Rusak</p>
                            <h3 class="stat-value">{{ number_format($stats['rusak'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-tools-line me-1"></i>Perlu maintenance</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded">
                                <i class="ri-tools-line text-danger"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Perlu Perbaikan</p>
                            <h3 class="stat-value">{{ number_format($stats['perbaikan'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-error-warning-line me-1"></i>Tindak lanjut</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         CARD UTAMA
    ============================================================ --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Inventaris Kamar</h5>
                            <p class="text-muted mb-0 small">{{ $dormitory->name ?? 'Asrama' }} &mdash; daftar item &amp; kondisi.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <a href="{{ route('user.asrama.inventories.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                   class="btn btn-primary btn-sm">
                                    <i class="ri-add-line me-1"></i> Tambah Item
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- ============================================================
                         FILTER CEPAT (inline, tanpa modal)
                    ============================================================ --}}
                    <form method="GET" class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <div class="flex-grow-1" style="min-width:180px;max-width:280px;">
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="Nama / kode item..." value="{{ request('search') }}">
                        </div>
                        <div style="min-width:150px;max-width:190px;">
                            <select name="room_id" class="form-select form-select-sm">
                                <option value="">Semua Kamar</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                                        {{ $room->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div style="min-width:150px;max-width:190px;">
                            <select name="condition" class="form-select form-select-sm">
                                <option value="">Semua Kondisi</option>
                                <option value="baik" {{ request('condition') === 'baik' ? 'selected' : '' }}>Baik</option>
                                <option value="rusak_ringan" {{ request('condition') === 'rusak_ringan' ? 'selected' : '' }}>Rusak Ringan</option>
                                <option value="rusak_berat" {{ request('condition') === 'rusak_berat' ? 'selected' : '' }}>Rusak Berat</option>
                                <option value="hilang" {{ request('condition') === 'hilang' ? 'selected' : '' }}>Hilang</option>
                            </select>
                        </div>
                        <div style="min-width:150px;max-width:190px;">
                            <select name="category_id" class="form-select form-select-sm">
                                <option value="">Semua Kategori</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                            <i class="ri-search-line"></i> Cari
                        </button>
                        <a href="{{ route('user.asrama.inventories.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                           class="btn btn-light btn-sm flex-shrink-0" title="Reset filter">
                            <i class="ri-refresh-line"></i>
                        </a>
                    </form>

                    @if(request()->hasAny(['search','room_id','condition','category_id']) && (request('search') || request('room_id') || request('condition') || request('category_id')))
                        <div class="mb-3">
                            <span class="text-muted small">Filter aktif:</span>
                            @if(request('search'))
                                <span class="filter-badge active"><i class="ri-search-line"></i> "{{ request('search') }}"</span>
                            @endif
                            @if(request('room_id'))
                                <span class="filter-badge active"><i class="ri-door-open-line"></i> {{ $rooms->firstWhere('id', request('room_id'))?->name ?? 'Kamar #'.request('room_id') }}</span>
                            @endif
                            @if(request('condition'))
                                <span class="filter-badge active"><i class="ri-flag-line"></i> {{ ucfirst(str_replace('_',' ',request('condition'))) }}</span>
                            @endif
                            @if(request('category_id'))
                                <span class="filter-badge active"><i class="ri-price-tag-3-line"></i> {{ $categories->firstWhere('id', request('category_id'))?->name ?? 'Kategori #'.request('category_id') }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $inventories->count() }}</strong> dari
                            <strong>{{ $inventories->total() }}</strong> item inventaris
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
                                    <th>Nama Kamar</th>
                                    <th>Nama Item</th>
                                    <th>Kategori</th>
                                    <th>Kode</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-center">Kondisi</th>
                                    <th>Terakhir Dicek</th>
                                    <th>Dicek Oleh</th>
                                    <th class="text-center" style="width:80px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventories ?? [] as $i => $inventory)
                                    <tr>
                                        <td class="text-center text-muted">{{ $inventories->firstItem() + $i }}</td>
                                        <td>{{ $inventory->room?->name ?? '—' }}</td>
                                        <td class="fw-semibold">{{ $inventory->item_name ?? '—' }}</td>
                                        <td>{{ $inventory->category?->name ?? '—' }}</td>
                                        <td>
                                            @if($inventory->item_code)
                                                <code class="text-dark">{{ $inventory->item_code }}</code>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ number_format($inventory->quantity) }}</td>
                                        <td class="text-center">
                                            @switch($inventory->condition)
                                                @case('baik')
                                                    <span class="badge bg-success-subtle text-success">
                                                        <i class="ri-checkbox-circle-line me-1"></i>Baik
                                                    </span>
                                                    @break
                                                @case('rusak_ringan')
                                                    <span class="badge bg-warning-subtle text-warning">
                                                        <i class="ri-tools-line me-1"></i>Rusak Ringan
                                                    </span>
                                                    @break
                                                @case('rusak_berat')
                                                    <span class="badge bg-danger-subtle text-danger">
                                                        <i class="ri-alert-line me-1"></i>Rusak Berat
                                                    </span>
                                                    @break
                                                @case('hilang')
                                                    <span class="badge bg-info-subtle text-info">
                                                        <i class="ri-exclamation-line me-1"></i>Hilang
                                                    </span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($inventory->condition) }}</span>
                                            @endswitch
                                        </td>
                                        <td>{{ $inventory->last_checked_at ? $inventory->last_checked_at->format('d/m/Y') : '—' }}</td>
                                        <td>{{ $inventory->checkedBy?->name ?? '—' }}</td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('user.asrama.inventories.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'itemUuid' => $inventory->id]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Edit Item">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="10" title="Belum ada data inventaris"
                                        message="Tidak ada item inventaris yang terdaftar.">
                                        <a href="{{ route('user.asrama.inventories.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                           class="btn btn-primary btn-sm mt-3">
                                            <i class="ri-add-line me-1"></i> Tambah Item Baru
                                        </a>
                                    </x-empty-state>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($inventories->hasPages())
                        @include('shared._pagination', ['paginator' => $inventories->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection