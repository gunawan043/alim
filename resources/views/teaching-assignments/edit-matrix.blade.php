@extends('layouts.master')
@section('title') Edit Matriks — {{ $decree->decree_number }} @endsection

@section('css')
<style>
    .entry-card { border-left: 3px solid #0d6efd; background: #fafbff; }
    .entry-card.task-type { border-left-color: #fd7e14; }
    .entry-card .row-label { font-size: 11px; font-weight: 600; color: #6c757d; text-transform: uppercase; }
    .col-jp { min-width: 50px; text-align: center; }
    .col-jp input { text-align: center; width: 46px; padding: 3px 4px; border-radius: 4px; border: 1px solid #dee2e6; }
    .col-jp input:focus { border-color: #0d6efd; background: #f0f4ff; outline: none; }
    .col-jp input.has-val { background: #e8f4ea; border-color: #28a745; }
    .teacher-header { font-weight: 700; font-size: 13px; color: #343a40; }
    .teacher-role { font-size: 11px; color: #adb5bd; }
    .del-row { color: #dc3545; cursor: pointer; }
    .del-row:hover { opacity: 0.7; }
    .grade-badge { font-size: 10px; background: #e9ecef; padding: 1px 5px; border-radius: 3px; color: #495057; }
    .add-btn { border: 2px dashed #adb5bd; color: #adb5bd; cursor: pointer; }
    .add-btn:hover { border-color: #0d6efd; color: #0d6efd; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Administrasi @endslot
        @slot('li_2') <a href="{{ route('user.institution-decrees.show', ['userId' => $userId, 'id' => $decree->id]) }}">{{ $decree->decree_number }}</a> @endslot
        @slot('title') Edit Matriks Pembagian Tugas @endslot
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

    {{-- Header SK info --}}
    <div class="alert alert-light border mb-3">
        <div class="mb-1" style="font-size:11px; color:#adb5bd; text-transform:uppercase; font-weight:600;">
            Lampiran I. Surat Keputusan Kepala {{ $decree->school?->name ?? 'SEKOLAH' }}
        </div>
        <div style="font-size:12px;">
            <strong>NOMOR&nbsp;&nbsp;&nbsp;:</strong> {{ $decree->decree_number }}<br>
            <strong>TANGGAL :</strong> {{ $decree->issued_date?->translatedFormat('d F Y') ?? '-' }}<br>
            <strong>TENTANG&nbsp;:</strong> {{ strtoupper($decree->title) }}
        </div>
    </div>

    <form method="POST" id="matrixForm"
          action="{{ route('user.teaching-assignments.update-matrix', ['userId' => $userId, 'decree_id' => $decree->id]) }}">
        @csrf
        @method('PUT')

        {{-- Add Teacher Form --}}
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="ri-user-add-line me-1"></i> Tambah Guru ke Matriks</h6>
            </div>
            <div class="card-body pt-2 pb-2">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label" style="font-size:12px;">Pilih Guru</label>
                        <select id="addTeacherSelect" class="form-control form-control-sm">
                            <option value="">-- Pilih Guru --</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}
                                    <span class="text-muted">({{ $t->getRoleNames()->first() ?? 'GTK' }})</span>
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size:12px;">Mapel yang diajar</label>
                        <select id="addSubjectSelect" class="form-control form-control-sm">
                            <option value="">-- Pilih Mapel --</option>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary btn-sm w-100" id="addSubjectBtn">
                            <i class="ri-add-line me-1"></i> Tambah Mapel
                        </button>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-outline-warning btn-sm" id="addTaskBtn" title="Tambah Tugas Tambahan">
                            <i class="ri-user-settings-line"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Matriks Table --}}
        @if($studyGroups->isEmpty())
            <div class="alert alert-warning"><i class="ri-error-warning-line me-1"></i> Tidak ada rombel aktif untuk sekolah & TA ini.</div>
        @else
            @php
                $byGrade = $studyGroups->groupBy(fn($sg) => $sg->gradeLevel->level ?? 0);
                $sortedGrades = $byGrade->sortKeys();
                $totalClassCols = $studyGroups->count();
            @endphp

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center bg-white">
                    <h6 class="mb-0">Matriks Pembagian Tugas Mengajar</h6>
                    <div class="d-flex gap-2">
                        <a href="{{ route('user.institution-decrees.show', ['userId' => $userId, 'id' => $decree->id]) }}"
                           class="btn btn-light btn-sm">Batal</a>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="ri-save-line me-1"></i> Simpan
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height:65vh; overflow:auto;">
                        <table class="table table-bordered mb-0" style="font-size:12px; min-width:700px;">
                            <thead class="table-light text-center align-middle position-sticky top-0" style="z-index:10;">
                                <tr>
                                    <th style="width:30px; min-width:30px;">No</th>
                                    <th style="min-width:170px;">Nama / Mapel / Tugas</th>
                                    @foreach($sortedGrades as $level => $groups)
                                        <th colspan="{{ $groups->count() }}"
                                            class="text-center fw-bold border-dark bg-light"
                                            style="border-bottom:0;">
                                            {{ $groups->first()->gradeLevel->name ?? "Kelas $level" }}
                                        </th>
                                    @endforeach
                                    <th class="text-center bg-success-subtle" style="width:55px;">Seb.<br>Jam</th>
                                    <th class="text-center bg-warning-subtle" style="width:55px;">Tugas<br>Lain</th>
                                    <th class="text-center bg-primary-subtle" style="width:50px;">Jml<br>Jam</th>
                                    <th style="width:30px;"></th>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    @foreach($sortedGrades as $level => $groups)
                                        @foreach($groups as $sg)
                                            <th class="text-center fw-normal" style="min-width:44px;">{{ $sg->name }}</th>
                                        @endforeach
                                    @endforeach
                                    <th class="bg-success-subtle"></th>
                                    <th class="bg-warning-subtle"></th>
                                    <th class="bg-primary-subtle"></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="matrixBody">
                                {{-- Rows injected by JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Hidden inputs for form submission --}}
        <input type="hidden" name="assignments_json" id="assignmentsJson" value="">
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Build JS objects from PHP
        const teachers = {!! json_encode($teachers->values()->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'role' => $t->getRoleNames()->first()])->toArray()) !!};
        const subjects = {!! json_encode($subjects->values()->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->toArray()) !!};
        const studyGroups = {!! json_encode($studyGroups->map(fn($sg) => ['id' => $sg->id, 'name' => $sg->name, 'level' => $sg->gradeLevel->level ?? 0])->toArray()) !!};
        const existing = {!! json_encode($existing->map(fn($e, $k) => [$k => ['id' => $e->id, 'hours' => $e->weekly_hours]])->collapse()->toArray()) !!};
        const existingKeys = Object.keys(existing).reduce((acc, k) => { acc[k] = true; return acc; }, {});

        // Parse existing into structured form
        // existing key format: "teacherId|subjectId|studyGroupId"
        const teacherData = {}; // teacherId -> { subjects: [], tasks: [] }

        // Pre-populate from existing teaching assignments
        {!! json_encode($existing->keys()) !!}.forEach(function(key) {
            const parts = key.split('|');
            if (parts.length === 3) {
                const [tid, sid, sgid] = parts;
                const hours = parseInt(existing[key]?.hours || 0);
                if (!teacherData[tid]) teacherData[tid] = { subjects: [], tasks: [] };
                const sub = subjects.find(s => s.id === sid);
                const sg = studyGroups.find(g => g.id === sgid);
                if (sub && sg && hours > 0) {
                    // Find or create subject entry
                    let entry = teacherData[tid].subjects.find(e => e.subjectId === sid);
                    if (!entry) {
                        entry = { subjectId: sid, subjectName: sub.name, studyGroups: {} };
                        teacherData[tid].subjects.push(entry);
                    }
                    entry.studyGroups[sgid] = hours;
                }
            }
        });

        // Also load other tasks from PHP passed variable
        const otherTasks = {!! json_encode($otherTasks ?? []) !!};
        otherTasks.forEach(function(ott) {
            if (!teacherData[ott.teacher_id]) teacherData[ott.teacher_id] = { subjects: [], tasks: [] };
            teacherData[ott.teacher_id].tasks.push({
                id: ott.id, name: ott.task_name, hours: ott.weekly_hours
            });
        });

        const rowEntries = []; // { teacherId, teacherName, teacherRole, type:'subject'|'task', subjectId, subjectName, studyGroups:{}, taskId, taskName, taskHours, isNew }

        function rebuildRows() {
            const tbody = document.getElementById('matrixBody');
            tbody.innerHTML = '';
            rowEntries.length = 0;

            Object.keys(teacherData).forEach(function(tid) {
                const t = teacherData[tid];
                const teacher = teachers.find(t => t.id === tid);
                if (!teacher) return;

                // First row: teacher name (spans all class cols)
                rowEntries.push({
                    type: 'teacher-header', teacherId: tid, teacherName: teacher.name, teacherRole: teacher.role
                });

                // Subject rows
                t.subjects.forEach(function(sub) {
                    rowEntries.push({
                        type: 'subject', teacherId: tid, subjectId: sub.subjectId, subjectName: sub.subjectName,
                        studyGroups: sub.studyGroups
                    });
                });

                // Task rows
                t.tasks.forEach(function(task) {
                    rowEntries.push({
                        type: 'task', teacherId: tid, taskId: task.id, taskName: task.name, taskHours: task.hours
                    });
                });
            });

            renderRows();
        }

        function renderRows() {
            const tbody = document.getElementById('matrixBody');
            tbody.innerHTML = '';

            let teacherNum = 0;
            let lastTeacherId = null;
            let rowIdx = 0;

            rowEntries.forEach(function(entry) {
                if (entry.type === 'teacher-header') {
                    if (entry.teacherId !== lastTeacherId) {
                        teacherNum++;
                        lastTeacherId = entry.teacherId;
                    }
                    const totalCols = studyGroups.length + 4;
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="text-center text-muted" style="width:30px; font-weight:700;">${teacherNum}</td>
                        <td class="fw-bold" style="background:#f0f4ff;">
                            <div style="font-size:12px;">${entry.teacherName}</div>
                            <div style="font-size:10px; color:#adb5bd;">${entry.teacherRole || ''}</div>
                        </td>
                        ${studyGroups.map(sg => `<td style="background:#f0f4ff;"></td>`).join('')}
                        <td style="background:#f0f4ff;"></td>
                        <td style="background:#f0f4ff;"></td>
                        <td style="background:#f0f4ff;"></td>
                        <td style="width:30px; background:#f0f4ff;">
                            <button type="button" class="btn btn-sm p-0 del-row" onclick="removeTeacher('${entry.teacherId}')" title="Hapus Guru">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                } else if (entry.type === 'subject') {
                    const rowId = `row-${rowIdx}`;
                    let sebaran = 0;
                    const sgInputs = studyGroups.map(function(sg) {
                        const val = entry.studyGroups[sg.id] || '';
                        if (val) sebaran += parseInt(val);
                        return val;
                    });

                    const tr = document.createElement('tr');
                    tr.id = rowId;
                    const maxCols = studyGroups.length + 4;
                    tr.innerHTML = `
                        <td style="width:30px;"></td>
                        <td style="padding-left:16px; font-size:12px;">
                            <span style="font-size:11px; color:#6c757d;">${entry.subjectName}</span>
                        </td>
                        ${studyGroups.map(function(sg, i) {
                            const v = sgInputs[i];
                            return `<td class="col-jp">
                                <input type="number" min="0" max="20" value="${v}"
                                    class="col-jp-input${v ? ' has-val' : ''}"
                                    data-tid="${entry.teacherId}" data-sid="${entry.subjectId}" data-sgid="${sg.id}"
                                    oninput="updateCell(this, '${entry.teacherId}', '${entry.subjectId}', '${sg.id}')">
                            </td>`;
                        }).join('')}
                        <td class="text-center fw-bold bg-success-subtle" style="width:55px;" id="sebaran-${rowId}">${sebaran}</td>
                        <td class="text-center bg-warning-subtle" style="width:55px;"></td>
                        <td class="text-center fw-bold bg-primary-subtle" style="width:50px;" id="total-${rowId}">${sebaran}</td>
                        <td style="width:30px;">
                            <button type="button" class="btn btn-sm p-0 del-row" onclick="removeRow(${rowIdx})">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                    rowIdx++;
                } else if (entry.type === 'task') {
                    const rowId = `row-${rowIdx}`;
                    const tr = document.createElement('tr');
                    tr.id = rowId;
                    tr.innerHTML = `
                        <td style="width:30px;"></td>
                        <td style="padding-left:16px;">
                            <span style="font-size:11px; color:#fd7e14; font-style:italic;">${entry.taskName}</span>
                        </td>
                        ${studyGroups.map(() => `<td style="background:#fff8ee;"></td>`).join('')}
                        <td class="text-center bg-success-subtle" style="width:55px;"></td>
                        <td class="text-center bg-warning-subtle fw-bold" style="width:55px;" id="task-hours-${rowId}">${entry.taskHours}</td>
                        <td class="text-center fw-bold bg-primary-subtle" style="width:50px;">${entry.taskHours}</td>
                        <td style="width:30px;">
                            <button type="button" class="btn btn-sm p-0 del-row" onclick="removeRow(${rowIdx})">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                    rowIdx++;
                }
            });

            if (rowEntries.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td colspan="${studyGroups.length + 5}" class="text-center py-4 text-muted">
                    <i class="ri-user-add-line fs-3 d-block mb-2"></i>
                    Belum ada guru. Gunakan form di atas untuk menambahkan.
                </td>`;
                tbody.appendChild(tr);
            }
        }

        function updateCell(input, tid, sid, sgid) {
            const val = input.value;
            input.className = 'col-jp-input' + (val ? ' has-val' : '');
            if (!teacherData[tid]) teacherData[tid] = { subjects: [], tasks: [] };
            let entry = teacherData[tid].subjects.find(e => e.subjectId === sid);
            if (!entry) {
                const sub = subjects.find(s => s.id === sid);
                entry = { subjectId: sid, subjectName: sub?.name || '', studyGroups: {} };
                teacherData[tid].subjects.push(entry);
            }
            entry.studyGroups[sgid] = val ? parseInt(val) : null;
            recalcRow(input.closest('tr').id);
        }

        function recalcRow(rowId) {
            const tr = document.getElementById(rowId);
            const inputs = tr.querySelectorAll('.col-jp-input');
            let sebaran = 0;
            inputs.forEach(i => { if (i.value) sebaran += parseInt(i.value); });
            const sebaranEl = document.getElementById('sebaran-' + rowId);
            const totalEl = document.getElementById('total-' + rowId);
            if (sebaranEl) sebaranEl.textContent = sebaran;
            if (totalEl) totalEl.textContent = sebaran;
        }

        // Add subject button
        document.getElementById('addSubjectBtn').addEventListener('click', function() {
            const tid = document.getElementById('addTeacherSelect').value;
            const sid = document.getElementById('addSubjectSelect').value;
            if (!tid || !sid) { alert('Pilih guru dan mapel terlebih dahulu.'); return; }
            if (!teacherData[tid]) teacherData[tid] = { subjects: [], tasks: [] };

            const teacher = teachers.find(t => t.id === tid);
            const sub = subjects.find(s => s.id === sid);

            // Check duplicate subject
            if (teacherData[tid].subjects.find(e => e.subjectId === sid)) {
                alert('Mapel ini sudah ditambahkan untuk guru ini.');
                return;
            }

            teacherData[tid].subjects.push({ subjectId: sid, subjectName: sub.name, studyGroups: {} });
            rebuildRows();
        });

        // Add task button
        document.getElementById('addTaskBtn').addEventListener('click', function() {
            const tid = document.getElementById('addTeacherSelect').value;
            if (!tid) { alert('Pilih guru terlebih dahulu.'); return; }
            const taskName = prompt('Nama tugas (cth: Wali Kelas 7A, Koordinator Bahasa Arab):');
            if (!taskName) return;
            const taskHours = parseInt(prompt('Jam per minggu:', '3') || '3');
            if (!teacherData[tid]) teacherData[tid] = { subjects: [], tasks: [] };
            const newId = 'new-task-' + Date.now();
            teacherData[tid].tasks.push({ id: newId, name: taskName, hours: taskHours });
            rebuildRows();
        });

        window.removeRow = function(idx) {
            const entry = rowEntries[idx];
            if (!entry) return;
            if (entry.type === 'subject') {
                const t = teacherData[entry.teacherId];
                if (t) {
                    t.subjects = t.subjects.filter(e => e.subjectId !== entry.subjectId);
                    if (t.subjects.length === 0 && t.tasks.length === 0) delete teacherData[entry.teacherId];
                }
            } else if (entry.type === 'task') {
                const t = teacherData[entry.teacherId];
                if (t) {
                    t.tasks = t.tasks.filter(e => e.id !== entry.taskId);
                    if (t.subjects.length === 0 && t.tasks.length === 0) delete teacherData[entry.teacherId];
                }
            }
            rebuildRows();
        };

        window.removeTeacher = function(tid) {
            if (confirm('Hapus semua entri untuk guru ini?')) {
                delete teacherData[tid];
                rebuildRows();
            }
        };

        // Form submit
        document.getElementById('matrixForm').addEventListener('submit', function(e) {
            // Build assignments JSON from teacherData
            const assignments = {};
            Object.keys(teacherData).forEach(tid => {
                if (!assignments[tid]) assignments[tid] = {};
                teacherData[tid].subjects.forEach(entry => {
                    if (!assignments[tid][entry.subjectId]) assignments[tid][entry.subjectId] = {};
                    Object.keys(entry.studyGroups).forEach(sgid => {
                        const val = entry.studyGroups[sgid];
                        if (val) assignments[tid][entry.subjectId][sgid] = val;
                    });
                });
            });
            document.getElementById('assignmentsJson').value = JSON.stringify(assignments);
        });

        // Init
        rebuildRows();
    });
    </script>
@endsection