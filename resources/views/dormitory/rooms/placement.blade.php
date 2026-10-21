@extends('layouts.master')
@section('title', 'Penempatan Kamar — '.$dormitory->name)

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.asrama.rooms.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Kamar</a> @endslot
        @slot('title') Penempatan Kamar — {{ $dormitory->name }} · {{ $dormitory->school?->name }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $totalCapacity = $rooms->sum(fn ($room) => (int) ($room->capacity ?? 0));
        $totalOccupied = $rooms->sum(fn ($room) => $room->residents->count());
        $availableBeds = max(0, $totalCapacity - $totalOccupied);
        $occupancyRate = $totalCapacity > 0 ? round(($totalOccupied / $totalCapacity) * 100) : 0;
        $wings = $rooms->pluck('wing')->filter()->unique('id')->sortBy('name');
    @endphp

    {{-- Statistik ringkas penempatan (pola GTK) --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded">
                                <i class="ri-user-search-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Belum Berkamar</p>
                            <h3 class="stat-value" id="belum-berkamar-count">{{ number_format($belumBerkamar) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Santri siap ditempatkan</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded">
                                <i class="ri-hotel-bed-line text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Total Kamar</p>
                            <h3 class="stat-value">{{ number_format($rooms->count()) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-door-open-line me-1"></i>{{ number_format($totalCapacity) }} kapasitas ranjang</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <i class="ri-team-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Penghuni</p>
                            <h3 class="stat-value">{{ number_format($totalOccupied) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-checkbox-circle-line me-1"></i>{{ number_format($availableBeds) }} ranjang tersedia</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded">
                                <i class="ri-pie-chart-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Okupansi</p>
                            <h3 class="stat-value">{{ $occupancyRate }}%</h3>
                        </div>
                    </div>
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar {{ $occupancyRate >= 90 ? 'bg-danger' : ($occupancyRate >= 70 ? 'bg-warning' : 'bg-success') }}"
                             style="width:{{ min(100, $occupancyRate) }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0"><i class="ri-layout-grid-line me-1 text-primary"></i>Daftar Kamar &amp; Penghuni</h5>
                    <p class="text-muted mb-0 small">{{ $dormitory->name }} · {{ $dormitory->school?->name }}</p>
                </div>
                <div class="col-sm-auto">
                    <div class="toolbar-actions">
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#pullModal">
                            <i class="ri-download-cloud-2-line me-1"></i> Tarik Santri
                        </button>
                        <button type="button" class="btn btn-soft-primary btn-sm" data-bs-toggle="modal" data-bs-target="#boardingModal">
                            <i class="ri-user-settings-line me-1"></i> Status Boarding
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex gap-2 flex-grow-1" style="max-width:420px;">
                    <input type="search" id="placement-search" class="form-control form-control-sm"
                           placeholder="Cari kamar, gedung, atau nama santri...">
                    <button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPlacementFilters()">
                        <i class="ri-search-line"></i> Cari
                    </button>
                </div>
                <div class="toolbar-actions">
                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#placementFilterModal">
                        <i class="ri-filter-3-line me-1"></i> Filter Lanjutan
                        <span id="placementFilterCount" class="badge bg-light text-dark ms-1" style="display:none;">0</span>
                    </button>
                    <button type="button" class="btn btn-light btn-sm" onclick="resetPlacementFilters()" title="Reset filter">
                        <i class="ri-refresh-line"></i>
                    </button>
                </div>
            </div>

            <div id="placementActiveFilters" class="mb-3"></div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <small class="text-muted">
                    <i class="ri-file-list-line me-1"></i> Menampilkan
                    <strong id="placementVisibleCount">{{ $rooms->count() }}</strong> dari
                    <strong>{{ $rooms->count() }}</strong> kamar
                </small>
            </div>

            <div class="table-container">
                <table class="table table-hover align-middle table-freeze mb-0" id="placementTable">
                    <thead>
                        <tr>
                            <th>Kamar</th>
                            <th>Gedung</th>
                            <th class="text-center">Kapasitas</th>
                            <th class="text-center">Terisi</th>
                            <th class="text-center">Sisa</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="width:220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rooms as $room)
                            @php
                                $aktif = $room->residents->count();
                                $kapasitas = (int) ($room->capacity ?? 0);
                                $sisa = max(0, $kapasitas - $aktif);
                                $isEmpty = $sisa <= 0;
                                $searchText = strtolower(($room->name ?: $room->code).' '.$room->code.' '.($room->wing?->name ?? '').' '.$room->residents->pluck('student.name')->filter()->implode(' '));
                            @endphp
                            <tr class="placement-room-row"
                                data-search-text="{{ $searchText }}"
                                data-status="{{ $isEmpty ? 'full' : 'available' }}"
                                data-wing="{{ $room->wing?->id ?? '' }}"
                                data-room-id="{{ $room->id }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar-xs rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center">
                                            <i class="ri-door-open-line text-primary"></i>
                                        </span>
                                        <div>
                                            <span class="fw-semibold d-block">{{ $room->name ?: $room->code }}</span>
                                            <small class="text-muted">{{ $room->code }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $room->wing?->name ?? '—' }}</td>
                                <td class="text-center">{{ $kapasitas }}</td>
                                <td class="text-center">{{ $aktif }}</td>
                                <td class="text-center">{{ $sisa }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $isEmpty ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}">
                                        {{ $isEmpty ? 'Penuh' : $sisa.' kosong' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-toggle-residents"
                                                data-target="#residents-{{ $room->id }}"
                                                aria-expanded="false"
                                                title="Lihat penghuni">
                                            <i class="ri-eye-line"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-soft-primary btn-tempatkan"
                                                data-room="{{ $room->id }}"
                                                data-room-name="{{ $room->name ?: $room->code }}"
                                                data-sisa="{{ $sisa }}"
                                                {{ $isEmpty ? 'disabled' : '' }}>
                                            <i class="ri-user-add-line me-1"></i>Tempatkan
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="placement-residents-row" id="residents-{{ $room->id }}" style="display:none;">
                                <td colspan="7" class="bg-light-subtle">
                                    <div class="px-2 py-2">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                            <strong class="small"><i class="ri-team-line me-1"></i>Penghuni {{ $room->name ?: $room->code }}</strong>
                                            <span class="text-muted small">{{ $aktif }} / {{ $kapasitas }} ranjang terisi</span>
                                        </div>
                                        @if($aktif > 0)
                                            <div class="row g-2">
                                                @foreach($room->residents as $resident)
                                                    <div class="col-md-6">
                                                        <div class="d-flex justify-content-between align-items-center border rounded px-2 py-1">
                                                            <span class="small">
                                                                <i class="ri-user-line me-1 text-muted"></i>
                                                                <strong>{{ $resident->student?->name ?? '—' }}</strong>
                                                                <span class="text-muted">· ranjang #{{ $resident->bed_number }}</span>
                                                            </span>
                                                            <span class="d-flex gap-1">
                                                                <button type="button" class="btn btn-sm btn-soft-danger btn-keluar"
                                                                        data-resident="{{ $resident->id }}" data-room="{{ $room->id }}"
                                                                        title="Keluarkan dari kamar"><i class="ri-logout-box-r-line"></i></button>
                                                                <button type="button" class="btn btn-sm btn-soft-warning btn-berhenti"
                                                                        data-resident="{{ $resident->id }}" data-nama="{{ $resident->student?->name }}"
                                                                        title="Berhenti mondok (tetap sekolah)"><i class="ri-user-unfollow-line"></i></button>
                                                                <button type="button" class="btn btn-sm btn-soft-primary btn-pindah"
                                                                        data-resident="{{ $resident->id }}" data-nama="{{ $resident->student?->name }}"
                                                                        title="Ajukan pindah kamar (butuh persetujuan)"><i class="ri-arrow-left-right-line"></i></button>
                                                            </span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-muted small mb-0">Belum ada penghuni.</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    Belum ada kamar. <a href="{{ route('user.asrama.rooms.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Tambah kamar</a> terlebih dahulu.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal filter lanjutan --}}
    <div class="modal fade" id="placementFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-filter-3-line me-2 text-primary"></i>Filter Kamar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="filter-group mb-0">
                                <div class="filter-group-title"><i class="ri-door-open-line"></i>Status Kamar</div>
                                <select id="placementFilterStatus" class="form-select">
                                    <option value="">Semua status</option>
                                    <option value="available">Tersedia</option>
                                    <option value="full">Penuh</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="filter-group mb-0">
                                <div class="filter-group-title"><i class="ri-building-line"></i>Gedung</div>
                                <select id="placementFilterWing" class="form-select">
                                    <option value="">Semua gedung</option>
                                    @foreach($wings as $wing)
                                        <option value="{{ $wing->id }}">{{ $wing->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" onclick="resetPlacementFilters()"><i class="ri-refresh-line me-1"></i>Reset</button>
                    <button type="button" class="btn btn-primary" onclick="applyPlacementFilters()"><i class="ri-filter-3-line me-1"></i>Terapkan Filter</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Tarik Data Santri --}}
    <div class="modal fade" id="pullModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success-subtle">
                    <h5 class="modal-title"><i class="ri-download-cloud-2-line me-1"></i>Tarik Data Santri Belum Berkamar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-6">
                            <label class="form-label small">Pilih Kamar Tujuan</label>
                            <select id="pull-room" class="form-select">
                                <option value="">— pilih kamar —</option>
                                @foreach($rooms as $room)
                                    @php $sisa = max(0, (int)($room->capacity ?? 0) - $room->residents->count()); @endphp
                                    @if($sisa > 0)
                                        <option value="{{ $room->id }}" data-sisa="{{ $sisa }}">{{ $room->name ?: $room->code }} (sisa {{ $sisa }})</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Cari</label>
                            <input type="search" id="pull-search" class="form-control" placeholder="Nama / NISN…">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-soft-primary w-100" id="pull-load"><i class="ri-refresh-line"></i></button>
                        </div>
                    </div>

                    <div id="pull-list" class="border rounded" style="max-height:340px;overflow:auto;">
                        <div class="text-center text-muted py-4 small">Klik tombol ⟳ untuk menarik data santri.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <span class="text-muted small me-auto" id="pull-count">0 dipilih</span>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="pull-submit" disabled>
                        <i class="ri-user-add-line me-1"></i>Tempatkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Kelola Status Boarding --}}
    <div class="modal fade" id="boardingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary-subtle">
                    <h5 class="modal-title"><i class="ri-user-settings-line me-1"></i>Kelola Status Boarding — {{ $dormitory->school?->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex gap-2 mb-2">
                        <input type="search" id="bs-search" class="form-control" placeholder="Cari nama / NISN…">
                        <button type="button" class="btn btn-soft-primary" id="bs-load"><i class="ri-refresh-line"></i></button>
                    </div>
                    <div id="bs-list" class="border rounded" style="max-height:340px;overflow:auto;">
                        <div class="text-center text-muted py-4 small">Memuat data…</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <span class="text-muted small me-auto" id="bs-count">0 dipilih</span>
                    <button type="button" class="btn btn-soft-success" id="bs-set-boarding" disabled><i class="ri-home-heart-line me-1"></i>Tandai Boarding</button>
                    <button type="button" class="btn btn-soft-secondary" id="bs-set-non" disabled><i class="ri-home-line me-1"></i>Tandai Non-Boarding</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const statusUrl = "{{ route('user.asrama.api.boarding-status', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}";
        const updateUrl = "{{ route('user.asrama.api.boarding-status.update', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}";
        const checkoutTemplate = "{{ route('user.asrama.api.residents.checkout', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'residentId' => '__R__']) }}";
        const csrf = "{{ csrf_token() }}";

        const bsList = document.getElementById('bs-list');
        const bsCount = document.getElementById('bs-count');
        const btnBoarding = document.getElementById('bs-set-boarding');
        const btnNon = document.getElementById('bs-set-non');

        function selectedIds() {
            return Array.from(bsList.querySelectorAll('input[type=checkbox]:checked')).map(c => c.value);
        }
        function refreshBs() {
            const n = selectedIds().length;
            bsCount.textContent = n + ' dipilih';
            btnBoarding.disabled = n === 0;
            btnNon.disabled = n === 0;
        }

        function loadBs() {
            const q = document.getElementById('bs-search').value.trim();
            bsList.innerHTML = '<div class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat…</div>';

            fetch(statusUrl + (q ? '?q=' + encodeURIComponent(q) : ''), { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(function (data) {
                    if (!data.success || !data.students.length) {
                        bsList.innerHTML = '<div class="text-center text-muted py-4 small">Tidak ada santri.</div>';
                        refreshBs();
                        return;
                    }
                    bsList.innerHTML = data.students.map(function (s) {
                        const badge = s.boarding_status === 'boarding'
                            ? '<span class="badge bg-success-subtle text-success">Boarding</span>'
                            : (s.boarding_status === 'non_boarding'
                                ? '<span class="badge bg-secondary-subtle text-secondary">Non-Boarding</span>'
                                : '<span class="badge bg-warning-subtle text-warning">Belum ditentukan</span>');
                        const room = s.has_room ? '<span class="badge bg-info-subtle text-info ms-1">Berkamar</span>' : '';
                        return '<label class="d-flex align-items-center gap-2 border-bottom px-3 py-2" style="cursor:pointer">' +
                            '<input type="checkbox" class="form-check-input" value="' + s.id + '">' +
                            '<span><span class="fw-semibold">' + s.name + '</span>' +
                            '<span class="text-muted small"> · NISN ' + (s.nisn || '-') + '</span> ' + badge + room + '</span></label>';
                    }).join('');
                    bsList.querySelectorAll('input[type=checkbox]').forEach(c => c.addEventListener('change', refreshBs));
                    refreshBs();
                });
        }

        function setStatus(status) {
            const ids = selectedIds();
            if (!ids.length) return;

            fetch(updateUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ student_ids: ids, status: status })
            })
            .then(r => r.json())
            .then(function (data) {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Tersimpan', text: data.message, timer: 1500, showConfirmButton: false })
                        .then(() => window.location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Terjadi kesalahan.' });
                }
            });
        }

        document.getElementById('bs-load').addEventListener('click', loadBs);
        document.getElementById('bs-search').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); loadBs(); } });
        btnBoarding.addEventListener('click', () => setStatus('boarding'));
        btnNon.addEventListener('click', () => setStatus('non_boarding'));
        document.getElementById('boardingModal').addEventListener('shown.bs.modal', loadBs);

        // Berhenti mondok
        document.querySelectorAll('.btn-berhenti').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = this.dataset.resident;
                const nama = this.dataset.nama || 'santri';

                Swal.fire({
                    icon: 'warning',
                    title: 'Berhenti Mondok?',
                    input: 'text',
                    inputLabel: 'Alasan (opsional)',
                    inputPlaceholder: 'mis. pindah domisili / permintaan orang tua',
                    text: nama + ' akan dikeluarkan dari asrama TETAPI tetap terdaftar di sekolah.',
                    showCancelButton: true,
                    confirmButtonText: 'Berhenti Mondok',
                    cancelButtonText: 'Batal',
                }).then(function (res) {
                    if (!res.isConfirmed) return;
                    fetch(checkoutTemplate.replace('__R__', id), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ reason: res.value || 'Berhenti mondok' })
                    })
                    .then(r => r.json())
                    .then(function (data) {
                        if (data.success) { window.location.reload(); }
                        else { Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Terjadi kesalahan.' }); }
                    });
                });
            });
        });

        // ── Mutasi kamar: ajukan, daftar pending, setujui/tolak ──
        const moveTemplate = "{{ route('user.asrama.api.residents.request-move', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'residentId' => '__R__']) }}";
        const pendingUrl = "{{ route('user.asrama.api.room-moves.pending', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}";
        const approveTemplate = "{{ route('user.asrama.api.room-moves.approve', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'moveId' => '__M__']) }}";
        const rejectTemplate = "{{ route('user.asrama.api.room-moves.reject', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'moveId' => '__M__']) }}";
        let moveResident = null;

        document.querySelectorAll('.btn-pindah').forEach(function (btn) {
            btn.addEventListener('click', function () {
                moveResident = this.dataset.resident;
                document.getElementById('move-nama').textContent = this.dataset.nama || 'santri';
                document.getElementById('move-reason').value = '';
                new bootstrap.Modal(document.getElementById('moveModal')).show();
            });
        });

        document.getElementById('move-submit').addEventListener('click', function () {
            const room = document.getElementById('move-room').value;
            if (!room) { Swal.fire({ icon: 'info', title: 'Pilih kamar tujuan', text: 'Silakan pilih kamar tujuan.' }); return; }

            fetch(moveTemplate.replace('__R__', moveResident), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ to_room_id: room, reason: document.getElementById('move-reason').value })
            })
            .then(r => r.json())
            .then(function (data) {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Diajukan', text: data.message, timer: 1500, showConfirmButton: false })
                        .then(() => window.location.reload());
                } else { Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Terjadi kesalahan.' }); }
            });
        });

        function loadPendingMoves() {
            fetch(pendingUrl, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(function (data) {
                    const card = document.getElementById('pending-moves-card');
                    const box = document.getElementById('pending-moves');

                    if (!data.success || !data.moves.length) { card.style.display = 'none'; return; }
                    card.style.display = '';

                    box.innerHTML = data.moves.map(function (m) {
                        return '<div class="d-flex justify-content-between align-items-center border-bottom py-2">' +
                            '<div><div class="fw-semibold">' + m.student + '</div>' +
                            '<div class="small text-muted">' + m.from_room + ' → ' + m.to_room +
                            (m.reason ? ' · ' + m.reason : '') + '</div></div>' +
                            '<div class="d-flex gap-2">' +
                            '<button class="btn btn-sm btn-success btn-approve-move" data-id="' + m.id + '"><i class="ri-check-line me-1"></i>Setujui</button>' +
                            '<button class="btn btn-sm btn-outline-danger btn-reject-move" data-id="' + m.id + '"><i class="ri-close-line me-1"></i>Tolak</button>' +
                            '</div></div>';
                    }).join('');

                    box.querySelectorAll('.btn-approve-move').forEach(b => b.addEventListener('click', function () {
                        fetch(approveTemplate.replace('__M__', this.dataset.id), { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } })
                            .then(r => r.json()).then(d => { if (d.success) { window.location.reload(); } else { Swal.fire({ icon: 'error', title: 'Gagal', text: d.message }); } });
                    }));
                    box.querySelectorAll('.btn-reject-move').forEach(b => b.addEventListener('click', function () {
                        fetch(rejectTemplate.replace('__M__', this.dataset.id), { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: '{}' })
                            .then(r => r.json()).then(d => { if (d.success) { window.location.reload(); } else { Swal.fire({ icon: 'error', title: 'Gagal', text: d.message }); } });
                    }));
                })
                .catch(() => {});
        }

        loadPendingMoves();
    })();
    </script>

    <div class="card mt-3" id="pending-moves-card" style="display:none">
        <div class="card-header border-bottom-dashed">
            <h6 class="mb-0"><i class="ri-arrow-left-right-line me-1"></i>Mutasi Kamar Menunggu Persetujuan</h6>
        </div>
        <div class="card-body"><div id="pending-moves"></div></div>
    </div>

    {{-- Modal: Ajukan Pindah Kamar --}}
    <div class="modal fade" id="moveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary-subtle">
                    <h5 class="modal-title"><i class="ri-arrow-left-right-line me-1"></i>Pindah Kamar — <span id="move-nama"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Kamar Tujuan</label>
                        <select id="move-room" class="form-select">
                            <option value="">— pilih kamar —</option>
                            @foreach($rooms as $room)
                                @php $sisa = max(0, (int)($room->capacity ?? 0) - $room->residents->count()); @endphp
                                @if($sisa > 0)
                                    <option value="{{ $room->id }}">{{ $room->name ?: $room->code }} (sisa {{ $sisa }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Alasan (opsional)</label>
                        <input type="text" id="move-reason" class="form-control" placeholder="mis. kepengurusan kamar / kondisi khusus">
                    </div>
                    <div class="alert alert-info small mb-0"><i class="ri-information-line me-1"></i>Mutasi kamar membutuhkan <strong>persetujuan</strong> sebelum penghuni dipindahkan.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="move-submit"><i class="ri-send-plane-line me-1"></i>Ajukan Mutasi</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const asrama = "{{ $dormitory->id }}";
    const userId = "{{ $userId }}";
    const csrf = "{{ csrf_token() }}";
    const pullUrl = "{{ route('user.asrama.api.pullable-students', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}";
    const assignUrlTemplate = "{{ route('user.asrama.api.rooms.assign-students', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => '__ROOM__']) }}";
    const removeUrlTemplate = "{{ route('user.asrama.api.rooms.remove-resident', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => '__ROOM__']) }}";

    const list = document.getElementById('pull-list');
    const submitBtn = document.getElementById('pull-submit');
    const countEl = document.getElementById('pull-count');
    const searchInput = document.getElementById('pull-search');

    function selected() {
        return Array.from(list.querySelectorAll('input[type=checkbox]:checked')).map(c => c.value);
    }
    function refreshCount() {
        const n = selected().length;
        countEl.textContent = n + ' dipilih';
        submitBtn.disabled = n === 0;
    }

    function load() {
        const q = searchInput.value.trim();
        list.innerHTML = '<div class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Menarik data…</div>';

        fetch(pullUrl + (q ? '?q=' + encodeURIComponent(q) : ''), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(function (data) {
                if (!data.success) { list.innerHTML = '<div class="text-center text-danger py-4 small">Gagal menarik data.</div>'; return; }
                if (!data.students.length) { list.innerHTML = '<div class="text-center text-muted py-4 small">Tidak ada santri yang perlu ditempatkan.</div>'; refreshCount(); return; }
                list.innerHTML = data.students.map(s =>
                    '<label class="d-flex align-items-center gap-2 border-bottom px-3 py-2" style="cursor:pointer">' +
                    '<input type="checkbox" class="form-check-input" value="' + s.id + '">' +
                    '<span><span class="fw-semibold">' + s.name + '</span>' +
                    '<span class="text-muted small"> · NISN ' + (s.nisn || '-') + ' · ' + (s.gender === 'L' ? 'Putra' : 'Putri') + '</span></span>' +
                    '</label>'
                ).join('');
                list.querySelectorAll('input[type=checkbox]').forEach(c => c.addEventListener('change', refreshCount));
                refreshCount();
            })
            .catch(() => { list.innerHTML = '<div class="text-center text-danger py-4 small">Gagal menarik data.</div>'; });
    }

    document.getElementById('pull-load').addEventListener('click', load);
    searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); load(); } });
    document.getElementById('pullModal').addEventListener('shown.bs.modal', load);

    submitBtn.addEventListener('click', function () {
        const room = document.getElementById('pull-room').value;
        const ids = selected();
        const maxSisa = parseInt(document.getElementById('pull-room').selectedOptions[0]?.dataset.sisa || '0');

        if (!room) { Swal.fire({ icon: 'info', title: 'Pilih kamar tujuan', text: 'Silakan pilih kamar terlebih dahulu.' }); return; }
        if (ids.length > maxSisa) { Swal.fire({ icon: 'warning', title: 'Melebihi kapasitas', text: 'Sisa ranjang kamar: ' + maxSisa + '.' }); return; }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…';

        fetch(assignUrlTemplate.replace('__ROOM__', room), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ student_ids: ids })
        })
        .then(r => r.json())
        .then(function (data) {
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: data.message, timer: 1600, showConfirmButton: false })
                    .then(() => window.location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Terjadi kesalahan.' });
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="ri-user-add-line me-1"></i>Tempatkan';
            }
        })
        .catch(function () {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan saat menyimpan.' });
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ri-user-add-line me-1"></i>Tempatkan';
        });
    });

    // Keluarkan penghuni dari kamar
    document.querySelectorAll('.btn-keluar').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const residentId = this.dataset.resident;
            const roomId = this.dataset.room;

            Swal.fire({
                icon: 'warning', title: 'Keluarkan dari kamar?',
                text: 'Santri akan dikeluarkan dari kamar ini (tetap terdaftar di asrama).',
                showCancelButton: true, confirmButtonText: 'Keluarkan', cancelButtonText: 'Batal'
            }).then(function (res) {
                if (!res.isConfirmed) return;

                fetch(removeUrlTemplate.replace('__ROOM__', roomId), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ resident_id: residentId })
                })
                .then(r => r.json())
                .then(function (data) {
                    if (data.success) { window.location.reload(); }
                    else { Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Terjadi kesalahan.' }); }
                });
            });
        });
    });
});
</script>
<script>
(function () {
    const searchInput = document.getElementById('placement-search');
    const table = document.getElementById('placementTable');
    const rows = table ? Array.from(table.querySelectorAll('.placement-room-row')) : [];
    const activeBox = document.getElementById('placementActiveFilters');
    const visibleCount = document.getElementById('placementVisibleCount');
    const filterCount = document.getElementById('placementFilterCount');
    if (!table || !rows.length) return;

    const statusLabels = { available: 'Status: Tersedia', full: 'Status: Penuh' };

    function selectedWingLabel() {
        const select = document.getElementById('placementFilterWing');
        return select && select.value ? (select.options[select.selectedIndex]?.text || '') : '';
    }

    function renderActiveBadges() {
        if (!activeBox) return;
        activeBox.innerHTML = '';

        const status = document.getElementById('placementFilterStatus')?.value || '';
        if (status) {
            const badge = document.createElement('span');
            badge.className = 'filter-badge active';
            badge.innerHTML = (statusLabels[status] || status) + ' <span class="remove-filter">&times;</span>';
            badge.querySelector('.remove-filter').addEventListener('click', function () {
                document.getElementById('placementFilterStatus').value = '';
                applyPlacementFilters();
            });
            activeBox.appendChild(badge);
        }

        const wingId = document.getElementById('placementFilterWing')?.value || '';
        if (wingId) {
            const badge = document.createElement('span');
            badge.className = 'filter-badge active';
            badge.innerHTML = 'Gedung: ' + selectedWingLabel() + ' <span class="remove-filter">&times;</span>';
            badge.querySelector('.remove-filter').addEventListener('click', function () {
                document.getElementById('placementFilterWing').value = '';
                applyPlacementFilters();
            });
            activeBox.appendChild(badge);
        }

        const query = (searchInput?.value || '').trim();
        if (query) {
            const badge = document.createElement('span');
            badge.className = 'filter-badge active';
            badge.innerHTML = 'Cari: "' + query + '" <span class="remove-filter">&times;</span>';
            badge.querySelector('.remove-filter').addEventListener('click', function () {
                searchInput.value = '';
                applyPlacementFilters();
            });
            activeBox.appendChild(badge);
        }
    }

    window.applyPlacementFilters = function () {
        const query = (searchInput?.value || '').trim().toLowerCase();
        const status = document.getElementById('placementFilterStatus')?.value || '';
        const wingId = document.getElementById('placementFilterWing')?.value || '';
        let visible = 0;

        rows.forEach(function (row) {
            const text = (row.dataset.searchText || '').toLowerCase();
            const matches = (!query || text.includes(query))
                && (!status || row.dataset.status === status)
                && (!wingId || row.dataset.wing === wingId);

            row.style.display = matches ? '' : 'none';
            const detail = document.getElementById('residents-' + row.dataset.roomId);
            if (detail) {
                if (!matches) detail.style.display = 'none';
            }
            if (matches) visible++;
        });

        if (visibleCount) visibleCount.textContent = visible;
        const count = [status, wingId, query].filter(Boolean).length;
        if (filterCount) {
            filterCount.textContent = count;
            filterCount.style.display = count ? '' : 'none';
        }
        renderActiveBadges();
    };

    window.resetPlacementFilters = function () {
        if (searchInput) searchInput.value = '';
        const status = document.getElementById('placementFilterStatus');
        const wing = document.getElementById('placementFilterWing');
        if (status) status.value = '';
        if (wing) wing.value = '';
        rows.forEach(function (row) {
            row.style.display = '';
            const detail = document.getElementById('residents-' + row.dataset.roomId);
            if (detail) detail.style.display = 'none';
        });
        applyPlacementFilters();
        const modal = bootstrap.Modal.getInstance(document.getElementById('placementFilterModal'));
        if (modal) modal.hide();
    };

    searchInput?.addEventListener('input', window.applyPlacementFilters);

    // Tombol mata: buka/tutup daftar penghuni kamar.
    document.querySelectorAll('.btn-toggle-residents').forEach(function (button) {
        button.addEventListener('click', function () {
            const target = document.querySelector(this.dataset.target);
            if (!target) return;
            const isHidden = target.style.display === 'none' || target.style.display === '';
            target.style.display = isHidden ? '' : 'none';
            this.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
            this.classList.toggle('btn-outline-primary', !isHidden);
            this.classList.toggle('btn-primary', isHidden);
        });
    });

    // Tombol Tempatkan: pilih kamar di modal tarik santri lalu buka modal.
    document.querySelectorAll('.btn-tempatkan').forEach(function (button) {
        button.addEventListener('click', function () {
            const select = document.getElementById('pull-room');
            if (!select) return;
            select.value = this.dataset.room || '';
            const modal = new bootstrap.Modal(document.getElementById('pullModal'));
            modal.show();
        });
    });

    window.applyPlacementFilters();
})();
</script>
@endsection
