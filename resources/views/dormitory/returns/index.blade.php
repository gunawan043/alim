@extends('layouts.master')
@section('title') Kedatangan Santri @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Kedatangan Santri @endslot
        @slot('action')
            <a href="{{ route('user.asrama.permit-wizard.step1', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-primary btn-sm me-1">
                <i class="ri-magic-line me-1"></i> Izin Kepulangan
            </a>
            <a href="{{ route('user.asrama.dormitory-returns.statistics', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light btn-sm">
                <i class="ri-bar-chart-box-line me-1"></i> Statistik
            </a>
        @endslot
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
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Belum Kembali</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['pending'] ?? 0) }}</h3>
                            <small class="text-muted">Santri yang izin disetujui & belum pulang</small>
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
                                <i class="ri-home-heart-line fs-24 text-success"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Kembali Hari Ini</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['today_returned'] ?? 0) }}</h3>
                            <small class="text-muted">Santri yang sudah kembali</small>
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
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Terlambat</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['overdue'] ?? 0) }}</h3>
                            <small class="text-muted">Belum kembali dari jadwal</small>
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
                            <h5 class="card-title mb-0">Pendataan Kepulangan Santri</h5>
                            <p class="text-muted mb-0">{{ $dormitory->name }} — Tahun Ajaran {{ $activeYear->name ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filter Tabs --}}
                    <ul class="nav nav-pills nav-justified mb-3 bg-light rounded">
                        <li class="nav-item">
                            <a class="nav-link{{ ($filter ?? 'pending') == 'pending' ? ' active' : '' }}"
                               href="{{ route('user.asrama.dormitory-returns.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'filter' => 'pending']) }}">
                                <i class="ri-time-line me-1"></i> Belum Kembali
                                @if(($stats['pending'] ?? 0) > 0)
                                    <span class="badge bg-warning ms-1">{{ $stats['pending'] }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link{{ ($filter ?? '') == 'today' ? ' active' : '' }}"
                               href="{{ route('user.asrama.dormitory-returns.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'filter' => 'today']) }}">
                                <i class="ri-home-heart-line me-1"></i> Hari Ini
                                @if(($stats['today_returned'] ?? 0) > 0)
                                    <span class="badge bg-success ms-1">{{ $stats['today_returned'] }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link{{ ($filter ?? '') == 'overdue' ? ' active' : '' }}"
                               href="{{ route('user.asrama.dormitory-returns.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'filter' => 'overdue']) }}">
                                <i class="ri-alarm-warning-line me-1"></i> Terlambat
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link{{ ($filter ?? '') == 'all' ? ' active' : '' }}"
                               href="{{ route('user.asrama.dormitory-returns.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'filter' => 'all']) }}">
                                <i class="ri-file-list-line me-1"></i> Semua
                            </a>
                        </li>
                    </ul>

                    {{-- Search --}}
                    <form method="GET" class="row g-3 mb-4">
                        <input type="hidden" name="filter" value="{{ $filter ?? 'pending' }}">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Cari nama santri..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-search-line me-1"></i> Cari
                            </button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Santri</th>
                                    <th>Kamar</th>
                                    <th>Jenis Izin</th>
                                    <th>Berangkat</th>
                                    <th>Estimasi Kembali</th>
                                    <th>Status</th>
                                    <th class="text-center" style="width:180px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permits as $i => $permit)
                                    <tr class="{{ $permit->status === 'overdue' ? 'table-danger' : '' }}">
                                        <td class="text-center">{{ $permits->firstItem() + $i }}</td>
                                        <td>
                                            <div class="fw-semibold">
                                                <a href="{{ route('user.asrama.dormitory-returns.history', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'studentUuid' => $permit->student_id]) }}">
                                                    {{ $permit->student?->name ?? '—' }}
                                                </a>
                                            </div>
                                            @if($permit->mahrom)
                                                <small class="text-muted">Wali: {{ $permit->mahrom->name }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $permit->room?->name ?? '—' }}</td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">{{ $permit->permit_type_text ?? ucwords(str_replace('_',' ', $permit->permit_type)) }}</span>
                                        </td>
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
                                        <td class="text-center">
                                            @if($permit->status === 'approved')
                                                <span class="badge bg-success-subtle text-success">Disetujui</span>
                                            @elseif($permit->status === 'overdue')
                                                <span class="badge bg-danger">Terlambat</span>
                                            @elseif($permit->status === 'returned')
                                                <span class="badge bg-secondary-subtle text-secondary">Sudah Kembali</span>
                                                @if($permit->actual_return_datetime)
                                                    <div class="text-muted small mt-1">{{ $permit->actual_return_datetime->format('d/m/Y H:i') }}</div>
                                                @endif
                                            @elseif($permit->status === 'rejected')
                                                <span class="badge bg-danger-subtle text-danger">Ditolak</span>
                                            @elseif($permit->status === 'pending')
                                                <span class="badge bg-warning-subtle text-warning">Menunggu</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $permit->status }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(in_array($permit->status, ['approved', 'overdue']) && is_null($permit->actual_return_datetime))
                                                <button type="button" class="btn btn-sm btn-success"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#returnModal{{ $permit->id }}">
                                                    <i class="ri-login-box-line me-1"></i> Catat Kembali
                                                </button>

                                                {{-- Modal Catat Kepulangan --}}
                                                <div class="modal fade" id="returnModal{{ $permit->id }}" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <form method="POST"
                                                                  action="{{ route('user.asrama.dormitory-returns.record', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}">
                                                                @csrf
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">
                                                                        <i class="ri-home-heart-line me-1"></i>
                                                                        Catat Kepulangan Santri
                                                                    </h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                                </div>
                                                                <div class="modal-body text-start">
                                                                    <div class="mb-3 p-3 bg-light-subtle rounded">
                                                                        <div class="fw-semibold fs-5">{{ $permit->student?->name }}</div>
                                                                        <small class="text-muted">
                                                                            Kamar {{ $permit->room?->name ?? '—' }} •
                                                                            Izin: {{ $permit->permit_type_text ?? ucwords(str_replace('_',' ', $permit->permit_type)) }}
                                                                        </small>
                                                                        <div class="mt-2">
                                                                            <small class="text-muted">Berangkat: {{ $permit->departure_datetime?->format('d/m/Y H:i') }}</small><br>
                                                                            <small class="text-muted">Estimasi kembali: {{ $permit->expected_return_datetime?->format('d/m/Y H:i') ?? '—' }}</small>
                                                                        </div>
                                                                    </div>

                                                                    <input type="hidden" name="student_name" value="{{ $permit->student?->name }}">

                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-semibold">
                                                                            Waktu Kembali Aktual <span class="text-danger">*</span>
                                                                        </label>
                                                                        <input type="datetime-local" name="actual_return_datetime"
                                                                               class="form-control @error('actual_return_datetime') is-invalid @enderror"
                                                                               value="{{ old('actual_return_datetime', now()->format('Y-m-d\TH:i')) }}"
                                                                               required>
                                                                        @error('actual_return_datetime')
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>

                                                                    <div class="alert alert-info mb-0 small">
                                                                        <i class="ri-information-line me-1"></i>
                                                                        Santri akan otomatis ditandai <strong>kembali ke asrama</strong> dan
                                                                        tercatat di timeline kepulangan.
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                                    <button type="submit" class="btn btn-success">
                                                                        <i class="ri-check-line me-1"></i> Simpan
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            @elseif($permit->status === 'returned')
                                                <a href="{{ route('user.asrama.permits.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}"
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="ri-eye-line me-1"></i> Detail
                                                </a>
                                            @else
                                                <a href="{{ route('user.asrama.permits.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                    {{-- Hidden quick links row for accessing history per student --}}
                                    {{-- Could be made visible via dropdown, intentionally hidden for simplicity --}}
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i class="ri-home-heart-line fs-1 d-block mb-2 text-muted"></i>
                                            @if(($filter ?? 'pending') == 'pending')
                                                Tidak ada izin yang perlu dicatat kepulangannya saat ini.
                                                <br><small class="text-muted">Halaman ini otomatis menampilkan izin yang sudah disetujui tapi belum ada catatan kembali.</small>
                                            @elseif(($filter ?? '') == 'today')
                                                Belum ada kepulangan yang tercatat hari ini.
                                            @elseif(($filter ?? '') == 'overdue')
                                                Tidak ada izin yang terlambat saat ini.
                                            @else
                                                Belum ada data perizinan.
                                            @endif
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