@extends('layouts.master')
@section('title') Perizinan Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Perizinan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-shrink-0">
                            <div class="avatar-md rounded-circle bg-warning-subtle">
                                <i class="ri-time-line fs-24 text-warning"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Menunggu</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['pending'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-shrink-0">
                            <div class="avatar-md rounded-circle bg-success-subtle">
                                <i class="ri-checkbox-circle-line fs-24 text-success"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Disetujui</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['approved'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-shrink-0">
                            <div class="avatar-md rounded-circle bg-danger-subtle">
                                <i class="ri-alarm-warning-line fs-24 text-danger"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Terlambat / Overdue</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['overdue'] ?? 0) }}</h3>
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
                            <h5 class="card-title mb-0">Daftar Perizinan</h5>
                            <p class="text-muted mb-0">{{ $dormitory->name }} — Tahun Ajaran {{ $activeYear->name ?? '-' }}</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.asrama.permits.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-primary">
                                <i class="ri-add-line align-bottom me-1"></i> Ajukan Izin
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filters --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Nama santri / tujuan..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="pending"   {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                                <option value="approved"  {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                                <option value="rejected"  {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                                <option value="returned"  {{ request('status') == 'returned' ? 'selected' : '' }}>Sudah Pulang</option>
                                <option value="overdue"   {{ request('status') == 'overdue' ? 'selected' : '' }}>Terlambat</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="permit_type" class="form-control">
                                <option value="">Semua Jenis</option>
                                <option value="pulang"           {{ request('permit_type') == 'pulang' ? 'selected' : '' }}>Pulang</option>
                                <option value="keluar_kota"       {{ request('permit_type') == 'keluar_kota' ? 'selected' : '' }}>Keluar Kota</option>
                                <option value="berobat"           {{ request('permit_type') == 'berobat' ? 'selected' : '' }}>Berobat</option>
                                <option value="sakit"            {{ request('permit_type') == 'sakit' ? 'selected' : '' }}>Sakit</option>
                                <option value="keperluan_keluarga" {{ request('permit_type') == 'keperluan_keluarga' ? 'selected' : '' }}>Keperluan Keluarga</option>
                                <option value="lainnya"           {{ request('permit_type') == 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line"></i></button>
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Santri</th>
                                    <th>Jenis Izin</th>
                                    <th>Penjemput</th>
                                    <th>Tujuan</th>
                                    <th>Berangkat</th>
                                    <th>Kembali (Est.)</th>
                                    <th>Kembali (Akt.)</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permits as $i => $permit)
                                    <tr class="{{ $permit->isOverdue ? 'table-danger' : '' }}">
                                        <td class="text-center">{{ $permits->firstItem() + $i }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $permit->student?->name ?? '—' }}</div>
                                            @if($permit->room)
                                                <div class="text-muted small">Kamar: {{ $permit->room->name }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">{{ $permit->permit_type_text }}</span>
                                        </td>
                                        <td>
                                            <div>{{ $permit->companion_name ?: '—' }}</div>
                                            @if($permit->companion_is_mahrom)
                                                <span class="badge bg-dark-subtle text-dark mt-1">
                                                    <i class="ri-shield-check-line me-1"></i>Mahrom
                                                </span>
                                            @elseif($permit->companion_relation)
                                                <div class="text-muted small">{{ $permit->companion_relation }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $permit->destination ?: '—' }}</td>
                                        <td>
                                            @if($permit->departure_datetime)
                                                <span class="small">{{ $permit->departure_datetime->format('d/m/Y') }}</span>
                                                <div class="text-muted small">{{ $permit->departure_datetime->format('H:i') }}</div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if($permit->expected_return_datetime)
                                                <span class="small">{{ $permit->expected_return_datetime->format('d/m/Y') }}</span>
                                                <div class="text-muted small">{{ $permit->expected_return_datetime->format('H:i') }}</div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if($permit->actual_return_datetime)
                                                <span class="small text-success">{{ $permit->actual_return_datetime->format('d/m/Y') }}</span>
                                                <div class="text-muted small">{{ $permit->actual_return_datetime->format('H:i') }}</div>
                                            @elseif($permit->status === 'returned')
                                                <span class="text-muted small">—</span>
                                            @else
                                                <span class="text-muted small">Belum</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($permit->status === 'pending')
                                                <span class="badge bg-warning-subtle text-warning">Menunggu</span>
                                            @elseif($permit->status === 'approved')
                                                <span class="badge bg-success-subtle text-success">Disetujui</span>
                                            @elseif($permit->status === 'rejected')
                                                <span class="badge bg-danger-subtle text-danger">Ditolak</span>
                                            @elseif($permit->status === 'returned')
                                                <span class="badge bg-secondary-subtle text-secondary">Sudah Pulang</span>
                                            @elseif($permit->status === 'overdue' || $permit->isOverdue)
                                                <span class="badge bg-danger">Terlambat</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $permit->status }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.asrama.permits.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}"
                                               class="btn btn-sm btn-outline-primary me-1" title="Detail">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            @if($permit->status === 'pending')
                                                <form method="POST"
                                                      action="{{ route('user.asrama.permits.approve', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}"
                                                      class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success me-1"
                                                            onclick="return confirm('Setujui izin ini?')" title="Setujui">
                                                        <i class="ri-check-line"></i>
                                                    </button>
                                                </form>
                                                <form method="POST"
                                                      action="{{ route('user.asrama.permits.reject', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}"
                                                      class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Tolak izin ini?')" title="Tolak">
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-5">
                                            <i class="ri-file-list-line fs-1 d-block mb-2 text-muted"></i>
                                            Belum ada data perizinan.
                                            <br>
                                            <a href="{{ route('user.asrama.permits.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                               class="btn btn-sm btn-primary mt-2">
                                                <i class="ri-add-line me-1"></i> Ajukan Izin Baru
                                            </a>
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