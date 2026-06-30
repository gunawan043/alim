@extends('layouts.master')
@section('title') Ajukan PD Masuk @endsection

@section('css')
<style>
    .letter-preview {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        background: #fff;
        font-family: 'Times New Roman', Times, serif;
        font-size: 11pt;
        line-height: 1.4;
        color: #000;
    }
    .letter-preview .lp-header { text-align: center; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 10px; }
    .letter-preview .lp-header-text { font-size: 13pt; font-weight: bold; text-transform: uppercase; }
    .letter-preview .lp-body { padding: 10px 5px; }
    .letter-preview .lp-table { width: 100%; border-collapse: collapse; }
    .letter-preview .lp-table td { padding: 2px 4px; font-size: 10pt; }
    .letter-preview .lp-table td:first-child { width: 130px; }
    .letter-preview .lp-body p { margin-bottom: 6px; font-size: 10pt; }
    .letter-preview .lp-sig { margin-top: 16px; }
    .letter-preview .lp-sig td { vertical-align: top; }
    .letter-preview .lp-sig-city { font-size: 10pt; margin-bottom: 4px; }
    .letter-preview .lp-sig-title { font-size: 10pt; margin-bottom: 36px; }
    .letter-preview .lp-sig-name { font-size: 11pt; font-weight: bold; text-decoration: underline; margin-bottom: 2px; }
    .letter-preview .lp-sig-nip { font-size: 9.5pt; }
    .letter-preview .empty-field { color: #bbb; font-style: italic; }
    .letter-preview .note { font-size: 9pt; margin-top: 10px; padding: 6px; border: 1px solid #ccc; background: #f9f9f9; }
    .out-type-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
        @slot('li_3') <a href="{{ route('user.mutations-in.index', ['userId' => $userId]) }}">PD Masuk</a> @endslot
        @slot('title') Ajukan PD Masuk @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.mutations-in.store', ['userId' => $userId]) }}" id="form-in">
        @csrf
        <input type="hidden" name="submit_now" value="0" id="submit-flag">
        <input type="hidden" name="school_id" value="{{ $schoolContextId }}">

        <div class="row">
            {{-- KIRI: FORM --}}
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-edit-2-line me-1"></i>Formulir PD Masuk</h5>
                    </div>
                    <div class="card-body">

                        {{-- Info Pejabat --}}
                        {{-- <div class="border rounded p-2 mb-3 small" style="background:#f0f9ff;border-color:#b6d4f5">
                            <div class="text-muted mb-1" style="font-size:10px;text-transform:uppercase;letter-spacing:0.5px">Pejabat yang Menandatangani</div>
                            <div class="row g-2">
                                <div class="col-12">Nama: <strong id="disp-head-name">{{ $defaultHeadName ?: '-' }}</strong></div>
                                <div class="col-md-6">Jabatan: <span id="disp-head-title">{{ $defaultHeadTitle }}</span></div>
                                <div class="col-md-6">NUPY: <span id="disp-head-nupy">{{ $defaultHeadNupy ?: '-' }}</span></div>
                            </div>
                        </div> --}}
                        <input type="hidden" name="head_name" id="f-head-name" value="{{ old('head_name', $defaultHeadName) }}">
                        <input type="hidden" name="head_title" id="f-head-title" value="{{ old('head_title', $defaultHeadTitle) }}">
                        <input type="hidden" name="head_nupy" id="f-head-nupy" value="{{ old('head_nupy', $defaultHeadNupy) }}">

                        {{-- Data Santri --}}
                        <h6 class="text-muted mb-2" style="font-size:10px;text-transform:uppercase">Data Santri</h6>
                        <div class="mb-2">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="student_name" id="f-student-name" class="form-control" value="{{ old('student_name') }}" required>
                        </div>
                        <input type="hidden" name="student_nis" id="f-nis" value="{{ old('student_nis', $defaultNis) }}">
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">NISN</label>
                                <input type="text" name="student_nisn" id="f-nisn" class="form-control" value="{{ old('student_nisn') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Tempat Lahir</label>
                                <input type="text" name="student_birth_place" id="f-birth-place" class="form-control" value="{{ old('student_birth_place') }}">
                            </div>
                        </div>
                            <div class="col-6">
                                <label class="form-label">Tanggal Lahir</label>
                                <input type="date" name="student_birth_date" id="f-birth-date" class="form-control" value="{{ old('student_birth_date') }}">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">Jenis Kelamin</label>
                                <select name="student_gender" id="f-gender" class="form-select">
                                    <option value="">—</option>
                                    <option value="L" {{ old('student_gender') === 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ old('student_gender') === 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Agama</label>
                                <input type="text" name="student_religion" id="f-religion" class="form-control" value="{{ old('student_religion', 'Islam') }}">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">Sekolah Asal</label>
                                <input type="text" name="student_previous_school" id="f-prev-school" class="form-control" value="{{ old('student_previous_school') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Kelas di Sekolah Asal</label>
                                <input type="text" name="student_previous_class" id="f-prev-class" class="form-control" value="{{ old('student_previous_class') }}">
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- Data Orang Tua --}}
                        <h6 class="text-muted mb-2" style="font-size:10px;text-transform:uppercase">Data Orang Tua/Wali</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">Nama Bapak</label>
                                <input type="text" name="father_name" id="f-father-name" class="form-control" value="{{ old('father_name') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Pekerjaan Bapak</label>
                                <input type="text" name="father_occupation" id="f-father-job" class="form-control" value="{{ old('father_occupation') }}">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">Nama Ibu</label>
                                <input type="text" name="mother_name" id="f-mother-name" class="form-control" value="{{ old('mother_name') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Pekerjaan Ibu</label>
                                <input type="text" name="mother_occupation" id="f-mother-job" class="form-control" value="{{ old('mother_occupation') }}">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Alamat Orang Tua</label>
                            <input type="text" name="parent_address" id="f-parent-addr" class="form-control" value="{{ old('parent_address') }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">No. HP</label>
                            <input type="text" name="parent_phone" id="f-parent-phone" class="form-control" value="{{ old('parent_phone') }}">
                        </div>

                        <hr class="my-3">

                        {{-- Data Penerimaan --}}
                        <h6 class="text-muted mb-2" style="font-size:10px;text-transform:uppercase">Data Penerimaan</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-4">
                                <label class="form-label">Kelas Diterima</label>
                                <input type="text" name="accepted_class" id="f-accepted-class" class="form-control" value="{{ old('accepted_class') }}">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Semester</label>
                                <input type="text" name="accepted_semester" id="f-semester" class="form-control" value="{{ old('accepted_semester') }}">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Tahun Ajaran</label>
                                <input type="text" name="accepted_academic_year" id="f-ay" class="form-control" value="{{ old('accepted_academic_year') }}">
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- Keterangan Surat --}}
                        <h6 class="text-muted mb-2" style="font-size:10px;text-transform:uppercase">Surat</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">No. Surat</label>
                                <input type="text" name="letter_number" id="f-nosurat" class="form-control" value="{{ old('letter_number') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Ditetapkan di</label>
                                <input type="text" name="established_city" id="f-kota" class="form-control" value="{{ old('established_city', $school?->city ?? '') }}">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">Tanggal Masehi</label>
                                <input type="date" name="established_date" id="f-tanggal" class="form-control"
                                    value="{{ old('established_date', $defaultDate) }}" onchange="syncHijriDate()">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Tanggal Hijriyah</label>
                                <input type="text" name="hijri_date" id="f-tanggal-hijri" class="form-control"
                                    value="{{ $defaultDateHijri }}" readonly>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" id="f-notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>

                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('user.mutations-in.index', ['userId' => $userId]) }}" class="btn btn-light">
                                <i class="ri-arrow-left-line me-1"></i> Batal
                            </a>
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

            {{-- KANAN: PREVIEW SURAT --}}
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="ri-eye-line me-1"></i>Preview Surat Rekomendasi</h5></div>
                    <div class="card-body p-0">
                        <div class="letter-preview" style="padding:15px;max-height:125vh;overflow-y:auto;">
                            @if($school?->kop_path && $school->kopsis_active)
                                <img src="{{ asset('storage/' . $school->kop_path) }}" alt="Kop Surat" style="max-width:100%;max-height:110px;object-fit:contain;display:block;margin:0 auto 8px;">
                            @else
                                <div class="lp-header">
                                    <div class="lp-header-text">{{ $school?->name ?? 'Pondok Pesantren' }}</div>
                                    @if($school?->address)
                                        <div style="font-size:9pt">{{ $school->address }}</div>
                                    @endif
                                </div>
                            @endif

                            <div class="lp-body" style="text-align:center;font-weight:bold;margin-bottom:10px;">
                                SURAT REKOMENDASI<br>
                                <div style="font-size:10pt;font-weight:normal;">Nomor: <span id="lp-nosurat" class="empty-field">………</span></div>
                            </div>

                            <table class="lp-table">
                                <tr><td colspan="3">Yang bertandatangan di bawah ini, Kepala <strong>{{ $school?->name ?? '[Nama Lembaga]' }}</strong> menerangkan bahwa:</td></tr>
                                <tr><td></td></tr>
                                <tr><td>Nama</td><td>:</td><td><strong id="lp-student-name" class="empty-field">[Nama]</strong></td></tr>
                                <tr><td>Tempat, Tanggal Lahir</td><td>:</td><td id="lp-birth">[Tempat, Tanggal]</td></tr>
                                <tr><td>Sekolah Asal</td><td>:</td><td id="lp-prev-school" class="empty-field">[Sekolah Asal]</td></tr>
                                <tr><td>Kelas di sekolah asal</td><td>:</td><td id="lp-prev-class" class="empty-field">[Kelas]</td></tr>
                                <tr><td>Jenis Kelamin</td><td>:</td><td id="lp-gender">[JK]</td></tr>
                                <tr><td>Agama</td><td>:</td><td id="lp-religion">Islam</td></tr>
                            </table>
                            
                            <table class="lp-table mt-3">
                                <tr><td colspan="3">Anak dari orang tua:</td></tr>
                                <tr><td>Bapak</td><td>:</td><td id="lp-father-name" class="empty-field">[Nama Bapak]</td></tr>
                                <tr><td>Ibu</td><td>:</td><td id="lp-mother-name" class="empty-field">[Nama Ibu]</td></tr>
                                <tr><td>Alamat</td><td>:</td><td id="lp-parent-addr" class="empty-field">[Alamat]</td></tr>
                                <tr><td>No. HP</td><td>:</td><td id="lp-parent-phone" class="empty-field">[No. HP]</td></tr>
                            </table>

                            <div class="lp-body">
                                <p>Berdasarkan hasil seleksi, calon siswa yang disebutkan di atas dinyatakan diterima di kelas <strong id="lp-accepted-class" class="empty-field">…. </strong> pada semester <strong id="lp-semester" class="empty-field">……</strong> tahun ajaran <strong id="lp-ay" class="empty-field">…………</strong>, dengan melengkapi persyaratan sebagai berikut:</p>
                                <ol style="font-size:10pt;margin-left:20px;">
                                    <li>Menyerahkan surat keterangan pindah dari sekolah asal (termasuk mutasi dapodik/emis).</li>
                                    <li>Menyerahkan foto copy Ijazah dan SKHUN Sekolah sebelumnya.</li>
                                    <li>Menyerahkan foto copy Akta Kelahiran dan Kartu Keluarga masing-masing 1 lembar.</li>
                                    <li>Membayar uang daftar ulang di Bendahara Pondok.</li>
                                    <li>Membayar IBS bulan pertama.</li>
                                    <li>Sanggup mentaati peraturan yang berlaku di Pondok Pesantren Abu Hurairah Mataram.</li>
                                </ol>
                                <p>Demikian surat rekomendasi ini diberikan untuk dipergunakan sebagaimana mestinya.</p>
                            </div>

                            <table class="lp-sig" style="width:100%;margin-top:16px;">
                                <tr>
                                    <td style="width:60%"></td>
                                    <td style="width:40%">
                                        <div class="lp-sig-city" id="lp-sig-city">{{ $school?->city ?? 'Kota' }}, <span class="lp-tgl-masehi">{{ now()->format('d F Y') }}</span> M</div>
                                        <div class="lp-sig-city"><span id="lp-sig-hijri">{{ $defaultDateHijri }}</span></div>
                                        <div class="lp-sig-title">Kepala Sekolah</div>
                                        <div class="lp-sig-name" id="lp-sig-name">{{ $defaultHeadName }}</div>
                                        <div class="lp-sig-nip">NUPY. <span id="lp-sig-nip">{{ $defaultHeadNupy }}</span></div>
                                    </td>
                                </tr>
                            </table>

                            <div class="note">
                                <strong>Tembusan:</strong><br>
                                1. Wakil Mudir Bidang Akademik dan Pengasuhan Ponpes Abu Hurairah Mataram<br>
                                2. Kepala Keuangan Ponpes Abu Hurairah Mataram<br>
                                3. Orang Tua/Wali Santri<br>
                                4. Pertinggal
                            </div>
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
    function qs(id) { return document.getElementById(id); }
    function sqs(id, val) { var el = qs(id); if (el) el.textContent = val || '-'; }

    // ── Hijri conversion ──────────────────────────────────────
    window.syncHijriDate = function() {
        var m = qs('f-tanggal').value;
        if (!m) return;
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '{{ route('user.mutations-in.hijri-convert', ['userId' => $userId]) }}?date=' + encodeURIComponent(m), true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.hijri) {
                        qs('f-tanggal-hijri').value = r.hijri;
                        qs('lp-sig-hijri').textContent = r.hijri;
                    }
                } catch(e) {}
            }
        };
        xhr.send();
    };

    // ── Live sync preview ─────────────────────────────────────
    function syncPreview() {
        sqs('lp-nosurat', qs('f-nosurat').value);
        sqs('lp-student-name', qs('f-student-name').value);
        sqs('lp-father-name', qs('f-father-name').value);
        sqs('lp-mother-name', qs('f-mother-name').value);
        sqs('lp-parent-addr', qs('f-parent-addr').value);
        sqs('lp-parent-phone', qs('f-parent-phone').value);
        sqs('lp-prev-school', qs('f-prev-school').value);
        sqs('lp-prev-class', qs('f-prev-class').value);
        sqs('lp-accepted-class', qs('f-accepted-class').value);
        sqs('lp-semester', qs('f-semester').value);
        sqs('lp-ay', qs('f-ay').value);
        sqs('lp-sig-city', (qs('f-kota').value || {{ Js::from($school?->city ?? 'Kota') }}) + ', ' + formatDate(qs('f-tanggal').value) + ' M');

        var birthPlace = qs('f-birth-place').value;
        var birthDate = qs('f-birth-date').value ? formatDate(qs('f-birth-date').value) : '';
        sqs('lp-birth', (birthPlace || '-') + (birthDate ? ', ' + birthDate : ''));

        var genderMap = { L: 'Laki-laki', P: 'Perempuan' };
        sqs('lp-gender', genderMap[qs('f-gender').value] || '-');
        sqs('lp-religion', qs('f-religion').value || 'Islam');
        sqs('lp-sig-name', qs('f-head-name').value || {{ Js::from($defaultHeadName) }});
        sqs('lp-sig-nip', qs('f-head-nupy').value || {{ Js::from($defaultHeadNupy) }});
        sqs('lp-sig-hijri', qs('f-tanggal-hijri').value || {{ Js::from($defaultDateHijri) }});

        // empty-field class
        ['lp-nosurat','lp-student-name','lp-father-name','lp-mother-name','lp-parent-addr','lp-parent-phone','lp-prev-school','lp-prev-class','lp-accepted-class','lp-semester','lp-ay'].forEach(function(id) {
            var el = qs(id);
            if (el) el.className = el.textContent && el.textContent !== '-' ? '' : 'empty-field';
        });
    }

    function formatDate(raw) {
        if (!raw) return '-';
        var d = new Date(raw);
        var months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }

    ['f-nosurat','f-kota','f-tanggal','f-tanggal-hijri','f-student-name','f-birth-place','f-birth-date',
     'f-gender','f-religion','f-prev-school','f-prev-class',
     'f-father-name','f-father-job','f-mother-name','f-mother-job',
     'f-parent-addr','f-parent-phone',
     'f-accepted-class','f-semester','f-ay','f-head-name','f-head-nupy'].forEach(function(id) {
        var el = qs(id);
        if (el) { el.addEventListener('input', syncPreview); el.addEventListener('change', syncPreview); }
    });

    window.submitForm = function(submitNow) {
        qs('submit-flag').value = submitNow ? '1' : '0';
        qs('form-in').submit();
    };

    syncPreview();
})();
</script>
@endsection
