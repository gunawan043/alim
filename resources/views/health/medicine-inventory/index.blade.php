@extends('layouts.master')
@section('title') Inventori Obat UKS @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('title') Inventori Obat @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <?php
    $total     = $inventories->total();
    $lowStock  = collect($inventories->items())->filter(fn($r) => $r->is_low_stock)->count();
    $expiring  = collect($inventories->items())->filter(fn($r) => $r->is_expiring_soon && !$r->is_expired)->count();
    $expired   = collect($inventories->items())->filter(fn($r) => $r->is_expired)->count();
    $catMap    = ['obat_dalam'=>'Obat Dalam','obat_luar'=>'Obat Luar','vitamin_suplemen'=>'Vitamin & Suplemen','antiseptik'=>'Antiseptik','alat_kesehatan'=>'Alat Kesehatan'];
    $catColor  = ['obat_dalam'=>'primary','obat_luar'=>'warning','vitamin_suplemen'=>'success','antiseptik'=>'info','alat_kesehatan'=>'dark'];
    ?>

    {{-- Stats --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card border-start border-1 border-primary">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-medicine-bottle-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Total Obat</p>
                        <h5 class="mb-0">{{ $total }} <span class="fs-6 text-muted">item</span></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-1 border-danger">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-danger bg-opacity-10 text-danger rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-error-warning-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Stok Minim</p>
                        <h5 class="mb-0">{{ $lowStock }} <span class="fs-6 text-muted">item</span></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-1 border-warning">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-warning bg-opacity-10 text-warning rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-time-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Kedaluarsa Soon</p>
                        <h5 class="mb-0">{{ $expiring }} <span class="fs-6 text-muted">item</span></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-1 border-dark">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-dark bg-opacity-10 text-dark rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-forbid-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Kedaluarsa</p>
                        <h5 class="mb-0">{{ $expired }} <span class="fs-6 text-muted">item</span></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'all' ? 'active' : '' }}"
               href="{{ route('user.uks.medicine-inventory.index', ['userId' => $userId, 'tab' => 'all']) }}">
                <i class="ri-list-check me-1"></i> Semua
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'low_stock' ? 'active' : '' }}"
               href="{{ route('user.uks.medicine-inventory.index', ['userId' => $userId, 'tab' => 'low_stock']) }}">
                <i class="ri-error-warning-line me-1"></i> Stok Minim
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'expiring' ? 'active' : '' }}"
               href="{{ route('user.uks.medicine-inventory.index', ['userId' => $userId, 'tab' => 'expiring']) }}">
                <i class="ri-time-line me-1"></i> Kedaluarsa Soon
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'expired' ? 'active' : '' }}"
               href="{{ route('user.uks.medicine-inventory.index', ['userId' => $userId, 'tab' => 'expired']) }}">
                <i class="ri-forbid-line me-1"></i> Kedaluarsa
            </a>
        </li>
    </ul>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Inventori Obat UKS</h5>
                            <p class="text-muted mb-0 small">Pengelolaan stok obat di UKS pondok</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.uks.medicine-inventory.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Obat
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Nama obat / kode..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="category" class="form-control">
                                <option value="">Semua Kategori</option>
                                @foreach($catMap as $k => $v)
                                    <option value="{{ $k }}" {{ request('category')==$k?'selected':'' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('user.uks.medicine-inventory.index', ['userId' => $userId, 'tab' => $tab]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Nama Obat</th>
                                    <th>Kategori</th>
                                    <th class="text-center">Stok</th>
                                    <th class="text-center">Satuan</th>
                                    <th>Kadaluarsa</th>
                                    <th>Lokasi</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventories as $i => $row)
                                    <tr class="{{ $row->is_expired ? 'table-danger' : ($row->is_low_stock ? 'table-warning' : ($row->is_expiring_soon ? 'table-active' : '')) }}">
                                        <td class="text-center text-muted">{{ $inventories->firstItem() + $i }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $row->medicine_name }}</span>
                                            @if($row->medicine_code)
                                                <br><small class="text-muted">{{ $row->medicine_code }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $catColor[$row->category] ?? 'secondary' }}">
                                                {{ $row->category_text }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold {{ $row->is_low_stock ? 'text-danger' : '' }}">
                                                {{ $row->current_stock }}
                                            </span>
                                            @if($row->is_low_stock)
                                                <br><small class="text-danger"><i class="ri-error-warning-line"></i> Minim</small>
                                            @endif
                                        </td>
                                        <td class="text-center text-muted">{{ $row->unit }}</td>
                                        <td>
                                            @if($row->expiry_date)
                                                <span class="{{ $row->is_expired ? 'text-danger fw-semibold' : ($row->is_expiring_soon ? 'text-warning fw-semibold' : 'text-muted') }}">
                                                    {{ $row->expiry_date->format('d/m/Y') }}
                                                </span>
                                                @if($row->is_expired)
                                                    <br><span class="badge bg-danger mt-1">Kedaluarsa</span>
                                                @elseif($row->is_expiring_soon)
                                                    <br><span class="badge bg-warning mt-1">Segera</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-muted">{{ $row->storage_location ?? '-' }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('user.uks.medicine-inventory.show', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                               class="btn btn-sm btn-outline-primary me-1"><i class="ri-eye-line"></i></a>
                                            <a href="{{ route('user.uks.medicine-inventory.edit', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                               class="btn btn-sm btn-outline-secondary me-1"><i class="ri-edit-line"></i></a>
                                            <form method="POST" action="{{ route('user.uks.medicine-inventory.destroy', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                                  class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn"><i class="ri-delete-bin-line"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="ri-medicine-bottle-line fs-1 d-block mb-2"></i>
                                            Belum ada data obat di inventori.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $inventories->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection