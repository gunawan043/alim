@extends('layouts.master')
@section('title') Inventaris Kamar — Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.index', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') {{ $dormitory->name ?? 'Asrama' }} @endslot
        @slot('title') Inventaris Kamar @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>Terjadi kesalahan pada formulir. Silakan perbaiki input Anda.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ============================================================
         STATS CARDS
    ============================================================ --}}
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-md rounded-circle bg-primary-subtle">
                                <i class="ri-file-list-3-line fs-24 text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-muted text-truncate mb-1">Total Item</p>
                            <h4 class="mb-0">{{ $stats['total'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-md rounded-circle bg-success-subtle">
                                <i class="ri-checkbox-circle-line fs-24 text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-muted text-truncate mb-1">Kondisi Baik</p>
                            <h4 class="mb-0">{{ $stats['baik'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-md rounded-circle bg-warning-subtle">
                                <i class="ri-alert-line fs-24 text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-muted text-truncate mb-1">Kondisi Rusak</p>
                            <h4 class="mb-0">{{ $stats['rusak'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-md rounded-circle bg-danger-subtle">
                                <i class="ri-tools-line fs-24 text-danger"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-muted text-truncate mb-1">Perlu Perbaikan</p>
                            <h4 class="mb-0">{{ $stats['perbaikan'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Inventaris Kamar</h5>
                            <p class="text-muted mb-0">
                                {{ $dormitory->name ?? 'Asrama' }} &mdash; {{ $inventories->total() ?? 0 }} item
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.asrama.inventories.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Item
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- ============================================================
                         FILTER FORM
                    ============================================================ --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <select name="room_id" class="form-control">
                                <option value="">Semua Kamar</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                                        {{ $room->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="condition" class="form-control">
                                <option value="">Semua Kondisi</option>
                                <option value="baik" {{ request('condition') === 'baik' ? 'selected' : '' }}>Baik</option>
                                <option value="rusak" {{ request('condition') === 'rusak' ? 'selected' : '' }}>Rusak</option>
                                <option value="perbaikan" {{ request('condition') === 'perbaikan' ? 'selected' : '' }}>Perlu Perbaikan</option>
                                <option value="hibahan" {{ request('condition') === 'hibahan' ? 'selected' : '' }}>Hibahan</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Nama / Kode Item..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-search-line me-1"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.asrama.inventories.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light w-100">
                                <i class="ri-reset-right-line"></i> Reset
                            </a>
                        </div>
                    </form>

                    {{-- ============================================================
                         TABLE
                    ============================================================ --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 5%">No</th>
                                    <th style="width: 14%">Kamar</th>
                                    <th>Nama Item</th>
                                    <th style="width: 12%">Kode</th>
                                    <th class="text-center" style="width: 8%">Jumlah</th>
                                    <th style="width: 13%">Kondisi</th>
                                    <th style="width: 11%">Terakhir Dicek</th>
                                    <th style="width: 13%">Dicek Oleh</th>
                                    <th class="text-center" style="width: 8%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventories as $i => $item)
                                    <tr>
                                        <td class="text-center text-muted">
                                            {{ $inventories->firstItem() + $i }}
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">
                                                <i class="ri-home-4-line me-1"></i>{{ $item->room?->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $item->name ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <code class="text-dark">{{ $item->code ?? '-' }}</code>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary">{{ $item->quantity ?? 0 }}</span>
                                        </td>
                                        <td>
                                            @if(($item->condition ?? '') === 'baik')
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="ri-checkbox-circle-line me-1"></i>Baik
                                                </span>
                                            @elseif(($item->condition ?? '') === 'rusak')
                                                <span class="badge bg-warning-subtle text-warning">
                                                    <i class="ri-alert-line me-1"></i>Rusak
                                                </span>
                                            @elseif(($item->condition ?? '') === 'perbaikan')
                                                <span class="badge bg-danger-subtle text-danger">
                                                    <i class="ri-tools-line me-1"></i>Perlu Perbaikan
                                                </span>
                                            @elseif(($item->condition ?? '') === 'hibahan')
                                                <span class="badge bg-primary-subtle text-primary">
                                                    <i class="ri-gift-line me-1"></i>Hibahan
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">
                                                    {{ ucfirst($item->condition ?? '-') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->last_checked_at)
                                                {{ $item->last_checked_at->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted small">{{ $item->checkedBy?->name ?? '-' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.asrama.inventories.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'inventoryUuid' => $item->id]) }}"
                                               class="btn btn-sm btn-outline-secondary"
                                               title="Edit">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-5">
                                            <div class="mb-3">
                                                <i class="ri-file-list-3-line fs-1 d-block text-muted"></i>
                                            </div>
                                            <h6 class="text-muted mb-1">Belum Ada Data Inventaris</h6>
                                            <p class="text-muted mb-3">Tidak ada item inventaris yang terdaftar.</p>
                                            <a href="{{ route('user.asrama.inventories.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                               class="btn btn-success btn-sm">
                                                <i class="ri-add-line me-1"></i> Tambah Item Baru
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($inventories->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $inventories->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
