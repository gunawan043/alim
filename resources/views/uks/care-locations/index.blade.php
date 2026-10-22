@extends('layouts.master')

@section('title', 'Papan Lokasi Perawatan UKS')

@section('css')
    @include('uks._ui-styles')
@endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? auth()->id();
        $hasFilter = request()->filled('care_location') || request()->filled('status') || request()->filled('search');
        $statusLabels = \App\Models\Uks\UksPatient::$statusLabels;
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') Rawat & Lokasi @endslot
        @slot('title') Papan Lokasi Perawatan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        @foreach(\App\Models\Uks\UksPatient::$careLocationLabels as $location => $label)
            @php
                $color = \App\Models\Uks\UksPatient::$careLocationColors[$location] ?? 'secondary';
                $icon = \App\Models\Uks\UksPatient::$careLocationIcons[$location] ?? 'ri-map-pin-line';
                $description = \App\Models\Uks\UksPatient::$careLocationDescriptions[$location] ?? '';
            @endphp
            <div class="col-xl-2 col-md-4 col-6">
                <a href="{{ route('user.uks.care-locations.index', ['userId' => $userId, 'care_location' => $location]) }}" class="text-decoration-none">
                    <div class="card card-animate h-100 border-{{ $color }}-subtle {{ request('care_location') === $location ? 'border-2' : '' }}">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-sm rounded bg-{{ $color }}-subtle d-flex align-items-center justify-content-center flex-shrink-0">
                                    <i class="{{ $icon }} text-{{ $color }} fs-5"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-muted text-uppercase fs-10 mb-0">{{ $label }}</p>
                                    <h4 class="mb-0 fw-bold text-{{ $color }}">{{ $stats[$location] ?? 0 }}</h4>
                                </div>
                            </div>
                            <p class="text-muted fs-11 mb-0 mt-2 text-truncate" title="{{ $description }}">{{ $description }}</p>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="alert alert-info d-flex align-items-center gap-2 py-2">
        <i class="ri-information-line fs-5"></i>
        <div class="fs-13">
            <strong>Papan ini mencatat posisi santri saat ini.</strong>
            Bed UKS otomatis mengisi lokasi <em>Dirawat di UKS/Ranjang</em>. Saat bed dikosongkan, pilih lokasi tujuan agar data tetap akurat. Untuk menempatkan pasien ke ranjang, gunakan menu <em>Bed & Ruang Rawat</em>.
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0">Daftar Santri Berdasarkan Lokasi Perawatan</h5>
                    <p class="text-muted mb-0 fs-12">Termasuk pasien aktif, rujukan, dan pasien yang keluar hari ini.</p>
                </div>
                <div class="col-sm-auto">
                    <div class="d-flex flex-wrap gap-2">
                        <form method="GET" action="{{ route('user.uks.care-locations.index', ['userId' => $userId]) }}" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari nama / NIS / No. RM" style="min-width: 220px;">
                            <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i></button>
                        </form>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#locationFilterModal">
                            <i class="bx bx-filter-alt align-bottom me-1"></i>Filter
                        </button>
                        <a href="{{ route('user.uks.beds.index', ['userId' => $userId]) }}" class="btn btn-soft-primary">
                            <i class="ri-hotel-bed-line me-1"></i>Kelola Bed
                        </a>
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
                    @if(request('care_location'))
                        <span class="filter-badge active">Lokasi: {{ \App\Models\Uks\UksPatient::$careLocationLabels[request('care_location')] ?? request('care_location') }}</span>
                    @endif
                    @if(request('status'))
                        <span class="filter-badge active">Status: {{ $statusLabels[request('status')] ?? request('status') }}</span>
                    @endif
                    @if(request('search'))
                        <span class="filter-badge active">Cari: {{ request('search') }}</span>
                    @endif
                    <a href="{{ route('user.uks.care-locations.index', ['userId' => $userId]) }}" class="btn btn-sm btn-link text-danger ms-auto">
                        <i class="ri-close-circle-line me-1"></i>Hapus Semua
                    </a>
                </div>
            </div>
        @endif

        <div class="card-header py-2 uks-quick-filter">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted me-1"><i class="ri-flashlight-line me-1"></i>Quick Filter:</span>
                <a class="filter-badge" href="{{ route('user.uks.care-locations.index', ['userId' => $userId]) }}">Semua</a>
                @foreach(\App\Models\Uks\UksPatient::$careLocationLabels as $key => $label)
                    <a class="filter-badge {{ request('care_location') === $key ? 'active' : '' }}"
                       href="{{ route('user.uks.care-locations.index', ['userId' => $userId, 'care_location' => $key]) }}">
                        <i class="{{ \App\Models\Uks\UksPatient::$careLocationIcons[$key] ?? 'ri-map-pin-line' }}"></i>{{ \App\Models\Uks\UksPatient::$careLocationShortLabels[$key] ?? $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover align-middle table-freeze" id="careLocationTable">
                    <thead class="table-light">
                        <tr>
                            <th data-column="pasien">Santri</th>
                            <th data-column="lokasi">Lokasi Perawatan</th>
                            <th data-column="status">Status Klinis</th>
                            <th data-column="bed">Bed</th>
                            <th data-column="diagnosis">Diagnosis / Keluhan</th>
                            <th data-column="sejak">Sejak</th>
                            <th data-column="action" class="text-end" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="list">
                        @forelse($patients as $index => $patient)
                            @php
                                $student = $patient->student;
                                $locationColor = $patient->care_location_color;
                                $statusColor = match ($patient->status) {
                                    \App\Models\Uks\UksPatient::STATUS_WAITING,
                                    \App\Models\Uks\UksPatient::STATUS_TREATED => 'warning',
                                    \App\Models\Uks\UksPatient::STATUS_OBSERVATION,
                                    \App\Models\Uks\UksPatient::STATUS_INPATIENT => 'primary',
                                    \App\Models\Uks\UksPatient::STATUS_RESTING_UKS => 'info',
                                    \App\Models\Uks\UksPatient::STATUS_RETURN_DORM,
                                    \App\Models\Uks\UksPatient::STATUS_RETURN_SCHOOL,
                                    \App\Models\Uks\UksPatient::STATUS_LEAVING,
                                    \App\Models\Uks\UksPatient::STATUS_COMPLETED => 'success',
                                    \App\Models\Uks\UksPatient::STATUS_PICKED_UP => 'secondary',
                                    \App\Models\Uks\UksPatient::STATUS_REFERRAL_CLINIC,
                                    \App\Models\Uks\UksPatient::STATUS_REFERRAL_HOSPITAL => 'danger',
                                    default => 'secondary',
                                };
                                $bed = $patient->currentBedAssignment?->bed;
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
                                                @if($student?->activeDormitoryResident?->dormitory?->name)
                                                    · {{ $student->activeDormitoryResident->dormitory->name }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td data-column="lokasi">
                                    <span class="badge bg-{{ $locationColor }}-subtle text-{{ $locationColor }}">
                                        <i class="{{ $patient->care_location_icon }} me-1"></i>{{ $patient->care_location_short_label }}
                                    </span>
                                    @if($patient->location_notes)
                                        <div class="text-muted fs-11 mt-1" title="{{ $patient->location_notes }}">{{ \Illuminate\Support\Str::limit($patient->location_notes, 40) }}</div>
                                    @endif
                                </td>
                                <td data-column="status">
                                    <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">{{ $statusLabels[$patient->status] ?? $patient->status }}</span>
                                </td>
                                <td data-column="bed">
                                    @if($bed)
                                        <span class="badge bg-danger-subtle text-danger">{{ $bed->bed_number }}</span>
                                        <div class="text-muted fs-11">{{ trim(($bed->building ?? '').' '.($bed->room ?? '')) ?: 'UKS' }}</div>
                                    @elseif($patient->care_location === \App\Models\Uks\UksPatient::LOCATION_UKS)
                                        <span class="badge bg-warning-subtle text-warning">Belum ada bed</span>
                                    @else
                                        <span class="text-muted fs-12">—</span>
                                    @endif
                                </td>
                                <td data-column="diagnosis" class="fs-13">{{ \Illuminate\Support\Str::limit($patient->diagnosis ?? $patient->chief_complaint ?? '-', 70) }}</td>
                                <td data-column="sejak" class="fs-12 text-muted">
                                    {{ $patient->location_updated_at?->format('d M Y H:i') ?? '-' }}
                                    @if($patient->locationUpdatedBy)
                                        <div class="fs-11">oleh {{ $patient->locationUpdatedBy->name }}</div>
                                    @endif
                                </td>
                                <td data-column="action" class="text-end">
                                    <div class="dropdown uks-table-actions">
                                        <button class="btn btn-sm btn-soft-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-2-fill"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.uks.clinic.workspace', ['userId' => $userId, 'uuid' => $patient->id]) }}">
                                                    <i class="ri-stethoscope-line text-primary me-2"></i>Lembar Pemeriksaan
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.uks.patients.show', ['userId' => $userId, 'uuid' => $patient->id]) }}">
                                                    <i class="ri-eye-line text-info me-2"></i>Detail Pasien
                                                </a>
                                            </li>
                                            @if($patient->care_location === \App\Models\Uks\UksPatient::LOCATION_UKS && ! $bed)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('user.uks.treatment-status.show', ['userId' => $userId, 'uuid' => $patient->id]) }}">
                                                        <i class="ri-hotel-bed-line text-danger me-2"></i>Tempatkan Bed
                                                    </a>
                                                </li>
                                            @endif
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button type="button" class="dropdown-item btn-change-location"
                                                        data-bs-toggle="modal" data-bs-target="#locationModal"
                                                        data-patient-id="{{ $patient->id }}"
                                                        data-patient-name="{{ $student?->name ?? '-' }}"
                                                        data-current-location="{{ $patient->care_location }}">
                                                    <i class="ri-map-pin-line text-warning me-2"></i>Ubah Lokasi
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="border-0 p-0">
                                    <div class="uks-empty">
                                        <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                        <h5 class="mt-2">Belum ada data lokasi perawatan</h5>
                                        <p class="text-muted mb-0">Data akan muncul setelah pasien didaftarkan atau dipindahkan ke bed.</p>
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
    <x-uks.filter-modal id="locationFilterModal" title="Filter Lokasi Perawatan" :action="route('user.uks.care-locations.index', ['userId' => $userId])">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="filter-group">
                    <div class="filter-group-title"><i class="ri-map-pin-line"></i>Lokasi</div>
                    <select name="care_location" class="form-select">
                        <option value="">Semua Lokasi</option>
                        @foreach(\App\Models\Uks\UksPatient::$careLocationLabels as $key => $label)
                            <option value="{{ $key }}" {{ request('care_location') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="filter-group">
                    <div class="filter-group-title"><i class="ri-pulse-line"></i>Status & Pencarian</div>
                    <div class="mb-2">
                        <label class="form-label">Status Klinis</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            @foreach($statusLabels as $key => $label)
                                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Cari Santri</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama / NIS / No. RM">
                    </div>
                </div>
            </div>
        </div>
    </x-uks.filter-modal>

    {{-- LOCATION MODAL --}}
    <div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="locationForm" action="#">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="locationModalLabel"><i class="ri-map-pin-line me-2 text-warning"></i>Ubah Lokasi Perawatan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">Santri: <strong id="locationPatientName">-</strong></p>
                        <div class="mb-2">
                            <label class="form-label">Lokasi Baru</label>
                            <select name="care_location" id="locationNewValue" class="form-select" required>
                                @foreach(\App\Models\Uks\UksPatient::$careLocationLabels as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" rows="2" class="form-control" placeholder="cth: Dipindahkan ke asrama untuk istirahat"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning"><i class="ri-save-line me-1"></i>Simpan Lokasi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = @json(route('user.uks.care-locations.update', ['userId' => $userId, 'uuid' => '00000000-0000-0000-0000-000000000000']));

    document.querySelectorAll('.btn-change-location').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('locationPatientName').textContent = this.dataset.patientName || '-';
            document.getElementById('locationNewValue').value = this.dataset.currentLocation || '';
            document.getElementById('locationForm').action = baseUrl.replace('00000000-0000-0000-0000-000000000000', this.dataset.patientId);
        });
    });
});
</script>
@endpush
