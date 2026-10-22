@extends('layouts.master')
@section('title') Daftar Pasien UKS @endsection

@section('css')
    @include('uks._ui-styles')
@endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? auth()->id();
        $isClinicMode = (string) setting('uks_mode', 'simple') === 'clinic';
        $statusLabels = \App\Models\Uks\UksPatient::$statusLabels;
        $statusColors = [
            \App\Models\Uks\UksPatient::STATUS_WAITING => 'warning',
            \App\Models\Uks\UksPatient::STATUS_TREATED => 'warning',
            \App\Models\Uks\UksPatient::STATUS_OBSERVATION => 'primary',
            \App\Models\Uks\UksPatient::STATUS_INPATIENT => 'primary',
            \App\Models\Uks\UksPatient::STATUS_RESTING_UKS => 'info',
            \App\Models\Uks\UksPatient::STATUS_RETURN_DORM => 'success',
            \App\Models\Uks\UksPatient::STATUS_RETURN_SCHOOL => 'success',
            \App\Models\Uks\UksPatient::STATUS_PICKED_UP => 'secondary',
            \App\Models\Uks\UksPatient::STATUS_LEAVING => 'success',
            \App\Models\Uks\UksPatient::STATUS_REFERRAL_CLINIC => 'danger',
            \App\Models\Uks\UksPatient::STATUS_REFERRAL_HOSPITAL => 'danger',
            \App\Models\Uks\UksPatient::STATUS_COMPLETED => 'success',
        ];
        $transitionMap = collect(array_keys($statusLabels))
            ->mapWithKeys(fn ($status) => [$status => app(\App\Services\Uks\UksClinicService::class)->allowedTransitions($status)])
            ->all();

        // Kompatibilitas status lama
        foreach (['aktif' => 'Aktif', 'selesai' => 'Selesai', 'dirujuk' => 'Dirujuk'] as $legacy => $legacyLabel) {
            $statusLabels[$legacy] = $legacyLabel;
            $statusColors[$legacy] = $legacy === 'dirujuk' ? 'danger' : ($legacy === 'selesai' ? 'success' : 'warning');
            $transitionMap[$legacy] = app(\App\Services\Uks\UksClinicService::class)->allowedTransitions($legacy);
        }
        $hasFilter = request()->filled('search') || request()->filled('status') || request()->filled('active_only') || request()->filled('month');
    @endphp

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

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- STATISTIK --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100 border-start border-primary">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar-sm rounded bg-primary-subtle d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="ri-hospital-line text-primary fs-5"></i>
                        </span>
                        <div>
                            <p class="text-uppercase text-muted fs-11 mb-1">Total Pasien</p>
                            <h3 class="mb-0 fw-bold">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted fs-11 mb-0 mt-2"><i class="ri-information-line me-1"></i>{{ $stats['today'] ?? 0 }} pendaftaran hari ini</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100 border-start border-success">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar-sm rounded bg-success-subtle d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="ri-pulse-line text-success fs-5"></i>
                        </span>
                        <div>
                            <p class="text-uppercase text-muted fs-11 mb-1">Sedang Aktif</p>
                            <h3 class="mb-0 fw-bold">{{ number_format($stats['active'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted fs-11 mb-0 mt-2">
                        <span class="badge bg-warning-subtle text-warning">{{ $stats['waiting'] ?? 0 }} menunggu</span>
                        <span class="badge bg-success-subtle text-success">{{ $stats['pharmacy'] ?? 0 }} farmasi</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100 border-start border-warning">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar-sm rounded bg-warning-subtle d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="ri-eye-line text-warning fs-5"></i>
                        </span>
                        <div>
                            <p class="text-uppercase text-muted fs-11 mb-1">Observasi / Rawat UKS</p>
                            <h3 class="mb-0 fw-bold">{{ number_format(($stats['observation'] ?? 0) + ($stats['inpatient'] ?? 0)) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted fs-11 mb-0 mt-2"><i class="ri-hotel-bed-line me-1"></i>Termasuk pasien yang menempati bed</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100 border-start border-danger">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar-sm rounded bg-danger-subtle d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="ri-ambulance-line text-danger fs-5"></i>
                        </span>
                        <div>
                            <p class="text-uppercase text-muted fs-11 mb-1">Dirujuk Faskes</p>
                            <h3 class="mb-0 fw-bold">{{ number_format($stats['referrals'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted fs-11 mb-0 mt-2"><i class="ri-hospital-line me-1"></i>{{ $isClinicMode ? 'Klinik / puskesmas / rumah sakit' : 'Pemeriksaan awal UKS' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-6">
            <a href="{{ route('user.uks.clinic.queue', ['userId' => $userId]) }}" class="text-decoration-none">
                <div class="alert alert-warning d-flex align-items-center justify-content-between mb-0 py-2">
                    <span><i class="ri-time-line me-1"></i>Menunggu antrean: <strong>{{ $stats['waiting'] ?? 0 }}</strong></span>
                    <i class="ri-arrow-right-line"></i>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="{{ route('user.uks.pharmacy.index', ['userId' => $userId]) }}" class="text-decoration-none">
                <div class="alert alert-success d-flex align-items-center justify-content-between mb-0 py-2">
                    <span><i class="ri-capsule-line me-1"></i>Menunggu obat / farmasi: <strong>{{ $stats['pharmacy'] ?? 0 }}</strong></span>
                    <i class="ri-arrow-right-line"></i>
                </div>
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0">Daftar Pasien UKS</h5>
                    <p class="text-muted mb-0 fs-12">Total {{ $stats['total'] ?? 0 }} pasien terdaftar</p>
                </div>
                <div class="col-sm-auto">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <form method="GET" action="{{ route('user.uks.patients.index', ['userId' => $userId]) }}" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                                   placeholder="Cari nama / NIS / No. RM / antrean" style="min-width: 240px;">
                            <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i></button>
                        </form>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#patientFilterModal">
                            <i class="bx bx-filter-alt align-bottom me-1"></i>Filter Lanjutan
                        </button>
                        @if ((string) setting('uks_mode', 'simple') === 'clinic')
                            <a href="{{ route('user.uks.clinic.queue', ['userId' => $userId]) }}" class="btn btn-soft-primary">
                                <i class="ri-list-check-2 me-1"></i>Antrean
                            </a>
                        @endif
                        <a href="{{ route('user.uks.patients.create', ['userId' => $userId]) }}" class="btn btn-primary">
                            <i class="ri-add-line me-1"></i>Daftarkan Pasien
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if($hasFilter)
            <div class="card-header py-2 bg-light border-bottom">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="fw-semibold"><i class="ri-filter-3-line me-1"></i>Filter Aktif:</span>
                    @if(request('search'))
                        <span class="filter-badge active">Cari: {{ request('search') }}</span>
                    @endif
                    @if(request('status'))
                        <span class="filter-badge active">Status: {{ $statusLabels[request('status')] ?? request('status') }}</span>
                    @endif
                    @if(request('active_only'))
                        <span class="filter-badge active">Hanya Aktif</span>
                    @endif
                    @if(request('month'))
                        <span class="filter-badge active">Bulan: {{ request('month') }}</span>
                    @endif
                    <a href="{{ route('user.uks.patients.index', ['userId' => $userId]) }}" class="btn btn-sm btn-link text-danger ms-auto">
                        <i class="ri-close-circle-line me-1"></i>Hapus Semua
                    </a>
                </div>
            </div>
        @endif

        <div class="card-header py-2 uks-quick-filter">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted me-1"><i class="ri-flashlight-line me-1"></i>Quick Filter:</span>
                <a class="filter-badge" href="{{ route('user.uks.patients.index', ['userId' => $userId]) }}">Semua</a>
                <a class="filter-badge" href="{{ route('user.uks.patients.index', ['userId' => $userId, 'active_only' => 1]) }}"><i class="ri-pulse-line"></i>Aktif</a>
                <a class="filter-badge" href="{{ route('user.uks.patients.index', ['userId' => $userId, 'status' => \App\Models\Uks\UksPatient::STATUS_WAITING]) }}"><i class="ri-time-line"></i>Menunggu</a>
                <a class="filter-badge" href="{{ route('user.uks.patients.index', ['userId' => $userId, 'status' => \App\Models\Uks\UksPatient::STATUS_INPATIENT]) }}"><i class="ri-hotel-bed-line"></i>Rawat UKS</a>
                <a class="filter-badge" href="{{ route('user.uks.patients.index', ['userId' => $userId, 'status' => \App\Models\Uks\UksPatient::STATUS_OBSERVATION]) }}"><i class="ri-eye-line"></i>Observasi</a>
                <a class="filter-badge" href="{{ route('user.uks.patients.index', ['userId' => $userId, 'status' => \App\Models\Uks\UksPatient::STATUS_REFERRAL_CLINIC]) }}"><i class="ri-ambulance-line"></i>Dirujuk</a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover align-middle table-freeze" id="patientsTable">
                    <thead class="table-light">
                        <tr>
                            <th data-column="pasien">Pasien</th>
                            @if ((string) setting('uks_mode', 'simple') === 'clinic')
                                <th data-column="antrean">Antrean</th>
                            @endif
                            <th data-column="triase">Triase</th>
                            <th data-column="tipe">Tipe</th>
                            <th data-column="status">Status</th>
                            <th data-column="lokasi">Lokasi</th>
                            <th data-column="tanggal">Tgl Daftar</th>
                            <th data-column="action" style="width: 100px;" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="list">
                        @forelse($patients as $patient)
                            @php
                                $status = $patient->status;
                                $statusColor = $statusColors[$status] ?? 'secondary';
                                $student = $patient->student;
                                $dormitory = $student?->activeDormitoryResident?->dormitory?->name;
                            @endphp
                            <tr>
                                <td data-column="pasien">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs flex-shrink-0">
                                            <div class="avatar-title rounded-circle bg-{{ $student?->gender === 'P' ? 'danger' : 'primary' }}-subtle text-{{ $student?->gender === 'P' ? 'danger' : 'primary' }} fw-bold">
                                                {{ strtoupper(substr($student?->name ?? '?', 0, 1)) }}
                                            </div>
                                        </div>
                                        <div class="ms-2">
                                            <div class="fw-semibold">{{ $student?->name ?? '-' }}</div>
                                            <div class="text-muted fs-11">
                                                {{ $patient->medical_record_no ?? '-' }}
                                                @if($dormitory) · {{ $dormitory }} @endif
                                            </div>
                                            @if($student?->nisn)<div class="text-muted fs-10">NISN {{ $student->nisn }}</div>@endif
                                        </div>
                                    </div>
                                </td>
                                @if ((string) setting('uks_mode', 'simple') === 'clinic')
                                    <td data-column="antrean">
                                        <span class="badge bg-dark">{{ $patient->queue_number ?? '—' }}</span>
                                        <div class="text-muted fs-10 mt-1">{{ $patient->queue_label }}</div>
                                    </td>
                                @endif
                                <td data-column="triase">
                                    <span class="badge bg-{{ $patient->triage_color }}-subtle text-{{ $patient->triage_color }}">{{ $patient->triage_level ?? '-' }}</span>
                                </td>
                                <td data-column="tipe">
                                    @if($patient->patient_type === 'rawat')
                                        <span class="badge bg-info-subtle text-info">Rawat</span>
                                    @elseif($patient->patient_type === 'balik')
                                        <span class="badge bg-warning-subtle text-warning">Balik</span>
                                    @elseif($patient->patient_type === 'pulang')
                                        <span class="badge bg-secondary-subtle text-secondary">Pulang</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td data-column="status">
                                    <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</span>
                                    @if($patient->diagnosis)
                                        <div class="text-muted fs-10 mt-1">{{ \Illuminate\Support\Str::limit($patient->diagnosis, 35) }}</div>
                                    @endif
                                </td>
                                <td data-column="lokasi">
                                    <span class="badge bg-{{ $patient->care_location_color }}-subtle text-{{ $patient->care_location_color }}">
                                        <i class="{{ $patient->care_location_icon }} me-1"></i>{{ $patient->care_location_short_label }}
                                    </span>
                                    @if($patient->currentBedAssignment?->bed)
                                        <div class="text-muted fs-10 mt-1"><i class="ri-hotel-bed-line me-1"></i>{{ $patient->currentBedAssignment->bed->bed_number }}</div>
                                    @endif
                                </td>
                                <td data-column="tanggal">
                                    <span class="fs-12 text-muted">{{ $patient->admitted_at?->format('d M Y H:i') ?? '-' }}</span>
                                </td>
                                <td data-column="action" class="text-end">
                                    <div class="dropdown uks-table-actions">
                                        <button class="btn btn-sm btn-soft-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-2-fill"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.uks.patients.show', ['userId' => $userId, 'uuid' => $patient->id]) }}">
                                                    <i class="ri-eye-line text-info me-2"></i>Detail Pasien
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.uks.clinic.workspace', ['userId' => $userId, 'uuid' => $patient->id]) }}">
                                                    <i class="ri-stethoscope-line text-primary me-2"></i>{{ $isClinicMode ? 'Lembar Pemeriksaan' : 'Pemeriksaan UKS' }}
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.uks.treatment-status.show', ['userId' => $userId, 'uuid' => $patient->id]) }}">
                                                    <i class="ri-pulse-line text-success me-2"></i>Status Perawatan
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button type="button" class="dropdown-item btn-change-status"
                                                        data-bs-toggle="modal" data-bs-target="#statusModal"
                                                        data-patient-id="{{ $patient->id }}"
                                                        data-patient-name="{{ $student?->name ?? '-' }}"
                                                        data-current-status="{{ $status }}">
                                                    <i class="ri-refresh-line text-warning me-2"></i>Ubah Status
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ (string) setting('uks_mode', 'simple') === 'clinic' ? 8 : 7 }}" class="border-0 p-0">
                                    <div class="uks-empty">
                                        <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                        <h5 class="mt-2">Belum ada data pasien</h5>
                                        <p class="text-muted mb-3">Daftarkan pasien untuk memulai pelayanan UKS.</p>
                                        <a href="{{ route('user.uks.patients.create', ['userId' => $userId]) }}" class="btn btn-primary btn-sm">
                                            <i class="ri-add-line me-1"></i>Daftarkan Pasien
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($patients->hasPages())
            <div class="card-footer">
                @include('shared._pagination', ['paginator' => $patients->withQueryString()])
            </div>
        @endif
    </div>

    {{-- FILTER MODAL --}}
    <x-uks.filter-modal id="patientFilterModal" title="Filter Data Pasien" :action="route('user.uks.patients.index', ['userId' => $userId])">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="filter-group">
                    <div class="filter-group-title"><i class="ri-search-line"></i>Cari Pasien</div>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama / NIS / NISN / No. RM / antrean">
                </div>
            </div>
            <div class="col-md-6">
                <div class="filter-group">
                    <div class="filter-group-title"><i class="ri-pulse-line"></i>Status & Kunjungan</div>
                    <div class="mb-2">
                        <label class="form-label">Status Perawatan</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            @foreach($statusLabels as $key => $label)
                                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Hanya Pasien Aktif</label>
                        <select name="active_only" class="form-select">
                            <option value="">Semua</option>
                            <option value="1" {{ request('active_only') === '1' ? 'selected' : '' }}>Aktif</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Bulan Pendaftaran</label>
                        <input type="month" name="month" class="form-control" value="{{ request('month') }}">
                    </div>
                </div>
            </div>
        </div>
    </x-uks.filter-modal>

    {{-- STATUS MODAL --}}
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="statusForm" action="#">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="statusModalLabel"><i class="ri-refresh-line me-2 text-warning"></i>Ubah Status Pasien</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">Pasien: <strong id="statusPatientName">-</strong></p>
                        <div class="mb-2">
                            <label class="form-label">Status Baru</label>
                            <select name="new_status" id="statusNewValue" class="form-select" required></select>
                        </div>
                        <div>
                            <label class="form-label">Catatan / Alasan</label>
                            <textarea name="notes" rows="2" class="form-control" placeholder="Opsional..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning"><i class="ri-save-line me-1"></i>Simpan Status</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const transitions = @json($transitionMap);
    const labels = @json($statusLabels);
    const baseUrl = @json(route('user.uks.patients.change-status', ['userId' => $userId, 'uuid' => '00000000-0000-0000-0000-000000000000']));

    document.querySelectorAll('.btn-change-status').forEach(function (button) {
        button.addEventListener('click', function () {
            const patientId = this.dataset.patientId;
            const currentStatus = this.dataset.currentStatus;
            document.getElementById('statusPatientName').textContent = this.dataset.patientName || '-';
            document.getElementById('statusForm').action = baseUrl.replace('00000000-0000-0000-0000-000000000000', patientId);

            const select = document.getElementById('statusNewValue');
            select.innerHTML = '';
            (transitions[currentStatus] || []).forEach(function (status) {
                const option = document.createElement('option');
                option.value = status;
                option.textContent = labels[status] || status;
                select.appendChild(option);
            });
            if (!select.options.length) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'Tidak ada transisi status';
                select.appendChild(option);
            }
        });
    });
});
</script>
@endpush
