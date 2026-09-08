@extends('layouts.master')
@section('title') Perizinan Santri — Sistem @endsection

@section('css')
    <style>
        .card-animate { transition: all 0.3s ease; }
        .card-animate:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sistem @endslot
        @slot('title') Perizinan Santri @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @php
        $totalApproved = ($stats['approved'] ?? 0) + ($stats['picked_up'] ?? 0);
        $totalActive   = ($stats['pending'] ?? 0) + ($totalApproved) + ($stats['overdue'] ?? 0);
        $totalAll      = ($stats['pending'] ?? 0) + ($totalApproved) + ($stats['returned'] ?? 0) + ($stats['overdue'] ?? 0) + ($stats['rejected'] ?? 0);
    @endphp

    {{-- STATISTICS CARDS --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2">
                                <i class="bx bx-receipt text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Izin</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalAll) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0" style="font-size:11px;">
                        <i class="ri-information-line me-1"></i>Tahun Ajaran {{ $activeYear?->name ?? '—' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2">
                                <i class="bx bx-time text-warning"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Menunggu</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['pending'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0" style="font-size:11px;">Perlu tindakan admin</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="bx bx-check-circle text-success"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Izin Aktif</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalActive) }}</h3>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-info-subtle text-info" style="font-size:10px;">
                            <i class="ri-checkbox-circle-fill me-1"></i>{{ number_format($stats['approved'] ?? 0) }} Disetujui
                        </span>
                        <span class="badge bg-primary-subtle text-primary" style="font-size:10px;">
                            <i class="ri-arrow-right-circle-fill me-1"></i>{{ number_format($stats['picked_up'] ?? 0) }} Dipinjam
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-2">
                                <i class="bx bx-error-circle text-danger"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Terlambat</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['overdue'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0" style="font-size:11px;">Perlu tindak lanjut</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed bg-light">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary-subtle rounded fs-3">
                                        <i class="bx bx-receipt text-primary"></i>
                                    </span>
                                </div>
                                <div>
                                    <h5 class="card-title mb-0">Daftar Semua Perizinan</h5>
                                    <p class="text-muted mb-0" style="font-size:11px;">
                                        <i class="ri-calendar-line me-1"></i>Tahun Ajaran {{ $activeYear?->name ?? '—' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filter Section --}}
                    <div class="filter-group" style="background:#f8fafc;border-radius:12px;padding:16px;margin-bottom:16px;">
                        <div class="filter-group-title" style="font-size:15px;font-weight:600;color:#1e293b;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                            <i class="ri-filter-3-line" style="color:#0a5f9e;font-size:18px;"></i> Filter & Pencarian
                        </div>
                        <form method="GET" class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Cari</label>
                                <input type="text" name="search" class="form-control form-control-sm"
                                       placeholder="Nama / NISN / tujuan..."
                                       value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">Semua Status</option>
                                    <option value="pending"   {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                                    <option value="approved"  {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                                    <option value="picked_up" {{ request('status') == 'picked_up' ? 'selected' : '' }}>Dipinjam</option>
                                    <option value="returned"  {{ request('status') == 'returned' ? 'selected' : '' }}>Kembali</option>
                                    <option value="overdue"   {{ request('status') == 'overdue' ? 'selected' : '' }}>Terlambat</option>
                                    <option value="rejected"  {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Asrama</label>
                                <select name="dormitory_id" class="form-select form-select-sm">
                                    <option value="">Semua Asrama</option>
                                    @foreach($dormitories as $d)
                                        <option value="{{ $d->id }}" {{ request('dormitory_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Dari</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Sampai</label>
                                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                            </div>
                        </form>
                        <div class="row g-2 mt-2 align-items-center">
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="ri-search-line"></i> Filter</button>
                            </div>
                            <div class="col-md-2">
                                <a href="{{ route('permits.index') }}" class="btn btn-light btn-sm w-100">Reset</a>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Santri</th>
                                    <th>Asrama</th>
                                    <th>Tujuan</th>
                                    <th>Jenis</th>
                                    <th class="text-center">Tanggal Keluar</th>
                                    <th class="text-center">Est. Kembali</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permits as $i => $p)
                                    <tr>
                                        <td class="text-center">{{ $permits->firstItem() + $i }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $p->student?->name ?? '—' }}</div>
                                            @if($p->student?->nisn)
                                                <div class="text-muted small">NISN: {{ $p->student->nisn }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($p->dormitory)
                                                <a href="{{ route('user.asrama.show', ['userId' => auth()->id(), 'asramaUuid' => $p->dormitory_id]) }}"
                                                   class="text-primary">{{ $p->dormitory->name }}</a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $p->destination ?? '—' }}</td>
                                        <td>
                                            <span class="badge bg-secondary-subtle">{{ ucfirst(str_replace('_', ' ', $p->permit_type ?? 'pulang')) }}</span>
                                        </td>
                                        <td class="text-center">
                                            {{ optional($p->departure_datetime)->format('d/m/Y H:i') ?? '—' }}
                                        </td>
                                        <td class="text-center">
                                            {{ optional($p->expected_return_datetime)->format('d/m/Y H:i') ?? '—' }}
                                        </td>
                                        <td>
                                            @if($p->status === 'pending')
                                                <span class="badge bg-warning-subtle text-warning">Menunggu</span>
                                            @elseif($p->status === 'approved')
                                                <span class="badge bg-success-subtle text-success">Disetujui</span>
                                            @elseif($p->status === 'picked_up')
                                                <span class="badge bg-primary-subtle text-primary">Dipinjam</span>
                                            @elseif($p->status === 'returned')
                                                <span class="badge bg-info-subtle text-info">Kembali</span>
                                            @elseif($p->status === 'overdue')
                                                <span class="badge bg-danger-subtle text-danger">Terlambat</span>
                                            @elseif($p->status === 'rejected')
                                                <span class="badge bg-danger">Ditolak</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $p->status_text ?? $p->status }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="#"
                                               class="btn btn-sm btn-outline-primary"
                                               title="Detail"
                                               {{ !$p->dormitory_id ? 'disabled' : '' }}>
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-5">
                                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon> <br>
                                            Belum ada data perizinan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">Menampilkan {{ $permits->firstItem() ?? 0 }} - {{ $permits->lastItem() ?? 0 }} dari {{ $permits->total() }} data</div>
                        <div>{{ $permits->withQueryString()->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
