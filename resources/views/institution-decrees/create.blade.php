@extends('layouts.master')
@section('title') Tambah Surat Keputusan @endsection

@section('css')
<style>
    .col-jp { min-width: 46px; text-align: center; }
    .col-jp input { text-align: center; width: 44px; padding: 3px 4px; }
    .col-jp input.has-val { background: #e8f4ea; border-color: #28a745; }
    .del-btn { color: #aaa; cursor: pointer; }
    .del-btn:hover { color: #333; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Administrasi @endslot
        @slot('li_2') <a href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}">Surat Keputusan</a> @endslot
        @slot('title') Buat SK & Matriks Pembagian Tugas @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" id="decreeForm" action="{{ route('user.institution-decrees.store', ['userId' => $userId]) }}">
        @csrf

        {{-- Section 1: Info SK (Collapsible) --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center" style="cursor:pointer"
                 onclick="document.getElementById('skInfoBody').classList.toggle('d-none')">
                <h5 class="mb-0"><i class="ri-file-text-line me-2"></i>Informasi Surat Keputusan</h5>
                <i class="ri-arrow-down-s-line" id="skInfoArrow"></i>
            </div>
            <div class="card-body" id="skInfoBody">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Nomor SK <span class="text-danger">*</span></label>
                        <input type="text" name="decree_number" class="form-control" value="{{ old('decree_number', '066/PAH/SMPIT-Pi/E/VII/'.date('Y')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jenis SK <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" value="SK Pembagian Tugas" readonly>
                        <input type="hidden" name="decree_type" value="SK Pembagian Tugas">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                        <select name="academic_year_id" class="form-control" required id="aySelect">
                            <option value="">— Pilih —</option>
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ old('academic_year_id', $selectedAyId ?? $activeYear?->id) == $ay->id ? 'selected' : '' }}>
                                    {{ $ay->name }} ({{ $ay->semester_text }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sekolah <span class="text-danger">*</span></label>
                        @if(!$canSelectSchool && $selectedSchool)
                            <input type="text" class="form-control" value="{{ $selectedSchool->name }}" readonly>
                            <input type="hidden" name="school_id" value="{{ $selectedSchool->id }}">
                        @else
                            <select name="school_id" class="form-control" required id="schoolSelect">
                                <option value="">— Pilih Sekolah —</option>
                                @foreach($schools as $s)
                                    <option value="{{ $s->id }}" {{ (string)($selectedSchoolId ?? '') === (string)$s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Judul SK <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}" required
                               placeholder="cth: PEMBAGIAN TUGAS MENGAJAR GURU SEMESTER GANJIL TAHUN AJARAN 2025/2026">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Dikeluarkan <span class="text-danger">*</span></label>
                        <input type="date" name="issued_date" class="form-control" value="{{ old('issued_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Efektif <span class="text-danger">*</span></label>
                        <input type="date" name="effective_date" class="form-control" value="{{ old('effective_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Penandatangan</label>
                        <select name="signed_by" class="form-control">
                            <option value="">— Pilih —</option>
                            @foreach($signers as $s)
                                <option value="{{ $s->id }}" {{ old('signed_by') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jabatan Penandatangan</label>
                        <input type="text" name="signed_position" class="form-control" value="{{ old('signed_position', 'Kepala Sekolah') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="1">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Matriks GTK (selalu tampil saat sekolah dipilih) --}}
        <div id="matrixSection">
            @php $byGrade = $studyGroups->groupBy(fn($sg) => $sg->gradeLevel->level ?? 0)->sortKeys(); @endphp

            @if($teachers->isEmpty())
                <div class="no-school-msg mb-3">
                    <i class="ri-government-line fs-1 d-block mb-2"></i>
                    <strong>Pilih sekolah terlebih dahulu</strong><br>
                    <small>Pilih sekolah di atas, lalu tabel GTK akan muncul di bawah.</small>
                </div>
            @else
                {{-- Header info for print preview --}}
                <div class="alert alert-info mb-3 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted text-uppercase fw-bold mb-1" style="font-size:11px;">
                                Lampiran I. Surat Keputusan Kepala
                            </div>
                            <div class="fw-bold" id="infoSchool">
                                {{ $selectedSchool?->name ?? '- Seleksi Sekolah -' }}
                            </div>
                            <div class="mt-1" style="font-size:11px;">
                                <span id="infoNomor">NOMOR&nbsp;&nbsp;: <strong>-</strong></span> &nbsp;|&nbsp;
                                <span id="infoTanggal">TANGGAL : <strong>-</strong></span> &nbsp;|&nbsp;
                                <span id="infoTA">TA <strong>-</strong></span>
                            </div>
                            <div class="mt-1" style="font-size:11px;" id="infoTitle">
                                TENTANG : <strong>-</strong>
                            </div>
                        </div>
                        <div class="text-end text-muted" style="font-size:10px;">
                            Matriks Pembagian Tugas Mengajar
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-light text-secondary border mb-1">
                                <i class="ri-group-line me-1"></i>
                                <span id="teacherCount">0</span> Guru
                            </span>
                            <span class="badge bg-light text-secondary border mb-1">
                                <i class="ri-stack-line me-1"></i>
                                {{ $studyGroups->count() }} Rombel
                            </span>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <select id="newTeacherSelect" class="form-control form-control-sm" style="width:220px;">
                                <option value="">-- Pilih Guru --</option>
                                @foreach($teachers as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addTeacherToMatrix()">
                                <i class="ri-add-line me-1"></i> Tambah
                            </button>
                            <button type="button" class="btn btn-sm" style="border:1px solid #ccc;" onclick="showAddTask()">
                                <i class="ri-user-settings-line me-1"></i> Tugas Tambahan
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height:60vh; overflow:auto;">
                            <table class="table table-bordered mb-0" style="font-size:12px; min-width:650px;">
                                <thead class="text-center align-middle position-sticky top-0" style="z-index:10; background:#f8f9fa; border-bottom:2px solid #dee2e6;">
                                    <tr>
                                        <th rowspan="2" class="text-center" style="width:25px; border-right:1px solid #dee2e6;">No</th>
                                        <th rowspan="2" style="min-width:180px; border-right:1px solid #dee2e6;">Nama / Mapel / Tugas</th>
                                        @foreach($byGrade as $level => $groups)
                                            <th colspan="{{ $groups->count() }}" style="background:#343a40; color:#fff; font-size:11px;">
                                                {{ $groups->first()->gradeLevel->name ?? "Kelas $level" }}
                                            </th>
                                        @endforeach
                                        <th rowspan="2" style="background:#343a40; color:#fff; width:48px; font-size:10px;">Seb.<br>Jam</th>
                                        <th rowspan="2" style="background:#343a40; color:#fff; width:48px; font-size:10px;">Tugas<br>Lain</th>
                                        <th rowspan="2" style="background:#343a40; color:#fff; width:44px; font-size:10px;">Jml<br>Jam</th>
                                        <th rowspan="2" style="width:26px;"></th>
                                    </tr>
                                    <tr>
                                        @foreach($byGrade as $level => $groups)
                                            @foreach($groups as $sg)
                                                <th class="table-light" style="min-width:42px; font-size:10px;">{{ $sg->name }}</th>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody id="matrixBody">
                                    <tr>
                                        <td colspan="20" class="text-center text-muted py-4" style="border:none;">
                                            <i class="ri-user-add-line fs-3 d-block mb-2"></i>
                                            Belum ada guru. Pilih guru di atas lalu klik "Tambah".
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Submit --}}
        <div class="d-flex justify-content-end gap-2 mt-2 mb-3">
            <a href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
            <button type="submit" class="btn btn-success">
                <i class="ri-save-line me-1"></i> Simpan SK
            </button>
        </div>

        <input type="hidden" name="assignments_json" id="assignmentsJson" value="">
        <input type="hidden" name="other_tasks_json" id="otherTasksJson" value="">
    </form>

    {{-- Modal: Add Task --}}
    <div class="modal fade" id="addTaskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-user-settings-line me-2"></i>Tambah Tugas Tambahan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Guru</label>
                        <select id="taskTeacherSelect" class="form-control">
                            <option value="">-- Pilih Guru --</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Nama Tugas</label>
                        <input type="text" id="taskName" class="form-control" placeholder="cth: Wali Kelas 7A, Koordinator Bahasa">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Kode Tugas (opsional)</label>
                        <input type="text" id="taskCode" class="form-control" placeholder="cth: walikelas, koordinator">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Jam per Minggu</label>
                        <input type="number" id="taskHours" class="form-control" value="3" min="0" max="40">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-warning" onclick="addTaskFromModal()">
                        <i class="ri-add-line me-1"></i> Tambah
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: pilih subject untuk guru --}}
    <div class="modal fade" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-book-open-line me-2"></i>Pilih Mapel untuk Guru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted" style="font-size:12px;" id="addSubjectTeacherLabel">Pilih mapel:</p>
                    <div id="subjectList" style="max-height:300px; overflow-y:auto;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="confirmAddSubjects()">
                        <i class="ri-add-line me-1"></i> Tambah Mapel
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
const teachers = {!! json_encode($teachers->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'role' => $t->getRoleNames()->first()])->values()->toArray()) !!};
const subjects = {!! json_encode($subjects->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values()->toArray()) !!};
const studyGroups = {!! json_encode($studyGroups->map(fn($sg) => ['id' => $sg->id, 'name' => $sg->name, 'level' => $sg->gradeLevel->level ?? 0])->toArray()) !!};

// Build grade columns for JS rendering
const gradeCols = studyGroups.map(sg => ({ id: sg.id, name: sg.name, level: sg.level }));

let activeSubjects = {}; // tid -> [{sid, name}]
let activeTasks = {};   // tid -> [{name, hours, code, studyGroupId}]
let teacherCounter = 0;

// Initialize — no subjects active yet, user adds them via (+) per teacher row
// Build column HTML once
function sgCells() {
    return gradeCols.map(() => `<td class="text-center"></td>`).join('');
}

// Add teacher row to matrix
function addTeacherToMatrix() {
    const sel = document.getElementById('newTeacherSelect');
    const tid = sel.value;
    if (!tid) { alert('Pilih guru terlebih dahulu.'); return; }
    if (document.getElementById(`teacher-row-${tid}`)) {
        alert('Guru sudah ada di tabel.');
        return;
    }

    const teacher = teachers.find(t => t.id === tid);
    if (!teacher) return;

    teacherCounter++;
    activeSubjects[tid] = [];

    const tbody = document.getElementById('matrixBody');
    // Remove empty state row if present
    const emptyRow = tbody.querySelector('tr td[colspan]');
    if (emptyRow) tbody.innerHTML = '';

    const tr = document.createElement('tr');
    tr.id = `teacher-row-${tid}`;
    tr.innerHTML = `
        <td class="text-center" style="width:25px; border-right:1px solid #dee2e6; background:#f8f9fa; font-weight:600;">${teacherCounter}</td>
        <td style="border-right:1px solid #dee2e6; background:#f8f9fa;">
            <button type="button" class="btn btn-sm p-0" style="color:#555;" onclick="showAddSubject('${tid}')" title="Tambah Mapel">
                <i class="ri-add-circle-line me-1"></i>
            </button>
            <span style="font-weight:600; font-size:12px;">${teacher.name}</span>
        </td>
        ${sgCells()}
        <td class="text-center" style="background:#e8e8e8; width:48px; font-size:11px;" id="teacher-sebaran-${tid}">0</td>
        <td class="text-center" style="background:#e8e8e8; width:48px; font-size:11px;" id="teacher-tugas-${tid}"></td>
        <td class="text-center" style="background:#e8e8e8; width:44px; font-size:11px; font-weight:700;" id="teacher-total-${tid}">0</td>
        <td style="width:26px;">
            <button type="button" class="btn btn-sm p-0" style="color:#aaa;" onclick="removeTeacher('${tid}')" title="Hapus Guru">
                <i class="ri-delete-bin-line"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);

    // Remove from dropdown
    sel.querySelector(`option[value="${tid}"]`)?.remove();
    document.getElementById('teacherCount').textContent = teacherCounter;

    // Clear selection
    sel.value = '';
}

// Render subject row for a teacher
function renderSubjectRow(tid, sid, sname) {
    const tbody = document.getElementById('matrixBody');
    const tr = document.createElement('tr');
    tr.id = `sub-row-${tid}-${sid}`;
    tr.setAttribute('data-teacher', tid);
    tr.setAttribute('data-subject', sid);
    tr.style = 'background:#fff;';
    tr.innerHTML = `
        <td style="width:25px; border-right:1px solid #dee2e6;"></td>
        <td style="border-right:1px solid #dee2e6; padding-left:20px; font-size:11px; color:#555;">
            <span id="sub-label-${tid}-${sid}">${sname}</span>
        </td>
        ${gradeCols.map(sg => `
            <td class="col-jp">
                <input type="number" min="0" max="20" value="" placeholder="" class="col-jp-input"
                       data-tid="${tid}" data-sid="${sid}" data-sgid="${sg.id}"
                       oninput="updateHours(this)">
            </td>
        `).join('')}
        <td class="text-center" style="background:#e8e8e8; width:48px; font-size:11px;" id="sebaran-${tid}-${sid}">0</td>
        <td class="text-center" style="background:#e8e8e8; width:48px;"></td>
        <td class="text-center" style="background:#e8e8e8; width:44px; font-size:11px; font-weight:600;" id="subtotal-${tid}-${sid}">0</td>
        <td style="width:26px;">
            <button type="button" class="btn btn-sm p-0" style="color:#aaa;" onclick="removeSubjectRow('${tid}', '${sid}')">
                <i class="ri-delete-bin-line"></i>
            </button>
        </td>
    `;

    // Insert after teacher row
    const teacherRow = document.getElementById(`teacher-row-${tid}`);
    if (teacherRow) teacherRow.after(tr);
    else tbody.appendChild(tr);
}

// Show add subject modal for a teacher
function showAddSubject(tid) {
    const teacher = teachers.find(t => t.id === tid);
    document.getElementById('addSubjectTeacherLabel').textContent =
        'Pilih Mapel untuk — ' + (teacher?.name || tid);
    const used = (activeSubjects[tid] || []).map(s => s.id);
    const list = document.getElementById('subjectList');
    list.innerHTML = subjects.map(s => `
        <div class="form-check">
            <input class="form-check-input subject-check" type="checkbox" value="${s.id}" id="sub-${s.id}"
                   ${used.includes(s.id) ? 'checked disabled' : ''}>
            <label class="form-check-label" for="sub-${s.id}" style="font-size:13px;">
                ${s.name} ${used.includes(s.id) ? '<span class="text-muted" style="font-size:10px;"> (sudah)</span>' : ''}
            </label>
        </div>
    `).join('');
    window._addingTeacherId = tid;
    new bootstrap.Modal(document.getElementById('addSubjectModal')).show();
}

function confirmAddSubjects() {
    const tid = window._addingTeacherId;
    if (!tid) return;
    const checks = document.querySelectorAll('.subject-check:not(:disabled):checked');
    checks.forEach(c => {
        const sid = c.value;
        const sub = subjects.find(s => s.id === sid);
        if (sub && !(activeSubjects[tid] || []).find(s => s.id === sid)) {
            activeSubjects[tid].push({ id: sid, name: sub.name });
            renderSubjectRow(tid, sid, sub.name);
        }
    });
    bootstrap.Modal.getInstance(document.getElementById('addSubjectModal')).hide();
    refreshTotal(tid);
}

// Update hours on input
function updateHours(input) {
    const tid = input.dataset.tid;
    const sid = input.dataset.sid;
    const val = input.value;
    input.className = 'col-jp-input' + (val ? ' has-val' : '');
    const row = input.closest('tr');
    const inputs = row.querySelectorAll('.col-jp-input');
    let sebaran = 0;
    inputs.forEach(i => { if (i.value) sebaran += parseInt(i.value); });
    document.getElementById(`sebaran-${tid}-${sid}`).textContent = sebaran;
    document.getElementById(`subtotal-${tid}-${sid}`).textContent = sebaran;
    refreshTotal(tid);
}

function refreshTotal(tid) {
    const rows = document.querySelectorAll(`tr[data-teacher="${tid}"]`);
    let sebaran = 0;
    rows.forEach(r => {
        r.querySelectorAll('.col-jp-input').forEach(i => { if (i.value) sebaran += parseInt(i.value); });
    });
    const taskHours = (activeTasks[tid] || []).reduce((sum, t) => sum + (parseInt(t.hours) || 0), 0);
    const el = document.getElementById(`teacher-total-${tid}`);
    if (el) el.textContent = sebaran + taskHours;
    const seb = document.getElementById(`teacher-sebaran-${tid}`);
    if (seb) seb.textContent = sebaran;
    const tug = document.getElementById(`teacher-tugas-${tid}`);
    if (tug) tug.textContent = taskHours ? taskHours + ' JP' : '';
}

// Add task from modal
function showAddTask() {
    new bootstrap.Modal(document.getElementById('addTaskModal')).show();
}

function addTaskFromModal() {
    const tid = document.getElementById('taskTeacherSelect').value;
    const name = document.getElementById('taskName').value;
    const code = document.getElementById('taskCode').value;
    const hours = parseInt(document.getElementById('taskHours').value) || 0;
    if (!tid || !name) { alert('Pilih guru dan isi nama tugas.'); return; }

    // Make sure teacher row exists
    if (!document.getElementById(`teacher-row-${tid}`)) {
        // Auto-add teacher first
        const teacher = teachers.find(t => t.id === tid);
        if (!teacher) { alert('Guru tidak ditemukan.'); return; }
        teacherCounter++;
        activeSubjects[tid] = [];
        const tbody = document.getElementById('matrixBody');
        const emptyRow = tbody.querySelector('tr td[colspan]');
        if (emptyRow) tbody.innerHTML = '';
        const tr = document.createElement('tr');
        tr.id = `teacher-row-${tid}`;
        tr.innerHTML = `
            <td class="text-center" style="width:25px; border-right:1px solid #dee2e6; background:#f8f9fa; font-weight:600;">${teacherCounter}</td>
            <td style="border-right:1px solid #dee2e6; background:#f8f9fa;">
                <button type="button" class="btn btn-sm p-0" style="color:#555;" onclick="showAddSubject('${tid}')" title="Tambah Mapel">
                    <i class="ri-add-circle-line me-1"></i>
                </button>
                <span style="font-weight:600; font-size:12px;">${teacher.name}</span>
            </td>
            ${sgCells()}
            <td class="text-center" style="background:#e8e8e8; width:48px; font-size:11px;" id="teacher-sebaran-${tid}">0</td>
            <td class="text-center" style="background:#e8e8e8; width:48px; font-size:11px;" id="teacher-tugas-${tid}"></td>
            <td class="text-center" style="background:#e8e8e8; width:44px; font-size:11px; font-weight:700;" id="teacher-total-${tid}">0</td>
            <td style="width:26px;">
                <button type="button" class="btn btn-sm p-0" style="color:#aaa;" onclick="removeTeacher('${tid}')" title="Hapus Guru">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    if (!activeTasks[tid]) activeTasks[tid] = [];
    activeTasks[tid].push({ name, code, hours, studyGroupId: null });

    // Render task row
    const taskRowsEl = document.getElementById(`task-rows-${tid}`) || createTaskContainer(tid);
    const rowId = `task-row-${tid}-${Date.now()}`;
    const tr = document.createElement('tr');
    tr.id = rowId;
    tr.style = 'background:#f0f0f0; border-top:1px dashed #ccc;';
    tr.innerHTML = `
        <td style="width:25px; border-right:1px solid #dee2e6;"></td>
        <td style="border-right:1px solid #dee2e6; padding-left:20px; font-size:11px; font-style:italic; color:#666;">${name}</td>
        ${sgCells()}
        <td class="text-center" style="background:#e8e8e8; width:48px;"></td>
        <td class="text-center" style="background:#e8e8e8; width:48px; font-weight:600; font-size:11px;">${hours}</td>
        <td class="text-center" style="background:#e8e8e8; width:44px; font-weight:700; font-size:11px;">${hours}</td>
        <td style="width:26px;">
            <button type="button" class="btn btn-sm p-0" style="color:#aaa;" onclick="removeTask('${tid}', '${rowId}')">
                <i class="ri-delete-bin-line"></i>
            </button>
        </td>
    `;
    document.querySelector(`#teacher-row-${tid}`)?.after(tr);

    refreshTotal(tid);
    document.getElementById('taskName').value = '';
    document.getElementById('taskCode').value = '';
    document.getElementById('taskHours').value = '3';
    bootstrap.Modal.getInstance(document.getElementById('addTaskModal')).hide();
}

function createTaskContainer(tid) {
    const cont = document.createElement('tbody');
    cont.id = `task-rows-${tid}`;
    document.getElementById(`teacher-row-${tid}`)?.after(cont);
    return cont;
}

function removeTask(tid, rowId) {
    document.getElementById(rowId)?.remove();
    // Remove from activeTasks
    if (activeTasks[tid]) {
        const idx = activeTasks[tid].findIndex(t => rowId.endsWith(String(Date.now() - parseInt(rowId.split('-').pop()))));
    }
    refreshTotal(tid);
}

// Remove subject row
function removeSubjectRow(tid, sid) {
    const row = document.getElementById(`sub-row-${tid}-${sid}`);
    if (row) row.remove();
    if (activeSubjects[tid]) activeSubjects[tid] = activeSubjects[tid].filter(s => s.id !== sid);
    refreshTotal(tid);
}

// Remove teacher row
function removeTeacher(tid) {
    if (!confirm('Hapus guru ini beserta semua barisnya?')) return;
    document.getElementById(`teacher-row-${tid}`)?.remove();
    document.querySelectorAll(`tr[data-teacher="${tid}"]`).forEach(r => r.remove());
    document.getElementById(`task-rows-${tid}`)?.remove();
    // Restore to dropdown
    const teacher = teachers.find(t => t.id === tid);
    if (teacher) {
        const opt = document.createElement('option');
        opt.value = tid;
        opt.textContent = teacher.name;
        document.getElementById('newTeacherSelect').appendChild(opt);
    }
    // Update counter
    const remaining = document.querySelectorAll('tr[id^="teacher-row-"]').length;
    document.getElementById('teacherCount').textContent = remaining;
    teacherCounter = remaining;
}

// Form submit — build assignments_json
document.getElementById('decreeForm').addEventListener('submit', function() {
    const assignments = {};
    document.querySelectorAll('tr[id^="sub-row-"]').forEach(row => {
        const tid = row.dataset.teacher;
        const sid = row.dataset.subject;
        if (!tid || !sid) return;
        const inputs = row.querySelectorAll('.col-jp-input');
        let hasValue = false;
        const groupHours = {};
        inputs.forEach(inp => {
            if (inp.value) { groupHours[inp.dataset.sgid] = parseInt(inp.value); hasValue = true; }
        });
        if (hasValue) {
            if (!assignments[tid]) assignments[tid] = {};
            assignments[tid][sid] = groupHours;
        }
    });
    document.getElementById('assignmentsJson').value = JSON.stringify(assignments);
    document.getElementById('otherTasksJson').value = JSON.stringify(
        Object.values(activeTasks).flatMap((tasks, tid) =>
            tasks.map(t => ({ teacher_id: tid, task_name: t.name, task_code: t.code, weekly_hours: t.hours }))
        )
    );
});

// Update info preview when form fields change
document.querySelectorAll('#decreeForm input, #decreeForm select').forEach(el => {
    el.addEventListener('change', updateInfoPreview);
});
updateInfoPreview();

function updateInfoPreview() {
    const schoolEl = document.getElementById('schoolSelect');
    const school = schoolEl
        ? schoolEl.selectedOptions[0]?.text || '-'
        : (document.querySelector('[name="school_id"]')?.closest('.col-md-3')?.querySelector('input[readonly]')?.value || '-');
    const ay = document.getElementById('aySelect').selectedOptions[0]?.text || '-';
    const num = document.querySelector('[name="decree_number"]')?.value || '-';
    const title = document.querySelector('[name="title"]')?.value || '-';
    const date = document.querySelector('[name="issued_date"]')?.value || '-';
    document.getElementById('infoSchool').textContent = school;
    document.getElementById('infoNomor').innerHTML = `NOMOR&nbsp;&nbsp;: <strong>${num}</strong>`;
    document.getElementById('infoTA').innerHTML = `TA <strong>${ay}</strong>`;
    document.getElementById('infoTitle').innerHTML = `TENTANG : <strong>${title}</strong>`;
    if (date) {
        const d = new Date(date);
        const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        document.getElementById('infoTanggal').innerHTML = `TANGGAL : <strong>${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}</strong>`;
    }
}

// Show add subject modal for a teacher
// (see renderSubjectRow() and confirmAddSubjects() above)
function updateHours(input) {
    const tid = input.dataset.tid;
    const sid = input.dataset.sid;
    const val = input.value;
    input.className = 'col-jp-input' + (val ? ' has-val' : '');
    // Update sebaran
    const row = input.closest('tr');
    const inputs = row.querySelectorAll('.col-jp-input');
    let sebaran = 0;
    inputs.forEach(i => { if (i.value) sebaran += parseInt(i.value); });
    document.getElementById(`sebaran-${tid}-${sid}`).textContent = sebaran;
    document.getElementById(`subtotal-${tid}-${sid}`).textContent = sebaran;
    refreshTotal(tid);
}

function refreshTotal(tid) {
    const rows = document.querySelectorAll(`tr[data-teacher="${tid}"]`);
    let sebaran = 0;
    rows.forEach(r => {
        r.querySelectorAll('.col-jp-input').forEach(i => { if (i.value) sebaran += parseInt(i.value); });
    });
    const taskHours = (activeTasks[tid] || []).reduce((sum, t) => sum + (parseInt(t.hours) || 0), 0);
    // Update via named IDs
    const el = document.getElementById(`teacher-total-${tid}`);
    if (el) el.textContent = sebaran + taskHours;
    const seb = document.getElementById(`teacher-sebaran-${tid}`);
    if (seb) seb.textContent = sebaran;
    const tug = document.getElementById(`teacher-tugas-${tid}`);
    if (tug) tug.textContent = taskHours ? taskHours + ' JP' : '';
}

// Add task from modal
function showAddTask() {
    new bootstrap.Modal(document.getElementById('addTaskModal')).show();
}

function addTaskFromModal() {
    const tid = document.getElementById('taskTeacherSelect').value;
    const name = document.getElementById('taskName').value;
    const code = document.getElementById('taskCode').value;
    const hours = parseInt(document.getElementById('taskHours').value) || 0;
    if (!tid || !name) { alert('Pilih guru dan isi nama tugas.'); return; }

    if (!activeTasks[tid]) activeTasks[tid] = [];
    activeTasks[tid].push({ name, code, hours, studyGroupId: null });

    // Render task row
    const taskRowsEl = document.getElementById(`task-rows-${tid}`);
    const rowId = `task-row-${tid}-${Date.now()}`;
    const tr = document.createElement('tr');
    tr.id = rowId;
    tr.className = 'task-row';
    tr.innerHTML = `
        <td style="width:25px;"></td>
        <td style="padding-left:16px; border-right:1px solid #dee2e6;">
            <span style="font-size:11px; color:#8b5e00;">${name}</span>
        </td>
        ${studyGroups.map(() => `<td style="background:#fffdf5;"></td>`).join('')}
        <td class="text-center total-col total-sebaran" style="width:48px;"></td>
        <td class="text-center total-col total-tugas"  style="width:48px; font-weight:700; font-size:12px;">${hours}</td>
        <td class="text-center total-col total-all"   style="width:44px; font-weight:700; font-size:12px;">${hours}</td>
        <td style="width:26px; border-left:1px solid #dee2e6;">
            <button type="button" class="btn btn-sm p-0 del-btn" onclick="removeTask('${tid}', '${rowId}')">
                <i class="ri-delete-bin-line"></i>
            </button>
        </td>
    `;
    taskRowsEl.appendChild(tr);
    refreshTotal(tid);

    // Clear form
    document.getElementById('taskName').value = '';
    document.getElementById('taskCode').value = '';
    document.getElementById('taskHours').value = '3';
    bootstrap.Modal.getInstance(document.getElementById('addTaskModal')).hide();
}

function removeTask(tid, rowId) {
    document.getElementById(rowId)?.remove();
    refreshTotal(tid);
}

// Remove subject row
function removeSubjectRow(tid, sid) {
    const row = document.getElementById(`sub-row-${tid}-${sid}`);
    if (row) row.remove();
    if (activeSubjects[tid]) activeSubjects[tid] = activeSubjects[tid].filter(s => s.id !== sid);
    refreshTotal(tid);
}

// Remove teacher row + restore to dropdown
function removeTeacher(tid) {
    if (!confirm('Hapus guru ini beserta semua barisnya?')) return;
    document.getElementById(`teacher-row-${tid}`)?.remove();
    document.querySelectorAll(`tr[data-teacher="${tid}"]`).forEach(r => r.remove());
    document.getElementById(`task-rows-${tid}`)?.remove();
    const teacher = teachers.find(t => t.id === tid);
    if (teacher) {
        const opt = document.createElement('option');
        opt.value = tid;
        opt.textContent = teacher.name;
        document.getElementById('newTeacherSelect').appendChild(opt);
    }
    const remaining = document.querySelectorAll('tr[id^="teacher-row-"]').length;
    document.getElementById('teacherCount').textContent = remaining;
    teacherCounter = remaining;
}

// Form submit — build assignments_json
document.getElementById('decreeForm').addEventListener('submit', function() {
    const assignments = {};
    document.querySelectorAll('tr[id^="sub-row-"]').forEach(row => {
        const tid = row.dataset.teacher;
        const sid = row.dataset.subject;
        if (!tid || !sid) return;
        const inputs = row.querySelectorAll('.col-jp-input');
        let hasValue = false;
        const groupHours = {};
        inputs.forEach(inp => {
            if (inp.value) { groupHours[inp.dataset.sgid] = parseInt(inp.value); hasValue = true; }
        });
        if (hasValue) {
            if (!assignments[tid]) assignments[tid] = {};
            assignments[tid][sid] = groupHours;
        }
    });
    document.getElementById('assignmentsJson').value = JSON.stringify(assignments);
    document.getElementById('otherTasksJson').value = JSON.stringify(
        Object.values(activeTasks).flatMap(([tid, tasks]) =>
            tasks.map(t => ({ teacher_id: tid, task_name: t.name, task_code: t.code, weekly_hours: t.hours }))
        )
    );
});
</script>
@endsection