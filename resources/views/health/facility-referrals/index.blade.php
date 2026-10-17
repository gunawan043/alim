@extends('layouts.master')
@section('title') Faskes Rujukan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('title') Faskes Rujukan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <?php
    $total    = $facilities->total();
    $active   = collect($facilities->items())->filter(fn($r) => $r->is_active)->count();
    $nearby   = collect($facilities->items())->filter(fn($r) => $r->distance_km && $r->distance_km <= 5)->count();
    $allTime  = collect($facilities->items())->filter(fn($r) => $r->is_available_24h)->count();
    $typeMap  = ['puskesmas'=>'Puskesmas','rumah_sakit'=>'Rumah Sakit','klinik'=>'Klinik','dokter_praktik'=>'Dokter Praktik','rs_psychologist'=>'RS Psych','posyandu'=>'Posyandu'];
    $typeColor= ['puskesmas'=>'primary','rumah_sakit'=>'danger','klinik'=>'info','dokter_praktik'=>'warning','rs_psychologist'=>'dark','posyandu'=>'success'];
    ?>

    {{-- Stats --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card border-start border-1 border-primary">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-hospital-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Total Faskes</p>
                        <h5 class="mb-0">{{ $total }} <span class="fs-6 text-muted">faskes</span></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-1 border-success">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-checkbox-circle-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Aktif</p>
                        <h5 class="mb-0">{{ $active }} <span class="fs-6 text-muted">faskes</span></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-1 border-info">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-info bg-opacity-10 text-info rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-map-pin-time-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Dekat (&le;5km)</p>
                        <h5 class="mb-0">{{ $nearby }} <span class="fs-6 text-muted">faskes</span></h5>
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
                        <p class="text-muted mb-0 small">Buka 24 Jam</p>
                        <h5 class="mb-0">{{ $allTime }} <span class="fs-6 text-muted">faskes</span></h5>
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
                            <h5 class="card-title mb-0">Faskes Rujukan</h5>
                            <p class="text-muted mb-0 small">Master fasilitas kesehatan rujukan</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.uks.facility-referrals.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Faskes
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Nama faskes / alamat..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="facility_type" class="form-control">
                                <option value="">Semua Jenis</option>
                                @foreach($typeMap as $k => $v)
                                    <option value="{{ $k }}" {{ request('facility_type')==$k?'selected':'' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.uks.facility-referrals.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Nama Faskes</th>
                                    <th>Jenis</th>
                                    <th>Alamat</th>
                                    <th>Telepon</th>
                                    <th class="text-center">24 Jam</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($facilities as $i => $row)
                                <tr class="{{ !$row->is_active ? 'table-secondary' : '' }}">
                                    <td class="text-center text-muted">{{ $facilities->firstItem() + $i }}</td>
                                    <td>
                                        <span class="fw-semibold">{{ $row->facility_name }}</span>
                                        @if($row->distance_km)
                                            <br><small class="text-muted"><i class="ri-map-pin-line me-1"></i>{{ $row->distance_km }} km</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $typeColor[$row->facility_type] ?? 'secondary' }}">
                                            {{ $typeMap[$row->facility_type] ?? $row->facility_type }}
                                        </span>
                                    </td>
                                    <td class="text-muted">{{ Str::limit($row->address, 40) ?: '-' }}</td>
                                    <td>
                                        @if($row->phone)
                                            <a href="tel:{{ $row->phone }}" class="text-primary text-decoration-none">{{ $row->phone }}</a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($row->is_available_24h)
                                            <span class="badge bg-success"><i class="ri-check-line me-1"></i>Ya</span>
                                        @else
                                            <span class="badge bg-light text-dark"><i class="ri-close-line me-1"></i>Tidak</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $row->is_active ? 'success' : 'secondary' }}">
                                            {{ $row->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('user.uks.facility-referrals.show', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                           class="btn btn-sm btn-outline-primary me-1"><i class="ri-eye-line"></i></a>
                                        <a href="{{ route('user.uks.facility-referrals.edit', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                           class="btn btn-sm btn-outline-secondary me-1"><i class="ri-edit-line"></i></a>
                                        <form method="POST" action="{{ route('user.uks.facility-referrals.destroy', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                              class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-btn"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="ri-hospital-line fs-1 d-block mb-2"></i>
                                        Belum ada data fasilitas kesehatan.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $facilities->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection