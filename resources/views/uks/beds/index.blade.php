@extends('layouts.master')

@section('title', 'Bed & Ruang Rawat UKS')

@section('css')
    @include('uks._ui-styles')
@endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? auth()->id();
        $hasFilter = request()->filled('gender') || request()->filled('status') || request()->filled('search');
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') Rawat Inap @endslot
        @slot('title') Bed & Ruang Rawat @endslot
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
        @foreach([
            ['label' => 'Total Bed', 'value' => $totalBeds, 'icon' => 'ri-hotel-bed-line', 'color' => 'primary'],
            ['label' => 'Terisi', 'value' => $occupiedBeds, 'icon' => 'ri-user-heart-line', 'color' => 'danger'],
            ['label' => 'Tersedia', 'value' => $availableBeds, 'icon' => 'ri-checkbox-circle-line', 'color' => 'success'],
            ['label' => 'Perbaikan', 'value' => $underRepair, 'icon' => 'ri-tools-line', 'color' => 'warning'],
        ] as $card)
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-sm rounded bg-{{ $card['color'] }}-subtle d-flex align-items-center justify-content-center">
                                <i class="{{ $card['icon'] }} text-{{ $card['color'] }} fs-5"></i>
                            </span>
                            <div>
                                <p class="text-muted text-uppercase fs-11 mb-1">{{ $card['label'] }}</p>
                                <h4 class="mb-0 fw-bold">{{ $card['value'] }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0">Okupansi Bed UKS</h5>
                    <p class="text-muted mb-0 fs-12">Pantau siapa yang menempati setiap ranjang dan lokasi perawatannya.</p>
                </div>
                <div class="col-sm-auto">
                    <div class="d-flex flex-wrap gap-2">
                        <form method="GET" action="{{ route('user.uks.beds.index', ['userId' => $userId]) }}" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari nomor / gedung / ruang" style="min-width: 220px;">
                            <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i></button>
                        </form>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#bedFilterModal">
                            <i class="bx bx-filter-alt align-bottom me-1"></i>Filter
                        </button>
                        <a href="{{ route('user.uks.care-locations.index', ['userId' => $userId]) }}" class="btn btn-soft-info">
                            <i class="ri-map-pin-line me-1"></i>Papan Lokasi
                        </a>
                        <button type="button" class="btn btn-primary btn-add-bed">
                            <i class="ri-add-line me-1"></i>Tambah Bed
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if($hasFilter)
            <div class="card-header py-2 bg-light border-bottom">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="fw-semibold"><i class="ri-filter-3-line me-1"></i>Filter Aktif:</span>
                    @if(request('gender'))<span class="filter-badge active">Gender: {{ request('gender') === 'L' ? 'Putra' : 'Putri' }}</span>@endif
                    @if(request('status'))<span class="filter-badge active">Status: {{ ucfirst(request('status')) }}</span>@endif
                    @if(request('search'))<span class="filter-badge active">Cari: {{ request('search') }}</span>@endif
                    <a href="{{ route('user.uks.beds.index', ['userId' => $userId]) }}" class="btn btn-sm btn-link text-danger ms-auto">
                        <i class="ri-close-circle-line me-1"></i>Hapus Semua
                    </a>
                </div>
            </div>
        @endif

        <div class="card-header py-2 uks-quick-filter">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted me-1"><i class="ri-flashlight-line me-1"></i>Quick Filter:</span>
                <a class="filter-badge {{ ! request('status') && ! request('gender') ? 'active' : '' }}" href="{{ route('user.uks.beds.index', ['userId' => $userId]) }}">Semua</a>
                <a class="filter-badge {{ request('status') === 'tersedia' ? 'active' : '' }}" href="{{ route('user.uks.beds.index', ['userId' => $userId, 'status' => 'tersedia']) }}"><i class="ri-checkbox-circle-line"></i>Tersedia</a>
                <a class="filter-badge {{ request('status') === 'dipakai' ? 'active' : '' }}" href="{{ route('user.uks.beds.index', ['userId' => $userId, 'status' => 'dipakai']) }}"><i class="ri-user-heart-line"></i>Terisi</a>
                <a class="filter-badge {{ request('status') === 'perbaikan' ? 'active' : '' }}" href="{{ route('user.uks.beds.index', ['userId' => $userId, 'status' => 'perbaikan']) }}"><i class="ri-tools-line"></i>Perbaikan</a>
                <a class="filter-badge {{ request('gender') === 'L' ? 'active' : '' }}" href="{{ route('user.uks.beds.index', ['userId' => $userId, 'gender' => 'L']) }}"><i class="ri-men-line"></i>Putra</a>
                <a class="filter-badge {{ request('gender') === 'P' ? 'active' : '' }}" href="{{ route('user.uks.beds.index', ['userId' => $userId, 'gender' => 'P']) }}"><i class="ri-women-line"></i>Putri</a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover align-middle table-freeze" id="bedsTable">
                    <thead class="table-light">
                        <tr>
                            <th data-column="bed">Bed</th>
                            <th data-column="ruang">Gedung / Ruang</th>
                            <th data-column="gender">Gender</th>
                            <th data-column="status">Status</th>
                            <th data-column="pasien">Pasien</th>
                            <th data-column="lokasi">Lokasi</th>
                            <th data-column="masuk">Masuk</th>
                            <th data-column="action" class="text-end" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groupedByDorm as $dormName => $beds)
                            <tr class="table-light">
                                <td colspan="8" class="fw-semibold text-muted">
                                    <i class="ri-building-line me-1"></i>{{ $dormName }}
                                    <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $beds->count() }} bed</span>
                                </td>
                            </tr>
                            @foreach($beds as $bed)
                                @php
                                    $assignment = $bed->currentAssignment;
                                    $patient = $assignment?->patient;
                                    $student = $patient?->student;
                                    $statusColor = match ($bed->status) {
                                        'tersedia' => 'success',
                                        'dipakai' => 'danger',
                                        'perbaikan' => 'warning',
                                        default => 'secondary',
                                    };
                                @endphp
                                <tr>
                                    <td data-column="bed">
                                        <span class="fw-bold">{{ $bed->bed_number }}</span>
                                        @if($bed->section)<div class="text-muted fs-11">{{ $bed->section }}</div>@endif
                                    </td>
                                    <td data-column="ruang">
                                        {{ $bed->building ?: $bed->building_or_room }}
                                        @if($bed->room)<div class="text-muted fs-11">{{ $bed->room }}</div>@endif
                                    </td>
                                    <td data-column="gender">{{ $bed->gender === 'L' ? 'Putra' : 'Putri' }}</td>
                                    <td data-column="status"><span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">{{ ucfirst($bed->status) }}</span></td>
                                    <td data-column="pasien">
                                        @if($assignment)
                                            <div class="fw-semibold">{{ $student?->name ?? '-' }}</div>
                                            <div class="text-muted fs-11">{{ $patient?->medical_record_no ?? '-' }} · {{ $student?->gender === 'P' ? 'Putri' : 'Putra' }}</div>
                                            <div class="text-muted fs-11">{{ \Illuminate\Support\Str::limit($patient?->diagnosis ?? $patient?->chief_complaint ?? '-', 40) }}</div>
                                        @else
                                            <span class="text-muted">Bed kosong</span>
                                        @endif
                                    </td>
                                    <td data-column="lokasi">
                                        @if($patient)
                                            <span class="badge bg-{{ $patient->care_location_color }}-subtle text-{{ $patient->care_location_color }}">
                                                <i class="{{ $patient->care_location_icon }} me-1"></i>{{ $patient->care_location_short_label }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td data-column="masuk" class="fs-12 text-muted">{{ $assignment?->assigned_at?->format('d M Y H:i') ?? '-' }}</td>
                                    <td data-column="action" class="text-end">
                                        <div class="dropdown uks-table-actions">
                                            <button class="btn btn-sm btn-soft-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ri-more-2-fill"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('user.uks.beds.show', ['userId' => $userId, 'uuid' => $bed->id]) }}">
                                                        <i class="ri-eye-line text-info me-2"></i>Detail Bed
                                                    </a>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item btn-edit-bed"
                                                            data-bs-toggle="modal" data-bs-target="#bedFormModal"
                                                            data-bed="{{ $bed->toJson() }}"
                                                            data-update-url="{{ route('user.uks.beds.update', ['userId' => $userId, 'uuid' => $bed->id]) }}">
                                                        <i class="ri-edit-line text-primary me-2"></i>Edit Bed
                                                    </button>
                                                </li>
                                                @if($assignment)
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.uks.clinic.workspace', ['userId' => $userId, 'uuid' => $patient->id]) }}">
                                                            <i class="ri-stethoscope-line text-success me-2"></i>Lembar Pemeriksaan
                                                        </a>
                                                    </li>
                                                @else
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" class="dropdown-item text-danger btn-delete-bed"
                                                                data-bs-toggle="modal" data-bs-target="#deleteBedModal"
                                                                data-bed-name="{{ $bed->building ?: $bed->building_or_room }} / {{ $bed->bed_number }}"
                                                                data-delete-url="{{ route('user.uks.beds.destroy', ['userId' => $userId, 'uuid' => $bed->id]) }}">
                                                            <i class="ri-delete-bin-line text-danger me-2"></i>Hapus Bed
                                                        </button>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="8" class="border-0 p-0">
                                    <div class="uks-empty">
                                        <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                        <h5 class="mt-2">Belum ada bed</h5>
                                        <p class="text-muted mb-3">Tambahkan bed UKS untuk mulai mencatat okupansi.</p>
                                        <button type="button" class="btn btn-primary btn-sm btn-add-bed"><i class="ri-add-line me-1"></i>Tambah Bed</button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-uks.filter-modal id="bedFilterModal" title="Filter Bed & Ruang Rawat" :action="route('user.uks.beds.index', ['userId' => $userId])">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="filter-group">
                    <div class="filter-group-title"><i class="ri-hotel-bed-line"></i>Status Bed</div>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="tersedia" {{ request('status') === 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                        <option value="dipakai" {{ request('status') === 'dipakai' ? 'selected' : '' }}>Dipakai</option>
                        <option value="perbaikan" {{ request('status') === 'perbaikan' ? 'selected' : '' }}>Perbaikan</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="filter-group">
                    <div class="filter-group-title"><i class="ri-user-line"></i>Gender</div>
                    <select name="gender" class="form-select">
                        <option value="">Semua</option>
                        <option value="L" {{ request('gender') === 'L' ? 'selected' : '' }}>Putra</option>
                        <option value="P" {{ request('gender') === 'P' ? 'selected' : '' }}>Putri</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="filter-group">
                    <div class="filter-group-title"><i class="ri-search-line"></i>Pencarian</div>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nomor / gedung / ruang">
                </div>
            </div>
        </div>
    </x-uks.filter-modal>

    {{-- BED FORM MODAL --}}
    <div class="modal fade" id="bedFormModal" tabindex="-1" aria-labelledby="bedFormModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" id="bedForm" action="{{ route('user.uks.beds.store', ['userId' => $userId]) }}">
                @csrf
                <input type="hidden" name="_method" id="bedMethod" value="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bedFormModalLabel"><i class="ri-hotel-bed-line me-2 text-primary"></i>Tambah Bed UKS</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Asrama / Unit</label>
                                <select name="dormitory_id" id="bedDormitory" class="form-select">
                                    <option value="">— Tanpa Asrama —</option>
                                    @foreach($dormitories as $dormitory)
                                        <option value="{{ $dormitory->id }}">{{ $dormitory->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <select name="gender" id="bedGender" class="form-select" required>
                                    <option value="L">Putra</option>
                                    <option value="P">Putri</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" id="bedStatus" class="form-select" required>
                                    <option value="tersedia">Tersedia</option>
                                    <option value="dipakai">Dipakai</option>
                                    <option value="perbaikan">Perbaikan</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gedung / Label Ruang <span class="text-danger">*</span></label>
                                <input type="text" name="building_or_room" id="bedBuildingOrRoom" class="form-control" required placeholder="cth: UKS Putra / Ruang A">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gedung</label>
                                <input type="text" name="building" id="bedBuilding" class="form-control" placeholder="cth: UKS Putra">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ruangan</label>
                                <input type="text" name="room" id="bedRoom" class="form-control" placeholder="cth: Ruang A">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Bagian / Section</label>
                                <input type="text" name="section" id="bedSection" class="form-control" placeholder="cth: Isolasi">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nomor Bed <span class="text-danger">*</span></label>
                                <input type="text" name="bed_number" id="bedNumber" class="form-control" required placeholder="cth: A-01">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Urutan Tampil</label>
                                <input type="number" name="sort_order" id="bedSortOrder" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" id="bedNotes" rows="2" class="form-control" placeholder="Catatan kondisi bed..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Bed</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- DELETE MODAL --}}
    <div class="modal fade" id="deleteBedModal" tabindex="-1" aria-labelledby="deleteBedModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="deleteBedForm" action="#">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-body text-center p-5">
                        <lord-icon src="https://cdn.lordicon.com/lupuorrc.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:110px;height:110px"></lord-icon>
                        <h4 class="mt-3">Hapus Bed?</h4>
                        <p class="text-muted">Bed <strong id="deleteBedName">-</strong> akan dihapus permanen.</p>
                        <div class="hstack gap-2 justify-content-center">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger"><i class="ri-delete-bin-line me-1"></i>Hapus</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('bedForm');
    const method = document.getElementById('bedMethod');
    const title = document.getElementById('bedFormModalLabel');
    const storeUrl = @json(route('user.uks.beds.store', ['userId' => $userId]));

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
        });
    }

    function resetForm() {
        form.action = storeUrl;
        method.value = 'POST';
        title.innerHTML = '<i class="ri-hotel-bed-line me-2 text-primary"></i>Tambah Bed UKS';
        form.reset();
        document.getElementById('bedSortOrder').value = 0;
    }

    document.querySelectorAll('.btn-add-bed').forEach(function (button) {
        button.addEventListener('click', function () {
            resetForm();
            new bootstrap.Modal(document.getElementById('bedFormModal')).show();
        });
    });

    document.querySelectorAll('.btn-edit-bed').forEach(function (button) {
        button.addEventListener('click', function () {
            const bed = JSON.parse(this.dataset.bed || '{}');
            form.action = this.dataset.updateUrl;
            method.value = 'PUT';
            title.innerHTML = '<i class="ri-edit-line me-2 text-primary"></i>Edit Bed ' + escapeHtml(bed.bed_number || '');

            document.getElementById('bedDormitory').value = bed.dormitory_id || '';
            document.getElementById('bedGender').value = bed.gender || 'L';
            document.getElementById('bedStatus').value = bed.status || 'tersedia';
            document.getElementById('bedBuildingOrRoom').value = bed.building_or_room || '';
            document.getElementById('bedBuilding').value = bed.building || '';
            document.getElementById('bedRoom').value = bed.room || '';
            document.getElementById('bedSection').value = bed.section || '';
            document.getElementById('bedNumber').value = bed.bed_number || '';
            document.getElementById('bedSortOrder').value = bed.sort_order || 0;
            document.getElementById('bedNotes').value = bed.notes || '';
        });
    });

    document.querySelectorAll('.btn-delete-bed').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('deleteBedName').textContent = this.dataset.bedName || '-';
            document.getElementById('deleteBedForm').action = this.dataset.deleteUrl;
        });
    });
});
</script>
@endpush
