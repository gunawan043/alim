@extends('layouts.master')
@section('title') Inventaris Kamar — Asrama @endsection

@section('css')
    <link rel="stylesheet" href="{{ URL::asset('build/libs/gridjs/theme/mermaid.min.css') }}">
    <style>
        .gridjs-wrapper table { border-collapse: collapse; width: 100%; }
        .gridjs-wrapper th { background-color: #f8f9fa; text-align: left; padding: 12px; font-weight: 600; border-bottom: 2px solid #dee2e6; }
        .gridjs-wrapper td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        .gridjs-wrapper tr:hover { background-color: #f8f9fa; }
        .sorting-button { cursor: pointer; padding: 4px; border-radius: 3px; }
        .sorting-button:hover { background-color: #e9ecef; }
    </style>
@endsection

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
         STATS CARDS — template perizinan (permits)
    ============================================================ --}}
    <div class="row g-3 mb-3">
        {{-- 1. Total Item --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2">
                                <i class="ri-file-list-3-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Item</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $stats['total'] ?? 0 }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">item</small></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0" style="font-size:11px;">
                        <i class="ri-information-line me-1"></i>Inventaris Kamar
                    </p>
                </div>
            </div>
        </div>

        {{-- 2. Kondisi Baik --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="ri-checkbox-circle-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Kondisi Baik</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $stats['baik'] ?? 0 }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">item</small></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0" style="font-size:11px;">
                        <i class="ri-information-line me-1"></i>Dapat digunakan
                    </p>
                </div>
            </div>
        </div>

        {{-- 3. Kondisi Rusak --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2">
                                <i class="ri-alert-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Kondisi Rusak</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $stats['rusak'] ?? 0 }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">item</small></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0" style="font-size:11px;">
                        <i class="ri-information-line me-1"></i>Maintenance
                    </p>
                </div>
            </div>
        </div>

        {{-- 4. Perlu Perbaikan --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-2">
                                <i class="ri-tools-line text-danger"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Perlu Perbaikan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $stats['perbaikan'] ?? 0 }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">item</small></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0" style="font-size:11px;">
                        <i class="ri-information-line me-1"></i>Tindak lanjut
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Table GridJS dengan style Bootstrap --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Inventaris Kamar</h5>
                    <span class="fw-normal text-muted">{{ $dormitory->name ?? 'Asrama' }}</span>
                </div>
                <div class="card-body">
                    {{-- Filter Form --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <select name="room_id" class="form-control">
                                <option value="">Semua Kamar</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                                        {{ $room->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="condition" class="form-control">
                                <option value="">Semua Kondisi</option>
                                <option value="baik" {{ request('condition') === 'baik' ? 'selected' : '' }}>Baik</option>
                                <option value="rusak" {{ request('condition') === 'rusak' ? 'selected' : '' }}>Rusak</option>
                                <option value="perbaikan" {{ request('condition') === 'perbaikan' ? 'selected' : '' }}>Perlu Perbaikan</option>
                                <option value="hilang" {{ request('condition') === 'hilang' ? 'selected' : '' }}>Hilang</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Nama / Kode Item..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="category_id" class="form-control" aria-label="Filter kategori">
                                <option value="">Semua Kategori</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-search-line me-1"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('user.asrama.inventories.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light w-100">
                                <i class="ri-reset-right-line"></i> Reset
                            </a>
                        </div>
                    </form>

                    {{-- GridJS Table Wrapper --}}
                    <div class="gridjs-wrapper" style="height: auto;">
                        <table role="grid" class="gridjs-table" style="height: auto;">
                            <thead class="gridjs-thead">
                                <tr class="gridjs-tr">
                                    <th class="gridjs-th gridjs-sortable" data-column-id="#">
                                        #
                                        <span class="sorting-button">▾</span>
                                    </th>
                                    <th class="gridjs-th gridjs-sortable" data-column-id="Nama Kamar">
                                        Nama Kamar
                                        <span class="sorting-button">▾</span>
                                    </th>
                                    <th class="gridjs-th gridjs-sortable" data-column-id="Nama Item">
                                        Nama Item
                                        <span class="sorting-button">▾</span>
                                    </th>
                                    <th class="gridjs-th gridjs-sortable" data-column-id="Kategori">
                                        Kategori
                                        <span class="sorting-button">▾</span>
                                    </th>
                                    <th class="gridjs-th gridjs-sortable" data-column-id="Kode">
                                        Kode
                                        <span class="sorting-button">▾</span>
                                    </th>
                                    <th class="gridjs-th gridjs-sortable" data-column-id="Jumlah">
                                        Jumlah
                                        <span class="sorting-button">▾</span>
                                    </th>
                                    <th class="gridjs-th gridjs-sortable" data-column-id="Kondisi">
                                        Kondisi
                                        <span class="sorting-button">▾</span>
                                    </th>
                                    <th class="gridjs-th gridjs-sortable" data-column-id="Terakhir Dicek">
                                        Terakhir Dicek
                                        <span class="sorting-button">▾</span>
                                    </th>
                                    <th class="gridjs-th gridjs-sortable" data-column-id="Dicek Oleh">
                                        Dicek Oleh
                                        <span class="sorting-button">▾</span>
                                    </th>
                                    <th class="gridjs-th" data-column-id="Aksi">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="gridjs-tbody">
                                @forelse ($inventories as $inventory)
                                    <tr class="gridjs-tr">
                                        <td class="gridjs-td" data-column-id="#">{{ $loop->iteration }}</td>
                                        <td class="gridjs-td" data-column-id="Nama Kamar">{{ e($inventory->room?->name ?? '-') }}</td>
                                        <td class="gridjs-td" data-column-id="Nama Item">{{ e($inventory->item_name ?? '-') }}</td>
                                        <td class="gridjs-td" data-column-id="Kategori">{{ e($inventory->category?->name ?? '-') }}</td>
                                        <td class="gridjs-td" data-column-id="Kode">{{ e($inventory->item_code ?? '-') }}</td>
                                        <td class="gridjs-td" data-column-id="Jumlah">{{ $inventory->quantity }}</td>
                                        <td class="gridjs-td" data-column-id="Kondisi">
                                            @switch($inventory->condition)
                                                @case('baik')
                                                    <span class="badge bg-success text-white"><i class="ri-checkbox-circle-line me-1"></i>Baik</span>
                                                @break
                                                @case('rusak')
                                                    <span class="badge bg-warning text-dark"><i class="ri-alert-line me-1"></i>Rusak</span>
                                                @break
                                                @case('perbaikan')
                                                    <span class="badge bg-danger text-white"><i class="ri-tools-line me-1"></i>Perlu Perbaikan</span>
                                                @break
                                                @case('hilang')
                                                    <span class="badge bg-info text-dark"><i class="ri-exclamation-line me-1"></i>Hilang</span>
                                                @break
                                                @default
                                                    <span class="badge bg-secondary">{{ ucfirst($inventory->condition) }}</span>
                                            @endswitch
                                        </td>
                                        <td class="gridjs-td" data-column-id="Terakhir Dicek">{{ $inventory->last_checked_at ? $inventory->last_checked_at->format('d/m/Y') : '-' }}</td>
                                        <td class="gridjs-td" data-column-id="Dicek Oleh">{{ e($inventory->checkedBy?->name ?? '-') }}</td>
                                        <td class="gridjs-td" data-column-id="Aksi">
                                            <a href="{{ route('user.asrama.inventories.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'itemUuid' => $inventory->id]) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="ri-edit-line"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="gridjs-tr">
                                        <td colspan="10" class="gridjs-td" style="text-align: center; padding: 40px;">
                                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                            <h6 class="text-muted mb-1 mt-3">Belum Ada Data Inventaris</h6>
                                            <p class="text-muted mb-3">Tidak ada item inventaris yang terdaftar.</p>
                                            <a href="{{ route('user.asrama.inventories.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                               class="btn btn-primary btn-sm">
                                                <i class="ri-add-line me-1"></i> Tambah Item Baru
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if ($inventories->hasPages())
                        <div class="mt-4">
                            {{ $inventories->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection