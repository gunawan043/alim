@extends('layouts.master')
@section('title') Daftar Pasien UKS @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') Rekam Medis Pasien @endslot
        @slot('title') Daftar Pasien @endslot
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

    {{-- ============================================================
         STATS CARDS
    ============================================================ --}}
    <div class="row g-3 mb-2">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90 border-start border-primary">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary rounded fs-2"><i class="ri-hospital-line fs-4 text-white"></i></span>
                        </div>
                        <div>
                            <p class="text-muted text-uppercase fs-12 mb-1">Total Pasien</p>
                            <h3 class="mb-0 fw-bold">{{ $stats['total'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90 border-start border-success">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success rounded fs-2"><i class="ri-pulse-line fs-4 text-white"></i></span>
                        </div>
                        <div>
                            <p class="text-muted text-uppercase fs-12 mb-1">Sedang Aktif</p>
                            <h3 class="mb-0 fw-bold">{{ $stats['active'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90 border-start border-warning">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning rounded fs-2"><i class="ri-eye-line fs-4 text-white"></i></span>
                        </div>
                        <div>
                            <p class="text-muted text-uppercase fs-12 mb-1">Observasi / Rawat UKS</p>
                            <h3 class="mb-0 fw-bold">{{ ($stats['observation'] ?? 0) + ($stats['inpatient'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90 border-start border-danger">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger rounded fs-2"><i class="ri-ambulance-line fs-4 text-white"></i></span>
                        </div>
                        <div>
                            <p class="text-muted text-uppercase fs-12 mb-1">Dirujuk Faskes</p>
                            <h3 class="mb-0 fw-bold">{{ $stats['referrals'] ?? 0 }}</h3>
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
                            <h5 class="card-title mb-0">Daftar Pasien UKS</h5>
                            <p class="text-muted mb-0">
                                Total {{ $stats['total'] ?? 0 }} pasien terdaftar &mdash;
                                {{ $stats['today'] ?? 0 }} pendaftaran hari ini
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('user.uks.patients.create', ['userId' => auth()->user()->id]) }}"
                                   class="btn btn-primary btn-sm">
                                    <i class="ri-add-line align-bottom me-1"></i> Daftarkan Pasien
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- ============================================================
                         FILTER FORM
                    ============================================================ --}}
                    <form method="GET" class="row g-3 align-items-end mb-4 pb-3 border-bottom border-light">
                        <div class="col-md-4">
                            <label class="form-label fs-12 text-muted mb-1">Cari Pasien</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="ri-search-line text-muted"></i>
                                </span>
                                <input type="text" name="search" class="form-control"
                                       placeholder="Nama siswa..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        @php
                            $allStatuses = [
                                \App\Models\Uks\UksPatient::STATUS_WAITING,
                                \App\Models\Uks\UksPatient::STATUS_TREATED,
                                \App\Models\Uks\UksPatient::STATUS_OBSERVATION,
                                \App\Models\Uks\UksPatient::STATUS_INPATIENT,
                                \App\Models\Uks\UksPatient::STATUS_RESTING_UKS,
                                \App\Models\Uks\UksPatient::STATUS_RETURN_DORM,
                                \App\Models\Uks\UksPatient::STATUS_RETURN_SCHOOL,
                                \App\Models\Uks\UksPatient::STATUS_PICKED_UP,
                                \App\Models\Uks\UksPatient::STATUS_LEAVING,
                                \App\Models\Uks\UksPatient::STATUS_REFERRAL_CLINIC,
                                \App\Models\Uks\UksPatient::STATUS_REFERRAL_HOSPITAL,
                                \App\Models\Uks\UksPatient::STATUS_COMPLETED,
                            ];
                        @endphp

                        @php
                            $statusMeta = [
                                \App\Models\Uks\UksPatient::STATUS_WAITING => ['label' => 'Menunggu Pemeriksaan', 'color' => 'warning'],
                                \App\Models\Uks\UksPatient::STATUS_TREATED => ['label' => 'Sedang Ditangani', 'color' => 'warning'],
                                \App\Models\Uks\UksPatient::STATUS_OBSERVATION => ['label' => 'Observasi', 'color' => 'primary'],
                                \App\Models\Uks\UksPatient::STATUS_INPATIENT => ['label' => 'Rawat UKS', 'color' => 'primary'],
                                \App\Models\Uks\UksPatient::STATUS_RESTING_UKS => ['label' => 'Istirahat di UKS', 'color' => 'info'],
                                \App\Models\Uks\UksPatient::STATUS_RETURN_DORM => ['label' => 'Kembali ke Asrama', 'color' => 'success'],
                                \App\Models\Uks\UksPatient::STATUS_RETURN_SCHOOL => ['label' => 'Kembali ke Sekolah', 'color' => 'success'],
                                \App\Models\Uks\UksPatient::STATUS_PICKED_UP => ['label' => 'Dijemput Wali', 'color' => 'secondary'],
                                \App\Models\Uks\UksPatient::STATUS_LEAVING => ['label' => 'Pulang', 'color' => 'success'],
                                \App\Models\Uks\UksPatient::STATUS_REFERRAL_CLINIC => ['label' => 'Dirujuk ke Klinik', 'color' => 'danger'],
                                \App\Models\Uks\UksPatient::STATUS_REFERRAL_HOSPITAL => ['label' => 'Dirujuk ke RS', 'color' => 'danger'],
                                \App\Models\Uks\UksPatient::STATUS_COMPLETED => ['label' => 'Selesai', 'color' => 'success'],
                            ];

                            $transitionsMap = [
                                \App\Models\Uks\UksPatient::STATUS_WAITING => [
                                    \App\Models\Uks\UksPatient::STATUS_TREATED,
                                    \App\Models\Uks\UksPatient::STATUS_OBSERVATION,
                                    \App\Models\Uks\UksPatient::STATUS_INPATIENT,
                                    \App\Models\Uks\UksPatient::STATUS_COMPLETED,
                                ],
                                \App\Models\Uks\UksPatient::STATUS_TREATED => [
                                    \App\Models\Uks\UksPatient::STATUS_OBSERVATION,
                                    \App\Models\Uks\UksPatient::STATUS_INPATIENT,
                                    \App\Models\Uks\UksPatient::STATUS_REFERRAL_CLINIC,
                                    \App\Models\Uks\UksPatient::STATUS_REFERRAL_HOSPITAL,
                                    \App\Models\Uks\UksPatient::STATUS_COMPLETED,
                                ],
                                \App\Models\Uks\UksPatient::STATUS_OBSERVATION => [
                                    \App\Models\Uks\UksPatient::STATUS_INPATIENT,
                                    \App\Models\Uks\UksPatient::STATUS_TREATED,
                                    \App\Models\Uks\UksPatient::STATUS_REFERRAL_CLINIC,
                                    \App\Models\Uks\UksPatient::STATUS_REFERRAL_HOSPITAL,
                                    \App\Models\Uks\UksPatient::STATUS_COMPLETED,
                                ],
                                \App\Models\Uks\UksPatient::STATUS_INPATIENT => [
                                    \App\Models\Uks\UksPatient::STATUS_OBSERVATION,
                                    \App\Models\Uks\UksPatient::STATUS_TREATED,
                                    \App\Models\Uks\UksPatient::STATUS_REFERRAL_CLINIC,
                                    \App\Models\Uks\UksPatient::STATUS_REFERRAL_HOSPITAL,
                                    \App\Models\Uks\UksPatient::STATUS_COMPLETED,
                                    \App\Models\Uks\UksPatient::STATUS_RESTING_UKS,
                                ],
                                \App\Models\Uks\UksPatient::STATUS_RESTING_UKS => [
                                    \App\Models\Uks\UksPatient::STATUS_TREATED,
                                    \App\Models\Uks\UksPatient::STATUS_OBSERVATION,
                                    \App\Models\Uks\UksPatient::STATUS_RETURN_DORM,
                                    \App\Models\Uks\UksPatient::STATUS_RETURN_SCHOOL,
                                    \App\Models\Uks\UksPatient::STATUS_PICKED_UP,
                                    \App\Models\Uks\UksPatient::STATUS_LEAVING,
                                ],
                                \App\Models\Uks\UksPatient::STATUS_RETURN_DORM => [],
                                \App\Models\Uks\UksPatient::STATUS_RETURN_SCHOOL => [],
                                \App\Models\Uks\UksPatient::STATUS_PICKED_UP => [],
                                \App\Models\Uks\UksPatient::STATUS_LEAVING => [],
                                \App\Models\Uks\UksPatient::STATUS_REFERRAL_CLINIC => [],
                                \App\Models\Uks\UksPatient::STATUS_REFERRAL_HOSPITAL => [],
                                \App\Models\Uks\UksPatient::STATUS_COMPLETED => [],
                            ];
                        @endphp
                        <div class="col-md-3">
                            <label class="form-label fs-12 text-muted mb-1">Status Perawatan</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                @foreach($allStatuses as $statusKey)
                                    <option value="{{ $statusKey }}" {{ request('status') === $statusKey ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $statusKey)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fs-12 text-muted mb-1">Hanya Aktif</label>
                            <select name="active_only" class="form-select">
                                <option value="">Semua</option>
                                <option value="1" {{ request('active_only') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ request('active_only') === '0' ? 'selected' : '' }}>Selesai</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="ri-search-line me-1"></i> Terapkan Filter
                                </button>
                                <a href="{{ route('user.uks.patients.index', ['userId' => auth()->user()->id]) }}"
                                   class="btn btn-outline-secondary"
                                   data-bs-toggle="tooltip" data-bs-placement="top" title="Reset Filter">
                                    <i class="ri-refresh-line"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    {{-- ============================================================
                         PATIENTS TABLE
                    ============================================================ --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 5%">No</th>
                                    <th>Pasien</th>
                                    <th style="width: 12%">Tipe Kunjungan</th>
                                    <th style="width: 14%">Status</th>
                                    <th style="width: 14%">Tgl Daftar</th>
                                    <th class="text-center" style="width: 12%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($patients as $i => $patient)
                                    @php
                                        $status = $patient->status;
                                        $statusLabel = ucfirst(str_replace('_', ' ', $status));
                                        $statusColor = match($status) {
                                            \App\Models\Uks\UksPatient::STATUS_WAITING,
                                            \App\Models\Uks\UksPatient::STATUS_TREATED          => 'warning',
                                            \App\Models\Uks\UksPatient::STATUS_OBSERVATION,
                                            \App\Models\Uks\UksPatient::STATUS_INPATIENT         => 'primary',
                                            \App\Models\Uks\UksPatient::STATUS_RESTING_UKS       => 'info',
                                            \App\Models\Uks\UksPatient::STATUS_RETURN_DORM,
                                            \App\Models\Uks\UksPatient::STATUS_RETURN_SCHOOL,
                                            \App\Models\Uks\UksPatient::STATUS_LEAVING,
                                            \App\Models\Uks\UksPatient::STATUS_COMPLETED         => 'success',
                                            \App\Models\Uks\UksPatient::STATUS_PICKED_UP         => 'secondary',
                                            \App\Models\Uks\UksPatient::STATUS_REFERRAL_CLINIC,
                                            \App\Models\Uks\UksPatient::STATUS_REFERRAL_HOSPITAL => 'danger',
                                            default                                               => 'light',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="text-center text-muted">
                                            {{ $patients->firstItem() + $i }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-3">
                                                    <div class="avatar-title rounded-circle bg-{{ $patient->student?->gender === 'P' ? 'danger' : 'primary' }}-subtle text-{{ $patient->student?->gender === 'P' ? 'danger' : 'primary' }} fw-bold fs-10">
                                                        {{ strtoupper(substr($patient->student?->name ?? '?', 0, 1)) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="fw-semibold">{{ $patient->student?->name ?? '-' }}</span>
                                                    @if($patient->student?->nisn)
                                                        <br><small class="text-muted">{{ $patient->student->nisn }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($patient->patient_type === 'rawat')
                                                <span class="badge bg-info-subtle text-info">
                                                    <i class="ri-hospital-line me-1"></i>Rawat
                                                </span>
                                            @elseif($patient->patient_type === 'balik')
                                                <span class="badge bg-warning-subtle text-warning">
                                                    <i class="ri-arrow-go-back-line me-1"></i>Balik Asrama
                                                </span>
                                            @elseif($patient->patient_type === 'pulang')
                                                <span class="badge bg-secondary-subtle text-secondary">
                                                    <i class="ri-roadster-line me-1"></i>Pulang
                                                </span>
                                            @else
                                                <span class="badge bg-light text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                                <i class="ri-pulse-line me-1"></i>{{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="ri-calendar-line me-1"></i>
                                                {{ $patient->admitted_at ? $patient->admitted_at->format('d M Y H:i') : '-' }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="{{ route('user.uks.patients.show', ['userId' => auth()->user()->id, 'uuid' => $patient->id]) }}"
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Detail Pasien">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                @php
                                                    $allowedNext = $allStatuses;
                                                @endphp
                                                @if(count($allowedNext))
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                                                id="statusDropdown-{{ $patient->id }}" data-bs-toggle="dropdown"
                                                                aria-expanded="false" title="Ubah Status">
                                                            <i class="ri-refresh-line"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="statusDropdown-{{ $patient->id }}">
                                                            @foreach($allowedNext as $nextStatus)
                                                                @php
                                                                    $meta = $statusMeta[$nextStatus] ?? ['label' => ucfirst(str_replace('_', ' ', $nextStatus)), 'color' => 'secondary'];
                                                                @endphp
                                                                <li>
                                                                    <form method="POST" action="{{ route('user.uks.patients.change-status', ['userId' => auth()->user()->id, 'uuid' => $patient->id]) }}" class="d-inline">
                                                                        @csrf
                                                                        <input type="hidden" name="new_status" value="{{ $nextStatus }}">
                                                                        <button type="submit" class="dropdown-item">
                                                                            <span class="badge bg-{{ $meta['color'] }}-subtle text-{{ $meta['color'] }}">
                                                                                {{ $meta['label'] }}
                                                                            </span>
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                            <h6 class="text-muted mb-1 mt-3">Belum Ada Pasien</h6>
                                            <p class="text-muted mb-3">Belum ada data pasien yang terdaftar di UKS.</p>
                                            <a href="{{ route('user.uks.patients.create', ['userId' => auth()->user()->id]) }}"
                                               class="btn btn-primary btn-sm">
                                                <i class="ri-add-line me-1"></i> Daftarkan Pasien
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination (shared template) --}}
                    @include('shared._pagination', ['paginator' => $patients->withQueryString()])
                </div>
            </div>
        </div>
    </div>
@endsection