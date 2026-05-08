@extends('layouts.master')
@section('title') Promosi Santri Baru @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2')
            <a href="{{ route('user.student-promotions.index', ['userId' => $userId]) }}">Promosi Santri</a>
        @endslot
        @slot('title') Promosi Baru @endslot
    @endcomponent

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" id="promotionForm" action="{{ route('user.student-promotions.store', ['userId' => $userId]) }}">
        @csrf

        {{-- ── STEP 1: KONFIGURASI PROMOSI ─────────────────── --}}
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-settings-3-line me-2"></i>Konfigurasi Promosi</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            {{-- Tahun ajaran asal --}}
                            <div class="col-md-6">
                                <label class="form-label">Tahun Ajaran Asal <span class="text-danger">*</span></label>
                                <select name="from_academic_year_id" id="fromAcademicYear"
                                        class="form-select @error('from_academic_year_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Tahun Ajaran Asal --</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ old('from_academic_year_id') == $ay->id ? 'selected' : '' }}>
                                            {{ $ay->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('from_academic_year_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Tahun ajaran tujuan --}}
                            <div class="col-md-6">
                                <label class="form-label">Tahun Ajaran Tujuan <span class="text-danger">*</span></label>
                                <select name="to_academic_year_id" id="toAcademicYear"
                                        class="form-select @error('to_academic_year_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Tahun Ajaran Tujuan --</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ old('to_academic_year_id') == $ay->id ? 'selected' : '' }}>
                                            {{ $ay->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('to_academic_year_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Rombel asal --}}
                            <div class="col-md-6">
                                <label class="form-label">Rombel Asal <span class="text-danger">*</span></label>
                                <select name="from_study_group_id" id="fromStudyGroup"
                                        class="form-select @error('from_study_group_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Rombel Asal --</option>
                                </select>
                                <input type="hidden" id="selectedSchoolId" value="{{ $schoolId ?? '' }}">
                                <small class="text-muted">Pilih tahun ajaran asal terlebih dahulu</small>
                                @error('from_study_group_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Rombel tujuan (opsional, auto-detect jika kosong) --}}
                            <div class="col-md-6">
                                <label class="form-label">Kelas Tujuan <small class="text-muted">(opsional)</small></label>
                                <select name="to_study_group_id" id="toStudyGroup"
                                        class="form-select @error('to_study_group_id') is-invalid @enderror">
                                    <option value="">-- Auto (dari tahun ajaran tujuan) --</option>
                                </select>
                                <small class="text-muted">Pilih kelas yang dituju atau biarkan auto-detect</small>
                            </div>

                            {{-- Tanggal efektif --}}
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Efektif <span class="text-danger">*</span></label>
                                <input type="date" name="promotion_date"
                                       class="form-control @error('promotion_date') is-invalid @enderror"
                                       value="{{ old('promotion_date', date('Y-m-d')) }}" required>
                                @error('promotion_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Grade shift --}}
                            <div class="col-md-4">
                                <label class="form-label">Naik Level</label>
                                <select name="grade_shift" class="form-select">
                                    <option value="1" {{ old('grade_shift', 1) == 1 ? 'selected' : '' }}>Naik 1 level (7→8)</option>
                                    <option value="2" {{ old('grade_shift') == 2 ? 'selected' : '' }}>Naik 2 level (7→9)</option>
                                    <option value="0" {{ old('grade_shift') == '0' ? 'selected' : '' }}>Tinggal kelas (tetap)</option>
                                    <option value="-1" {{ old('grade_shift') == '-1' ? 'selected' : '' }}>Turun 1 level</option>
                                </select>
                            </div>

                            {{-- Opsi tambahan --}}
                            <div class="col-md-4">
                                <label class="form-label d-block">Opsi</label>
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" name="auto_enroll" class="form-check-input" id="autoEnroll"
                                           value="1" {{ old('auto_enroll', '1') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="autoEnroll">Auto enroll</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" name="skip_graduate" class="form-check-input" id="skipGraduate"
                                           value="1" {{ old('skip_graduate', '1') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="skipGraduate">Skip graduate</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" name="include_inactive" class="form-check-input" id="includeInactive"
                                           value="1" {{ old('include_inactive') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="includeInactive">Siswa non-aktif</label>
                                </div>
                            </div>

                            {{-- Keterangan --}}
                            <div class="col-12">
                                <label class="form-label">Keterangan</label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Keterangan opsional">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Ringkasan Opsi --}}
            <div class="col-lg-4">
                <div class="card" style="top: 20px">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="ri-information-line me-1"></i>Pengaturan Opsi</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-2">
                                <i class="ri-checkbox-circle-line text-success me-1"></i>
                                <strong>Auto enroll</strong> — Siswa langsung dimasukan ke rombel baru
                            </li>
                            <li class="mb-2">
                                <i class="ri-checkbox-circle-line text-success me-1"></i>
                                <strong>Skip graduate</strong> — Siswa tingkat akhir otomatis diluluskan
                            </li>
                            <li class="mb-2">
                                <i class="ri-checkbox-circle-line text-muted me-1"></i>
                                <strong>Siswa non-aktif</strong> — Sertakan siswa dropout/transfer
                            </li>
                            <li class="mb-2">
                                <i class="ri-arrow-up-line text-primary me-1"></i>
                                <strong>Naik 1 level</strong> — Default: kelas 7→8, 8→9
                            </li>
                            <li class="mb-0">
                                <i class="ri-user-follow-line text-warning me-1"></i>
                                <strong>Per siswa</strong> — Aksi bisa diubah di daftar siswa
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── STEP 2: DAFTAR SISWA ─────────────────────────── --}}
        <div class="card" id="studentsCard" style="display: none">
            <div class="card-header bg-light">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Daftar Santri</h5>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="checkAllStudents" checked>
                            <label class="form-check-label" for="checkAllStudents">Pilih Semua</label>
                        </div>
                        <select id="bulkActionSelect" class="form-select form-select-sm d-inline-block w-auto ms-2">
                            <option value="promote">Naik Kelas</option>
                            <option value="retain">Tinggal Kelas</option>
                            <option value="graduate">Lulus</option>
                            <option value="mutate_out">Mutasi Keluar</option>
                            <option value="skip">Dilompati</option>
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-primary ms-1" id="applyBulkAction">
                            Terapkan
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light text-muted sticky-top" style="top: 0; z-index: 1;">
                            <tr>
                                <th style="width:40px"><input class="form-check-input" type="checkbox" id="checkAllTable"></th>
                                <th>Nama</th>
                                <th>NISN</th>
                                <th>JK</th>
                                <th>Tgl Lahir</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="studentsTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="ri-arrow-up-line fs-1"></i>
                                    <p class="mb-0">Pilih rombel asal untuk menampilkan daftar siswa</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top">
                    <small class="text-muted" id="studentCountInfo">0 siswa dipilih</small>
                </div>
            </div>

            <div class="card-footer text-end">
                <a href="{{ route('user.student-promotions.index', ['userId' => $userId]) }}"
                   class="btn btn-light">Batal</a>
                <button type="button" class="btn btn-success" data-bs-toggle="modal"
                        data-bs-target="#confirmModal" id="btnSubmit" disabled>
                    <i class="ri-save-line me-1"></i>Simpan Promosi
                </button>
            </div>
        </div>
    </form>

    {{-- Modal Konfirmasi --}}
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">
                        <i class="ri-checkbox-circle-line me-1 text-success"></i>Konfirmasi Promosi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Promosi akan disimpan sebagai <strong>draft</strong> terlebih dahulu.</p>
                    <p>Anda bisa review dan <strong>eksekusi</strong> nanti dari halaman detail promosi.</p>
                    <div class="alert alert-warning mb-0">
                        <i class="ri-information-line me-1"></i>
                        Pastikan semua opsi dan daftar siswa sudah benar sebelum disimpan.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" form="promotionForm" class="btn btn-success">
                        <i class="ri-save-line me-1"></i>Ya, Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // ── Load rombel saat tahun ajaran asal berubah ───────────
    $('#fromAcademicYear, #toAcademicYear').on('change', function () {
        const fromAyId = $('#fromAcademicYear').val();
        const toAyId = $('#toAcademicYear').val();
        const schoolId = $('#selectedSchoolId').val();
        const $fromSelect = $('#fromStudyGroup');

        $fromSelect.html('<option value="">Memuat...</option>');
        $('#studentsCard').hide();
        $('#btnSubmit').prop('disabled', true);

        if (!fromAyId) return;

        let url = `/api/grade-levels/by-academic-year/${fromAyId}`;
        if (schoolId) url += `?school_id=${schoolId}`;

        $.get(url, function (data) {
            let html = '<option value="">-- Pilih Rombel --</option>';
            (data.study_groups || data.data || []).forEach(function (sg) {
                html += `<option value="${sg.id}">${sg.full_name || sg.name} (${sg.grade_level?.name || ''})</option>`;
            });
            $fromSelect.html(html);
        }).fail(function () {
            $fromSelect.html('<option value="">Gagal memuat rombel</option>');
        });

        // Load rombel tujuan berdasarkan tahun ajaran tujuan
        loadTargetStudyGroups(toAyId, schoolId);
    });

    // ── Load siswa saat rombel asal berubah ─────────────────
    $('#fromStudyGroup').on('change', function () {
        const sgId = $(this).val();
        const ayId = $('#fromAcademicYear').val();
        const $tbody = $('#studentsTableBody');

        if (!sgId || !ayId) {
            $('#studentsCard').hide();
            $('#btnSubmit').prop('disabled', true);
            return;
        }

        $tbody.html('<tr><td colspan="6" class="text-center py-4"><i class="ri-loader-line spin"></i> Memuat...</td></tr>');
        $('#studentsCard').show();

        const url = `/api/study-groups/${sgId}/students/assigned?academic_year_id=${ayId}`;
        $.get(url, function (res) {
            const students = res.students || res.data || [];
            renderStudents(students);
        }).fail(function () {
            $tbody.html('<tr><td colspan="6" class="text-center py-4 text-danger">Gagal memuat data siswa.</td></tr>');
            $('#btnSubmit').prop('disabled', true);
        });
    });

    function renderStudents(students) {
        const $tbody = $('#studentsTableBody');

        if (!students.length) {
            $tbody.html('<tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada siswa aktif di rombel ini.</td></tr>');
            $('#btnSubmit').prop('disabled', true);
            return;
        }

        let html = '';
        students.forEach(function (s) {
            const student = s.student || s;
            const historyId = s.id || '';
            const genderBadge = student.gender === 'L'
                ? '<span class="badge bg-primary-subtle text-primary">L</span>'
                : '<span class="badge bg-danger-subtle text-danger">P</span>';

            html += `
            <tr>
                <td class="text-center">
                    <input class="form-check-input student-check" type="checkbox"
                           name="student_ids[]" value="${student.id}" checked data-name="${student.name}">
                </td>
                <td class="fw-semibold">${student.name}</td>
                <td><code>${student.nisn || '-'}</code></td>
                <td>${genderBadge}</td>
                <td><small>${student.birth_date || '-'}</small></td>
                <td>
                    <select name="student_actions[${student.id}]" class="form-select form-select-sm d-inline-block w-auto student-action-select">
                        <option value="promote">🚀 Naik Kelas</option>
                        <option value="retain">📌 Tinggal Kelas</option>
                        <option value="graduate">🎓 Lulus</option>
                        <option value="mutate_out">🚪 Mutasi Keluar</option>
                        <option value="skip">⏭ Dilompati</option>
                    </select>
                </td>
            </tr>`;
        });

        $tbody.html(html);
        updateStudentCount();
        $('#btnSubmit').prop('disabled', false);

        // Sync checkbox header dengan individual
        $('#checkAllTable, #checkAllStudents').on('change', function () {
            const checked = $(this).prop('checked');
            $('#studentsTableBody .student-check').prop('checked', checked);
            updateStudentCount();
        });

        $('#studentsTableBody .student-check').on('change', updateStudentCount);
    }

    // ── Load rombel tujuan berdasarkan tahun ajaran tujuan ───
    function loadTargetStudyGroups(toAyId, schoolId) {
        const $toSelect = $('#toStudyGroup');

        if (!toAyId) {
            $toSelect.html('<option value="">-- Auto (naik 1 level) --</option>');
            return;
        }

        let url = `/api/grade-levels/by-academic-year/${toAyId}`;
        if (schoolId) url += `?school_id=${schoolId}`;

        $.get(url, function (data) {
            let html = '<option value="">-- Auto (naik 1 level) --</option>';
            (data.study_groups || data.data || []).forEach(function (sg) {
                html += `<option value="${sg.id}">${sg.full_name || sg.name} (${sg.grade_level?.name || ''})</option>`;
            });
            $toSelect.html(html);
        }).fail(function () {
            $toSelect.html('<option value="">-- Auto (naik 1 level) --</option>');
        });
    }

    function updateStudentCount() {
        const count = $('#studentsTableBody .student-check:checked').length;
        $('#studentCountInfo').text(`${count} siswa dipilih`);
    }

    // ── Bulk action ──────────────────────────────────────────
    $('#applyBulkAction').on('click', function () {
        const action = $('#bulkActionSelect').val();
        $('#studentsTableBody .student-action-select').val(action);
    });
});
</script>
@endsection
