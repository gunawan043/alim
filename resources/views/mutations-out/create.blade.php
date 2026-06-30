@extends('layouts.master')
@section('title') Ajukan PD Keluar @endsection
<style>
    .letter-preview {
        font-family: 'Times New Roman', Times, serif;
        font-size: 11pt;
        line-height: 1.5;
        color: #000;
        background: #fff;
        border: 1px solid #dee2e6;
        padding: 28px 36px;
        min-height: 600px;
        position: relative;
    }
    .letter-preview .lp-header {
        text-align: center;
        border-bottom: 3px double #000;
        padding-bottom: 10px;
        margin-bottom: 16px;
    }
    .letter-preview .lp-institution { font-size: 14pt; font-weight: bold; text-transform: uppercase; }
    .letter-preview .lp-address { font-size: 9pt; }
    .letter-preview .lp-contact { font-size: 8.5pt; }
    .letter-preview .lp-meta { margin-bottom: 14px; font-size: 11pt; }
    .letter-preview .lp-meta table { border-collapse: collapse; }
    .letter-preview .lp-meta td { padding: 1px 0; }
    .letter-preview .lp-meta .lp-label { width: 130px; }
    .letter-preview .lp-body { font-size: 11pt; text-align: justify; margin-bottom: 10px; }
    .letter-preview .lp-indent { padding-left: 2em; }
    .letter-preview .lp-table { margin: 10px 0 16px 0; border-collapse: collapse; width: 100%; }
    .letter-preview .lp-table td { padding: 2px 4px; font-size: 10.5pt; vertical-align: top; }
    .letter-preview .lp-table td:first-child { width: 170px; }
    .letter-preview .lp-note { font-size: 9pt; border: 1px solid #ccc; padding: 6px 8px; background: #f9f9f9; margin-top: 12px; }
    .letter-preview .lp-sig { margin-top: 30px; }
    .letter-preview .lp-sig td { vertical-align: top; }
    .letter-preview .lp-sig-city { font-size: 10pt; margin-bottom: 4px; }
    .letter-preview .lp-sig-hijri { font-size: 10pt; padding-left: 30px; }
    .letter-preview .lp-sig-title { font-size: 10pt; margin-bottom: 36px; }
    .letter-preview .lp-sig-name { font-size: 11pt; font-weight: bold; text-decoration: underline; margin-bottom: 2px; }
    .letter-preview .lp-sig-nip { font-size: 9.5pt; }
    .letter-preview .empty-field { color: #bbb; font-style: italic; }

    /* out type badge */
    .out-type-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
    .out-type-badge.mutation { background: #e7f5ff; color: #1971c2; }
    .out-type-badge.graduation { background: #e5f9e8; color: #2f9e44; }
    .out-type-badge.dropout { background: #fff3bf; color: #e67700; }

    /* scan hint */
    .preview-scan-hint {
        font-size: 11px;
        color: #adb5bd;
        text-align: center;
        margin-bottom: 6px;
        letter-spacing: 0.5px;
    }

    /* debug banner */
    .debug-banner {
        font-size: 10px;
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 4px;
        padding: 4px 8px;
        color: #856404;
        margin-bottom: 8px;
        word-break: break-all;
    }
</style>

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Akademik @endslot
    @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
    @slot('li_3') <a href="{{ route('user.mutations-out.index', ['userId' => $userId]) }}">PD Keluar</a> @endslot
    @slot('title') Ajukan PD Keluar @endslot
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

<form method="POST" action="{{ route('user.mutations-out.store', ['userId' => $userId]) }}" id="form-out">
    @csrf
    <input type="hidden" name="submit_now" value="0" id="submit-flag">

    <div class="row">
        {{-- =============================================== --}}
        {{-- KIRI: FORM INPUT --}}
        {{-- =============================================== --}}
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-edit-2-line me-1"></i>Formulir PD Keluar</h5>
                </div>
                <div class="card-body">

                    {{-- Jenis --}}
                    <div class="mb-3">
                        <label class="form-label">Jenis PD Keluar <span class="text-danger">*</span></label>
                        <select name="out_type" id="out-type" class="form-select" required onchange="toggleOutFields()">
                            <option value="">— Pilih —</option>
                            <option value="mutation" {{ (old('out_type', $defaultOutType ?? '') === 'mutation') ? 'selected' : '' }}>Mutasi Keluar</option>
                            <option value="graduation" {{ (old('out_type', $defaultOutType ?? '') === 'graduation') ? 'selected' : '' }}>Lulus / Tamat</option>
                            <option value="dropout" {{ (old('out_type', $defaultOutType ?? '') === 'dropout') ? 'selected' : '' }}>Drop Out</option>
                        </select>
                    </div>

                    {{-- Cari Santri --}}
                    <div class="mb-3">
                        <label class="form-label">Cari Santri <span class="text-danger">*</span></label>
                        <select id="student-select" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            @foreach($groupedStudents as $sgName => $students)
                                @foreach($students as $s)
                                    <option value="{{ $s->id }}"
                                        data-nisn="{{ $s->nisn }}"
                                        data-nis="{{ $s->nis }}"
                                        data-gender="{{ $s->gender }}"
                                        data-gender-text="{{ $s->gender_text }}"
                                        data-birth-place="{{ $s->birth_place }}"
                                        data-birth-date="{{ $s->birth_date?->format('d/m/Y') }}"
                                        data-birth-date-raw="{{ $s->birth_date?->format('d F Y') }}"
                                        data-address="{{ $s->address }}"
                                        data-class="{{ $sgName }}"
                                        data-prev-school="{{ $s->previous_school }}"
                                        {{ isset($student) && old('student_id', $student->id) == $s->id ? 'selected' : '' }}
                                    >{{ $s->name }} - {{ $sgName }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>

                    {{-- Info Santri (readonly, update via JS) --}}
                    <div class="border rounded p-2 mb-3 small bg-light">
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
                    </div>

                    {{-- Mutation: sekolah tujuan --}}
                    {{-- Mutasi: sekolah tujuan + alasan --}}
                    <div id="field-mutation" style="display:none">
                        <div class="mb-3">
                            <label class="form-label">Nama Sekolah Tujuan <span class="text-danger">*</span></label>
                            <input type="text" name="destination_school_name" id="f-destination" class="form-control"
                                value="{{ old('destination_school_name') }}" placeholder="Nama lengkap sekolah tujuan">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat Sekolah Tujuan</label>
                            <input type="text" name="destination_school_address" id="f-dest-addr" class="form-control"
                                value="{{ old('destination_school_address') }}" placeholder="Alamat sekolah tujuan">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alasan Pindah</label>
                            <textarea name="reason" id="f-reason" class="form-control" rows="3"
                                placeholder="Jelaskan alasan pindah...">{{ old('reason') }}</textarea>
                        </div>
                    </div>

                    {{-- Dropout: alasan --}}
                    <div id="field-dropout" style="display:none">
                        <label class="form-label">Alasan Drop Out <span class="text-danger">*</span></label>
                        <textarea id="f-reason-dropout" class="form-control" rows="3"
                            placeholder="Jelaskan alasan drop out..." oninput="copyReasonDropout()">{{ old('reason') }}</textarea>
                    </div>

                    <hr class="my-3">

                    {{-- Data Orang Tua/Wali --}}
                    <div class="row g-3 mb-3">
                        <div class="col-12"><label class="form-label">Nama Orang Tua/Wali</label>
                            <input type="text" name="parent_name" id="f-parent-name" class="form-control"
                                value="{{ old('parent_name') }}" placeholder="Nama lengkap orang tua/wali">
                        </div>
                        <div class="col-md-6"><label class="form-label">Pekerjaan</label>
                            <input type="text" name="parent_occupation" id="f-parent-job" class="form-control"
                                value="{{ old('parent_occupation') }}" placeholder="Pekerjaan">
                        </div>
                        <div class="col-md-6"><label class="form-label">No. HP</label>
                            <input type="text" name="parent_phone" id="f-parent-phone" class="form-control"
                                value="{{ old('parent_phone') }}" placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="col-12"><label class="form-label">Alamat Orang Tua</label>
                            <input type="text" name="parent_address" id="f-parent-addr" class="form-control"
                                value="{{ old('parent_address') }}" placeholder="Alamat lengkap">
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- Keterangan Surat --}}
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
                                value="{{ old('established_date', $defaultDate) }}" oninput="syncHijriDate()" onchange="syncHijriDate()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Hijriyah</label>
                            <input type="text" id="f-tanggal-hijri" name="hijri_date" class="form-control"
                                value="{{ $defaultDateHijri }}" readonly placeholder="Akan dihitung otomatis">
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- Data Kepala Sekolah (auto dari employment) --}}
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

                    {{-- Hidden fields for student data submission --}}
                    <input type="hidden" name="student_id" id="student-id-hidden" value="{{ $student?->id ?? '' }}">
                    <input type="hidden" name="school_id" value="{{ $schoolContextId }}">
                    <input type="hidden" name="student_name" id="s-name" value="{{ old('student_name', $student?->name ?? '') }}">
                    <input type="hidden" name="student_nisn" id="s-nisn" value="{{ old('student_nisn', $student?->nisn ?? '') }}">
                    <input type="hidden" name="student_nis" id="s-nis" value="{{ old('student_nis', $student?->nis ?? '') }}">
                    <input type="hidden" name="student_gender" id="s-gender" value="{{ old('student_gender', $student?->gender ?? '') }}">
                    <input type="hidden" name="student_birth_date" id="s-birth-date" value="{{ old('student_birth_date', $student?->birth_date?->format('Y-m-d') ?? '') }}">
                    <input type="hidden" name="student_birth_place" id="s-birth-place" value="{{ old('student_birth_place', $student?->birth_place ?? '') }}">
                    <input type="hidden" name="student_address" id="s-address" value="{{ old('student_address', $student?->address ?? '') }}">
                    <input type="hidden" name="student_previous_school" id="s-prev-school" value="{{ old('student_previous_school', $student?->previous_school ?? '') }}">
                    <input type="hidden" name="student_current_class" id="s-class" value="{{ old('student_current_class', $student?->studyGroup?->name ?? '') }}">
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="/{{ $userId }}/mutations-out"
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ri-file-text-line me-1"></i>Preview Surat</h5>
                    <span id="out-type-badge" class="out-type-badge mutation">Mutasi Keluar</span>
                </div>
                <div class="card-body p-0">
                    <div class="letter-preview" id="letter-preview">

                        {{-- Header --}}
                        <div class="">
                            @if($school?->kop_path && $school->kopsis_active)
                                <img src="{{ asset('storage/' . $school->kop_path) }}" alt="Kop Surat" style="max-width:100%;max-height:110px;object-fit:contain;">
                            @else
                                <div class="lp-institution lp-header">{{ $school?->name ?? 'Nama Sekolah' }}</div>
                                <div class="lp-address">{{ $school?->address ?? '' }}</div>
                                @if($school?->phone || $school?->email)
                                    <div class="lp-contact">
                                        {{ $school?->phone ? 'Telp: ' . $school->phone : '' }}
                                        {{ $school?->email ? ' | Email: ' . $school->email : '' }}
                                    </div>
                                @endif
                            @endif
                        </div>

                        {{-- Meta Surat --}}
                        <div class="lp-meta">
                            <p class="text-center fw-bold mb-0">SURAT KETERANGAN PINDAH SEKOLAH</p>
                            <p class="text-center mt-0 mb-1">Nomor: <span id="lp-nosurat" class="empty-field">………</span></p>
                            <p class="mb-0">Yang bertanda tangan di bawah ini, Kepala Sekolah:</p>
                        </div>

                        {{-- Data Sekolah --}}
                        <table class="lp-table">
                            <tr><td>Nama Sekolah</td><td>:</td><td id="lp-school-name">{{ $school?->name ?? '-' }}</td></tr>
                            <tr><td>Alamat Sekolah</td><td>:</td><td id="lp-school-addr">{{ $school?->address ?? '-' }}</td></tr>
                            <tr><td>No. Telepon</td><td>:</td><td id="lp-school-phone">{{ $school?->phone ?? '-' }}</td></tr>
                        </table>

                        <p class="lp-body mb-1">Dengan ini menerangkan bahwa:</p>

                        {{-- Data Santri --}}
                        <table class="lp-table">
                            <tr><td>Nama Siswa</td><td>:</td><td><strong id="lp-name" class="empty-field">[Nama Santri]</strong></td></tr>
                            <tr><td>Tempat, Tanggal Lahir</td><td>:</td><td id="lp-birth">-</td></tr>
                            <tr><td>NISN / NIS</td><td>:</td><td id="lp-nisn">-</td></tr>
                            <tr><td>Jenis Kelamin</td><td>:</td><td id="lp-gender">-</td></tr>
                            <tr><td>Kelas</td><td>:</td><td id="lp-class">-</td></td></tr>
                            <tr><td>Nama Orang Tua/Wali</td><td>:</td><td id="lp-parent-name">-</td></tr>
                            <tr><td>Pekerjaan Orang Tua</td><td>:</td><td id="lp-parent-job">-</td></tr>
                            <tr><td>No. HP Orang Tua</td><td>:</td><td id="lp-parent-phone">-</td></tr>
                            <tr><td>Alamat Orang Tua</td><td>:</td><td id="lp-parent-addr">-</td></tr>
                        </table>

                        <p id="lp-body-mutation" class="lp-body mb-1">Bahwa siswa tersebut di atas mengajukan permohonan pindah sekolah ke:</p>
                        <p id="lp-body-graduation" class="lp-body mb-1" style="display:none">Bahwa siswa tersebut di atas telah <strong>menyelesaikan pendidikan</strong> dan dinyatakan:</p>
                        <p id="lp-body-dropout" class="lp-body mb-1" style="display:none">Bahwa siswa tersebut di atas <strong>tidak dapat melanjutkan</strong> pendidikan dengan alasan:</p>

                        {{-- Data Tujuan / Kelulusan / Dropout --}}
                        <table class="lp-table" id="lp-dest-table">
                            <tr id="lp-dest-name-row"><td>Nama Sekolah Tujuan</td><td>:</td><td id="lp-dest-school" class="empty-field">[Nama Sekolah Tujuan]</td></tr>
                            <tr id="lp-dest-addr-row"><td>Alamat Sekolah Tujuan</td><td>:</td><td id="lp-dest-addr" class="empty-field">[Alamat Sekolah Tujuan]</td></tr>
                            <tr id="lp-dest-reason-row"><td>Alasan Pindah</td><td>:</td><td id="lp-reason" class="empty-field">[Alasan]</td></tr>
                        </table>

                        <p id="lp-graduation-result" class="lp-body" style="display:none">LULUS dari jenjang pendidikan yang ditempuh.</p>

                        <p class="lp-body">Demikian surat keterangan pindah sekolah ini dibuat untuk dapat digunakan sebagaimana mestinya.</p>

                        {{-- Tanda Tangan --}}
                        <table class="lp-sig">
                            <tr>
                                <td style="width:68%"></td>
                                <td style="width:32%">
                                    <div class="lp-sig-city" id="lp-sig-city">{{ $school?->city ?? 'Kota' }}, <span class="lp-tgl-masehi">{{ now()->format('d F Y') }}</span>M</div>
                                    <div class="lp-sig-city"><span class="lp-sig-hijri">{{ $defaultDateHijri }}</span></div>
                                    <div class="lp-sig-title">Kepala Sekolah</div>
                                    <div class="lp-sig-name" id="lp-sig-name">{{ $defaultHeadName }}</div>
                                    <div class="lp-sig-nip">NUPY. <span id="lp-sig-nip">{{ $defaultHeadNupy }}</span></div>
                                </td>
                            </tr>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('script')
<script>
(function() {
    try {

    // ── Hijri conversion (via server API using pharaonic library) ─
    window.syncHijriDate = function() {
        var m = document.getElementById('f-tanggal').value;
        if (!m) {
            document.getElementById('f-tanggal-hijri').value = '';
            document.getElementById('lp-sig-hijri').textContent = {{ Js::from($defaultDateHijri) }};
            return;
        }
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '{{ route('user.mutations-out.hijri-convert', ['userId' => $userId]) }}?date=' + encodeURIComponent(m), true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.hijri) {
                        document.getElementById('f-tanggal-hijri').value = r.hijri;
                        document.getElementById('lp-sig-hijri').textContent = r.hijri;
                    }
                } catch(e) {}
            }
        };
        xhr.send();
    };
    function qs(id) { var e = document.getElementById(id); if (!e) console.warn('Missing element:', id); return e; }
    function sqs(id) { var e = qs(id); return e ? e : { textContent: '', value: '', style: {}} ; }
    function syncPreview() {
        // No. Surat & Kota/Tanggal
        var noSuratEl = qs('lp-nosurat');
        if (noSuratEl) { noSuratEl.textContent = qs('f-nosurat').value || '………'; noSuratEl.className = qs('f-nosurat').value ? '' : 'empty-field'; }

        var tglRaw = qs('f-tanggal').value;
        var tglFormatted = tglRaw ? formatDate(tglRaw) : '{{ now()->format("d F Y") }}';
        sqs('lp-sig-city').innerHTML =
            (qs('f-kota').value || {{ Js::from($school?->city ?? 'Kota') }}) + ', <span class="lp-tgl-masehi">' + tglFormatted + '</span>';

        // Kepala Sekolah
        var headName = qs('f-head-name').value || {{ Js::from($defaultHeadName ?: '-') }};
        sqs('lp-sig-name').textContent = headName;
        sqs('lp-sig-nip').textContent = qs('f-head-nupy').value || {{ Js::from($defaultHeadNupy ?: '-') }};
        sqs('lp-sig-title').textContent = qs('f-head-title').value || 'Kepala Sekolah';
        sqs('lp-sig-hijri').textContent = qs('f-tanggal-hijri').value || {{ Js::from($defaultDateHijri) }};

        // Out type toggle on preview
        var outType = qs('out-type').value;
        var badge = sqs('out-type-badge');
        var destSchoolRow = qs('lp-dest-name-row');
        var destAddrRow = qs('lp-dest-addr-row');
        var destReasonRow = qs('lp-dest-reason-row');
        var mutationBody = qs('lp-body-mutation');
        var graduationBody = qs('lp-body-graduation');
        var dropoutBody = qs('lp-body-dropout');
        var graduationResult = qs('lp-graduation-result');

        badge.className = 'out-type-badge ' + (outType === 'graduation' ? 'graduation' : outType === 'dropout' ? 'dropout' : 'mutation');
        badge.textContent = outType === 'graduation' ? 'Lulus' : outType === 'dropout' ? 'Drop Out' : 'Mutasi Keluar';
        mutationBody.style.display = outType === 'mutation' ? '' : 'none';
        graduationBody.style.display = outType === 'graduation' ? '' : 'none';
        dropoutBody.style.display = outType === 'dropout' ? '' : 'none';
        graduationResult.style.display = outType === 'graduation' ? '' : 'none';
        if (destSchoolRow) destSchoolRow.style.display = outType === 'mutation' ? '' : 'none';
        if (destAddrRow) destAddrRow.style.display = outType === 'mutation' ? '' : 'none';
        if (destReasonRow) destReasonRow.style.display = outType !== 'graduation' ? '' : 'none';

        // Sekolah tujuan (mutation only)
        var destSchool = qs('f-destination').value;
        sqs('lp-dest-school').textContent = destSchool || '[Nama Sekolah Tujuan]';
        sqs('lp-dest-school').className = destSchool ? '' : 'empty-field';

        var destAddr = qs('f-dest-addr').value;
        sqs('lp-dest-addr').textContent = destAddr || '[Alamat Sekolah Tujuan]';
        sqs('lp-dest-addr').className = destAddr ? '' : 'empty-field';

        // Alasan
        var reason = qs('f-reason').value;
        sqs('lp-reason').textContent = reason || '[Alasan]';
        sqs('lp-reason').className = reason ? '' : 'empty-field';

        // Tembusan
        sqs('lp-dest-tembusan').textContent = destSchool || {{ Js::from($school?->name ?? 'Sekolah') }};

        // Orang tua
        var parentName = qs('f-parent-name').value;
        sqs('lp-parent-name').textContent = parentName || '-';
        sqs('lp-parent-job').textContent = qs('f-parent-job').value || '-';
        sqs('lp-parent-addr').textContent = qs('f-parent-addr').value || '-';
        sqs('lp-parent-phone').textContent = qs('f-parent-phone').value || '-';
    }

    function formatDate(raw) {
        if (!raw) return '-';
        var d = new Date(raw);
        var months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }

    // ── Simple Select2 from pre-loaded grouped options ───────────
    $('#student-select').select2({
        placeholder: '-- Ketik nama atau NISN —',
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() { return 'Santri tidak ditemukan'; },
        },
    });

    function getSelectedStudentData() {
        var sel = document.getElementById('student-select');
        var opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.value) return {};
        return {
            id: opt.value,
            name: opt.text.split(' - ')[0].trim(),
            nisn: opt.dataset.nisn || '',
            nis: opt.dataset.nis || '',
            gender: opt.dataset.gender || '',
            gender_text: opt.dataset.genderText || '',
            birth_place: opt.dataset.birthPlace || '',
            birth_date: opt.dataset.birthDate || '',
            birth_date_raw: opt.dataset.birthDateRaw || '',
            address: opt.dataset.address || '',
            previous_school: opt.dataset.prevSchool || '',
            'class': opt.dataset.class || '',
        };
    }

    $('#student-select').on('select2:select', function(e) {
        document.getElementById('student-id-hidden').value = e.params.data.id;
        fillStudent(getSelectedStudentData());
    });

    $('#student-select').on('select2:clear', function(e) {
        document.getElementById('student-id-hidden').value = '';
        clearStudent();
    });

    // Convert dd/mm/yyyy → yyyy-mm-dd
    function convertDmYtoYmd(dmy) {
        if (!dmy || !dmy.includes('/')) return '';
        var parts = dmy.split('/');
        if (parts.length !== 3) return '';
        return parts[2] + '-' + parts[1].padStart(2,'0') + '-' + parts[0].padStart(2,'0');
    }

    window.fillStudent = function(s) {
        sqs('disp-name').textContent = s.name || '-';
        sqs('disp-nisn').textContent = s.nisn || '-';
        sqs('disp-nis').textContent = s.nis || '-';
        sqs('disp-gender').textContent = s.gender_text || '-';
        sqs('disp-class').textContent = s['class'] || '-';
        sqs('disp-birth').textContent = (s.birth_place || '') + (s.birth_date ? ', ' + s.birth_date : '') || '-';
        sqs('disp-address').textContent = s.address || '-';

        sqs('lp-name').textContent = s.name || '[Nama Santri]';
        sqs('lp-name').className = s.name ? 'fw-bold' : 'empty-field';
        sqs('lp-nisn').textContent = s.nisn || '-';
        sqs('lp-nis').textContent = s.nis || '-';
        sqs('lp-gender').textContent = s.gender_text || '-';
        sqs('lp-birth').textContent = (s.birth_place || '') + (s.birth_date_raw ? ', ' + s.birth_date_raw : (s.birth_date ? ', ' + s.birth_date : ''));
        sqs('lp-class').textContent = s['class'] || '-';

        // Update hidden form fields for submission
        qs('student-id-hidden').value = s.id || '';
        qs('s-name').value = s.name || '';
        qs('s-nisn').value = s.nisn || '';
        qs('s-nis').value = s.nis || '';
        qs('s-gender').value = s.gender || '';
        qs('s-birth-date').value = convertDmYtoYmd(s.birth_date) || '';
        qs('s-birth-place').value = s.birth_place || '';
        qs('s-address').value = s.address || '';
        qs('s-prev-school').value = s.previous_school || '';
        qs('s-class').value = s['class'] || '';
    };

    function clearStudent() {
        ['disp-name','disp-nisn','disp-nis','disp-gender','disp-class','disp-birth','disp-address'].forEach(function(id) {
            sqs(id).textContent = '-';
        });
        sqs('lp-name').textContent = '[Nama Santri]';
        sqs('lp-name').className = 'empty-field';
        ['lp-nisn','lp-nis','lp-gender','lp-birth','lp-class'].forEach(function(id) {
            sqs(id).textContent = '-';
        });
        ['lp-parent-name','lp-parent-job','lp-parent-addr','lp-parent-phone'].forEach(function(id) {
            sqs(id).textContent = '-';
        });

        // Clear hidden student form fields
        qs('student-id-hidden').value = '';
        qs('s-name').value = '';
        qs('s-nisn').value = '';
        qs('s-nis').value = '';
        qs('s-gender').value = '';
        qs('s-birth-date').value = '';
        qs('s-birth-place').value = '';
        qs('s-address').value = '';
        qs('s-prev-school').value = '';
        qs('s-class').value = '';
    }

    // ── Toggle out type ─────────────────────────────────────────
    function toggleOutFields() {
        var t = document.getElementById('out-type').value;
        document.getElementById('field-mutation').style.display = t === 'mutation' ? 'block' : 'none';
        document.getElementById('field-dropout').style.display = t === 'dropout' ? 'block' : 'none';
        syncPreview();
    }

    // ── Sync reason from dropout textarea to the real reason field ─
    function copyReasonDropout() {
        document.getElementById('f-reason').value = document.getElementById('f-reason-dropout').value;
        syncPreview();
    }
    toggleOutFields();

    // ── Live sync ──────────────────────────────────────────────
    ['f-nosurat','f-kota','f-tanggal','f-tanggal-hijri','f-destination','f-dest-addr','f-reason',
     'f-head-name','f-head-title','f-head-nupy','f-parent-name','f-parent-job','f-parent-addr',
     'f-parent-phone'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', syncPreview);
            el.addEventListener('change', syncPreview);
        }
    });

    // ── Initial Hijri date sync ────────────────────────────────
    syncHijriDate();

    // ── Submit ─────────────────────────────────────────────────
    window.submitForm = function(submitNow) {
        document.getElementById('submit-flag').value = submitNow ? '1' : '0';
        document.getElementById('form-out').submit();
    };

    // ── Prefill from controller (URL with ?student_id=) ──────────
    @if($student)
    document.getElementById('student-id-hidden').value = {{ json_encode($student->id) }};
    fillStudent({
        id: {{ json_encode($student->id) }},
        name: {{ json_encode($student->name) }},
        nisn: {{ json_encode($student->nisn) }},
        nis: {{ json_encode($student->nis) }},
        gender: {{ json_encode($student->gender) }},
        gender_text: {{ json_encode($student->gender_text) }},
        birth_place: {{ json_encode($student->birth_place) }},
        birth_date: {{ json_encode($student->birth_date?->format('d/m/Y')) }},
        birth_date_raw: {{ json_encode($student->birth_date?->format('d F Y')) }},
        address: {{ json_encode($student->address) }},
        previous_school: {{ json_encode($student->previous_school) }},
        'class': {{ json_encode($student->studyGroup?->name ?? ($student->entry_grade_level ? 'Kelas ' . $student->entry_grade_level : '-')) }},
    });
    @endif

    syncPreview();
    } catch(e) {
        console.error('Page JS error:', e);
        alert('Error: ' + e.message);
    }
    })();
</script>
@endsection
