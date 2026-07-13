@extends('layouts.master')
@section('title') {{ $room->name ?? $room->code }} @endsection
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.rooms.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Kamar</a> @endslot
        @slot('title') {{ $room->name ?? $room->code }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="row">
        {{-- Info Kamar --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ $room->name ?? $room->code }}</h5></div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th class="text-muted" style="width:160px;">Asrama</th>
                            <td>{{ $dormitory->name }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Kode</th>
                            <td><span class="badge bg-dark">{{ $room->code }}</span></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Gedung</th>
                            <td>{{ $room->wing?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Lantai</th>
                            <td>{{ $room->floor ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Tipe</th>
                            <td>
                                @if($room->room_type)
                                    <span class="badge bg-{{ $room->room_type === 'musyrif' ? 'warning' : 'info' }}-subtle">
                                        {{ ucfirst($room->room_type) }}
                                    </span>
                                @else — @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Kapasitas</th>
                            <td>{{ $stats['total_residents'] }} / {{ $room->capacity }} orang</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Okupansi</th>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:8px">
                                        <div class="progress-bar bg-{{ $stats['occupancy_rate'] >= 100 ? 'danger' : ($stats['occupancy_rate'] >= 80 ? 'warning' : 'success') }}"
                                             role="progressbar" style="width: {{ min($stats['occupancy_rate'], 100) }}%">
                                        </div>
                                    </div>
                                    <span class="small text-muted">{{ $stats['occupancy_rate'] }}%</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Fasilitas</th>
                            <td>{{ $room->facility_notes ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Status</th>
                            <td>
                                @if($room->is_active)
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="{{ route('user.asrama.rooms.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => $room->id]) }}" class="btn btn-primary">
                        <i class="ri-pencil-line me-1"></i> Edit
                    </a>
                    <a href="{{ route('user.asrama.rooms.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">Kembali</a>
                </div>
            </div>
        </div>

        {{-- Penghuni Kamar --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Penghuni Kamar</h5>
                    <button type="button" class="btn btn-primary btn-sm" onclick="openBulkModal()">
                        <i class="ri-add-line me-1"></i> Tarik Penghuni
                    </button>
                </div>
                <div class="card-body p-0">
                    @if($activeResidents->isEmpty())
                        <div class="text-center text-muted py-5">
                            <i class="ri-user-search-line fs-1 d-block mb-2"></i>
                            Belum ada penghuni di kamar ini.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th style="width:40px">No</th>
                                        <th>Nama Santri</th>
                                        <th>NISN</th>
                                        <th>JK</th>
                                        <th>No. Tempat Tidur</th>
                                        <th style="width:60px"></th>
                                    </tr>
                                </thead>
                                <tbody id="memberTableBody">
                                    @foreach($activeResidents as $resident)
                                        <tr id="resident-row-{{ $resident->id }}">
                                            <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                            <td>
                                                <a href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $resident->student_id]) }}">
                                                    {{ $resident->student?->name ?? '-' }}
                                                </a>
                                            </td>
                                            <td><span class="text-muted">{{ $resident->student?->nisn ?? '-' }}</span></td>
                                            <td>
                                                @if($resident->student?->gender === 'L')
                                                    <span class="badge bg-primary-subtle text-primary">L</span>
                                                @elseif($resident->student?->gender === 'P')
                                                    <span class="badge bg-danger-subtle text-danger">P</span>
                                                @endif
                                            </td>
                                            <td>{{ $resident->bed_number ?? '-' }}</td>
                                            <td>
                                                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1"
                                                    onclick="removeResident('{{ $resident->id }}')"
                                                    title="Keluarkan dari kamar">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                @if($activeResidents->isNotEmpty())
                <div class="card-footer text-muted small">
                    <i class="ri-information-line me-1"></i>
                    {{ $activeResidents->count() }} / {{ $room->capacity }} kuota terisi
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection

{{-- Modal Tarik Penghuni Massal --}}
@section('modal')
<div class="modal fade" id="bulkAddModal" tabindex="-1" aria-labelledby="bulkAddModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkAddModalLabel">
                    <i class="ri-user-add-line me-1"></i>
                    Tarik Penghuni ke {{ $room->name ?? $room->code }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="px-3 pt-3 pb-2 border-bottom bg-light-subtle">
                    <div class="row align-items-center g-2">
                        <div class="col-md-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="ri-search-line"></i></span>
                                <input type="text" id="bulkSearchInput" class="form-control"
                                    placeholder="Cari nama atau NISN..." autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="checkbox" id="selectAllResidents" onchange="toggleSelectAll(this)">
                                <label class="form-check-label fw-semibold" for="selectAllResidents">Pilih Semua</label>
                            </div>
                            <span id="selectedCountBadge" class="badge bg-success ms-1" style="display:none"></span>
                        </div>
                    </div>
                </div>

                <div id="bulkResidentList" class="list-group list-group-flush" style="max-height:380px;overflow-y:auto;">
                    <div class="text-center text-muted py-5" id="bulkLoadingState">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <div class="mt-2">Memuat data...</div>
                    </div>
                </div>

                <div id="bulkEmptyState" class="text-center text-muted py-5" style="display:none">
                    <i class="ri-user-follow-line fs-1 d-block mb-2"></i>
                    <p id="bulkEmptyMessage" class="mb-0">Semua penghuni sudah ada di kamar ini</p>
                </div>

                <div id="bulkErrorState" class="text-center py-4" style="display:none">
                    <i class="ri-error-warning-line fs-1 text-danger d-block mb-2"></i>
                    <p class="text-danger mb-2" id="bulkErrorMessage">Gagal memuat data.</p>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadAvailableResidents()">
                        <i class="ri-refresh-line me-1"></i> Coba Lagi
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <span id="selectedInfoText" class="me-auto text-muted small"></span>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="confirmBulkAddBtn" disabled onclick="submitBulkAdd()">
                    <i class="ri-add-line me-1"></i> Masukkan ke Kamar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
/* ─── Constants ─── */
const ROOM_ID   = '{{ $room->id }}';
const DORMITORY_UUID = '{{ $dormitory->id }}';
const USER_ID   = '{{ $userId }}';
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const URL_AVAILABLE = '{{ route('user.asrama.api.rooms.available-residents', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => $room->id]) }}';
const URL_BULK_ADD  = '{{ route('user.asrama.api.rooms.bulk-add-residents',  ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => $room->id]) }}';
const URL_REMOVE    = '{{ route('user.asrama.api.rooms.remove-resident',     ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => $room->id]) }}';

/* ─── State ─── */
let allResidents      = [];
let displayedResidents = [];
let selectedIds        = new Set();
let searchTimer        = null;

/* ─── Modal Open ─── */
function openBulkModal() {
    selectedIds = new Set();
    allResidents = [];
    displayedResidents = [];

    document.getElementById('bulkSearchInput').value = '';
    document.getElementById('selectAllResidents').checked = false;
    document.getElementById('confirmBulkAddBtn').disabled = true;
    document.getElementById('selectedCountBadge').style.display = 'none';
    document.getElementById('selectedInfoText').textContent = '';

    showBulkState('loading');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkAddModal')).show();
    loadAvailableResidents();
}

/* ─── Load Data ─── */
async function loadAvailableResidents() {
    showBulkState('loading');
    try {
        const res = await fetch(URL_AVAILABLE, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        if (!res.ok) {
            const errJson = await res.json().catch(() => ({}));
            throw new Error(errJson.message || 'Server error: ' + res.status);
        }
        const json = await res.json();
        allResidents = json.data || [];
        displayedResidents = [...allResidents];
        renderBulkList(displayedResidents);
    } catch (err) {
        console.error('[BulkAdd] loadAvailableResidents error:', err);
        document.getElementById('bulkErrorMessage').textContent = 'Gagal memuat data: ' + err.message;
        showBulkState('error');
    }
}

/* ─── State Helper ─── */
function showBulkState(state) {
    document.getElementById('bulkResidentList').style.display   = state === 'list'    ? '' : 'none';
    document.getElementById('bulkLoadingState').style.display  = state === 'loading' ? '' : 'none';
    document.getElementById('bulkEmptyState').style.display    = state === 'empty'   ? '' : 'none';
    document.getElementById('bulkErrorState').style.display    = state === 'error'   ? '' : 'none';
}

/* ─── Search ─── */
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('bulkSearchInput')?.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim().toLowerCase();
        searchTimer = setTimeout(function () {
            if (!q) {
                displayedResidents = [...allResidents];
            } else {
                displayedResidents = allResidents.filter(function (r) {
                    return (r.student_name || '').toLowerCase().includes(q) ||
                           (r.nisn || '').toLowerCase().includes(q);
                });
            }
            renderBulkList(displayedResidents);
        }, 200);
    });
});

/* ─── Render List ─── */
function renderBulkList(residents) {
    if (!residents.length) {
        document.getElementById('bulkEmptyMessage').textContent =
            allResidents.length === 0
                ? 'Semua penghuni sudah ada di kamar ini'
                : 'Tidak ada hasil pencarian';
        showBulkState('empty');
        return;
    }
    showBulkState('list');

    const container = document.getElementById('bulkResidentList');
    container.innerHTML = residents.map(function (r) {
        var genderClass = (r.gender === 'P') ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary';
        var genderLabel = (r.gender === 'P') ? 'P' : 'L';
        return '<div class="list-group-item d-flex align-items-center gap-2 py-2 resident-item" data-id="' + r.id + '">' +
            '<input class="form-check-input resident-checkbox flex-shrink-0" type="checkbox"' +
            ' value="' + r.id + '" id="cb-' + r.id + '"' +
            ' onchange="toggleResident(\'' + r.id + '\')">' +
            '<label class="form-check-label flex-grow-1" for="cb-' + r.id + '" style="cursor:pointer">' +
                '<strong>' + escHtml(r.student_name) + '</strong>' +
                '<span class="text-muted ms-2">NISN: ' + escHtml(r.nisn) + '</span>' +
            '</label>' +
            '<span class="badge ' + genderClass + '">' + genderLabel + '</span>' +
        '</div>';
    }).join('');

    selectedIds.forEach(function (id) {
        var cb = document.getElementById('cb-' + id);
        if (cb) cb.checked = true;
    });

    updateBulkUI();
}

/* ─── Selection ─── */
function toggleResident(id) {
    var cb = document.getElementById('cb-' + id);
    if (cb && cb.checked) {
        selectedIds.add(id);
    } else {
        selectedIds.delete(id);
        document.getElementById('selectAllResidents').checked = false;
    }
    updateBulkUI();
}

function toggleSelectAll(el) {
    if (el.checked) {
        displayedResidents.forEach(function (r) { selectedIds.add(r.id); });
        document.querySelectorAll('.resident-checkbox').forEach(function (cb) { cb.checked = true; });
    } else {
        displayedResidents.forEach(function (r) { selectedIds.delete(r.id); });
        document.querySelectorAll('.resident-checkbox').forEach(function (cb) { cb.checked = false; });
    }
    updateBulkUI();
}

function updateBulkUI() {
    var count = selectedIds.size;
    var btn   = document.getElementById('confirmBulkAddBtn');
    var badge = document.getElementById('selectedCountBadge');
    var info  = document.getElementById('selectedInfoText');

    btn.disabled = count === 0;
    btn.innerHTML = count > 0
        ? '<i class="ri-add-line me-1"></i> Masukkan ' + count + ' Penghuni'
        : '<i class="ri-add-line me-1"></i> Masukkan ke Kamar';

    badge.style.display = count > 0 ? '' : 'none';
    badge.textContent   = count > 0 ? count : '';

    info.textContent = count > 0
        ? count + ' penghuni dipilih dari ' + displayedResidents.length + ' yang ditampilkan'
        : '';
}

/* ─── Submit Bulk Add ─── */
async function submitBulkAdd() {
    if (selectedIds.size === 0) return;

    var ids = Array.from(selectedIds);
    var btn = document.getElementById('confirmBulkAddBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

    try {
        var res = await fetch(URL_BULK_ADD, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ resident_ids: ids }),
        });

        var json = await res.json();

        if (json.success) {
            bootstrap.Modal.getInstance(document.getElementById('bulkAddModal')).hide();
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: json.message, timer: 2000, showConfirmButton: false });
            }
            setTimeout(function () { location.reload(); }, 800);
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Gagal', text: json.message || 'Gagal menambahkan penghuni.' });
            } else {
                alert(json.message || 'Gagal menambahkan penghuni.');
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-add-line me-1"></i> Masukkan ke Kamar';
        }
    } catch (err) {
        console.error('[BulkAdd] submitBulkAdd error:', err);
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan: ' + err.message });
        } else {
            alert('Terjadi kesalahan: ' + err.message);
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-add-line me-1"></i> Masukkan ke Kamar';
    }
}

/* ─── Remove Resident ─── */
async function removeResident(residentId) {
    var confirmed = await new Promise(function (resolve) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Keluarkan dari kamar?',
                text: 'Penghuni ini akan dikeluarkan dari kamar ini.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, keluarkan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
            }).then(function (r) { resolve(r.isConfirmed); });
        } else {
            resolve(confirm('Keluarkan penghuni ini dari kamar?'));
        }
    });

    if (!confirmed) return;

    try {
        var res = await fetch(URL_REMOVE, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ resident_id: residentId }),
        });

        var json = await res.json();

        if (json.success) {
            var row = document.getElementById('resident-row-' + residentId);
            if (row) {
                row.style.transition = 'opacity 0.3s';
                row.style.opacity = '0';
                setTimeout(function () { row.remove(); }, 300);
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: json.message, timer: 1500, showConfirmButton: false });
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Gagal', text: json.message });
            } else {
                alert(json.message);
            }
        }
    } catch (err) {
        console.error('[BulkAdd] removeResident error:', err);
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan: ' + err.message });
        } else {
            alert('Terjadi kesalahan: ' + err.message);
        }
    }
}

/* ─── Utility ─── */
function escHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
</script>
@endsection
