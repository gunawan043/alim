@extends('layouts.master')
@section('title') Tambah Surat Keputusan @endsection


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
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-primary-subtle text-primary">
                                <i class="ri-group-line me-1"></i><span id="teacherCount">0</span> GTK
                            </span>
                            <span class="badge bg-info-subtle text-info">
                                <i class="ri-stack-line me-1"></i>{{ $studyGroups->count() }} Rombel
                            </span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center ms-auto">
                            <button type="button" class="btn btn-sm btn-icon btn-soft-secondary" id="undoBtn" onclick="undoTable()" disabled title="Undo">
                                <i class="ri-arrow-go-back-line"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-soft-secondary" id="redoBtn" onclick="redoTable()" disabled title="Redo">
                                <i class="ri-arrow-go-forward-line"></i>
                            </button>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="ri-search-line"></i></span>
                                <input type="search" id="rosterSearch" class="form-control" placeholder="Cari nama, jabatan, atau mapel..." size="28" oninput="filterRoster()">
                            </div>
                            <select id="newTeacherSelect" class="d-none"><option value="">-- Pilih Guru --</option>@foreach($teachers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
                            <button type="button" class="btn btn-sm btn-soft-primary" onclick="showAddTask()">
                                <i class="ri-user-settings-line me-1"></i> Tugas Tambahan
                            </button>
                        </div>
                    </div>
                    <div class="alert alert-info border-0 rounded-0 py-2 mb-0">
                        <i class="ri-information-line me-1"></i>
                        Ubah jabatan melalui dropdown, ganti mapel pada kolom Mapel/Tugas, dan isi JP pada kolom rombel. Total jam dihitung otomatis.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm align-middle mb-0">
                            <thead class="table-light text-center align-middle position-sticky top-0 z-1">
                                <tr>
                                    <th rowspan="2" class="text-nowrap">No</th>
                                    <th rowspan="2" class="text-nowrap">Nama</th>
                                    <th rowspan="2" class="text-wrap">Jabatan</th>
                                    <th rowspan="2" class="text-wrap">Mapel/Tugas</th>
                                    <th rowspan="2" class="text-wrap">Tugas Tambahan</th>
                                    @foreach($byGrade as $level => $groups)
                                        <th colspan="{{ $groups->count() }}" class="text-nowrap">
                                            {{ $groups->first()->gradeLevel->name ?? "Kelas $level" }}
                                        </th>
                                    @endforeach
                                    <th rowspan="2" class="text-nowrap">JP</th>
                                    <th rowspan="2" class="text-nowrap">Total Jam</th>
                                    <th rowspan="2"></th>
                                </tr>
                                <tr>
                                    @foreach($byGrade as $level => $groups)
                                        @foreach($groups as $sg)
                                            <th class="text-nowrap">{{ $sg->name }}</th>
                                        @endforeach
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody id="matrixBody">
                                <tr>
                                    <td colspan="30" class="text-center text-muted py-4">
                                        <i class="ri-team-line fs-3 d-block mb-2"></i>
                                        Belum ada GTK pada satuan kerja ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Submit --}}
        <div class="d-flex justify-content-end gap-2 sticky-bottom bg-body border-top py-2 z-3">
            <a href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
            <button type="submit" class="btn btn-success">
                <i class="ri-save-line me-1"></i> Simpan SK
            </button>
        </div>

        <input type="hidden" name="assignments_json" id="assignmentsJson" value="">
        <input type="hidden" name="other_tasks_json" id="otherTasksJson" value="">
        <input type="hidden" name="positions_json" id="positionsJson" value="">
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
                        <label class="form-label">Jenis Tugas</label>
                        <select id="taskTypeSelect" class="form-control" onchange="toggleTaskFields()">
                            <option value="wali_kelas">Wali Kelas</option>
                            <option value="koordinator">Koordinator Mata Pelajaran</option>
                            <option value="kesiswaan">Kesiswaan / PD / PO</option>
                            <option value="custom">Tugas Lain (Custom)</option>
                        </select>
                    </div>

                    {{-- Rombel select (shown when Wali Kelas) --}}
                    <div class="mb-2" id="rombelSelectWrap">
                        <label class="form-label">Rombel</label>
                        <select id="taskRombelSelect" class="form-control">
                            <option value="">-- Pilih Rombel --</option>
                            @foreach($studyGroups as $sg)
                                <option value="{{ $sg->id }}">{{ $sg->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Custom task fields (shown when custom) --}}
                    <div id="customTaskWrap" style="display:none;">
                        <div class="mb-2">
                            <label class="form-label">Nama Tugas</label>
                            <input type="text" id="taskName" class="form-control" placeholder="cth: Wali Kelas 7A, Koordinator Bahasa">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Jam per Minggu</label>
                            <input type="number" id="taskHours" class="form-control" value="3" min="0" max="40">
                        </div>
                    </div>

                    {{-- Kordinator mapel select (shown when koordinator) --}}
                    <div class="mb-2" id="koordinatorMapelWrap" style="display:none;">
                        <label class="form-label">Mata Pelajaran</label>
                        <select id="koordinatorMapelSelect" class="form-control">
                            <option value="">-- Pilih Mapel --</option>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="addTaskFromModal()">
                        <i class="ri-add-line me-1"></i> Tambah
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: pilih rombel untuk Wali Kelas --}}
    <div class="modal fade" id="taskRombelPickerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-door-open-line me-2"></i>Pilih Rombel Wali Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Rombel</label>
                    <select id="taskRombelPickerSelect" class="form-select">
                        <option value="">-- Pilih Rombel --</option>
                        @foreach($studyGroups as $sg)
                            <option value="{{ $sg->id }}">{{ $sg->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="confirmTaskRombel()">
                        <i class="ri-check-line me-1"></i> Tambahkan
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
const currentAssignments = {!! json_encode($currentAssignments) !!};
const currentTasks = {!! json_encode($currentTasks) !!};
const currentPositions = {!! json_encode($currentPositions) !!};
const currentPositionIds = {!! json_encode($currentPositionIds) !!};
const positionOptions = {!! json_encode($positionOptions) !!};
const jpDefaults = {!! json_encode($jpDefaults) !!};
const taskTypeOptions = {!! json_encode($taskTypeOptions) !!};

// Build grade columns for JS rendering
const gradeCols = studyGroups.map(sg => ({ id: sg.id, name: sg.name, level: sg.level }));

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
}
function positionOptionsHtml(tid) {
    const current = currentPositionIds[tid] || '';
    const currentName = currentPositions[tid] || '';
    const hasCurrent = positionOptions.some(p => p.id === current);
    const keep = (!hasCurrent && currentName && currentName !== 'GTK')
        ? `<option value="__keep__" selected>${escapeHtml(currentName)}</option>`
        : '';
    return '<option value="">GTK</option>' + keep + positionOptions.map(p =>
        `<option value="${p.id}" ${current === p.id ? 'selected' : ''}>${escapeHtml(p.name)}</option>`
    ).join('');
}
function subjectOptionsHtml(sid) {
    return subjects.map(s => `<option value="${s.id}" ${s.id === sid ? 'selected' : ''}>${escapeHtml(s.name)}</option>`).join('');
}
function taskOptionsHtml() {
    return '<option value="">+ Tambah tugas...</option>' + taskTypeOptions.map(t =>
        `<option value="${t.id}">${escapeHtml(t.nama)}</option>`
    ).join('');
}

let activeSubjects = {}; // tid -> [{sid, name}]
let activeTasks = {};   // tid -> [{name, hours, code, studyGroupId}]
let teacherCounter = 0;

// Initialize — no subjects active yet, user adds them via (+) per teacher row
// Build column HTML once
function sgCells() {
    return gradeCols.map(() => `<td class="text-center"></td>`).join('');
}

// Add teacher row to matrix
function addTeacherToMatrix(teacherId) {
    const sel = document.getElementById('newTeacherSelect');
    const tid = teacherId || sel.value;
    if (!tid) { alert('Pilih guru terlebih dahulu.'); return; }
    if (document.getElementById(`teacher-row-${tid}`)) {
        alert('Guru sudah ada di tabel.');
        return;
    }

    const teacher = teachers.find(t => t.id === tid);
    if (!teacher) return;

    teacherCounter++;
    activeSubjects[tid] = [];
    activeTasks[tid] = (currentTasks[tid] || []).slice();

    const tbody = document.getElementById('matrixBody');
    // Remove empty state row if present
    const emptyRow = tbody.querySelector('tr td[colspan]');
    if (emptyRow) tbody.innerHTML = '';

    const tr = document.createElement('tr');
    tr.id = `teacher-row-${tid}`;
    tr.className = 'table-light';
    tr.dataset.teacher = tid;
    tr.innerHTML = `
        <td class="text-center fw-semibold">${teacherCounter}</td>
        <td class="fw-semibold text-nowrap">${escapeHtml(teacher.name)}</td>
        <td class="text-wrap">
            <select class="form-select form-select-sm position-select border-0 bg-transparent shadow-none w-auto" data-teacher-id="${tid}" onchange="changePosition(this)">
                ${positionOptionsHtml(tid)}
            </select>
        </td>
        <td class="text-nowrap">
            <button type="button" class="btn btn-sm btn-soft-primary" onclick="showAddSubject('${tid}')" title="Tambah Mapel">
                <i class="ri-add-line me-1"></i>Mapel
            </button>
        </td>
        <td class="text-wrap" id="teacher-tugas-${tid}">
            <div id="task-badges-${tid}" class="d-flex flex-wrap gap-1 mb-1"></div>
            <select class="form-select form-select-sm border-0 bg-transparent shadow-none text-muted w-auto" data-teacher-id="${tid}" onchange="addTaskFromSelect(this)">
                ${taskOptionsHtml()}
            </select>
        </td>
        ${sgCells()}
        <td class="text-center fw-semibold" id="teacher-sebaran-${tid}">0</td>
        <td class="text-center fw-bold" id="teacher-total-${tid}">0</td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-icon btn-soft-danger" onclick="removeTeacher('${tid}')" title="Hapus GTK">
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
    tr.innerHTML = `
        <td></td>
        <td></td>
        <td></td>
        <td class="text-wrap">
            <div class="d-flex align-items-center gap-1">
                <select class="form-select form-select-sm subject-select border-0 bg-transparent shadow-none w-auto" data-old-sid="${sid}" onchange="changeSubject(this)">
                    ${subjectOptionsHtml(sid)}
                </select>
                <button type="button" class="btn btn-sm btn-icon btn-soft-danger" onclick="removeSubjectRowByButton(this)" title="Hapus Mapel">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </td>
        <td></td>
        ${gradeCols.map(sg => `
            <td class="text-center">
                <label class="form-check d-inline-flex align-items-center gap-1 m-0">
                    <input type="checkbox" class="form-check-input rounded-circle rombel-toggle m-0"
                           data-tid="${tid}" data-sid="${sid}" data-sgid="${sg.id}"
                           data-hours="${jpDefaults[`${sid}|${sg.id}`] ?? ''}"
                           data-default="${jpDefaults[`${sid}|${sg.id}`] ?? ''}"
                           onchange="toggleRombel(this)" title="Pilih rombel">
                    <span class="jp-badge small fw-semibold text-success d-none">${jpDefaults[`${sid}|${sg.id}`] ?? ''} JP</span>
                </label>
            </td>
        `).join('')}
        <td class="text-center fw-semibold" id="sebaran-${tid}-${sid}">0</td>
        <td class="text-center fw-semibold" id="subtotal-${tid}-${sid}">0</td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-icon btn-soft-danger" onclick="removeSubjectRowByButton(this)" title="Hapus Mapel">
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

function subjectIsTahfidz(sid) {
    const subject = subjects.find(s => s.id === sid);
    return (subject?.name || '').toLowerCase().includes('tahfidz');
}

function subjectRombelTaken(toggle, sid, sgid) {
    if (subjectIsTahfidz(sid)) return false;
    return Array.from(document.querySelectorAll('.rombel-toggle:checked')).some(other =>
        other !== toggle && other.dataset.sid === sid && other.dataset.sgid === sgid
    );
}

function updateSubjectRow(row) {
    const tid = row.dataset.teacher;
    const sid = row.dataset.subject;
    let total = 0;
    row.querySelectorAll('.rombel-toggle:checked').forEach(toggle => {
        total += parseFloat(toggle.dataset.hours) || 0;
    });
    document.getElementById(`sebaran-${tid}-${sid}`).textContent = total;
    document.getElementById(`subtotal-${tid}-${sid}`).textContent = total;
    refreshTotal(tid);
}

function toggleRombel(toggle) {
    const sid = toggle.dataset.sid;
    const sgid = toggle.dataset.sgid;

    if (toggle.checked && subjectRombelTaken(toggle, sid, sgid)) {
        toggle.checked = false;
        toggle.dataset.active = '0';
        const badge = toggle.closest('td')?.querySelector('.jp-badge');
        if (badge) badge.classList.add('d-none');
        updateSubjectRow(toggle.closest('tr'));
        alert('Mapel dan rombel ini sudah dipakai GTK lain. Khusus Tahfidz boleh lebih dari satu.');
        return;
    }

    const active = toggle.checked;
    toggle.dataset.active = active ? '1' : '0';
    const badge = toggle.closest('td')?.querySelector('.jp-badge');
    if (badge) {
        badge.textContent = `${toggle.dataset.hours || 0} JP`;
        badge.classList.toggle('d-none', !active);
    }
    updateSubjectRow(toggle.closest('tr'));
}

function addActiveTask(tid, type, extra = {}) {
    const code = extra.code || type.kode || type.id;
    const studyGroupId = extra.studyGroupId || null;
    activeTasks[tid] = activeTasks[tid] || [];

    const duplicate = activeTasks[tid].some(t => {
        if ((t.code || t.name) !== code) return false;
        if (!studyGroupId) return true;
        return t.studyGroupId && t.studyGroupId === studyGroupId;
    });
    if (duplicate) {
        alert('Tugas tersebut sudah ada untuk GTK ini.');
        return false;
    }

    activeTasks[tid].push({
        name: extra.name || type.nama,
        code,
        hours: extra.hours ?? 3,
        studyGroupId,
        rombel: extra.rombel || null,
    });
    refreshTotal(tid);
    return true;
}

function addTaskFromSelect(select) {
    const tid = select.dataset.teacherId;
    const type = taskTypeOptions.find(t => t.id === select.value);
    if (!tid || !type) return;

    const isWaliKelas = type.kode === 'wali-kelas' || /wali kelas/i.test(type.nama || '');
    if (isWaliKelas) {
        window._pendingTask = { tid, type };
        document.getElementById('taskRombelPickerSelect').value = '';
        new bootstrap.Modal(document.getElementById('taskRombelPickerModal')).show();
        select.value = '';
        return;
    }

    addActiveTask(tid, type);
    select.value = '';
}

function confirmTaskRombel() {
    const select = document.getElementById('taskRombelPickerSelect');
    const rombel = studyGroups.find(sg => sg.id === select.value);
    const pending = window._pendingTask;
    if (!pending || !rombel) {
        alert('Pilih rombel terlebih dahulu.');
        return;
    }

    addActiveTask(pending.tid, pending.type, {
        name: `Wali Kelas ${rombel.name}`,
        code: 'wali-kelas',
        hours: 0,
        studyGroupId: rombel.id,
        rombel: rombel.name,
    });
    window._pendingTask = null;
    bootstrap.Modal.getInstance(document.getElementById('taskRombelPickerModal')).hide();
}

let undoStack = [];
let redoStack = [];

function captureTableState() {
    const body = document.getElementById('matrixBody');
    return {
        html: body.innerHTML,
        subjects: JSON.parse(JSON.stringify(activeSubjects)),
        tasks: JSON.parse(JSON.stringify(activeTasks)),
        counter: teacherCounter,
        teacherCount: document.getElementById('teacherCount')?.textContent || '0',
        selects: Array.from(body.querySelectorAll('select')).map(select => select.value),
        toggles: Array.from(body.querySelectorAll('.rombel-toggle')).map(toggle => ({
            checked: toggle.checked,
            active: toggle.dataset.active || '',
            hours: toggle.dataset.hours || '',
        })),
    };
}

function restoreTableState(state) {
    const body = document.getElementById('matrixBody');
    body.innerHTML = state.html;
    body.querySelectorAll('select').forEach((select, index) => {
        if (state.selects[index] !== undefined) select.value = state.selects[index];
    });
    body.querySelectorAll('.rombel-toggle').forEach((toggle, index) => {
        const snapshot = state.toggles[index];
        if (!snapshot) return;
        toggle.checked = snapshot.checked;
        toggle.dataset.active = snapshot.active;
        if (snapshot.hours !== '') toggle.dataset.hours = snapshot.hours;
        const badge = toggle.closest('td')?.querySelector('.jp-badge');
        if (badge) {
            badge.textContent = `${toggle.dataset.hours || 0} JP`;
            badge.classList.toggle('d-none', !toggle.checked);
        }
    });
    activeSubjects = state.subjects;
    activeTasks = state.tasks;
    teacherCounter = state.counter;
    document.getElementById('teacherCount').textContent = state.teacherCount;
    refreshAllTotals();
    updateHistoryButtons();
}

function refreshAllTotals() {
    document.querySelectorAll('tr[id^="teacher-row-"]').forEach(row => {
        refreshTotal(row.id.replace('teacher-row-', ''));
    });
}

function updateHistoryButtons() {
    const undo = document.getElementById('undoBtn');
    const redo = document.getElementById('redoBtn');
    if (undo) undo.disabled = undoStack.length === 0;
    if (redo) redo.disabled = redoStack.length === 0;
}

function pushHistory() {
    if (!document.getElementById('matrixBody')) return;
    undoStack.push(captureTableState());
    if (undoStack.length > 25) undoStack.shift();
    redoStack = [];
    updateHistoryButtons();
}

function undoTable() {
    if (!undoStack.length) return;
    redoStack.push(captureTableState());
    restoreTableState(undoStack.pop());
}

function redoTable() {
    if (!redoStack.length) return;
    undoStack.push(captureTableState());
    restoreTableState(redoStack.pop());
}

document.addEventListener('keydown', function (event) {
    if (!(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 'z') return;
    if (event.target.closest('input, select, textarea')) return;
    event.preventDefault();
    event.shiftKey ? redoTable() : undoTable();
});

function filterRoster() {
    const query = (document.getElementById('rosterSearch')?.value || '').trim().toLowerCase();
    document.querySelectorAll('tr[id^="teacher-row-"]').forEach(teacherRow => {
        const tid = teacherRow.id.replace('teacher-row-', '');
        const rows = [teacherRow, ...document.querySelectorAll(`tr[data-teacher="${tid}"]`)];
        const text = rows.map(row => row.textContent).join(' ').toLowerCase();
        rows.forEach(row => row.classList.toggle('d-none', !!query && !text.includes(query)));
    });
}

function changePosition(select) {
    const tid = select.dataset.teacherId;
    if (!tid) return;
    currentPositions[tid] = select.options[select.selectedIndex]?.text || 'GTK';
}

function changeSubject(select) {
    const row = select.closest('tr');
    const tid = row.dataset.teacher;
    const oldSid = row.dataset.subject;
    const newSid = select.value;
    if (!tid || !newSid || newSid === oldSid) return;

    if ((activeSubjects[tid] || []).some(s => s.id === newSid)) {
        alert('Mapel tersebut sudah ada untuk GTK ini.');
        select.value = oldSid;
        return;
    }

    if (!subjectIsTahfidz(newSid)) {
        const conflict = Array.from(row.querySelectorAll('.rombel-toggle:checked')).some(toggle =>
            subjectRombelTaken(toggle, newSid, toggle.dataset.sgid)
        );
        if (conflict) {
            alert('Mapel dan rombel ini sudah dipakai GTK lain. Khusus Tahfidz boleh lebih dari satu.');
            select.value = oldSid;
            return;
        }
    }

    const subject = subjects.find(s => s.id === newSid);
    row.id = `sub-row-${tid}-${newSid}`;
    row.dataset.subject = newSid;
    row.querySelectorAll('.rombel-toggle').forEach(toggle => toggle.dataset.sid = newSid);
    const sebaran = row.querySelector('[id^="sebaran-"]');
    if (sebaran) sebaran.id = `sebaran-${tid}-${newSid}`;
    const subtotal = row.querySelector('[id^="subtotal-"]');
    if (subtotal) subtotal.id = `subtotal-${tid}-${newSid}`;
    select.dataset.oldSid = newSid;
    activeSubjects[tid] = (activeSubjects[tid] || []).map(s =>
        s.id === oldSid ? { id: newSid, name: subject?.name || '' } : s
    );
    row.querySelectorAll('.rombel-toggle').forEach(toggle => {
        const hours = jpDefaults[`${newSid}|${toggle.dataset.sgid}`] ?? '';
        toggle.dataset.default = hours;
        toggle.dataset.hours = hours;
        const badge = toggle.closest('td')?.querySelector('.jp-badge');
        if (badge) {
            badge.textContent = `${hours || 0} JP`;
            badge.classList.toggle('d-none', !toggle.checked);
        }
    });
    updateSubjectRow(row);
}

function removeSubjectRowByButton(button) {
    const row = button.closest('tr');
    if (!row) return;
    removeSubjectRow(row.dataset.teacher, row.dataset.subject);
}

function refreshTotal(tid) {
    const rows = document.querySelectorAll(`tr[data-teacher="${tid}"]`);
    let sebaran = 0;
    rows.forEach(r => {
        r.querySelectorAll('.rombel-toggle:checked').forEach(toggle => {
            sebaran += parseFloat(toggle.dataset.hours) || 0;
        });
    });
    const taskHours = (activeTasks[tid] || []).reduce((sum, t) => sum + (parseInt(t.hours) || 0), 0);
    const el = document.getElementById(`teacher-total-${tid}`);
    if (el) el.textContent = sebaran + taskHours;
    const seb = document.getElementById(`teacher-sebaran-${tid}`);
    if (seb) seb.textContent = sebaran;
    const tug = document.getElementById(`task-badges-${tid}`) || document.getElementById(`teacher-tugas-${tid}`);
    if (tug) {
        const tasks = (activeTasks[tid] || []).filter(t => t.name);
        tug.innerHTML = tasks.length
            ? tasks.map(t => `<span class="badge bg-warning-subtle text-warning me-1 mb-1">${escapeHtml(t.name)}${t.hours ? ` · ${t.hours} JP` : ''}</span>`).join('')
            : '<span class="text-muted">-</span>';
    }
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
        activeTasks[tid] = (currentTasks[tid] || []).slice();
        const tbody = document.getElementById('matrixBody');
        const emptyRow = tbody.querySelector('tr td[colspan]');
        if (emptyRow) tbody.innerHTML = '';
        const tr = document.createElement('tr');
        tr.id = `teacher-row-${tid}`;
        tr.innerHTML = `
            <td class="text-center fw-semibold">${teacherCounter}</td>
            <td class="fw-semibold text-nowrap">${escapeHtml(teacher.name)}</td>
            <td class="text-wrap">
                <select class="form-select form-select-sm position-select border-0 bg-transparent shadow-none w-auto" data-teacher-id="${tid}" onchange="changePosition(this)">
                    ${positionOptionsHtml(tid)}
                </select>
            </td>
            <td class="text-nowrap">
                <button type="button" class="btn btn-sm btn-soft-primary" onclick="showAddSubject('${tid}')" title="Tambah Mapel">
                    <i class="ri-add-line me-1"></i>Mapel
                </button>
            </td>
            <td class="text-wrap" id="teacher-tugas-${tid}">
            <div id="task-badges-${tid}" class="d-flex flex-wrap gap-1 mb-1"></div>
            <select class="form-select form-select-sm border-0 bg-transparent shadow-none text-muted w-auto" data-teacher-id="${tid}" onchange="addTaskFromSelect(this)">
                ${taskOptionsHtml()}
            </select>
        </td>
            ${sgCells()}
            <td class="text-center fw-semibold" id="teacher-sebaran-${tid}">0</td>
            <td class="text-center fw-bold" id="teacher-total-${tid}">0</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-icon btn-soft-danger" onclick="removeTeacher('${tid}')" title="Hapus GTK">
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
    tr.className = 'table-light';
    tr.dataset.teacher = tid;
    tr.innerHTML = `
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td class="text-wrap fst-italic">${name}</td>
        ${sgCells()}
        <td></td>
        <td class="text-center fw-semibold">${hours}</td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-icon btn-soft-danger" onclick="removeTask('${tid}', '${rowId}')" title="Hapus Tugas">
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
    pushHistory();
    document.getElementById(rowId)?.remove();
    // Remove from activeTasks
    if (activeTasks[tid]) {
        const idx = activeTasks[tid].findIndex(t => rowId.endsWith(String(Date.now() - parseInt(rowId.split('-').pop()))));
    }
    refreshTotal(tid);
}

// Remove subject row
function removeSubjectRow(tid, sid) {
    pushHistory();
    const row = document.getElementById(`sub-row-${tid}-${sid}`);
    if (row) row.remove();
    if (activeSubjects[tid]) activeSubjects[tid] = activeSubjects[tid].filter(s => s.id !== sid);
    refreshTotal(tid);
}

// Remove teacher row
function removeTeacher(tid) {
    if (!confirm('Hapus guru ini beserta semua barisnya?')) return;
    pushHistory();
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
        const toggles = row.querySelectorAll('.rombel-toggle:checked');
        let hasValue = false;
        const groupHours = {};
        toggles.forEach(toggle => {
            const hours = parseFloat(toggle.dataset.hours) || 0;
            if (hours > 0) {
                groupHours[toggle.dataset.sgid] = hours;
                hasValue = true;
            }
        });
        if (hasValue) {
            if (!assignments[tid]) assignments[tid] = {};
            assignments[tid][sid] = groupHours;
        }
    });
    document.getElementById('assignmentsJson').value = JSON.stringify(assignments);
    const positions = {};
    document.querySelectorAll('.position-select').forEach(select => {
        if (select.dataset.teacherId && select.value !== '__keep__') {
            positions[select.dataset.teacherId] = select.value;
        }
    });
    document.getElementById('positionsJson').value = JSON.stringify(positions);
    document.getElementById('otherTasksJson').value = JSON.stringify(
        Object.entries(activeTasks).flatMap(([tid, tasks]) =>
            tasks.map(t => ({ teacher_id: tid, task_name: t.name, task_code: t.code, weekly_hours: t.hours, study_group_id: t.studyGroupId || null }))
        )
    );
});

// Tampilkan seluruh GTK otomatis dan isi penugasan yang sedang berjalan.
document.addEventListener('DOMContentLoaded', function () {
    teachers.forEach(function (teacher) {
        addTeacherToMatrix(teacher.id);

        (currentAssignments[teacher.id] || []).forEach(function (assignment) {
            if (!activeSubjects[teacher.id].some(s => s.id === assignment.subject_id)) {
                activeSubjects[teacher.id].push({ id: assignment.subject_id, name: assignment.subject });
                renderSubjectRow(teacher.id, assignment.subject_id, assignment.subject);
            }
            const toggle = document.querySelector(
                `.rombel-toggle[data-tid="${teacher.id}"][data-sid="${assignment.subject_id}"][data-sgid="${assignment.study_group_id}"]`
            );
            if (toggle && assignment.hours) {
                toggle.checked = true;
                toggle.dataset.active = '1';
                toggle.dataset.hours = assignment.hours;
                const badge = toggle.closest('td')?.querySelector('.jp-badge');
                if (badge) {
                    badge.textContent = `${assignment.hours} JP`;
                    badge.classList.remove('d-none');
                }
                updateSubjectRow(toggle.closest('tr'));
            }
        });

        refreshTotal(teacher.id);
    });
    updateHistoryButtons();
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

function addTaskFromModal() {
    const tid = document.getElementById('taskTeacherSelect').value;
    const type = document.getElementById('taskTypeSelect').value;
    if (!tid) { alert('Pilih guru terlebih dahulu.'); return; }

    let taskCode = '';
    let taskName = '';
    let hours = 0;
    let rombelId = null;

    if (type === 'wali_kelas') {
        const sgId = document.getElementById('taskRombelSelect').value;
        const sgName = document.querySelector(`#taskRombelSelect option[value="${sgId}"]`)?.text || '';
        if (!sgId) { alert('Pilih rombel untuk Wali Kelas.'); return; }
        taskCode = 'wali_kelas';
        taskName = `Wali Kelas ${sgName}`;
        hours = 0;
        rombelId = sgId;
    } else if (type === 'koordinator') {
        const mapelId = document.getElementById('koordinatorMapelSelect').value;
        const mapelName = document.querySelector(`#koordinatorMapelSelect option[value="${mapelId}"]`)?.text || '';
        if (!mapelId) { alert('Pilih mata pelajaran.'); return; }
        taskCode = 'koordinator';
        taskName = `Koordinator ${mapelName}`;
        hours = 3;
    } else if (type === 'kesiswaan') {
        taskCode = 'kesiswaan';
        taskName = document.getElementById('taskName')?.value || 'Kesiswaan / PD / PO';
        hours = parseInt(document.getElementById('taskHours')?.value) || 3;
    } else {
        taskName = document.getElementById('taskName')?.value;
        taskCode = 'custom';
        hours = parseInt(document.getElementById('taskHours')?.value) || 3;
        if (!taskName) { alert('Isi nama tugas.'); return; }
    }

    if (!activeTasks[tid]) activeTasks[tid] = [];
    activeTasks[tid].push({ name: taskName, code: taskCode, hours, studyGroupId: rombelId });

    // Ensure teacher row exists
    if (!document.getElementById(`teacher-row-${tid}`)) {
        const teacher = teachers.find(t => t.id === tid);
        if (!teacher) return;
        teacherCounter++;
        activeSubjects[tid] = [];
        activeTasks[tid] = (currentTasks[tid] || []).slice();
        const tbody = document.getElementById('matrixBody');
        const emptyRow = tbody.querySelector('tr td[colspan]');
        if (emptyRow) tbody.innerHTML = '';
        const trow = document.createElement('tr');
        trow.id = `teacher-row-${tid}`;
        trow.innerHTML = `
            <td class="text-center fw-semibold">${teacherCounter}</td>
            <td class="fw-semibold text-nowrap">${escapeHtml(teacher.name)}</td>
            <td class="text-wrap">
                <select class="form-select form-select-sm position-select border-0 bg-transparent shadow-none w-auto" data-teacher-id="${tid}" onchange="changePosition(this)">
                    ${positionOptionsHtml(tid)}
                </select>
            </td>
            <td class="text-nowrap">
                <button type="button" class="btn btn-sm btn-soft-primary" onclick="showAddSubject('${tid}')" title="Tambah Mapel">
                    <i class="ri-add-line me-1"></i>Mapel
                </button>
            </td>
            <td class="text-wrap" id="teacher-tugas-${tid}">
            <div id="task-badges-${tid}" class="d-flex flex-wrap gap-1 mb-1"></div>
            <select class="form-select form-select-sm border-0 bg-transparent shadow-none text-muted w-auto" data-teacher-id="${tid}" onchange="addTaskFromSelect(this)">
                ${taskOptionsHtml()}
            </select>
        </td>
            ${sgCells()}
            <td class="text-center fw-semibold" id="teacher-sebaran-${tid}">0</td>
            <td class="text-center fw-bold" id="teacher-total-${tid}">0</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-icon btn-soft-danger" onclick="removeTeacher('${tid}')" title="Hapus GTK">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </td>
        `;
        tbody.appendChild(trow);
    }

    // Render task row
    const rowId = `task-row-${tid}-${Date.now()}`;
    const tr = document.createElement('tr');
    tr.id = rowId;
    tr.className = 'table-light';
    tr.dataset.teacher = tid;
    tr.innerHTML = `
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td class="text-wrap fst-italic">${taskName}</td>
        ${sgCells()}
        <td></td>
        <td class="text-center fw-semibold">${hours}</td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-icon btn-soft-danger" onclick="removeTask('${tid}', '${rowId}')" title="Hapus Tugas">
                <i class="ri-delete-bin-line"></i>
            </button>
        </td>
    `;
    document.querySelector(`#teacher-row-${tid}`)?.after(tr);
    refreshTotal(tid);
    bootstrap.Modal.getInstance(document.getElementById('addTaskModal')).hide();
}

function removeTask(tid, rowId) {
    pushHistory();
    document.getElementById(rowId)?.remove();
    refreshTotal(tid);
}

// Remove subject row
function removeSubjectRow(tid, sid) {
    pushHistory();
    const row = document.getElementById(`sub-row-${tid}-${sid}`);
    if (row) row.remove();
    if (activeSubjects[tid]) activeSubjects[tid] = activeSubjects[tid].filter(s => s.id !== sid);
    refreshTotal(tid);
}

// Remove teacher row + restore to dropdown
function removeTeacher(tid) {
    if (!confirm('Hapus guru ini beserta semua barisnya?')) return;
    pushHistory();
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
        const toggles = row.querySelectorAll('.rombel-toggle:checked');
        let hasValue = false;
        const groupHours = {};
        toggles.forEach(toggle => {
            const hours = parseFloat(toggle.dataset.hours) || 0;
            if (hours > 0) {
                groupHours[toggle.dataset.sgid] = hours;
                hasValue = true;
            }
        });
        if (hasValue) {
            if (!assignments[tid]) assignments[tid] = {};
            assignments[tid][sid] = groupHours;
        }
    });
    document.getElementById('assignmentsJson').value = JSON.stringify(assignments);
    const positions = {};
    document.querySelectorAll('.position-select').forEach(select => {
        if (select.dataset.teacherId && select.value !== '__keep__') {
            positions[select.dataset.teacherId] = select.value;
        }
    });
    document.getElementById('positionsJson').value = JSON.stringify(positions);
    document.getElementById('otherTasksJson').value = JSON.stringify(
        Object.values(activeTasks).flatMap(([tid, tasks]) =>
            tasks.map(t => ({ teacher_id: tid, task_name: t.name, task_code: t.code, weekly_hours: t.hours, study_group_id: t.studyGroupId || null }))
        )
    );
});
</script>
@endsection