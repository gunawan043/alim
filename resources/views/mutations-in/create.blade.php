@extends('layouts.master')
@section('title') Ajukan PD Masuk @endsection

@section('css')
<style>
    /* Kartu pratinjau & badge mengikuti pola halaman PD Keluar. */
    .out-type-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
    .out-type-badge.mutation { background: #e7f5ff; color: #1971c2; }

    .preview-panel { height: 720px; }
</style>
@endsection

@section('content')
@php
    $mutationLayout = $mutationLayout ?? \App\Models\StudentMutationSetting::defaultLayout();
@endphp

@component('components.breadcrumb')
    @slot('li_1') Peserta Didik @endslot
    @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
    @slot('li_3') <a href="{{ route('user.mutations-in.index', ['userId' => $userId]) }}">PD Masuk</a> @endslot
    @slot('title') Ajukan PD Masuk @endslot
@endcomponent

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ route('user.mutations-in.store', ['userId' => $userId]) }}"
      id="form-in" data-preview-url="{{ route('user.mutations-in.preview-pdf', ['userId' => $userId]) }}">
    @csrf
    <input type="hidden" name="submit_now" value="0" id="submit-flag">
    <input type="hidden" name="school_id" value="{{ $schoolContextId }}">
    <input type="hidden" name="student_id" id="student-id-hidden" value="{{ $student?->id ?? '' }}">
    <input type="hidden" name="student_nis" id="f-nis" value="{{ old('student_nis', $defaultNis) }}">

    <div class="row">
        {{-- =============================================== --}}
        {{-- KIRI: FORM INPUT --}}
        {{-- =============================================== --}}
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header border-bottom-dashed">
                    <h5 class="mb-0"><i class="ri-edit-2-line me-1"></i>Formulir PD Masuk</h5>
                </div>
                <div class="card-body">

                    <div class="alert alert-info py-2 small">
                        <i class="ri-information-line me-1"></i>
                        Isi data pokok di bawah ini. Data lain (NIK, No. KK, alamat lengkap, data kesehatan, dll.)
                        dilengkapi setelah santri disetujui — sistem akan menandai santri yang datanya belum lengkap.
                    </div>

                    {{-- Cari santri terdaftar (opsional) --}}
                    <!-- <div class="mb-3">
                        <label class="form-label">Cari Santri Terdaftar</label>
                        <select id="student-select" class="form-select">
                            <option value="">— Santri baru (isi manual) —</option>
                            @foreach($groupedStudents as $sgName => $students)
                                @foreach($students as $s)
                                    <option value="{{ $s->id }}"
                                        data-name="{{ $s->name }}"
                                        data-nisn="{{ $s->nisn }}"
                                        data-nis="{{ $s->nis }}"
                                        data-gender="{{ $s->gender }}"
                                        data-gender-text="{{ $s->gender_text }}"
                                        data-birth-place="{{ $s->birth_place }}"
                                        data-birth-date="{{ $s->birth_date?->format('Y-m-d') }}"
                                        data-birth-date-text="{{ $s->birth_date?->format('d F Y') }}"
                                        data-religion="{{ $s->religion }}"
                                        data-address="{{ $s->address }}"
                                        data-class="{{ $sgName }}"
                                        data-prev-school="{{ $s->previous_school }}"
                                        data-parent-name="{{ $studentParentData[$s->id]['name'] ?? '' }}"
                                        data-parent-occupation="{{ $studentParentData[$s->id]['occupation'] ?? '' }}"
                                        data-parent-phone="{{ $studentParentData[$s->id]['phone'] ?? '' }}"
                                        data-parent-address="{{ $studentParentData[$s->id]['address'] ?? '' }}"
                                        {{ isset($student) && old('student_id', $student->id) == $s->id ? 'selected' : '' }}
                                    >{{ $s->name }} - {{ $sgName }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <div class="form-text">Kosongkan bila santri belum terdaftar — data santri baru akan dibuat saat disetujui.</div>
                    </div> -->

                    {{-- Info Santri (ringkasan pilihan) --}}
                    <!-- <div class="border rounded p-2 mb-3 small bg-light">
                        <div class="text-muted mb-1" style="font-size:10px;text-transform:uppercase;letter-spacing:0.5px">Data Santri</div>
                        <div class="row g-2">
                            <div class="col-6"><strong id="disp-name">{{ $student?->name ?? '-' }}</strong></div>
                            <div class="col-3">NISN: <span id="disp-nisn">{{ $student?->nisn ?? '-' }}</span></div>
                            <div class="col-3">NIS: <span id="disp-nis">{{ $student?->nis ?? '-' }}</span></div>
                            <div class="col-6">JK: <span id="disp-gender">{{ $student?->gender_text ?? '-' }}</span></div>
                            <div class="col-6">Kelas: <span id="disp-class">-</span></div>
                            <div class="col-12">TTL: <span id="disp-birth">-</span></div>
                            <div class="col-12">Alamat: <span id="disp-address">-</span></div>
                        </div>
                    </div> -->

                    <hr class="my-3">

                    {{-- Data Santri --}}
                    <h6 class="text-muted mb-2" style="font-size:10px;text-transform:uppercase">Data Santri</h6>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="student_name" id="f-student-name" class="form-control"
                            value="{{ old('student_name', $student?->name ?? '') }}" placeholder="Nama lengkap santri" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">NISN</label>
                            <input type="text" name="student_nisn" id="f-nisn" class="form-control"
                                value="{{ old('student_nisn', $student?->nisn ?? '') }}" placeholder="Boleh dikosongkan">
                            <div class="form-text">Kosongkan bila NISN belum ada — dilengkapi pada tahap pelengkapan data.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
                            <input type="text" name="student_birth_place" id="f-birth-place" class="form-control"
                                value="{{ old('student_birth_place', $student?->birth_place ?? '') }}" placeholder="Kota/kabupaten" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                            <input type="date" name="student_birth_date" id="f-birth-date" class="form-control"
                                value="{{ old('student_birth_date', $student?->birth_date?->format('Y-m-d') ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="student_gender" id="f-gender" class="form-select" required>
                                <option value="">—</option>
                                <option value="L" {{ old('student_gender', $student?->gender ?? null) === 'L' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="P" {{ old('student_gender', $student?->gender ?? null) === 'P' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Agama</label>
                            <input type="text" name="student_religion" id="f-religion" class="form-control"
                                value="{{ old('student_religion', $student?->religion ?? 'Islam') }}" placeholder="Agama">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kelas di Sekolah Asal</label>
                            <input type="text" name="student_previous_class" id="f-prev-class" class="form-control"
                                value="{{ old('student_previous_class') }}" placeholder="Contoh: 6">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Sekolah Asal <span class="text-danger">*</span></label>
                            <input type="text" name="student_previous_school" id="f-prev-school" class="form-control"
                                value="{{ old('student_previous_school', $student?->previous_school ?? '') }}" placeholder="Nama sekolah asal" required>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- Data Orang Tua/Wali --}}
                    <h6 class="text-muted mb-2" style="font-size:10px;text-transform:uppercase">Data Orang Tua/Wali</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Bapak <span class="text-danger">*</span></label>
                            <input type="text" name="father_name" id="f-father-name" class="form-control"
                                value="{{ old('father_name') }}" placeholder="Nama bapak" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pekerjaan Bapak</label>
                            <input type="text" name="father_occupation" id="f-father-job" class="form-control"
                                value="{{ old('father_occupation') }}" placeholder="Pekerjaan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Ibu <span class="text-danger">*</span></label>
                            <input type="text" name="mother_name" id="f-mother-name" class="form-control"
                                value="{{ old('mother_name') }}" placeholder="Nama ibu" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pekerjaan Ibu</label>
                            <input type="text" name="mother_occupation" id="f-mother-job" class="form-control"
                                value="{{ old('mother_occupation') }}" placeholder="Pekerjaan">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat Orang Tua</label>
                            <input type="text" name="parent_address" id="f-parent-addr" class="form-control"
                                value="{{ old('parent_address') }}" placeholder="Alamat lengkap">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. HP <span class="text-danger">*</span></label>
                            <input type="text" name="parent_phone" id="f-parent-phone" class="form-control"
                                value="{{ old('parent_phone') }}" placeholder="08xxxxxxxxxx" required>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- Data Penerimaan --}}
                    <h6 class="text-muted mb-2" style="font-size:10px;text-transform:uppercase">Data Penerimaan</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Kelas Diterima <span class="text-danger">*</span></label>
                            <input type="text" name="accepted_class" id="f-accepted-class" class="form-control"
                                value="{{ old('accepted_class') }}" placeholder="Contoh: 7" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Semester</label>
                            <input type="text" name="accepted_semester" id="f-semester" class="form-control"
                                value="{{ old('accepted_semester') }}" placeholder="Contoh: 1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                            <input type="text" name="accepted_academic_year" id="f-ay" class="form-control"
                                value="{{ old('accepted_academic_year') }}" placeholder="2026/2027" required>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- Keterangan Surat --}}
                    <h6 class="text-muted mb-2" style="font-size:10px;text-transform:uppercase">Surat</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">No. Surat</label>
                            <input type="text" name="letter_number" id="f-nosurat" class="form-control"
                                value="{{ old('letter_number') }}" placeholder="XX/YYYY">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ditetapkan di</label>
                            <input type="text" name="established_city" id="f-kota" class="form-control"
                                value="{{ old('established_city', $school?->city ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Masehi</label>
                            <input type="date" name="established_date" id="f-tanggal" class="form-control"
                                value="{{ old('established_date', $defaultDate) }}" onchange="syncHijriDate()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Hijriyah</label>
                            <input type="text" id="f-tanggal-hijri" name="hijri_date" class="form-control"
                                value="{{ $defaultDateHijri }}" readonly placeholder="Akan dihitung otomatis">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" id="f-notes" class="form-control" rows="2" placeholder="Catatan tambahan">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- Pejabat penandatangan --}}
                    <div class="border rounded p-2 mb-3 small" style="background:#f0f9ff;border-color:#b6d4f5">
                        <div class="text-muted mb-1" style="font-size:10px;text-transform:uppercase;letter-spacing:0.5px">Pejabat yang Menandatangani</div>
                        <div class="row g-2">
                            <div class="col-12">Nama: <strong id="disp-head-name">{{ $defaultHeadName ?: '-' }}</strong></div>
                            <div class="col-md-6">Jabatan: <span id="disp-head-title">{{ $defaultHeadTitle }}</span></div>
                            <div class="col-md-6">NUPY: <span id="disp-head-nupy">{{ $defaultHeadNupy ?: '-' }}</span></div>
                        </div>
                    </div>
                    <input type="hidden" name="head_name" id="f-head-name" value="{{ old('head_name', $defaultHeadName) }}">
                    <input type="hidden" name="head_title" id="f-head-title" value="{{ old('head_title', $defaultHeadTitle) }}">
                    <input type="hidden" name="head_nupy" id="f-head-nupy" value="{{ old('head_nupy', $defaultHeadNupy) }}">
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('user.mutations-in.index', ['userId' => $userId]) }}"
                           class="btn btn-light"><i class="ri-arrow-left-line me-1"></i> Batal</a>
                        <div>
                            <button type="button" class="btn btn-secondary" onclick="submitForm(false)">
                                <i class="ri-save-line me-1"></i> Draft
                            </button>
                            <button type="button" class="btn btn-success" onclick="submitForm(true)">
                                <i class="ri-send-plane-line me-1"></i> Ajukan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- =============================================== --}}
        {{-- KANAN: PREVIEW SURAT --}}
        {{-- =============================================== --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="mb-0"><i class="ri-file-text-line me-1"></i>Preview Surat</h5>
                        <small class="text-muted">
                            Margin {{ $mutationLayout['margin_top'] }}/{{ $mutationLayout['margin_right'] }}/{{ $mutationLayout['margin_bottom'] }}/{{ $mutationLayout['margin_left'] }} mm
                            · {{ $mutationLayout['font_family'] }} {{ $mutationLayout['font_size'] }} pt
                            · {{ strtoupper($mutationLayout['paper_size']) }}
                        </small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#mutationLayoutModal">
                            <i class="ri-settings-3-line me-1"></i>Pengaturan Layout
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#documentSignatureModal">
                            <i class="ri-shield-check-line me-1"></i>Tanda Tangan
                        </button>
                        <span class="out-type-badge mutation">PD Masuk</span>
                    </div>
                </div>
                <div class="card-body p-2">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <small class="text-muted"><i class="ri-eye-line me-1"></i>Preview PDF surat</small>
                        <span class="badge bg-info-subtle text-info">PDF</span>
                    </div>
                    <div class="border rounded overflow-hidden bg-light preview-panel">
                        <iframe id="mutationPdfPreview" title="Preview PDF surat rekomendasi"
                                style="width:100%;height:100%;border:0;background:#fff;"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="modal fade" id="mutationLayoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('user.mutations-in.layout', ['userId' => $userId]) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-settings-3-line me-1"></i>Pengaturan Layout PDF</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        @foreach(['margin_top' => 'Margin Atas', 'margin_right' => 'Margin Kanan', 'margin_bottom' => 'Margin Bawah', 'margin_left' => 'Margin Kiri'] as $field => $label)
                            <div class="col-6 col-md-3">
                                <label class="form-label">{{ $label }} (mm)</label>
                                <input type="number" name="{{ $field }}" class="form-control" min="0" max="50" step="0.5" required value="{{ old($field, $mutationLayout[$field]) }}">
                            </div>
                        @endforeach
                        <div class="col-md-6">
                            <label class="form-label">Jenis Font</label>
                            <select name="font_family" class="form-select" required>
                                @foreach(['Times New Roman', 'Arial', 'Georgia', 'Bookman Old Style', 'Tahoma'] as $font)
                                    <option value="{{ $font }}" @selected(old('font_family', $mutationLayout['font_family']) === $font)>{{ $font }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ukuran Font (pt)</label>
                            <input type="number" name="font_size" class="form-control" min="6" max="24" step="0.5" required value="{{ old('font_size', $mutationLayout['font_size']) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Line Height</label>
                            <input type="number" name="line_height" class="form-control" min="1" max="2.5" step="0.05" required value="{{ old('line_height', $mutationLayout['line_height']) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ukuran Kertas</label>
                            <select name="paper_size" class="form-select" required>
                                <option value="a4" @selected(old('paper_size', $mutationLayout['paper_size']) === 'a4')>A4</option>
                                <option value="folio" @selected(old('paper_size', $mutationLayout['paper_size']) === 'folio')>F4 / Folio</option>
                                <option value="letter" @selected(old('paper_size', $mutationLayout['paper_size']) === 'letter')>Letter</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="show_kop" value="1" id="mutationShowKop" @checked(old('show_kop', $mutationLayout['show_kop']))>
                                <label class="form-check-label" for="mutationShowKop">Tampilkan kop surat</label>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info small mt-3 mb-0">
                        Pengaturan berlaku untuk preview dan semua cetak surat PD Masuk pada satuan pendidikan ini.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


<div class="modal fade" id="documentSignatureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-shield-check-line me-1"></i>Tanda Tangan Digital &amp; Validasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('documents._signature_settings', ['type' => 'mutasi', 'userId' => $userId])
            </div>
        </div>
    </div>
</div>

@section('script')
<script>
(function() {
    try {
        function qs(id) { return document.getElementById(id); }
        function val(id) { var el = qs(id); return el ? el.value : ''; }

        // ── Konversi tanggal Hijriyah (server side) ──────────────
        window.syncHijriDate = function() {
            var m = val('f-tanggal');
            if (!m) {
                qs('f-tanggal-hijri').value = '';
                return;
            }
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '{{ route('user.mutations-in.hijri-convert', ['userId' => $userId]) }}?date=' + encodeURIComponent(m), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var r = JSON.parse(xhr.responseText);
                        if (r.hijri) {
                            qs('f-tanggal-hijri').value = r.hijri;
                            schedulePdfPreview();
                        }
                    } catch(e) {}
                }
            };
            xhr.send();
        };

        // ── Pratinjau PDF (debounced) ────────────────────────────
        var pdfPreviewTimer = null;
        var pdfPreviewObjectUrl = null;

        function schedulePdfPreview() {
            clearTimeout(pdfPreviewTimer);
            pdfPreviewTimer = setTimeout(refreshPdfPreview, 350);
        }

        function refreshPdfPreview() {
            var form = qs('form-in');
            var frame = qs('mutationPdfPreview');
            if (!form || !frame || !form.dataset.previewUrl) return;

            fetch(form.dataset.previewUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/pdf',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            }).then(function(response) {
                if (!response.ok) throw new Error('Preview PDF gagal dimuat.');
                return response.blob();
            }).then(function(blob) {
                if (pdfPreviewObjectUrl) URL.revokeObjectURL(pdfPreviewObjectUrl);
                pdfPreviewObjectUrl = URL.createObjectURL(blob);
                frame.src = pdfPreviewObjectUrl;
            }).catch(function(error) {
                console.warn(error);
            });
        }

        // ── Pilih santri terdaftar ───────────────────────────────
        $('#student-select').select2({
            placeholder: '— Santri baru (isi manual) —',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function() { return 'Santri tidak ditemukan'; },
            },
        });

        function optionToData(opt) {
            if (!opt || !opt.value) return null;
            return {
                id: opt.value,
                name: opt.dataset.name || '',
                nisn: opt.dataset.nisn || '',
                nis: opt.dataset.nis || '',
                gender: opt.dataset.gender || '',
                gender_text: opt.dataset.genderText || '',
                birth_place: opt.dataset.birthPlace || '',
                birth_date: opt.dataset.birthDate || '',
                birth_date_text: opt.dataset.birthDateText || '',
                religion: opt.dataset.religion || '',
                address: opt.dataset.address || '',
                class: opt.dataset.class || '',
                prev_school: opt.dataset.prevSchool || '',
                parent_name: opt.dataset.parentName || '',
                parent_occupation: opt.dataset.parentOccupation || '',
                parent_phone: opt.dataset.parentPhone || '',
                parent_address: opt.dataset.parentAddress || '',
            };
        }

        $('#student-select').on('select2:select', function(e) {
            var sel = qs('student-select');
            var opt = Array.prototype.find.call(sel.options, function(o) {
                return o.value === e.params.data.id;
            }) || sel.options[sel.selectedIndex];
            var s = optionToData(opt);
            if (s) fillStudent(s);
        });

        $('#student-select').on('select2:clear', function() {
            qs('student-id-hidden').value = '';
            clearStudent();
        });

        window.fillStudent = function(s) {
            qs('student-id-hidden').value = s.id || '';
            qs('f-student-name').value = s.name || '';
            qs('f-nisn').value = s.nisn || '';
            qs('f-nis').value = s.nis || '';
            qs('f-birth-place').value = s.birth_place || '';
            qs('f-birth-date').value = s.birth_date || '';
            qs('f-gender').value = s.gender || '';
            qs('f-religion').value = s.religion || 'Islam';
            qs('f-prev-school').value = s.prev_school || '';
            qs('f-prev-class').value = s.class || '';
            qs('f-father-name').value = s.parent_name || '';
            qs('f-father-job').value = s.parent_occupation || '';
            qs('f-parent-addr').value = s.parent_address || '';
            qs('f-parent-phone').value = s.parent_phone || '';

            qs('disp-name').textContent = s.name || '-';
            qs('disp-nisn').textContent = s.nisn || '-';
            qs('disp-nis').textContent = s.nis || '-';
            qs('disp-gender').textContent = s.gender_text || '-';
            qs('disp-class').textContent = s.class || '-';
            qs('disp-birth').textContent = (s.birth_place || '') + (s.birth_date_text ? ', ' + s.birth_date_text : '') || '-';
            qs('disp-address').textContent = s.address || '-';

            schedulePdfPreview();
        };

        function clearStudent() {
            ['f-student-name', 'f-nisn', 'f-birth-place', 'f-birth-date', 'f-gender',
             'f-prev-school', 'f-prev-class', 'f-father-name', 'f-father-job',
             'f-parent-addr', 'f-parent-phone'].forEach(function(id) {
                var el = qs(id);
                if (el) el.value = '';
            });
            qs('f-religion').value = 'Islam';

            ['disp-name', 'disp-nisn', 'disp-nis', 'disp-gender', 'disp-class', 'disp-birth', 'disp-address'].forEach(function(id) {
                qs(id).textContent = '-';
            });

            schedulePdfPreview();
        }

        // ── Sinkronisasi isian → preview ─────────────────────────
        ['f-nosurat', 'f-kota', 'f-tanggal', 'f-tanggal-hijri', 'f-student-name',
         'f-birth-place', 'f-birth-date', 'f-gender', 'f-religion', 'f-prev-school', 'f-prev-class',
         'f-father-name', 'f-father-job', 'f-mother-name', 'f-mother-job',
         'f-parent-addr', 'f-parent-phone',
         'f-accepted-class', 'f-semester', 'f-ay', 'f-notes',
         'f-head-name', 'f-head-title', 'f-head-nupy'].forEach(function(id) {
            var el = qs(id);
            if (el) {
                el.addEventListener('input', schedulePdfPreview);
                el.addEventListener('change', schedulePdfPreview);
            }
        });

        // ── Submit ───────────────────────────────────────────────
        window.submitForm = function(submitNow) {
            qs('submit-flag').value = submitNow ? '1' : '0';
            qs('form-in').submit();
        };

        // ── Prefill dari controller (?student_id=) ───────────────
        @if($student)
        qs('student-id-hidden').value = {{ json_encode($student->id) }};
        fillStudent({
            id: {{ json_encode($student->id) }},
            name: {{ json_encode($student->name) }},
            nisn: {{ json_encode($student->nisn) }},
            nis: {{ json_encode($student->nis) }},
            gender: {{ json_encode($student->gender) }},
            gender_text: {{ json_encode($student->gender_text) }},
            birth_place: {{ json_encode($student->birth_place) }},
            birth_date: {{ json_encode($student->birth_date?->format('Y-m-d')) }},
            birth_date_text: {{ json_encode($student->birth_date?->format('d F Y')) }},
            religion: {{ json_encode($student->religion) }},
            address: {{ json_encode($student->address) }},
            class: {{ json_encode($student->currentClassHistory?->studyGroup?->full_name ?? $student->currentClassHistory?->studyGroup?->name ?? '') }},
            prev_school: {{ json_encode($student->previous_school) }},
            parent_name: {{ json_encode($studentParentData[$student->id]['name'] ?? '') }},
            parent_occupation: {{ json_encode($studentParentData[$student->id]['occupation'] ?? '') }},
            parent_phone: {{ json_encode($studentParentData[$student->id]['phone'] ?? '') }},
            parent_address: {{ json_encode($studentParentData[$student->id]['address'] ?? '') }},
        });
        @endif

        syncHijriDate();
        schedulePdfPreview();
    } catch (e) {
        console.error('Page JS error:', e);
    }
})();
</script>
@endsection
