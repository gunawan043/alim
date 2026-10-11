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

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card card-animate">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <span class="avatar-title bg-warning-subtle text-warning rounded fs-22"><i class="ri-user-search-line"></i></span>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Belum Berkamar</p>
                        <h3 class="fw-bold ff-secondary mb-0" id="belum-berkamar-count">{{ $belumBerkamar }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-animate">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <span class="avatar-title bg-info-subtle text-info rounded fs-22"><i class="ri-hotel-bed-line"></i></span>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Kamar</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $rooms->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 d-flex align-items-center justify-content-md-end">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#pullModal">
                <i class="ri-download-cloud-2-line me-1"></i> Tarik Data Santri Belum Berkamar
            </button>
            <button type="button" class="btn btn-soft-primary ms-1" data-bs-toggle="modal" data-bs-target="#boardingModal">
                <i class="ri-user-settings-line me-1"></i> Kelola Status Boarding
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <h6 class="mb-0"><i class="ri-layout-grid-line me-1"></i>Daftar Kamar &amp; Penghuni</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @forelse($rooms as $room)
                    @php
                        $aktif = $room->residents->count();
                        $kapasitas = (int) ($room->capacity ?? 0);
                        $sisa = max(0, $kapasitas - $aktif);
                    @endphp
                    <div class="col-lg-6">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-semibold">{{ $room->name ?: $room->code }}</div>
                                    <div class="small text-muted">Kapasitas {{ $kapasitas }} · Terisi {{ $aktif }} · Sisa {{ $sisa }}</div>
                                </div>
                                <span class="badge {{ $sisa > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                    {{ $sisa > 0 ? $sisa.' ranjang kosong' : 'Penuh' }}
                                </span>
                            </div>

                            @if($aktif > 0)
                                <ul class="list-unstyled mb-2 small">
                                    @foreach($room->residents as $resident)
                                        <li class="d-flex justify-content-between align-items-center border-bottom py-1">
                                            <span>
                                                <i class="ri-user-line me-1"></i>
                                                {{ $resident->student?->name ?? '—' }}
                                                <span class="text-muted">· ranjang #{{ $resident->bed_number }}</span>
                                            </span>
                                            <button type="button" class="btn btn-sm btn-soft-danger btn-keluar"
                                                    data-resident="{{ $resident->id }}"
                                                    data-room="{{ $room->id }}"
                                                    title="Keluarkan dari kamar">
                                                <i class="ri-logout-box-r-line"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-soft-warning btn-berhenti ms-1"
                                                    data-resident="{{ $resident->id }}"
                                                    data-nama="{{ $resident->student?->name }}"
                                                    title="Berhenti mondok (tetap sekolah)">
                                                <i class="ri-user-unfollow-line"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-soft-primary btn-pindah ms-1"
                                                    data-resident="{{ $resident->id }}"
                                                    data-nama="{{ $resident->student?->name }}"
                                                    title="Ajukan pindah kamar (butuh persetujuan)">
                                                <i class="ri-arrow-left-right-line"></i>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted small mb-2">Belum ada penghuni.</p>
                            @endif

                            <button type="button" class="btn btn-sm btn-soft-primary btn-tempatkan"
                                    data-room="{{ $room->id }}"
                                    data-room-name="{{ $room->name ?: $room->code }}"
                                    data-sisa="{{ $sisa }}"
                                    {{ $sisa <= 0 ? 'disabled' : '' }}>
                                <i class="ri-user-add-line me-1"></i> Tempatkan Santri
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center text-muted py-5">
                        Belum ada kamar. <a href="{{ route('user.asrama.rooms.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Tambah kamar</a> terlebih dahulu.
                    </div>
                @endforelse
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
@endsection
