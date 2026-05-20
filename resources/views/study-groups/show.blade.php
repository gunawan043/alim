@extends('layouts.master')
@section('title') {{ $studyGroup->full_name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}">Rombongan Belajar</a> @endslot
        @slot('title') {{ $studyGroup->full_name }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Warning: kapasitas penuh --}}
    @php $sgFilled = $activeHistories->count(); @endphp
    @if($sgFilled > $studyGroup->capacity)
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="ri-error-warning-fill fs-4"></i>
            <div>
                <strong>Kapasitas melebihi batas!</strong>
                Rombel ini sudah berisi <strong>{{ $sgFilled }} siswa</strong>,
                sedangkan kapasitas hanya <strong>{{ $studyGroup->capacity }} siswa</strong>
                ({{ $sgFilled - $studyGroup->capacity }} siswa lebih).
                Segera pindahkan atau naikkan kapasitas rombel.
            </div>
        </div>
    @endif

    <div class="row">
        {{-- Info Rombel --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ $studyGroup->full_name }}</h5></div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th class="text-muted" style="width:160px;">Sekolah</th>
                            <td>
                                @if($studyGroup->school)
                                    <a href="{{ route('user.schools.show', ['userId' => $userId, 'schoolId' => $studyGroup->school_id]) }}">{{ $studyGroup->school->name }}</a>
                                @else - @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Tahun Ajaran</th>
                            <td>
                                @if($studyGroup->academicYear)
                                    <span class="badge bg-{{ $studyGroup->academicYear->semester === 'ganjil' ? 'primary' : 'info' }}-subtle text-{{ $studyGroup->academicYear->semester === 'ganjil' ? 'primary' : 'info' }}">
                                        {{ $studyGroup->academicYear->name }} ({{ $studyGroup->academicYear->semester_text }})
                                    </span>
                                @else - @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Tingkat</th>
                            <td>{{ $studyGroup->gradeLevel?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Nama Rombel</th>
                            <td>{{ $studyGroup->name }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Kapasitas</th>
                            <td>
                                @php
                                    $filled = $activeHistories->count();
                                    $cap    = $studyGroup->capacity;
                                    $pct    = $cap > 0 ? min(100, round($filled / $cap * 100)) : 0;
                                    $full   = $filled >= $cap;
                                @endphp
                                <div class="d-flex align-items-center gap-3">
                                    <div class="progress flex-grow-1" style="height:8px;max-width:200px">
                                        <div class="progress-bar bg-{{ $full ? 'danger' : ($pct >= 90 ? 'warning' : 'success') }}" style="width:{{ $pct }}%"></div>
                                    </div>
                                    <span class="{{ $full ? 'text-danger fw-bold' : 'text-muted' }}" style="font-size:13px">
                                        {{ $filled }}/{{ $cap }} siswa
                                        @if($full)
                                            <i class="ri-error-warning-fill ms-1 align-middle"></i> PENUH
                                        @elseif($pct >= 90)
                                            <span class="text-warning ms-1">(hampir penuh)</span>
                                        @endif
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Ruang</th>
                            <td>{{ $studyGroup->room ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Wali Kelas</th>
                            <td>{{ $studyGroup->homeroomTeacher?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Kurikulum</th>
                            <td>{{ ucfirst($studyGroup->curriculum_type ?? '-') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Shift</th>
                            <td>{{ ucfirst($studyGroup->shift ?? '-') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Status</th>
                            <td>
                                @if($studyGroup->is_active)
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                @endif
                            </td>
                        </tr>
                        @if($studyGroup->notes)
                        <tr>
                            <th class="text-muted">Catatan</th>
                            <td>{{ $studyGroup->notes }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
                <div class="card-footer">
                    <a href="{{ route('user.student-promotions.index', ['userId' => $userId]) }}?from_study_group_id={{ $studyGroup->id }}"
                       class="btn btn-warning">
                        <i class="ri-arrow-up-line me-1"></i> Promosi Santri
                    </a>
                    <a href="{{ route('user.study-groups.edit', ['userId' => $userId, 'id' => $studyGroup->id]) }}" class="btn btn-primary">
                        <i class="ri-pencil-line me-1"></i> Edit
                    </a>
                    <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}" class="btn btn-light">Kembali</a>
                </div>
            </div>
        </div>

        {{-- Anggota Rombel --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Anggota Rombel</h5>
                    <button type="button" class="btn btn-success btn-sm" onclick="openBulkModal()">
                        <i class="ri-add-line me-1"></i> Tarik Santri
                    </button>
                </div>
                <div class="card-body p-0">
                    @if($activeHistories->isEmpty())
                        <div class="text-center text-muted py-5">
                            <i class="ri-user-search-line fs-1 d-block mb-2"></i>
                            Belum ada anggota rombel
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
                                        <th style="width:60px"></th>
                                    </tr>
                                </thead>
                                <tbody id="memberTableBody">
                                    @foreach($activeHistories as $history)
                                        <tr id="student-row-{{ $history->student_id }}">
                                            <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                            <td>
                                                <a href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $history->student_id]) }}">
                                                    {{ $history->student?->name ?? '-' }}
                                                </a>
                                            </td>
                                            <td><span class="text-muted">{{ $history->student?->nisn ?? '-' }}</span></td>
                                            <td>
                                                @if($history->student?->gender === 'L')
                                                    <span class="badge bg-primary-subtle text-primary">L</span>
                                                @elseif($history->student?->gender === 'P')
                                                    <span class="badge bg-danger-subtle text-danger">P</span>
                                                @endif
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1"
                                                    onclick="removeStudent('{{ $history->student_id }}')"
                                                    title="Keluarkan dari rombel">
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
                @if($activeHistories->isNotEmpty())
                <div class="card-footer text-muted small" id="memberCountFooter">
                    <i class="ri-information-line me-1"></i>
                    {{ $activeHistories->count() }} / {{ $studyGroup->capacity }} kuota terisi
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection

{{-- Modal Tambah Massal --}}
@section('modal')
<div class="modal fade" id="bulkAddModal" tabindex="-1" aria-labelledby="bulkAddModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkAddModalLabel">
                    <i class="ri-user-add-line me-1"></i>
                    Tarik Santri ke {{ $studyGroup->full_name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                {{-- Filter & Select All --}}
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
                                <input class="form-check-input" type="checkbox" id="selectAllStudents" onchange="toggleSelectAll(this)">
                                <label class="form-check-label fw-semibold" for="selectAllStudents">Pilih Semua</label>
                            </div>
                            <span id="selectedCountBadge" class="badge bg-success ms-1" style="display:none"></span>
                        </div>
                    </div>
                </div>

                {{-- Student List --}}
                <div id="bulkStudentList" class="list-group list-group-flush" style="max-height:380px;overflow-y:auto;">
                    <div class="text-center text-muted py-5" id="bulkLoadingState">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <div class="mt-2">Memuat data...</div>
                    </div>
                </div>

                {{-- Empty state — dibuat sebagai div terpisah agar tidak perlu querySelector --}}
                <div id="bulkEmptyState" class="text-center text-muted py-5" style="display:none">
                    <i class="ri-user-follow-line fs-1 d-block mb-2"></i>
                    <p id="bulkEmptyMessage" class="mb-0">Semua santri sudah masuk rombel</p>
                </div>

                {{-- Error state --}}
                <div id="bulkErrorState" class="text-center py-4" style="display:none">
                    <i class="ri-error-warning-line fs-1 text-danger d-block mb-2"></i>
                    <p class="text-danger mb-2" id="bulkErrorMessage">Gagal memuat data santri.</p>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadUnassignedStudents()">
                        <i class="ri-refresh-line me-1"></i> Coba Lagi
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <span id="selectedInfoText" class="me-auto text-muted small"></span>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="confirmBulkAddBtn" disabled onclick="submitBulkAdd()">
                    <i class="ri-add-line me-1"></i> Masukkan ke Rombel
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
/* ==========================================================================
   CONSTANTS — diisi dari Blade agar tidak ada hardcode di JS
   ========================================================================== */
const STUDY_GROUP_ID = '{{ $studyGroup->id }}';
const USER_ID        = '{{ $userId }}';
const CSRF_TOKEN     = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// URL API — gunakan route() dari Blade agar konsisten dengan middleware prefix {userId}
const URL_UNASSIGNED = '{{ route('user.api.study-groups.students.unassigned', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}';
const URL_BULK_ADD   = '{{ route('user.api.study-groups.students.bulk-add',   ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}';
const URL_REMOVE     = '{{ route('user.api.study-groups.students.remove',     ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}';

/* ==========================================================================
   STATE
   ========================================================================== */
let allStudents      = [];
let displayedStudents = [];
let selectedIds      = new Set();
let searchTimer      = null;

/* ==========================================================================
   MODAL — OPEN
   ========================================================================== */
function openBulkModal() {
    // Reset state
    selectedIds = new Set();
    allStudents = [];
    displayedStudents = [];

    // Reset UI
    const searchInput = document.getElementById('bulkSearchInput');
    if (searchInput) searchInput.value = '';
    document.getElementById('selectAllStudents').checked = false;
    document.getElementById('confirmBulkAddBtn').disabled = true;
    document.getElementById('selectedCountBadge').style.display = 'none';
    document.getElementById('selectedInfoText').textContent = '';

    // Show loading, hide others
    showBulkState('loading');

    // Show modal first, then load data
    const modalEl = document.getElementById('bulkAddModal');
    bootstrap.Modal.getOrCreateInstance(modalEl).show();

    loadUnassignedStudents();
}

/* ==========================================================================
   LOAD DATA — FIX: tambah credentials: 'same-origin' agar session cookie
   ikut terkirim ke Laravel. Tanpa ini, middleware auth redirect ke /login
   dan response-nya bukan JSON sehingga .json() throw error.
   ========================================================================== */
async function loadUnassignedStudents() {
    showBulkState('loading');

    try {
        const res = await fetch(URL_UNASSIGNED, {
            method: 'GET',
            credentials: 'same-origin',                    // ← FIX UTAMA
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',      // agar Laravel tahu ini AJAX
            }
        });

        // Tangani non-OK response dengan pesan yang informatif
        if (!res.ok) {
            let errMsg = 'Server error: ' + res.status;
            try {
                const errJson = await res.json();
                errMsg = errJson.message || errMsg;
            } catch (_) { /* response bukan JSON, abaikan */ }
            throw new Error(errMsg);
        }

        const json = await res.json();
        allStudents       = json.data || [];
        displayedStudents = [...allStudents];
        renderBulkList(displayedStudents);

    } catch (err) {
        console.error('[BulkAdd] loadUnassignedStudents error:', err);
        document.getElementById('bulkErrorMessage').textContent =
            'Gagal memuat data santri: ' + err.message;
        showBulkState('error');
    }
}

/* ==========================================================================
   SHOW STATE HELPER — menghindari manipulasi DOM yang fragile
   ========================================================================== */
function showBulkState(state) {
    // state: 'loading' | 'list' | 'empty' | 'error'
    document.getElementById('bulkStudentList').style.display  = state === 'list'    ? '' : 'none';
    document.getElementById('bulkLoadingState').style.display = state === 'loading' ? '' : 'none';
    document.getElementById('bulkEmptyState').style.display   = state === 'empty'   ? '' : 'none';
    document.getElementById('bulkErrorState').style.display   = state === 'error'   ? '' : 'none';
}

/* ==========================================================================
   SEARCH
   ========================================================================== */
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('bulkSearchInput')?.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim().toLowerCase();
        searchTimer = setTimeout(function () {
            if (!q) {
                displayedStudents = [...allStudents];
            } else {
                displayedStudents = allStudents.filter(function (s) {
                    return (s.name || '').toLowerCase().includes(q) ||
                           (s.nisn || '').toLowerCase().includes(q);
                });
            }
            renderBulkList(displayedStudents);
        }, 200);
    });
});

/* ==========================================================================
   RENDER LIST
   ========================================================================== */
function renderBulkList(students) {
    if (!students.length) {
        document.getElementById('bulkEmptyMessage').textContent =
            allStudents.length === 0
                ? 'Semua santri sudah masuk rombel'
                : 'Tidak ada hasil pencarian';
        showBulkState('empty');
        return;
    }

    showBulkState('list');

    const container = document.getElementById('bulkStudentList');
    container.innerHTML = students.map(function (s) {
        var genderClass = (s.gender === 'P')
            ? 'bg-danger-subtle text-danger'
            : 'bg-primary-subtle text-primary';
        var genderLabel = (s.gender === 'P') ? 'P' : 'L';
        var nisnText    = s.nisn ? 'NISN: ' + s.nisn : '';

        return '<div class="list-group-item d-flex align-items-center gap-2 py-2 student-item" data-id="' + s.id + '">' +
            '<input class="form-check-input student-checkbox flex-shrink-0" type="checkbox"' +
            ' value="' + s.id + '" id="cb-' + s.id + '"' +
            ' onchange="toggleStudent(\'' + s.id + '\')">' +
            '<label class="form-check-label flex-grow-1" for="cb-' + s.id + '" style="cursor:pointer">' +
                '<strong>' + escHtml(s.name) + '</strong>' +
                '<span class="text-muted ms-2">' + escHtml(nisnText) + '</span>' +
            '</label>' +
            '<span class="badge ' + genderClass + '">' + genderLabel + '</span>' +
        '</div>';
    }).join('');

    // Restore checked state
    selectedIds.forEach(function (id) {
        var cb = document.getElementById('cb-' + id);
        if (cb) cb.checked = true;
    });

    updateBulkUI();
}

/* ==========================================================================
   SELECTION
   ========================================================================== */
function toggleStudent(id) {
    var cb = document.getElementById('cb-' + id);
    if (cb && cb.checked) {
        selectedIds.add(id);
    } else {
        selectedIds.delete(id);
        document.getElementById('selectAllStudents').checked = false;
    }
    updateBulkUI();
}

function toggleSelectAll(el) {
    if (el.checked) {
        displayedStudents.forEach(function (s) { selectedIds.add(s.id); });
        document.querySelectorAll('.student-checkbox').forEach(function (cb) { cb.checked = true; });
    } else {
        displayedStudents.forEach(function (s) { selectedIds.delete(s.id); });
        document.querySelectorAll('.student-checkbox').forEach(function (cb) { cb.checked = false; });
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
        ? '<i class="ri-add-line me-1"></i> Masukkan ' + count + ' Santri'
        : '<i class="ri-add-line me-1"></i> Masukkan ke Rombel';

    badge.style.display = count > 0 ? '' : 'none';
    badge.textContent   = count > 0 ? count : '';

    info.textContent = count > 0
        ? count + ' Santri dipilih dari ' + displayedStudents.length + ' yang ditampilkan'
        : '';
}

/* ==========================================================================
   SUBMIT BULK ADD — FIX: credentials: 'same-origin'
   ========================================================================== */
async function submitBulkAdd() {
    if (selectedIds.size === 0) return;

    var ids = Array.from(selectedIds);
    var btn = document.getElementById('confirmBulkAddBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

    try {
        var res = await fetch(URL_BULK_ADD, {
            method: 'POST',
            credentials: 'same-origin',                    // ← FIX
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ student_ids: ids }),
        });

        var json = await res.json();

        if (json.success) {
            bootstrap.Modal.getInstance(document.getElementById('bulkAddModal')).hide();

            // Coba gunakan Toastify jika tersedia, fallback ke Swal
            if (typeof Toastify !== 'undefined') {
                Toastify({
                    text: json.message || ids.length + ' Santri berhasil ditambahkan.',
                    gravity: 'top',
                    position: 'center',
                    backgroundColor: '#28a745',
                    duration: 3000,
                }).showToast();
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: json.message || ids.length + ' Santri berhasil ditambahkan.', timer: 2000, showConfirmButton: false });
            }

            setTimeout(function () { location.reload(); }, 800);
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Gagal', text: json.message || 'Gagal menambahkan Santri.' });
            } else {
                alert(json.message || 'Gagal menambahkan Santri.');
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-add-line me-1"></i> Masukkan ke Rombel';
        }
    } catch (err) {
        console.error('[BulkAdd] submitBulkAdd error:', err);
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan: ' + err.message });
        } else {
            alert('Terjadi kesalahan: ' + err.message);
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-add-line me-1"></i> Masukkan ke Rombel';
    }
}

/* ==========================================================================
   REMOVE STUDENT — FIX: credentials: 'same-origin'
   ========================================================================== */
async function removeStudent(studentId) {
    var confirmed = await new Promise(function (resolve) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Keluarkan dari rombel?',
                text: 'Santri ini akan dikeluarkan dari rombel ini.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, keluarkan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
            }).then(function (r) { resolve(r.isConfirmed); });
        } else {
            resolve(confirm('Keluarkan Santri ini dari rombel?'));
        }
    });

    if (!confirmed) return;

    try {
        var res = await fetch(URL_REMOVE, {
            method: 'POST',
            credentials: 'same-origin',                    // ← FIX
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ student_id: studentId }),
        });

        var json = await res.json();

        if (json.success) {
            var row = document.getElementById('student-row-' + studentId);
            if (row) {
                row.style.transition = 'opacity 0.3s';
                row.style.opacity    = '0';
                setTimeout(function () { row.remove(); }, 300);
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: json.message || 'Santri berhasil dikeluarkan.', timer: 1500, showConfirmButton: false });
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Gagal', text: json.message || 'Gagal mengeluarkan santri.' });
            } else {
                alert(json.message || 'Gagal mengeluarkan');
            }
        }
    } catch (err) {
        console.error('[BulkAdd] removeStudent error:', err);
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan: ' + err.message });
        } else {
            alert('Terjadi kesalahan: ' + err.message);
        }
    }
}

/* ==========================================================================
   UTILITY
   ========================================================================== */
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