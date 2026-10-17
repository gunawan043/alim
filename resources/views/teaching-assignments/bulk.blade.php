@extends('layouts.master')
@section('title', 'Input Massal Penugasan')
@section('css')
    @include('kurikulum._styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}">Plotting Guru Mengajar</a> @endslot
        @slot('title') Input Massal Penugasan @endslot
    @endcomponent

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="alert alert-info d-flex align-items-start gap-2">
        <i class="ri-table-line fs-5"></i>
        <div class="small">
            <strong>Isi beberapa penugasan sekaligus</strong> dengan tabel di bawah — JP terisi otomatis dari struktur kurikulum.
            SK bersifat opsional: bila dikosongkan, sistem membuat <em>SK Pembagian Tugas (Input Massal)</em> secara otomatis.
            <br><strong>Aturan mapel per rombel:</strong> satu mapel hanya boleh diajar <strong>satu guru</strong> pada rombel yang sama —
            kecuali <strong>Tahfidz</strong> &amp; <strong>Hadits</strong> (boleh lebih dari satu guru karena berkaitan dengan hafalan).
            <div class="mt-1">
                Untuk <strong>jabatan struktural</strong> (Kepala, Wakil, TU) atur di
                <a href="{{ route('user.gtk.index', ['userId' => $userId]) }}" class="fw-semibold">Data GTK (Kepegawaian)</a>;
                untuk <strong>tugas tambahan</strong> (koordinator mapel, wali kelas, dll.) gunakan
                <a href="{{ route('user.other-teacher-tasks.index', ['userId' => $userId]) }}" class="fw-semibold">Tugas Tambahan</a>.
            </div>
        </div>
    </div>

    @if($studyGroups->isEmpty())
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="ri-error-warning-line fs-5"></i>
            <div class="small">
                <strong>Belum ada rombel di satuan pendidikan ini.</strong>
                Penugasan massal membutuhkan rombel (kelas). Buat rombel terlebih dahulu di
                <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}" class="fw-semibold">Pengaturan Rombel</a>,
                lalu kembali ke halaman ini.
            </div>
        </div>
    @endif

    @if($subjects->isEmpty())
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="ri-error-warning-line fs-5"></i>
            <div class="small">
                <strong>Belum ada mata pelajaran aktif.</strong>
                Tambahkan mapel di <a href="{{ route('user.subjects.index', ['userId' => $userId]) }}" class="fw-semibold">Mata Pelajaran</a>
                dan daftarkan ke tingkat di <a href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}" class="fw-semibold">Data Kelas</a>.
            </div>
        </div>
    @endif

    @if($teachers->isEmpty())
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="ri-error-warning-line fs-5"></i>
            <div class="small"><strong>Belum ada data GTK/guru</strong> yang tertaut ke satuan pendidikan ini.</div>
        </div>
    @endif

    <form method="POST" action="{{ route('user.teaching-assignments.bulk-store', ['userId' => $userId]) }}" id="bulkForm">
        @csrf

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    @if($isGlobalView && ! $scopeSchoolId)
                        <div class="col-md-4">
                            <label class="form-label">Satuan Pendidikan <span class="text-danger">*</span></label>
                            <select name="school_id" id="bulkSchool" class="form-select" required>
                                <option value="">— pilih sekolah —</option>
                                @foreach($schools as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-4">
                        <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                        <select name="academic_year_id" id="bulkAy" class="form-select" required>
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ $activeAyId === $ay->id ? 'selected' : '' }}>
                                    {{ $ay->name }}{{ $ay->is_active ? ' (aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">SK <span class="text-muted small">(opsional)</span></label>
                        <select name="decree_id" id="bulkDecree" class="form-select">
                            <option value="">— buat otomatis —</option>
                            @foreach($decrees as $decree)
                                <option value="{{ $decree->id }}" data-school="{{ $decree->school_id }}">
                                    {{ $decree->decree_number }} — {{ $decree->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Penugasan (tabel)</h5>
                <button type="button" class="btn btn-sm btn-success" onclick="addBulkRow()">
                    <i class="ri-add-line me-1"></i>Tambah Baris
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:40px">#</th>
                                <th style="min-width:200px">Guru</th>
                                <th style="min-width:190px">Mata Pelajaran</th>
                                <th style="min-width:160px">Kelas / Rombel</th>
                                <th class="text-center" style="width:110px">JP / Pekan</th>
                                <th class="text-center" style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody id="bulkRows"></tbody>
                    </table>
                </div>
                <p class="text-muted small mb-0 mt-2">
                    <i class="ri-information-line me-1"></i>
                    Baris duplikat (guru + mapel + kelas + SK yang sama) otomatis dilewati saat disimpan.
                </p>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <a href="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                <button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan Semua</button>
            </div>
        </div>
    </form>

    @php
        $teachersJson = $teachers->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'school' => $teacherSchools[$t->id] ?? null,
            'label' => $t->name,
        ])->values()->all();

        $subjectsJson = $subjects->map(fn ($s) => [
            'id' => $s->id,
            'school' => $s->school_id,
            'label' => $s->name.' ('.$s->code.')',
            'multi' => $s->allowsMultipleTeachers(),
        ])->values()->all();

        $ayNames = $academicYears->pluck('name', 'id');

        $groupsJson = $studyGroups->map(fn ($g) => [
            'id' => $g->id,
            'school' => $g->school_id,
            'grade' => $g->grade_level_id,
            'ay' => $g->academic_year_id,
            'label' => $g->full_name.($g->academic_year_id ? ' — '.($ayNames[$g->academic_year_id] ?? '') : ''),
        ])->values()->all();

        $groupGradesJson = $studyGroups->mapWithKeys(fn ($g) => [$g->id => $g->grade_level_id]);
    @endphp

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    const BULK_TEACHERS = @json($teachersJson);
    const BULK_SUBJECTS = @json($subjectsJson);
    const BULK_GROUPS = @json($groupsJson);
    const BULK_CLAIMS = @json($existingAssignments);
    const GROUP_GRADE = @json($groupGradesJson);
    const GLS_HOURS = @json($glsHours);
    const SCOPE_SCHOOL = @json($scopeSchoolId);

    let bulkRowIndex = 0;

    function bulkSchoolId() {
        const schoolSelect = document.getElementById('bulkSchool');
        return SCOPE_SCHOOL || (schoolSelect ? schoolSelect.value : null);
    }

    function bulkAyId() {
        const aySelect = document.getElementById('bulkAy');
        return aySelect ? aySelect.value : null;
    }

    function subjectMeta(id) {
        return BULK_SUBJECTS.find(s => s.id === id) || null;
    }

    function optionList(list, options) {
        options = options || {};
        const filtered = list.filter(item => {
            if (options.schoolId && item.school && item.school !== options.schoolId) return false;
            if (options.ay && item.ay && item.ay !== options.ay) return false;
            return true;
        });

        // Jangan biarkan daftar kosong: bila tersaring habis, tampilkan semua.
        const used = (options.fallbackAll && filtered.length === 0) ? list : filtered;

        return '<option value="">— pilih —</option>' + used
            .map(item => '<option value="' + item.id + '">' + item.label + '</option>')
            .join('');
    }

    function addBulkRow() {
        const schoolId = bulkSchoolId();
        const ayId = bulkAyId();
        const index = bulkRowIndex++;
        const tbody = document.getElementById('bulkRows');

        const row = document.createElement('tr');
        row.innerHTML =
            '<td class="text-center row-no"></td>' +
            '<td><select name="rows[' + index + '][teacher_id]" class="form-select form-select-sm bulk-teacher" required>' + optionList(BULK_TEACHERS, {schoolId: schoolId}) + '</select></td>' +
            '<td><select name="rows[' + index + '][subject_id]" class="form-select form-select-sm bulk-subject" required>' + optionList(BULK_SUBJECTS, {schoolId: schoolId}) + '</select><div class="small text-danger row-note mt-1"></div></td>' +
            '<td><select name="rows[' + index + '][study_group_id]" class="form-select form-select-sm bulk-group" required>' + optionList(BULK_GROUPS, {schoolId: schoolId, ay: ayId, fallbackAll: true}) + '</select></td>' +
            '<td><input type="number" name="rows[' + index + '][weekly_hours]" class="form-control form-control-sm text-center bulk-hours" min="0" max="40" required></td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-soft-danger" onclick="this.closest(\'tr\').remove(); renumberBulkRows(); validateBulkRows();"><i class="ri-delete-bin-line"></i></button></td>';

        tbody.appendChild(row);

        row.querySelector('.bulk-subject').addEventListener('change', () => { autoFillJp(row); validateBulkRows(); });
        row.querySelector('.bulk-group').addEventListener('change', () => { autoFillJp(row); validateBulkRows(); });

        // Select2: dropdown bisa dicari (guru/mapel/kelas).
        if (window.jQuery && $.fn.select2) {
            $(row).find('.bulk-teacher, .bulk-subject, .bulk-group').each(function () {
                $(this).select2({ width: '100%' });
            });

            // Select2 memicu event via jQuery — pastikan JP otomatis & validasi tetap jalan.
            $(row).find('.bulk-subject, .bulk-group').on('change', function () {
                autoFillJp(row);
                validateBulkRows();
            });
        }

        renumberBulkRows();
        validateBulkRows();
    }

    function autoFillJp(row) {
        const subjectId = row.querySelector('.bulk-subject').value;
        const groupId = row.querySelector('.bulk-group').value;
        const gradeId = GROUP_GRADE[groupId];
        const hoursInput = row.querySelector('.bulk-hours');
        if (subjectId && gradeId && Object.prototype.hasOwnProperty.call(GLS_HOURS, subjectId + '|' + gradeId)) {
            hoursInput.value = GLS_HOURS[subjectId + '|' + gradeId];
        }
    }

    function claimFor(schoolId, ayId, groupId, subjectId) {
        return BULK_CLAIMS.find(c =>
            (!schoolId || !c.school || c.school === schoolId) &&
            (!ayId || !c.ay || c.ay === ayId) &&
            c.group === groupId && c.subject === subjectId
        ) || null;
    }

    function validateBulkRows() {
        const schoolId = bulkSchoolId();
        const ayId = bulkAyId();
        const rows = Array.from(document.querySelectorAll('#bulkRows tr'));

        const seen = {};
        rows.forEach(row => {
            const subjectId = row.querySelector('.bulk-subject')?.value;
            const groupId = row.querySelector('.bulk-group')?.value;
            if (subjectId && groupId) {
                const key = subjectId + '|' + groupId;
                (seen[key] = seen[key] || []).push(row);
            }
        });

        rows.forEach(row => {
            const subjectSelect = row.querySelector('.bulk-subject');
            const groupSelect = row.querySelector('.bulk-group');
            const note = row.querySelector('.row-note');
            const subjectId = subjectSelect ? subjectSelect.value : '';
            const groupId = groupSelect ? groupSelect.value : '';
            const meta = subjectId ? subjectMeta(subjectId) : null;

            row.classList.remove('table-danger', 'table-warning');
            if (note) note.textContent = '';

            if (!subjectId || !groupId || (meta && meta.multi)) return;

            const claim = claimFor(schoolId, ayId, groupId, subjectId);
            if (claim) {
                row.classList.add('table-danger');
                if (note) note.textContent = 'Sudah diajar oleh ' + (claim.teacher || 'guru lain') + ' di rombel ini.';
                return;
            }

            const key = subjectId + '|' + groupId;
            if (seen[key] && seen[key].length > 1) {
                row.classList.add('table-warning');
                if (note) note.textContent = 'Mapel ini duplikat di baris lain untuk rombel yang sama.';
            }
        });
    }

    function rebuildGroupOptions() {
        const schoolId = bulkSchoolId();
        const ayId = bulkAyId();
        document.querySelectorAll('#bulkRows .bulk-group').forEach(select => {
            const current = select.value;
            const html = optionList(BULK_GROUPS, {schoolId: schoolId, ay: ayId, fallbackAll: true});

            if (window.jQuery && $(select).data('select2')) {
                $(select).html(html);
                if (current && $(select).find('option[value="' + current + '"]').length) {
                    $(select).val(current);
                }
                $(select).trigger('change');
            } else {
                select.innerHTML = html;
                if (current && Array.from(select.options).some(o => o.value === current)) {
                    select.value = current;
                }
            }
        });
        validateBulkRows();
    }

    const bulkSchoolSelect = document.getElementById('bulkSchool');
    if (bulkSchoolSelect) {
        bulkSchoolSelect.addEventListener('change', function () {
            document.getElementById('bulkRows').innerHTML = '';
            bulkRowIndex = 0;
            addBulkRow();
        });
    }

    const bulkAySelect = document.getElementById('bulkAy');
    if (bulkAySelect) {
        bulkAySelect.addEventListener('change', rebuildGroupOptions);
    }

    addBulkRow();
    </script>
@endpush
@endsection
