@extends('layouts.master')
@php $isEdit = isset($decree) && $decree; @endphp
@section('title') {{ $isEdit ? 'Edit SK & Matriks' : 'Tambah Surat Keputusan' }} @endsection

@section('css')
<style>
    #matrixBody td .subject-select { min-width: 140px; }
    #matrixBody td .d-flex > .btn-icon {
        width: 32px; height: 32px; padding: 0;
        display: inline-flex; align-items: center; justify-content: center;
    }
    #matrixBody td .badge.bg-warning-subtle,
    #matrixBody td .badge.bg-info-subtle {
        font-size: 0.75rem; font-weight: 500; padding: 0.4rem 0.6rem;
    }
    #matrixBody tr[id^="teacher-row-"] > td {
        vertical-align: top; padding-top: 0.75rem; padding-bottom: 0.75rem;
    }

    /* ── Kolom rombel: beri ruang kiri-kanan agar kode kelas tidak rapat ── */
    .matrix-table th.rombel-col,
    .matrix-table td.rombel-col {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
        min-width: 62px;
    }

    /* ── Grid pemisah: garis tipis antar sel + garis tegas antar tingkat ── */
    .matrix-table {
        border-collapse: separate;
        border-spacing: 0;
    }
    .matrix-table th,
    .matrix-table td {
        border-right: 1px solid var(--bs-border-color);
        border-bottom: 1px solid var(--bs-border-color);
    }
    .matrix-table thead th {
        border-bottom: 1px solid var(--bs-border-color-translucent);
    }
    /* Garis tegas di awal kelompok kelas (1A, 2A, 3A, ...). */
    .matrix-table .grade-start {
        border-left: 2px solid var(--bs-secondary-border-subtle, var(--bs-border-color));
    }
    /* Selang-seling per tingkat kelas agar mudah dibedakan saat menggeser. */
    .matrix-table .grade-odd {
        background: var(--bs-tertiary-bg);
    }
    .matrix-table tbody tr:hover > td {
        background: var(--bs-table-hover-bg);
    }

    /* ── Header tabel tetap terlihat saat menggulir ── */
    .matrix-scroll {
        max-height: calc(100vh - 220px);
        overflow: auto;
        position: relative;
    }
    .matrix-scroll thead th {
        position: sticky;
        z-index: 3;
        background: var(--bs-tertiary-bg);
        box-shadow: inset 0 -1px 0 var(--bs-border-color);
    }
    /* Dua baris header: baris 1 menempel paling atas, baris 2 di bawahnya. */
    .matrix-scroll thead tr:nth-child(1) th { top: 0; }
    .matrix-scroll thead tr:nth-child(2) th { top: var(--matrix-head-row2, 38px); }

    /* Kolom identitas dibekukan agar nama tetap terlihat saat menggeser.
       Lebar dipatok supaya posisi kiri kolom berikutnya bisa dihitung pasti.
       Ditandai lewat class, bukan :nth-child, karena baris kedua header
       punya kolom yang urutannya berbeda (tanpa No/Nama/Jabatan). */
    .matrix-scroll thead th.freeze-col,
    .matrix-scroll tbody td.freeze-col {
        position: sticky;
        z-index: 2;
        background: var(--bs-body-bg);
        box-shadow: 1px 0 0 var(--bs-border-color);
    }
    .matrix-scroll thead th.freeze-col { z-index: 4; background: var(--bs-tertiary-bg); }

    .matrix-scroll .freeze-no {
        left: 0;
        min-width: 52px; max-width: 52px; width: 52px;
    }
    .matrix-scroll .freeze-nama {
        left: 52px;
        min-width: 160px; max-width: 160px; width: 160px;
        box-shadow: 2px 0 4px -2px rgba(0,0,0,.18);
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Administrasi @endslot
        @slot('li_2') <a href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}">Surat Keputusan</a> @endslot
        @slot('title') {{ $isEdit ? 'Edit SK & Matriks Pembagian Tugas' : 'Buat SK & Matriks Pembagian Tugas' }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div id="draftRestoredAlert" class="alert alert-info alert-dismissible fade show d-none" role="alert">
        <i class="ri-draft-line me-1"></i> Draft tersimpan berhasil dipulihkan. Silakan lanjutkan pengeditan.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>

    <div id="autosaveIndicator" class="position-fixed bottom-0 end-0 m-3 badge bg-success-subtle text-success d-none" style="z-index:1055;">
        <i class="ri-checkbox-circle-line me-1"></i><span id="autosaveText">Tersimpan otomatis</span>
    </div>

    <form method="POST" id="decreeForm"
          action="{{ $isEdit ? route('user.institution-decrees.update', ['userId' => $userId, 'id' => $decree->id]) : route('user.institution-decrees.store', ['userId' => $userId]) }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Section 1: Info SK --}}
        <div class="card mb-3">
            <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center" style="cursor:pointer"
                 onclick="document.getElementById('skInfoBody').classList.toggle('d-none')">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-file-text-line me-2 text-primary"></i>Informasi Surat Keputusan</h5>
                    <p class="text-muted mb-0 small">Nomor, jenis, tahun ajaran, dan detail dasar SK.</p>
                </div>
                <i class="ri-arrow-down-s-line fs-4" id="skInfoArrow"></i>
            </div>
            <div class="card-body" id="skInfoBody">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Nomor SK <span class="text-danger">*</span></label>
                        <input type="text" name="decree_number" class="form-control" value="{{ old('decree_number', $isEdit ? $decree->decree_number : '066/PAH/SMPIT-Pi/E/VII/'.date('Y')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Jenis SK <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" value="{{ $isEdit ? $decree->decree_type : 'SK Pembagian Tugas' }}" readonly>
                        <input type="hidden" name="decree_type" value="{{ $isEdit ? $decree->decree_type : 'SK Pembagian Tugas' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Tahun Ajaran <span class="text-danger">*</span></label>
                        @php $ayValue = old('academic_year_id', $isEdit ? $decree->academic_year_id : ($selectedAyId ?? $activeYear?->id)); @endphp
                        <select name="academic_year_id" class="form-select" required id="aySelect">
                            <option value="">— Pilih —</option>
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ (string) $ayValue === (string) $ay->id ? 'selected' : '' }}>
                                    {{ $ay->name }}@if($ay->semester_text) ({{ $ay->semester_text }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Sekolah <span class="text-danger">*</span></label>
                        @if($isEdit || (!$canSelectSchool && $selectedSchool))
                            <input type="text" class="form-control" value="{{ $selectedSchool?->name ?? $decree?->school?->name }}" readonly>
                            <input type="hidden" name="school_id" value="{{ $selectedSchoolId ?? $decree?->school_id }}">
                        @else
                            <select name="school_id" class="form-select" required id="schoolSelect">
                                <option value="">— Pilih Sekolah —</option>
                                @foreach($schools as $s)
                                    <option value="{{ $s->id }}" {{ (string)($selectedSchoolId ?? '') === (string)$s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Judul SK <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $isEdit ? $decree->title : '') }}" required
                               placeholder="cth: PEMBAGIAN TUGAS MENGAJAR GURU SEMESTER GANJIL TAHUN AJARAN 2025/2026">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Tanggal Dikeluarkan <span class="text-danger">*</span></label>
                        <input type="date" name="issued_date" class="form-control" value="{{ old('issued_date', $isEdit ? $decree->issued_date?->format('Y-m-d') : date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Tanggal Efektif <span class="text-danger">*</span></label>
                        <input type="date" name="effective_date" class="form-control" value="{{ old('effective_date', $isEdit ? $decree->effective_date?->format('Y-m-d') : date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Tanggal Berakhir</label>
                        <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $isEdit ? $decree->end_date?->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Penandatangan</label>
                        <select name="signed_by" class="form-select">
                            <option value="">— Pilih —</option>
                            @foreach($signers as $s)
                                <option value="{{ $s->id }}" {{ (string) old('signed_by', $isEdit ? $decree->signed_by : '') === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Jabatan Penandatangan</label>
                        <input type="text" name="signed_position" class="form-control" value="{{ old('signed_position', $isEdit ? $decree->signed_position : 'Kepala Sekolah') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Status</label>
                        @if($isEdit)
                            <div class="pt-1">
                                @if($decree->status === 'active')
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                @elseif($decree->status === 'archived')
                                    <span class="badge bg-secondary-subtle text-secondary">Arsip</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">Draft</span>
                                @endif
                                <div class="small text-muted mt-1">Ubah lewat tombol Aktifkan / Nonaktifkan di daftar SK.</div>
                            </div>
                        @else
                            <select name="status" class="form-select">
                                @foreach(['draft' => 'Draft', 'active' => 'Aktif', 'archived' => 'Arsip'] as $statusValue => $statusLabel)
                                    <option value="{{ $statusValue }}" {{ old('status', 'draft') === $statusValue ? 'selected' : '' }}>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="1">{{ old('description', $isEdit ? $decree->description : '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Matriks GTK --}}
        <div id="matrixSection">
            @php $byGrade = $studyGroups->groupBy(fn($sg) => $sg->gradeLevel->level ?? 0)->sortKeys(); @endphp

            @if($teachers->isEmpty())
                <x-empty-state colspan="1" title="Pilih sekolah terlebih dahulu"
                    message="Pilih sekolah di atas, lalu tabel GTK akan muncul di bawah.">
                    <i class="ri-government-line fs-1 d-block mb-2 text-muted"></i>
                </x-empty-state>
            @else
                <div class="alert alert-info mb-3 py-2 px-3 border">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted text-uppercase fw-bold mb-1" style="font-size:11px;">
                                Lampiran I. Surat Keputusan Kepala
                            </div>
                            <div class="fw-bold" id="infoSchool">
                                {{ $selectedSchool?->name ?? '— Seleksi Sekolah —' }}
                            </div>
                            <div class="mt-1" style="font-size:11px;">
                                <span id="infoNomor">NOMOR&nbsp;&nbsp;: <strong>—</strong></span> &nbsp;|&nbsp;
                                <span id="infoTanggal">TANGGAL : <strong>—</strong></span> &nbsp;|&nbsp;
                                <span id="infoTA">TA <strong>—</strong></span>
                            </div>
                            <div class="mt-1" style="font-size:11px;" id="infoTitle">
                                TENTANG : <strong>—</strong>
                            </div>
                        </div>
                        <div class="text-end text-muted" style="font-size:10px;">
                            Matriks Pembagian Tugas Mengajar
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <div class="row g-3 align-items-center">
                            <div class="col-sm">
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <h5 class="card-title mb-0">Matriks Pembagian Tugas</h5>
                                    <span class="badge bg-primary-subtle text-primary">
                                        <i class="ri-group-line me-1"></i><span id="teacherCount">0</span> GTK
                                    </span>
                                    <span class="badge bg-info-subtle text-info">
                                        <i class="ri-stack-line me-1"></i>{{ $studyGroups->count() }} Rombel
                                    </span>
                                </div>
                                <p class="text-muted mb-0 small mt-1">Atur jabatan, mapel, dan tugas tambahan tiap GTK.</p>
                            </div>
                            <div class="col-sm-auto">
                                <div class="toolbar-actions d-flex flex-wrap gap-2 align-items-center">
                                    @if($isEdit)
                                        <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap flex-shrink-0"
                                                data-bs-toggle="modal" data-bs-target="#printSettingsModal" title="Pengaturan Cetak PDF">
                                            <i class="ri-printer-settings-line me-1"></i><span class="d-none d-lg-inline">Pengaturan Cetak</span>
                                        </button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary flex-shrink-0" id="undoBtn" onclick="undoTable()" disabled title="Undo (Ctrl+Z)">
                                        <i class="ri-arrow-go-back-line"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary flex-shrink-0" id="redoBtn" onclick="redoTable()" disabled title="Redo (Ctrl+Shift+Z)">
                                        <i class="ri-arrow-go-forward-line"></i>
                                    </button>

                                    <div class="input-group input-group-sm" style="flex: 1 1 200px; max-width:280px;">
                                        <span class="input-group-text bg-white"><i class="ri-search-line text-muted"></i></span>
                                        <input type="search" id="rosterSearch" class="form-control" placeholder="Cari nama, jabatan, mapel..." oninput="filterRoster()">
                                    </div>

                                    @if($selectedSchool?->work_unit_id)
                                        <a href="{{ route('user.schools.satuan-kerja.task-hours', ['userId' => $userId, 'workUnitId' => $selectedSchool->work_unit_id]) }}"
                                           class="btn btn-sm btn-outline-info text-nowrap flex-shrink-0" target="_blank" title="Atur JP tugas tambahan">
                                            <i class="ri-settings-3-line me-1"></i><span class="d-none d-lg-inline">JP Tugas</span>
                                        </a>
                                    @endif

                                    <button type="button" class="btn btn-sm btn-outline-primary text-nowrap flex-shrink-0" onclick="showAddTask()" title="Tugas Tambahan">
                                        <i class="ri-user-settings-line me-1"></i><span class="d-none d-lg-inline">Tugas Tambahan</span>
                                    </button>

                                    <button type="button" class="btn btn-sm btn-outline-warning text-nowrap flex-shrink-0" onclick="resetTableData()" title="Kosongkan isian mapel & tugas pada formulir (tidak menghapus data SK di database)">
                                        <i class="ri-refresh-line me-1"></i><span class="d-none d-lg-inline">Kosongkan Isian</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info border-0 border-bottom rounded-0 py-2 mb-0">
                        <i class="ri-information-line me-1"></i>
                        Nama, jabatan, mapel, dan tugas tambahan berada pada baris yang sama. Jika GTK mengajar lebih dari satu mapel atau memiliki lebih dari satu tugas, baris ditambah mengikuti jumlah terbanyak. Total jam dihitung otomatis.
                    </div>
                    <div class="table-container matrix-scroll">
                        <table class="table table-hover table-sm align-middle table-freeze matrix-table mb-0">
                            <thead class="table-light text-center align-middle">
                                <tr>
                                    <th rowspan="2" class="text-nowrap freeze-col freeze-no">No</th>
                                    <th rowspan="2" class="text-nowrap freeze-col freeze-nama">Nama</th>
                                    <th rowspan="2" class="text-wrap">Jabatan</th>
                                    <th rowspan="2" class="text-wrap">Mapel</th>
                                    <th rowspan="2" class="text-wrap">Tugas Tambahan</th>
                                    @foreach($byGrade as $level => $groups)
                                        <th colspan="{{ $groups->count() }}" class="text-nowrap grade-start grade-{{ $loop->index % 2 === 0 ? 'odd' : 'even' }}">
                                            {{ $groups->first()->gradeLevel->name ?? "Kelas $level" }}
                                        </th>
                                    @endforeach                                    <th rowspan="2" class="text-nowrap">JP</th>
                                    <th rowspan="2" class="text-nowrap">Total Jam</th>
                                </tr>
                                <tr>
                                    @foreach($byGrade as $level => $groups)
                                        @foreach($groups as $sg)
                                            <th class="text-nowrap rombel-col {{ $loop->first ? 'grade-start' : '' }} grade-{{ $loop->parent->index % 2 === 0 ? 'odd' : 'even' }}">{{ $sg->name }}</th>
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
        <div class="d-flex justify-content-start gap-2 sticky-bottom bg-body border-top z-3 py-2 mt-3">
            <a href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
            <button type="button" class="btn btn-outline-primary" onclick="saveDraft(true)">
                <i class="ri-draft-line me-1"></i> Simpan Draft
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="ri-save-line me-1"></i> {{ $isEdit ? 'Simpan Perubahan' : 'Simpan SK' }}
            </button>
        </div>

        <input type="hidden" name="assignments_json" id="assignmentsJson" value="">
        <input type="hidden" name="other_tasks_json" id="otherTasksJson" value="">
        <input type="hidden" name="positions_json" id="positionsJson" value="">
    </form>

    {{-- Modal: Add Task --}}
    <div class="modal fade" id="addTaskModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-user-settings-line me-2 text-primary"></i>Tambah Tugas Tambahan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small mb-1">Guru</label>
                        <select id="taskTeacherSelect" class="form-select">
                            <option value="">— Pilih Guru —</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small mb-1">Jenis Tugas</label>
                        <select id="taskTypeSelect" class="form-select" onchange="toggleTaskFields()">
                            <option value="">— Pilih Jenis Tugas —</option>
                            <option value="koordinator">Koordinator Mata Pelajaran (pilih mapel)</option>
                            @foreach($taskTypeOptions as $type)
                                <option value="{{ $type['kode'] }}"
                                        data-code="{{ $type['kode'] }}"
                                        data-name="{{ $type['nama'] }}"
                                        data-hours="{{ $type['hours'] }}">{{ $type['nama'] }}</option>
                            @endforeach
                            <option value="kesiswaan">Kesiswaan / PD / PO</option>
                            <option value="custom">Tugas Lain (Custom)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="rombelSelectWrap">
                        <label class="form-label small mb-1">Rombel</label>
                        <select id="taskRombelSelect" class="form-select">
                            <option value="">— Pilih Rombel —</option>
                            @foreach($studyGroups as $sg)
                                <option value="{{ $sg->id }}">{{ $sg->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="taskHoursOnlyWrap" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label small mb-1">Jam per Minggu</label>
                            <input type="number" id="taskHoursOnly" class="form-control" value="3" min="0" max="40">
                        </div>
                    </div>

                    <div id="customTaskWrap" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label small mb-1">Nama Tugas</label>
                            <input type="text" id="taskName" class="form-control" placeholder="cth: Wali Kelas 7A, Koordinator Bahasa">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small mb-1">Jam per Minggu</label>
                            <input type="number" id="taskHours" class="form-control" value="3" min="0" max="40">
                        </div>
                    </div>

                    <div class="mb-3" id="koordinatorMapelWrap" style="display:none;">
                        <label class="form-label small mb-1">Mata Pelajaran</label>
                        <select id="koordinatorMapelSelect" class="form-select">
                            <option value="">— Pilih Mapel —</option>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="addTaskFromModal()">
                        <i class="ri-add-line me-1"></i> Tambah
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: pilih rombel untuk Wali Kelas --}}
    <div class="modal fade" id="taskRombelPickerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-door-open-line me-2 text-primary"></i>Pilih Rombel Wali Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small mb-1">Rombel</label>
                    <select id="taskRombelPickerSelect" class="form-select">
                        <option value="">— Pilih Rombel —</option>
                        @foreach($studyGroups as $sg)
                            <option value="{{ $sg->id }}">{{ $sg->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="confirmTaskRombel()">
                        <i class="ri-check-line me-1"></i> Tambahkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($isEdit)
        @include('institution-decrees._print_settings_modal')
    @endif
@endsection

@section('script')
<script>
/* =====================================================================
   KONFIGURASI & STATE
   ===================================================================== */
const teachers        = {!! json_encode($teachers->map(function ($t) use ($crossUnitByTeacher, $currentPositions) {
    $cross = $crossUnitByTeacher->get($t->id);
    return [
        'id' => $t->id,
        'name' => $t->name,
        'role' => $t->getRoleNames()->first(),
        'position' => $currentPositions[$t->id] ?? 'GTK',
        'external' => (bool) $cross,
        'home_work_unit' => $cross?->homeWorkUnit?->name,
        'recommended_subject' => $cross?->subject?->name,
        'cross_unit_assignment_id' => $cross?->id,
    ];
})->values()->toArray()) !!};
const subjects        = {!! json_encode($subjects->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values()->toArray()) !!};
const studyGroups     = {!! json_encode($studyGroups->map(fn($sg) => ['id' => $sg->id, 'name' => $sg->name, 'level' => $sg->gradeLevel->level ?? 0])->toArray()) !!};
const currentAssignments = {!! json_encode($currentAssignments) !!};
const currentTasks       = {!! json_encode($currentTasks) !!};
const currentPositions   = {!! json_encode($currentPositions) !!};
const currentPositionIds = {!! json_encode($currentPositionIds) !!};
const positionOptions    = {!! json_encode($positionOptions) !!};
const jpDefaults         = {!! json_encode($jpDefaults) !!};
const taskTypeOptions    = {!! json_encode($taskTypeOptions) !!};
const draftStorageKey    = {!! json_encode('institution-decree-draft:v3:'.$userId.':'.($selectedSchoolId ?? 'none').':'.($selectedAyId ?? 'none').':'.($isEdit ? $decree->id : 'new')) !!};
const AUTOSAVE_DELAY     = 2500;

// Field yang tidak boleh ikut draft: token CSRF & method hanya berlaku untuk
// sesi/halaman saat draft dibuat. Memulihkannya membuat form mengirim token
// kedaluwarsa → HTTP 419 saat menyimpan.
const DRAFT_IGNORED_FIELDS = ['_token', '_method'];

const gradeCols = studyGroups.map(sg => ({ id: sg.id, name: sg.name, level: sg.level }));

/**
 * Selang-seling warna per tingkat kelas. Peringkat level dipakai (bukan
 * nilainya) supaya 1, 2, 3 … tetap bergantian walau ada level yang kosong.
 */
const gradeBands = [...new Set(gradeCols.map(sg => String(sg.level)))].sort((a, b) => Number(a) - Number(b));
function gradeBand(level) {
    const idx = gradeBands.indexOf(String(level));
    return idx % 2 === 0 ? 'odd' : 'even';
}

let activeSubjects = {};
let activeTasks    = {};
let teacherCounter = 0;
let matrixTeacherOrder = [];
let undoStack = [];
let redoStack = [];
let autosaveTimer = null;
let isSubmitting = false;

/* =====================================================================
   UTILITAS
   ===================================================================== */
function showWarning(message, title = 'Perhatian') {
    if (window.Swal) {
        Swal.fire({ icon: 'warning', title, text: message, confirmButtonText: 'Mengerti' });
    } else { alert(message); }
}

/**
 * Notifikasi ringan tanpa tombol: cukup memberi tahu apa yang baru dipindah.
 * Memakai toast SweetAlert agar tidak menghalangi pengisian matriks.
 */
function showSuccess(message) {
    if (window.Swal && Swal.mixin) {
        Swal.mixin({
            toast: true,
            position: 'top-end',
            icon: 'success',
            timer: 2600,
            showConfirmButton: false,
            timerProgressBar: true,
        }).fire({ title: message });
        return;
    }

    showWarning(message, 'Berhasil');
}

function confirmRemoval(title, text, onConfirm) {
    if (window.Swal) {
        Swal.fire({ icon: 'warning', title, text, showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal' })
            .then(result => { if (result.isConfirmed) onConfirm(); });
        return;
    }
    if (confirm(text)) onConfirm();
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[ch]));
}

function positionOptionsHtml(tid) {
    const current = currentPositionIds[tid] || '';
    const currentName = currentPositions[tid] || '';
    const hasCurrent = positionOptions.some(p => String(p.id) === String(current));
    const keep = (!hasCurrent && currentName && currentName !== 'GTK')
        ? `<option value="__keep__" selected>${escapeHtml(currentName)}</option>` : '';
    return '<option value="">GTK</option>' + keep + positionOptions.map(p =>
        `<option value="${p.id}" ${String(current) === String(p.id) ? 'selected' : ''}>${escapeHtml(p.name)}</option>`
    ).join('');
}

function subjectOptionsHtml(sid, tid) {
    const used = (activeSubjects[tid] || []).map(s => String(s.id || '')).filter(id => id && id !== String(sid));
    return '<option value="">— Pilih Mapel —</option>' + subjects.map(s => {
        const disabled = used.includes(String(s.id)) ? 'disabled' : '';
        return `<option value="${s.id}" ${String(s.id) === String(sid) ? 'selected' : ''} ${disabled}>${escapeHtml(s.name)}</option>`;
    }).join('');
}

function subjectJp(tid, sid) {
    if (!sid) return 0;
    const row = document.getElementById(`sub-row-${tid}-${sid}`);
    if (!row) return 0;
    let total = 0;
    row.querySelectorAll('.rombel-toggle:checked').forEach(t => { total += parseFloat(t.dataset.hours) || 0; });
    return total;
}

/* ---------------------------------------------------------------------
   MAPEL REPEATER — format identik dengan Tugas Tambahan:
   - mapel terpilih ditampilkan sebagai badge + tombol + / trash
   - slot kosong (select) hanya muncul saat user klik tombol +
   - jika belum ada mapel & tidak ada slot kosong → tampilkan tombol + saja
   --------------------------------------------------------------------- */
/* =====================================================================
   RENDER MATRIKS: satu baris = satu pasangan mapel/tugas.
   Jumlah baris per GTK = max(jumlah mapel, jumlah tugas), minimal 1.
   ===================================================================== */
function subjectTotalHours(sub) {
    if (!sub || !sub.hours) return 0;
    return Object.values(sub.hours).reduce((sum, h) => sum + (parseFloat(h) || 0), 0);
}

function teacherTotalHours(tid) {
    const subjectHours = (activeSubjects[tid] || []).reduce((sum, sub) => sum + (sub.id ? subjectTotalHours(sub) : 0), 0);
    const taskHours = (activeTasks[tid] || []).reduce((sum, task) => sum + (parseFloat(task.hours) || 0), 0);
    return subjectHours + taskHours;
}

function positionControlHtml(teacher, tid) {
    if (teacher.external) {
        return `<span class="badge bg-info-subtle text-info">${escapeHtml(teacher.position || 'GTK')}</span>`;
    }

    return `<select class="form-select form-select-sm position-select" data-teacher-id="${tid}" onchange="changePosition(this)" style="min-width:180px">
        ${positionOptionsHtml(tid)}
    </select>`;
}

function mapelCellHtml(tid, sub, index, subjectCount) {
    if (sub && sub.id) {
        return `<div class="d-flex align-items-center gap-1">
            <span class="badge bg-warning-subtle text-warning text-nowrap flex-grow-1 text-start">${escapeHtml(sub.name || '')}</span>
            <button type="button" class="btn btn-sm btn-icon btn-soft-primary flex-shrink-0"
                    onclick="addSubjectSlot('${tid}')" title="Tambah Mapel">
                <i class="ri-add-line"></i>
            </button>
            <button type="button" class="btn btn-sm btn-icon btn-soft-danger flex-shrink-0"
                    onclick="removeSubjectItem('${tid}', '${escapeHtml(sub.id)}')" title="Hapus Mapel">
                <i class="ri-delete-bin-6-line"></i>
            </button>
        </div>`;
    }

    if (sub && !sub.id) {
        return `<div class="d-flex align-items-center gap-1">
            <select class="form-select form-select-sm subject-select flex-grow-1"
                    data-teacher-id="${tid}" data-sub-index="${index}"
                    data-searchable data-placeholder="Cari mapel..."
                    onchange="changeSubject(this)">
                ${subjectOptionsHtml('', tid)}
            </select>
            <button type="button" class="btn btn-sm btn-icon btn-soft-danger flex-shrink-0"
                    onclick="removeSubjectItem('${tid}', '')" title="Batal">
                <i class="ri-delete-bin-6-line"></i>
            </button>
        </div>`;
    }

    if (index === subjectCount) {
        return `<button type="button" class="btn btn-sm btn-icon btn-soft-primary"
                        onclick="addSubjectSlot('${tid}')" title="Tambah Mapel">
            <i class="ri-add-line"></i>
        </button>`;
    }

    return '';
}

function taskCellHtml(tid, task, index, taskCount) {
    if (task) {
        return `<div class="d-flex align-items-center gap-1">
            <span class="badge bg-info-subtle text-info text-nowrap flex-grow-1 text-start">
                ${escapeHtml(task.name || '')}${task.hours ? ` · ${task.hours} JP` : ''}
            </span>
            <button type="button" class="btn btn-sm btn-icon btn-soft-primary flex-shrink-0"
                    onclick="showAddTask('${tid}')" title="Tambah Tugas Tambahan">
                <i class="ri-add-line"></i>
            </button>
            <button type="button" class="btn btn-sm btn-icon btn-soft-danger flex-shrink-0"
                    onclick="removeActiveTask('${tid}', ${index})" title="Hapus tugas">
                <i class="ri-delete-bin-6-line"></i>
            </button>
        </div>`;
    }

    if (index === taskCount) {
        return `<button type="button" class="btn btn-sm btn-icon btn-soft-info"
                        onclick="showAddTask('${tid}')" title="Tambah Tugas Tambahan">
            <i class="ri-add-line"></i>
        </button>`;
    }

    return '';
}

function rombelCellHtml(tid, sub, sg) {
    if (!sub || !sub.id) return '';

    const stored = sub.hours ? sub.hours[sg.id] : null;
    const checked = stored !== null && stored !== undefined && Number(stored) > 0;
    const defaultHours = jpDefaults[`${sub.id}|${sg.id}`] ?? '';
    const hours = checked ? stored : defaultHours;

    return `<label class="form-check d-inline-flex flex-column align-items-center gap-1 m-0">
        <input type="checkbox" class="form-check-input rounded-circle rombel-toggle m-0"
               data-tid="${tid}" data-sid="${sub.id}" data-sgid="${sg.id}"
               data-hours="${hours}" data-default="${defaultHours}"
               onchange="toggleRombel(this)" title="Pilih rombel" ${checked ? 'checked data-active="1"' : 'data-active="0"'}>
        <span class="jp-badge small fw-semibold text-success ${checked ? '' : 'd-none'}">${hours || 0} JP</span>
    </label>`;
}

function renderTeacherRows(tbody, teacher, tid, number) {
    const subjects = activeSubjects[tid] || [];
    const tasks = activeTasks[tid] || [];
    const rowCount = Math.max(subjects.length, tasks.length, 1);

    for (let index = 0; index < rowCount; index++) {
        const sub = subjects[index] || null;
        const task = tasks[index] || null;
        const tr = document.createElement('tr');
        tr.dataset.teacher = tid;
        tr.dataset.rowIndex = index;
        if (index === 0) tr.id = `teacher-row-${tid}`;
        if (sub && sub.id) tr.dataset.subject = sub.id;

        let cells = '';

        if (index === 0) {
            cells += `<td rowspan="${rowCount}" class="text-center fw-semibold freeze-col freeze-no">${number}</td>`;
            cells += `<td rowspan="${rowCount}" class="fw-semibold text-nowrap freeze-col freeze-nama">
                ${escapeHtml(teacher.name)}
                ${teacher.external ? `<div class="small text-info"><i class="ri-building-2-line me-1"></i>GTK Penugasan Pondok${teacher.home_work_unit ? ` · ${escapeHtml(teacher.home_work_unit)}` : ''}</div>` : ''}
            </td>`;
            cells += `<td rowspan="${rowCount}" class="text-wrap">${positionControlHtml(teacher, tid)}</td>`;
        }

        cells += `<td class="text-wrap">${mapelCellHtml(tid, sub, index, subjects.length)}</td>`;
        cells += `<td class="text-wrap">${taskCellHtml(tid, task, index, tasks.length)}</td>`;
        cells += gradeCols.map((sg, gi) => `<td class="text-center rombel-col ${gi > 0 && sg.level !== gradeCols[gi - 1].level ? 'grade-start' : ''} grade-${gradeBand(sg.level)}">${rombelCellHtml(tid, sub, sg)}</td>`).join('');
        cells += `<td class="text-center fw-semibold row-jp">${sub && sub.id ? subjectTotalHours(sub) : 0}</td>`;

        if (index === 0) {
            cells += `<td rowspan="${rowCount}" class="text-center fw-bold" id="teacher-total-${tid}">${teacherTotalHours(tid)}</td>`;
        }

        tr.innerHTML = cells;
        tbody.appendChild(tr);
    }
}

/**
 * Peringkat jabatan, cermin dari App\Services\DecreePositionOrdering agar
 * urutan baris di layar sama dengan urutan matriks saat dicetak.
 * 1 KSP, 2 Wakil, 3 Kepala unit, 4 Koordinator, 5 Guru, 6 Staf, 7 Tata Usaha.
 */
function jabatanRank(position) {
    const name = String(position || '').toLowerCase().trim().replace(/\s+/g, ' ');
    if (!name) return 8;

    const starts = prefix => name.startsWith(prefix);
    const has = needle => name.includes(needle);
    const isTu = has('tata usaha') || /\btu\b/.test(name);

    if (starts('wakil') || starts('wadir') || starts('wakasek')) return 2;

    const isKepala = starts('kepala') || starts('ketua');
    if (isKepala && !isTu && (has('satuan pendidikan') || has('sekolah') || has('madrasah')
        || starts('mudir') || starts('direktur') || starts('pengasuh'))) {
        return 1;
    }
    if (starts('mudir') || starts('direktur')) return 1;

    if (isTu && !isKepala && !starts('koordinator')) return 7;
    if (isKepala) return 3;
    if (starts('koordinator')) return 4;
    if (starts('guru') || has(' guru')) return 5;
    if (starts('staf') || starts('staff') || starts('tenaga') || isTu) return 6;

    return 8;
}

function teacherSortKey(t) {
    const position = currentPositions[t.id] || t.position || '';
    return String(jabatanRank(position)).padStart(2, '0') + '|' + String(t.name || '').toLowerCase();
}

function renderMatrix() {
    const tbody = document.getElementById('matrixBody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!matrixTeacherOrder.length) {
        tbody.innerHTML = `<tr><td colspan="30" class="text-center text-muted py-4">
            <i class="ri-team-line fs-3 d-block mb-2"></i>Belum ada GTK pada satuan kerja ini.
        </td></tr>`;
        return;
    }

    // Baris selalu diurutkan berdasarkan jabatan (tertinggi lebih dulu), bukan
    // urutan penambahan atau urutan draft. Nama hanya penentu bila jabatan sama.
    // GTK yang tidak ditemukan pada daftar tetap dihitung agar Nomor tidak bolong.
    const ordered = matrixTeacherOrder
        .map(tid => ({ tid, teacher: teachers.find(t => String(t.id) === String(tid)) }))
        .sort((a, b) => {
            if (!a.teacher) return 1;
            if (!b.teacher) return -1;
            return teacherSortKey(a.teacher).localeCompare(teacherSortKey(b.teacher));
        });

    let number = 0;
    ordered.forEach(entry => {
        if (!entry.teacher) return;
        number += 1;
        renderTeacherRows(tbody, entry.teacher, entry.tid, number);
    });

    if (typeof window.initSearchableSelects === 'function') {
        window.initSearchableSelects(tbody);
    }

    document.getElementById('teacherCount').textContent = matrixTeacherOrder.length;
    refreshTotal();
    filterRoster();
}

function refreshTotal(tid) {
    const tids = tid ? [tid] : matrixTeacherOrder;

    tids.forEach(t => {
        (activeSubjects[t] || []).forEach(sub => {
            if (!sub.id) return;
            const row = document.querySelector(`tr[data-teacher="${t}"][data-subject="${sub.id}"]`);
            if (!row) return;
            const cell = row.querySelector('.row-jp');
            if (cell) cell.textContent = subjectTotalHours(sub);
        });

        const totalEl = document.getElementById(`teacher-total-${t}`);
        if (totalEl) totalEl.textContent = teacherTotalHours(t);
    });
}

function refreshSubjectRepeater() {
    renderMatrix();
}

/* =====================================================================
   AKSI MATRIKS
   ===================================================================== */
function addTeacherToMatrix(teacherId) {
    const sel = document.getElementById('newTeacherSelect');
    const tid = teacherId || (sel ? sel.value : null);
    if (!tid) { showWarning('Pilih guru terlebih dahulu.'); return; }
    if (matrixTeacherOrder.some(id => String(id) === String(tid))) { showWarning('Guru sudah ada di tabel.'); return; }

    const teacher = teachers.find(t => String(t.id) === String(tid));
    if (!teacher) return;

    matrixTeacherOrder.push(tid);
    activeSubjects[tid] = activeSubjects[tid] || [];
    activeTasks[tid] = activeTasks[tid] || (currentTasks[tid] ? currentTasks[tid].slice() : []);
    teacherCounter = matrixTeacherOrder.length;

    if (sel) {
        sel.querySelector(`option[value="${tid}"]`)?.remove();
        sel.value = '';
    }

    renderMatrix();
}

function addSubjectSlot(tid) {
    activeSubjects[tid] = activeSubjects[tid] || [];

    if (activeSubjects[tid].some(item => !item.id)) {
        const empty = document.querySelector(`#matrixBody select[data-teacher-id="${tid}"][data-sub-index]`);
        if (empty) { if (window.jQuery && jQuery(empty).data('select2')) jQuery(empty).select2('open'); else empty.focus(); }
        return;
    }

    activeSubjects[tid].push({ id: '', name: '', hours: {} });
    renderMatrix();

    const select = document.querySelector(`#matrixBody select[data-teacher-id="${tid}"][data-sub-index]`);
    if (select) { if (window.jQuery && jQuery(select).data('select2')) jQuery(select).select2('open'); else select.focus(); }
}

function changeSubject(select) {
    const tid = select.dataset.teacherId;
    const index = parseInt(select.dataset.subIndex);
    const newSid = select.value;
    if (!tid || !newSid || Number.isNaN(index)) return;

    const subject = subjects.find(s => String(s.id) === String(newSid));
    if (!subject) return;

    const entries = activeSubjects[tid] || [];
    if (entries.some((s, i) => i !== index && String(s.id) === String(newSid))) {
        showWarning('Mapel tersebut sudah ada untuk GTK ini.');
        select.value = '';
        return;
    }

    const current = entries[index] || { id: '', name: '', hours: {} };
    current.id = newSid;
    current.name = subject.name;
    current.hours = current.hours || {};
    entries[index] = current;
    activeSubjects[tid] = entries;

    renderMatrix();
    scheduleAutosave();
}

function removeSubjectItem(tid, sid) {
    if (!sid) {
        activeSubjects[tid] = (activeSubjects[tid] || []).filter(item => item.id);
    } else {
        activeSubjects[tid] = (activeSubjects[tid] || []).filter(item => String(item.id) !== String(sid));
    }

    renderMatrix();
    scheduleAutosave();
}

function removeSubjectRow(tid, sid) {
    removeSubjectItem(tid, sid);
}

function subjectIsTahfidz(sid) {
    const subject = subjects.find(s => String(s.id) === String(sid));
    return (subject?.name || '').toLowerCase().includes('tahfidz');
}

/**
 * Cari toggle lain yang memegang mapel + rombel yang sama (GTK lain).
 * Tahfidz dikecualikan karena boleh diampu lebih dari satu guru.
 */
function findRombelHolder(toggle, sid, sgid) {
    if (subjectIsTahfidz(sid)) return null;

    return Array.from(document.querySelectorAll('.rombel-toggle:checked')).find(other =>
        other !== toggle
        && String(other.dataset.sid) === String(sid)
        && String(other.dataset.sgid) === String(sgid)
    ) || null;
}

/**
 * Pindahkan mapel + rombel dari GTK lain ke GTK yang baru dicentang.
 * Nilai JP ikut dipindah agar guru baru tidak kehilangan jam yang sudah diisi.
 */
function moveSubjectToTeacher(holder, target, sid, sgid) {
    const fromTid = holder.dataset.tid;
    const toTid = target.dataset.tid;
    if (String(fromTid) === String(toTid)) return;

    const fromSub = (activeSubjects[fromTid] || []).find(s => String(s.id) === String(sid));
    const hours = parseFloat(holder.dataset.hours) || parseFloat(holder.dataset.default) || 0;

    const toSub = (activeSubjects[toTid] || []).find(s => String(s.id) === String(sid));
    if (!toSub) {
        const subject = subjects.find(s => String(s.id) === String(sid));
        activeSubjects[toTid] = activeSubjects[toTid] || [];
        activeSubjects[toTid].push({
            id: sid,
            name: subject?.name || fromSub?.name || '',
            hours: { [sgid]: hours },
        });
    } else {
        toSub.hours = toSub.hours || {};
        toSub.hours[sgid] = hours;
    }

    // Lepas dari guru lama; baris mapelnya dibuang bila rombelnya sudah kosong.
    if (fromSub?.hours) {
        delete fromSub.hours[sgid];
        if (!Object.keys(fromSub.hours).length) {
            activeSubjects[fromTid] = (activeSubjects[fromTid] || []).filter(
                s => String(s.id) !== String(sid)
            );
        }
    }

    const fromLabel = teachers.find(t => String(t.id) === String(fromTid))?.name || 'GTK lain';
    const toLabel = teachers.find(t => String(t.id) === String(toTid))?.name || 'GTK ini';
    showSuccess(`${subjectLabel(sid)} ${rombelLabel(sgid)} dipindahkan dari ${fromLabel} ke ${toLabel}.`);
}

function subjectLabel(sid) {
    return subjects.find(s => String(s.id) === String(sid))?.name || 'Mapel';
}

function rombelLabel(sgid) {
    const name = studyGroups.find(sg => String(sg.id) === String(sgid))?.name || '';
    return name ? `(${name})` : '';
}

function toggleRombel(toggle) {
    const tid = toggle.dataset.tid;
    const sid = toggle.dataset.sid;
    const sgid = toggle.dataset.sgid;

    const holder = toggle.checked ? findRombelHolder(toggle, sid, sgid) : null;

    // Mapel + rombel pindah ke GTK ini bila sudah dipegang GTK lain. Langkah ini
    // juga membuat baris mapel pada GTK penerima bila belum ada.
    if (holder) moveSubjectToTeacher(holder, toggle, sid, sgid);

    const sub = (activeSubjects[tid] || []).find(s => String(s.id) === String(sid));
    if (!sub) return;

    sub.hours = sub.hours || {};
    const badge = toggle.closest('td')?.querySelector('.jp-badge');

    if (toggle.checked) {
        const hours = parseFloat(toggle.dataset.hours) || parseFloat(toggle.dataset.default) || 0;
        sub.hours[sgid] = hours;
        toggle.dataset.hours = hours;
        toggle.dataset.active = '1';
        if (badge) { badge.textContent = `${hours} JP`; badge.classList.remove('d-none'); }
    } else {
        delete sub.hours[sgid];
        toggle.dataset.active = '0';
        if (badge) badge.classList.add('d-none');
    }

    if (holder) {
        // Baris guru lama dan guru baru berubah, jadi tabel digambar ulang.
        renderMatrix();
    } else {
        refreshTotal(tid);
    }

    scheduleAutosave();
}

function changePosition(select) {
    const tid = select.dataset.teacherId;
    if (!tid) return;
    currentPositions[tid] = select.options[select.selectedIndex]?.text || 'GTK';
    currentPositionIds[tid] = select.value;
    scheduleAutosave();
}
function removeActiveTask(tid, index) {
    if (!activeTasks[tid] || !activeTasks[tid][index]) return;
    pushHistory();
    activeTasks[tid].splice(index, 1);
    renderMatrix();
    scheduleAutosave();
}

/* =====================================================================
   TAMBAH TUGAS
   ===================================================================== */
function confirmTaskRombel() {
    const select = document.getElementById('taskRombelPickerSelect');
    const rombel = studyGroups.find(sg => String(sg.id) === String(select.value));
    const pending = window._pendingTask;
    if (!pending || !rombel) { showWarning('Pilih rombel terlebih dahulu.'); return; }
    addActiveTask(pending.tid, pending.type, {
        name: `Wali Kelas ${rombel.name}`, code: 'wali-kelas',
        hours: pending.type.hours ?? 12, studyGroupId: rombel.id, rombel: rombel.name,
    });
    window._pendingTask = null;
    bootstrap.Modal.getInstance(document.getElementById('taskRombelPickerModal')).hide();
}

function addActiveTask(tid, type, extra = {}) {
    const code = extra.code || type.kode || type.id;
    const studyGroupId = extra.studyGroupId || null;
    activeTasks[tid] = activeTasks[tid] || [];
    const duplicate = activeTasks[tid].some(t => {
        if ((t.code || t.name) !== code) return false;
        if (!studyGroupId) return true;
        return t.studyGroupId && String(t.studyGroupId) === String(studyGroupId);
    });
    if (duplicate) { showWarning('Tugas tersebut sudah ada untuk GTK ini.'); return false; }
    activeTasks[tid].push({
        name: extra.name || type.nama, code,
        hours: extra.hours ?? type.hours ?? 3, studyGroupId, rombel: extra.rombel || null,
    });
    renderMatrix();
    scheduleAutosave();
    return true;
}

/* =====================================================================
   MODAL TAMBAH TUGAS
   ===================================================================== */
function showAddTask(tid = null) {
    if (tid) {
        const teacherSelect = document.getElementById('taskTeacherSelect');
        if (teacherSelect) teacherSelect.value = tid;
    }
    toggleTaskFields();
    new bootstrap.Modal(document.getElementById('addTaskModal')).show();
}

function toggleTaskFields() {
    const select = document.getElementById('taskTypeSelect');
    const type = select.value;
    const code = (select.selectedOptions[0]?.dataset.code || type || '').toLowerCase();
    const isWali = type === 'wali_kelas' || code === 'wali-kelas' || /wali kelas/i.test(select.selectedOptions[0]?.dataset.name || '');
    const isKoordinator = type === 'koordinator';
    const isCustom = type === 'custom' || type === 'kesiswaan';
    const isMaster = !!select.selectedOptions[0]?.dataset.code && !isWali && !isKoordinator;

    document.getElementById('rombelSelectWrap').style.display = isWali ? '' : 'none';
    document.getElementById('koordinatorMapelWrap').style.display = isKoordinator ? '' : 'none';
    document.getElementById('customTaskWrap').style.display = isCustom ? '' : 'none';
    document.getElementById('taskHoursOnlyWrap').style.display = isMaster ? '' : 'none';
    if (isMaster) { document.getElementById('taskHoursOnly').value = select.selectedOptions[0]?.dataset.hours || 3; }
}

function addTaskFromModal() {
    const tid = document.getElementById('taskTeacherSelect').value;
    const type = document.getElementById('taskTypeSelect').value;
    if (!tid) { showWarning('Pilih guru terlebih dahulu.'); return; }
    if (!type) { showWarning('Pilih jenis tugas terlebih dahulu.'); return; }

    const teacher = teachers.find(t => String(t.id) === String(tid));
    if (!teacher) { showWarning('Guru tidak ditemukan.'); return; }

    let taskCode = '', taskName = '', hours = 0, rombelId = null;
    const selectedOption = document.getElementById('taskTypeSelect').selectedOptions[0];
    const selectedCode = selectedOption?.dataset.code || '';
    const selectedName = selectedOption?.dataset.name || selectedOption?.text || '';

    if (type === 'wali_kelas' || selectedCode === 'wali-kelas') {
        const sgId = document.getElementById('taskRombelSelect').value;
        if (!sgId) { showWarning('Pilih rombel untuk Wali Kelas.'); return; }
        const sgName = document.querySelector(`#taskRombelSelect option[value="${sgId}"]`)?.text || '';
        taskCode = selectedCode || 'wali-kelas';
        taskName = `Wali Kelas ${sgName}`;
        hours = 12;
        rombelId = sgId;
    } else if (type === 'koordinator') {
        const mapelId = document.getElementById('koordinatorMapelSelect').value;
        if (!mapelId) { showWarning('Pilih mata pelajaran.'); return; }
        const mapelName = document.querySelector(`#koordinatorMapelSelect option[value="${mapelId}"]`)?.text || '';
        taskCode = 'koordinator';
        taskName = `Koordinator ${mapelName}`;
        hours = 3;
    } else if (type === 'kesiswaan') {
        taskCode = 'kesiswaan';
        taskName = document.getElementById('taskName')?.value?.trim() || 'Kesiswaan / PD / PO';
        hours = parseInt(document.getElementById('taskHours')?.value) || 3;
    } else if (selectedCode) {
        taskCode = selectedCode;
        taskName = selectedName || selectedCode;
        hours = parseInt(document.getElementById('taskHoursOnly')?.value) || parseInt(selectedOption?.dataset.hours) || 3;
    } else {
        taskCode = 'custom';
        taskName = document.getElementById('taskName')?.value?.trim();
        hours = parseInt(document.getElementById('taskHours')?.value) || 3;
        if (!taskName) { showWarning('Isi nama tugas.'); return; }
    }

    if (!document.getElementById(`teacher-row-${tid}`)) { addTeacherToMatrix(tid); }

    if (!activeTasks[tid]) activeTasks[tid] = [];
    const duplicate = activeTasks[tid].some(t => t.code === taskCode && (!rombelId || String(t.studyGroupId) === String(rombelId)));
    if (duplicate) { showWarning('Tugas tersebut sudah ada untuk GTK ini.'); return; }

    activeTasks[tid].push({ name: taskName, code: taskCode, hours, studyGroupId: rombelId });
    renderMatrix();
    scheduleAutosave();

    document.getElementById('taskName').value = '';
    document.getElementById('taskHours').value = '3';
    bootstrap.Modal.getInstance(document.getElementById('addTaskModal')).hide();
}

/* =====================================================================
   UNDO / REDO
   ===================================================================== */
function captureTableState() {
    const body = document.getElementById('matrixBody');
    if (!body) return null;
    return {
        subjects: JSON.parse(JSON.stringify(activeSubjects)),
        tasks: JSON.parse(JSON.stringify(activeTasks)),
        order: matrixTeacherOrder.slice(),
        positions: { ...currentPositionIds },
        positionNames: { ...currentPositions },
    };
}

function restoreTableState(state) {
    if (!state) return;
    // Tabel SELALU digambar ulang dari data, bukan dari HTML snapshot:
    // HTML lama bisa berasal dari roster yang berbeda (mis. sebelum GTK
    // ditemukan) sehingga baris GTK baru tidak pernah muncul.
    const body = document.getElementById('matrixBody');
    if (body) body.innerHTML = '';
    activeSubjects = state.subjects || {};
    activeTasks = state.tasks || {};

    // GTK dari server menjadi dasar urutan; GTK tambahan pada snapshot
    // disisipkan agar isian yang belum disimpan tidak hilang.
    const knownIds = teachers.map(t => String(t.id));
    const extraIds = (state.order || []).map(String).filter(id => !knownIds.includes(id));

    matrixTeacherOrder = [
        ...teachers.map(t => String(t.id)),
        ...extraIds.filter(id => activeSubjects[id] || activeTasks[id]),
    ];

    // Setiap GTK pada daftar harus punya entri, walau kosong, supaya
    // barisnya tetap tergambar saat draft belum memuatnya.
    matrixTeacherOrder.forEach(id => {
        if (!activeSubjects[id]) activeSubjects[id] = [];
        if (!activeTasks[id]) activeTasks[id] = [];
    });

    Object.keys(currentPositionIds).forEach(key => delete currentPositionIds[key]);
    Object.assign(currentPositionIds, state.positions || {});
    Object.keys(currentPositions).forEach(key => delete currentPositions[key]);
    Object.assign(currentPositions, state.positionNames || {});
    teacherCounter = matrixTeacherOrder.length;

    renderMatrix();
    updateHistoryButtons();
    scheduleAutosave();
}

function updateHistoryButtons() {
    const undo = document.getElementById('undoBtn');
    const redo = document.getElementById('redoBtn');
    if (undo) undo.disabled = undoStack.length === 0;
    if (redo) redo.disabled = redoStack.length === 0;
}

function pushHistory() {
    if (!document.getElementById('matrixBody')) return;
    const state = captureTableState();
    if (!state) return;
    undoStack.push(state);
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

/* =====================================================================
   AUTOSAVE
   ===================================================================== */
function scheduleAutosave() {
    clearTimeout(autosaveTimer);
    autosaveTimer = setTimeout(() => saveDraft(false), AUTOSAVE_DELAY);
}

function collectDraftFields() {
    const fields = {};
    document.querySelectorAll('#decreeForm [name]').forEach(field => {
        if (DRAFT_IGNORED_FIELDS.includes(field.name)) return;
        if (field.type === 'checkbox' || field.type === 'radio') { fields[field.name] = field.checked; }
        else { fields[field.name] = field.value; }
    });
    return fields;
}

function saveDraft(showToast = true) {
    try {
        const payload = { form: collectDraftFields(), table: captureTableState(), savedAt: new Date().toISOString() };
        localStorage.setItem(draftStorageKey, JSON.stringify(payload));
        flashAutosaveIndicator();
        if (showToast && window.Swal) {
            Swal.fire({ icon: 'success', title: 'Draft tersimpan', text: 'Data akan dipulihkan saat halaman dibuka kembali.', timer: 1500, showConfirmButton: false });
        }
    } catch (e) { console.warn('Autosave failed', e); }
}

function flashAutosaveIndicator() {
    const el = document.getElementById('autosaveIndicator');
    if (!el) return;
    const txt = document.getElementById('autosaveText');
    const now = new Date();
    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    if (txt) txt.textContent = `Tersimpan ${hh}:${mm}`;
    el.classList.remove('d-none');
    clearTimeout(flashAutosaveIndicator._t);
    flashAutosaveIndicator._t = setTimeout(() => el.classList.add('d-none'), 2500);
}

function loadDraft() {
    try {
        const raw = localStorage.getItem(draftStorageKey);
        if (!raw) return;
        const draft = JSON.parse(raw);
        if (!draft?.table) return;
        Object.entries(draft.form || {}).forEach(([name, value]) => {
            if (DRAFT_IGNORED_FIELDS.includes(name)) return;
            const field = document.querySelector(`#decreeForm [name="${name}"]`);
            if (!field) return;
            if (field.type === 'checkbox' || field.type === 'radio') { field.checked = !!value; }
            else { field.value = value; }
        });
        restoreTableState(draft.table);
        document.getElementById('draftRestoredAlert')?.classList.remove('d-none');
        updateInfoPreview();
    } catch (e) { console.warn('Draft restore failed', e); }
}

/* =====================================================================
   RESET DATA
   ===================================================================== */
function resetTableData() {
    const confirmFn = () => doResetTableData();
    if (window.Swal) {
        Swal.fire({
            icon: 'warning', title: 'Kosongkan isian matriks?',
            html: 'Isian <b>mapel, rombel, dan tugas tambahan</b> pada formulir akan dikosongkan.<br><b>Data SK yang sudah tersimpan di database tidak dihapus.</b><br>Nama GTK dan <b>jabatan</b> tetap dipertahankan.',
            showCancelButton: true, confirmButtonText: 'Ya, kosongkan', cancelButtonText: 'Batal', confirmButtonColor: '#d33',
        }).then(r => { if (r.isConfirmed) confirmFn(); });
    } else {
        if (confirm('Kosongkan isian mapel, rombel, dan tugas pada formulir? Data SK di database tidak dihapus.')) confirmFn();
    }
}

function doResetTableData() {
    pushHistory();

    const activeTeacherIds = matrixTeacherOrder.slice();
    activeSubjects = {};
    activeTasks = {};
    teacherCounter = 0;

    activeTeacherIds.forEach(tid => {
        activeSubjects[tid] = [];
        activeTasks[tid] = [];
    });

    matrixTeacherOrder = activeTeacherIds;
    teacherCounter = activeTeacherIds.length;
    renderMatrix();

    const sel = document.getElementById('newTeacherSelect');
    if (sel) {
        sel.innerHTML = '<option value="">-- Pilih Guru --</option>' +
            teachers.filter(t => !activeTeacherIds.includes(String(t.id))).map(t => `<option value="${t.id}">${escapeHtml(t.name)}</option>`).join('');
    }

    scheduleAutosave();
}

/* =====================================================================
   SEARCH / FILTER
   ===================================================================== */
function filterRoster() {
    const query = (document.getElementById('rosterSearch')?.value || '').trim().toLowerCase();
    document.querySelectorAll('tr[id^="teacher-row-"]').forEach(teacherRow => {
        const tid = teacherRow.id.replace('teacher-row-', '');
        const rows = [teacherRow, ...document.querySelectorAll(`tr[data-teacher="${tid}"]`)];
        const text = rows.map(r => r.textContent).join(' ').toLowerCase();
        rows.forEach(r => r.classList.toggle('d-none', !!query && !text.includes(query)));
    });
}

/* =====================================================================
   INFO PREVIEW
   ===================================================================== */
function updateInfoPreview() {
    if (!document.getElementById('infoSchool')) return;
    const schoolEl = document.getElementById('schoolSelect');
    const school = schoolEl ? schoolEl.selectedOptions[0]?.text || '-' : (document.querySelector('[name="school_id"]')?.closest('.col-md-3')?.querySelector('input[readonly]')?.value || '-');
    const ayEl = document.getElementById('aySelect');
    const ay = ayEl ? ayEl.selectedOptions[0]?.text || '-' : '-';
    const num = document.querySelector('[name="decree_number"]')?.value || '-';
    const title = document.querySelector('[name="title"]')?.value || '-';
    const date = document.querySelector('[name="issued_date"]')?.value || '-';

    document.getElementById('infoSchool').textContent = school;
    document.getElementById('infoNomor').innerHTML = `NOMOR&nbsp;&nbsp;: <strong>${escapeHtml(num)}</strong>`;
    document.getElementById('infoTA').innerHTML = `TA <strong>${escapeHtml(ay)}</strong>`;
    document.getElementById('infoTitle').innerHTML = `TENTANG : <strong>${escapeHtml(title)}</strong>`;
    if (date) {
        const d = new Date(date);
        const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        document.getElementById('infoTanggal').innerHTML = `TANGGAL : <strong>${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}</strong>`;
    }
}

/* =====================================================================
   SUBMIT
   ===================================================================== */
document.getElementById('decreeForm').addEventListener('submit', function () {
    // Hentikan autosave & buang draft agar sebelum-unload tidak menulis ulang
    // data yang baru saja dikirim (dan agar form berikutnya mulai bersih).
    isSubmitting = true;
    clearTimeout(autosaveTimer);
    try { localStorage.removeItem(draftStorageKey); } catch (_) {}

    const assignments = {};

    Object.entries(activeSubjects).forEach(([tid, subjectEntries]) => {
        (subjectEntries || []).forEach(sub => {
            if (!sub.id) return;
            const groupHours = {};
            Object.entries(sub.hours || {}).forEach(([sgid, hours]) => {
                const value = parseFloat(hours) || 0;
                if (value > 0) groupHours[sgid] = value;
            });
            if (Object.keys(groupHours).length) {
                if (!assignments[tid]) assignments[tid] = {};
                assignments[tid][sub.id] = groupHours;
            }
        });
    });
    document.getElementById('assignmentsJson').value = JSON.stringify(assignments);

    const positions = {};
    document.querySelectorAll('.position-select').forEach(sel => {
        if (sel.dataset.teacherId && sel.value !== '__keep__') { positions[sel.dataset.teacherId] = sel.value; }
    });
    document.getElementById('positionsJson').value = JSON.stringify(positions);

    const tasksArr = [];
    Object.entries(activeTasks).forEach(([tid, tasks]) => {
        tasks.forEach(t => {
            tasksArr.push({
                teacher_id: tid, task_name: t.name, task_code: t.code,
                weekly_hours: t.hours, study_group_id: t.studyGroupId || null,
            });
        });
    });
    document.getElementById('otherTasksJson').value = JSON.stringify(tasksArr);
});

/* =====================================================================
   KEEP-ALIVE SESI
   Form SK bisa terbuka lama; tanpa aktivitas server sesi kedaluwarsa dan
   token CSRF lama → simpan gagal 419. Ping ringan tiap 2 menit.
   ===================================================================== */
const SESSION_PING_INTERVAL = 2 * 60 * 1000;
let lastSessionPing = Date.now();

function refreshCsrfToken() {
    if (isSubmitting) return;
    lastSessionPing = Date.now();

    fetch('{{ route('csrf-token', [], false) }}', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    })
        .then(res => (res.ok ? res.json() : null))
        .then(data => {
            if (! data || ! data.token) return;
            document.querySelectorAll('#decreeForm input[name="_token"]').forEach(el => { el.value = data.token; });
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) meta.setAttribute('content', data.token);
        })
        .catch(() => {});
}

setInterval(function () {
    if (isSubmitting || document.visibilityState === 'hidden') return;
    if (Date.now() - lastSessionPing >= SESSION_PING_INTERVAL) refreshCsrfToken();
}, 60 * 1000);

/* =====================================================================
   INIT
   ===================================================================== */

/**
 * Baris kedua header (kode rombel) harus menempel tepat di bawah baris
 * pertama. Tingginya berbeda menurut font/pembesaran pengguna, jadi diukur
 * saat halaman dimuat dan setiap kali ukuran jendela berubah.
 */
function syncStickyHeaderOffset() {
    const table = document.querySelector('.matrix-table');
    if (!table) return;
    const firstRow = table.querySelector('thead tr:nth-child(1)');
    if (!firstRow) return;
    const height = Math.ceil(firstRow.getBoundingClientRect().height);
    if (height > 0) {
        document.querySelector('.matrix-scroll')?.style.setProperty('--matrix-head-row2', height + 'px');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    matrixTeacherOrder = teachers.map(t => String(t.id));
    teacherCounter = matrixTeacherOrder.length;

    syncStickyHeaderOffset();
    window.addEventListener('resize', syncStickyHeaderOffset);

    teachers.forEach(teacher => {
        const subjectEntries = [];

        (currentAssignments[teacher.id] || []).forEach(assignment => {
            let entry = subjectEntries.find(s => String(s.id) === String(assignment.subject_id));
            if (!entry) {
                entry = { id: assignment.subject_id, name: assignment.subject, hours: {} };
                subjectEntries.push(entry);
            }
            if (assignment.hours) {
                entry.hours[assignment.study_group_id] = assignment.hours;
            }
        });

        activeSubjects[teacher.id] = subjectEntries;
        activeTasks[teacher.id] = (currentTasks[teacher.id] || []).map(task => ({
            name: task.name,
            code: task.code,
            hours: task.hours,
            studyGroupId: task.studyGroupId ?? task.study_group_id ?? null,
            rombel: task.rombel || null,
        }));
    });

    renderMatrix();

    updateHistoryButtons();
    loadDraft();

    document.querySelectorAll('#decreeForm input, #decreeForm select, #decreeForm textarea').forEach(el => {
        el.addEventListener('change', () => { updateInfoPreview(); scheduleAutosave(); });
        if (el.type === 'text' || el.type === 'number' || el.tagName === 'TEXTAREA') {
            el.addEventListener('input', scheduleAutosave);
        }
    });

    updateInfoPreview();

    window.addEventListener('beforeunload', function () {
        if (isSubmitting) return;
        try { saveDraft(false); } catch (_) {}
    });
});
</script>
@endsection
