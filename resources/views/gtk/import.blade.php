@extends('layouts.master')
@section('title')Import GTK via Excel @endsection
@php $userId = request()->route('userId') ?? Auth::id(); @endphp

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container { width: 100% !important; }
.import-hero {
    border: 2px dashed #dee2e6;
    border-radius: 16px;
    transition: all 0.3s;
    cursor: pointer;
}
.import-hero:hover, .import-hero.dragover {
    border-color: #0d6efd;
    background: #f0f7ff;
    transform: translateY(-2px);
}
.preview-table-wrap { max-height: 420px; overflow: auto; border-radius: 10px; border: 1px solid #dee2e6; }
.preview-table-wrap table { margin-bottom: 0; font-size: 12.5px; }
.preview-table-wrap thead th { position: sticky; top: 0; z-index: 10; background: #f8f9fa; font-size: 11px; white-space: nowrap; padding: 8px 12px; }
.preview-table-wrap tbody td { padding: 6px 12px; vertical-align: middle; }
.row-status-error td { background: #fff5f5 !important; }
.step-card {
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1.1rem 1.25rem;
    transition: box-shadow 0.2s;
}
.step-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
.step-number {
    width: 34px; height: 34px;
    background: #0d6efd; color: #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 14px; flex-shrink: 0;
}
.unit-info-box {
    background: #f0f9ff;
    border-left: 4px solid #0d6efd;
    border-radius: 8px;
    padding: 0.75rem 1rem;
}
#importProgressOverlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.55); z-index: 9999; align-items: center; justify-content: center; }
#importProgressOverlay.show { display: flex; }
.progress-card { background: #fff; border-radius: 16px; padding: 2rem 2.5rem; width: 420px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,.3); }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') GTK @endslot
    @slot('title') Import GTK via Excel @endslot
@endcomponent

{{-- Feedback Session --}}
@if(session('import_result'))
    @php $result = session('import_result'); @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-success shadow-sm">
                <div class="card-body py-2 text-center">
                    <div class="text-success fw-bold fs-1">{{ $result['created'] ?? 0 }}</div>
                    <div class="text-muted small">Berhasil Diimport</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning shadow-sm">
                <div class="card-body py-2 text-center">
                    <div class="text-warning fw-bold fs-1">{{ count($result['duplicates'] ?? []) }}</div>
                    <div class="text-muted small">Duplikat (Dilewati)</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger shadow-sm">
                <div class="card-body py-2 text-center">
                    <div class="text-danger fw-bold fs-1">{{ count($result['errors'] ?? []) }}</div>
                    <div class="text-muted small">Error</div>
                </div>
            </div>
        </div>
    </div>
    @if(count($result['errors'] ?? []))
        <div class="card border-danger mb-4">
            <div class="card-header bg-danger text-white py-2">
                <h6 class="mb-0"><i class="ri-error-line me-1"></i> {{ count($result['errors']) }} Error — Perbaiki lalu import ulang</h6>
            </div>
            <div class="card-body p-0" style="max-height:250px;overflow-y:auto;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th style="width:40px">#</th><th>Pesan Error</th></tr></thead>
                    <tbody>
                        @foreach($result['errors'] as $i => $e)
                            <tr><td class="text-center text-muted">{{ $i+1 }}</td><td class="text-danger small">{{ $e }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-line me-1"></i><strong>Error:</strong> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-line me-1"></i><strong>Validasi gagal:</strong>
        <ul class="mb-0 mt-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════
     LAYOUT: KIRI = Panduan, KANAN = Form Upload
════════════════════════════════════════════════════════ --}}
<div class="row g-4">

    {{-- KIRI: Panduan Langkah demi Langkah --}}
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="ri-guide-line me-1 text-primary"></i>Cara Import GTK
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-3">

                    <div class="step-card">
                        <div class="d-flex align-items-start gap-3">
                            <div class="step-number">1</div>
                            <div>
                                <div class="fw-semibold mb-1">Unduh Template</div>
                                <p class="text-muted small mb-2">Pilih unit kerja tujuan, lalu download template Excel yang sudah disesuaikan dengan kolom GTK versi Dapodik/EMIS.</p>
                                <div class="mb-2">
                                    <select id="workUnitSelect" class="form-select form-select-sm">
                                        <option value="">— Ketik nama unit kerja —</option>
                                        @foreach($workUnits as $wu)
                                            <option value="{{ $wu->id }}"
                                                data-name="{{ $wu->name }}"
                                                data-code="{{ $wu->code }}">
                                                {{ $wu->name }} ({{ $wu->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div id="unitInfoBox" class="unit-info-box d-none mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ri-building-2-line text-primary"></i>
                                        <div>
                                            <strong class="text-primary" id="selUnitName">-</strong>
                                            <span class="badge bg-primary-subtle text-primary ms-1" id="selUnitCode">-</span>
                                        </div>
                                    </div>
                                </div>
                                <a id="btnTemplate" href="#" class="btn btn-sm btn-success disabled" onclick="return false;">
                                    <i class="ri-download-2-line me-1"></i> Download Template .xlsx
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="step-card">
                        <div class="d-flex align-items-start gap-3">
                            <div class="step-number">2</div>
                            <div>
                                <div class="fw-semibold mb-1">Isi Data di Template</div>
                                <p class="text-muted small mb-0">
                                    Baris 1 = header kolom.<br>
                                    Baris 2+ = data GTK.<br>
                                    <strong>Nama</strong>, <strong>Email</strong>, <strong>NIK</strong>, <strong>No HP</strong>, <strong>NUPY</strong>, <strong>Jenis GTK</strong>, <strong>Jabatan</strong>, <strong>Status Kepegawaian</strong>, <strong>SK</strong> wajib diisi.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="step-card">
                        <div class="d-flex align-items-start gap-3">
                            <div class="step-number">3</div>
                            <div>
                                <div class="fw-semibold mb-1">Pastikan Unit Kerja Benar</div>
                                <p class="text-muted small mb-0">
                                    Semua data GTK yang diimport akan ditempatkan ke unit kerja yang sudah dipilih di langkah 1. Pastikan unit kerja sudah sesuai sebelum mengupload file.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="step-card">
                        <div class="d-flex align-items-start gap-3">
                            <div class="step-number">4</div>
                            <div>
                                <div class="fw-semibold mb-1">Upload &amp; Preview</div>
                                <p class="text-muted small mb-0">
                                    Drag file atau klik area upload.<br>
                                    Preview akan menampilkan data yang valid dan error.<br>
                                    Baris dengan error akan dilewati saat import.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Ketentuan kolom --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-list-check me-1 text-warning"></i>Ketentuan Kolom</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:260px;overflow:auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Kolom</th><th>Keterangan</th><th class="text-center">Wajib</th></tr>
                        </thead>
                        <tbody class="small">
                            @foreach([
                                ['name','Nama Lengkap',true],['email','Email',true],['nik','NIK (16 digit)',true],
                                ['no_kk','Nomor KK',false],['tempat_lahir','Tempat Lahir',true],
                                ['tanggal_lahir','Tgl Lahir (YYYY-MM-DD)',true],['jenis_kelamin','L atau P',true],
                                ['golongan_darah','A/B/AB/O',false],['agama','Islam/Kristen/dst',false],
                                ['status_perkawinan','Belum Kawin/Kawin',false],['npwp','NPWP',false],
                                ['no_hp','No HP',true],['no_whatsapp','WhatsApp',false],
                                ['nupy','NUPY (jd password awal)',true],['jenis_gtk','Jenis GTK',true],
                                ['jabatan','Jabatan',true],['status_kepegawaian','PTT/PTY/GTT/GTY',true],
                                ['tmt','Tgl TMT (YYYY-MM-DD)',true],['nomor_sk','No SK',true],
                                ['tanggal_sk','Tgl SK (YYYY-MM-DD)',true],
                                ['pangkat_golongan','III/A dst',false],
                                ['jenjang_pendidikan','SD/SMP/SMA/S1/dst',false],
                                ['nama_sekolah','Nama sekolah',false],['jurusan','Jurusan',false],
                                ['tahun_lulus','Tahun lulus',false],
                            ] as [$c,$k,$w])
                                <tr>
                                    <td><code>{{ $c }}</code></td>
                                    <td>{{ $k }}</td>
                                    <td class="text-center">
                                        @if($w)
                                            <span class="badge bg-danger-subtle text-danger">Wajib</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Opsional</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- KANAN: Form Upload + Preview --}}
    <div class="col-lg-7">

        {{-- Upload Card --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="ri-upload-2-line me-1 text-primary"></i>Unggah File Excel
                </h5>
            </div>
            <div class="card-body">
                <form id="importForm">
                    @csrf
                    <input type="hidden" name="work_unit_id" id="workUnitId" value="">

                    {{-- Dropzone --}}
                    <div class="import-hero text-center p-4 mb-3" id="dropZone"
                         onclick="document.getElementById('excelFile').click()">
                        <input type="file" id="excelFile" name="file" accept=".xlsx,.xls" class="d-none"
                               onchange="handleFileSelect(this)">
                        <div style="width:70px;height:70px;background:linear-gradient(135deg,#0d6efd20,#19875420);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                            <i class="ri-file-upload-line" style="font-size:1.8rem;color:#0d6efd;"></i>
                        </div>
                        <p class="mb-1 fw-semibold" id="dropZoneText">Klik atau seret file Excel ke sini</p>
                        <p class="text-muted small mb-0">Format: .xlsx / .xls — Maksimal 10 MB</p>
                        <p id="fileNameDisplay" class="mt-2 fw-semibold mb-0" style="display:none"></p>
                    </div>

                    {{-- File info bar --}}
                    <div id="fileInfo" class="alert alert-success d-flex align-items-center gap-2 py-2 mb-3" style="display:none!important;">
                        <i class="ri-file-excel-2-fill fs-5"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold" id="fileName">-</div>
                            <small class="text-muted" id="fileSize">-</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearFile()">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                            <i class="ri-refresh-line me-1"></i> Reset
                        </button>
                        <button type="button" class="btn btn-primary" id="previewBtn" onclick="previewFile()" disabled>
                            <i class="ri-eye-line me-1"></i> Preview Data
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Preview Card (hidden until file selected) --}}
        <div class="card" id="previewCard" style="display:none;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ri-table-line me-1 text-primary"></i>
                    Preview Data <span id="previewCount" class="badge bg-primary ms-2">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-success-subtle text-success">
                        <i class="ri-checkbox-circle-line me-1"></i><span id="countValid">0</span> Valid
                    </span>
                    <span class="badge bg-danger-subtle text-danger">
                        <i class="ri-close-circle-line me-1"></i><span id="countError">0</span> Error
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="preview-table-wrap" id="previewTableWrap"></div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-muted">Baris valid akan diimport langsung ke unit kerja yang dipilih.</small>
                <div class="d-flex gap-2">
                    <button class="btn btn-light" onclick="clearFile()">
                        <i class="ri-refresh-line me-1"></i> Reset
                    </button>
                    <button class="btn btn-success" id="importBtn" onclick="startImport()" disabled>
                        <i class="ri-upload-cloud-2-line me-1"></i> Import <span id="importCountLabel">0</span> GTK
                    </button>
                </div>
            </div>
        </div>

        {{-- Placeholder --}}
        <div id="previewPlaceholder" class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="ri-file-list-3-line" style="font-size:3rem;"></i>
                <h6 class="mt-3">Pilih unit kerja dan upload file Excel</h6>
                <p class="mb-0 small">Klik "Preview Data" untuk melihat isi file sebelum diimport.</p>
            </div>
        </div>

        {{-- Result Card --}}
        <div class="card mt-4" id="importResultCard" style="display:none;">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-bar-chart-line me-1 text-success"></i>Hasil Import</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center" id="importResultStats"></div>
                <div id="importResultDetail" class="mt-3"></div>
                <div class="d-flex gap-2 mt-3">
                    <a href="{{ route('user.gtk.index', ['userId' => $userId]) }}" class="btn btn-primary">
                        <i class="ri-list-check me-1"></i> Lihat Daftar GTK
                    </a>
                    <button class="btn btn-outline-secondary" onclick="location.reload()">
                        <i class="ri-refresh-line me-1"></i> Import Lagi
                    </button>
                </div>
            </div>
        </div>

        {{-- Note --}}
        <div class="alert alert-warning py-2 px-3 small mb-0 mt-4">
            <i class="ri-error-warning-line me-1"></i>
            <strong>Catatan:</strong> NIK yang sudah terdaftar akan dilewati dan dilaporkan. Semua data akan masuk ke unit kerja yang sudah dipilih di langkah 1.
        </div>
    </div>
</div>

{{-- PROGRESS OVERLAY --}}
<div id="importProgressOverlay">
    <div class="progress-card">
        <i class="ri-loader-4-line text-primary" style="font-size:3rem;animation:spin 1s linear infinite;"></i>
        <h5 class="mt-3 mb-1">Sedang Mengimport...</h5>
        <p class="text-muted small mb-3" id="importProgressText">Memproses...</p>
        <div class="progress" style="height:8px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" id="importProgressBar" style="width:0%"></div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script>
const REQUIRED = ['name','email','nik','tempat_lahir','tanggal_lahir','jenis_kelamin','no_hp','nupy','jenis_gtk','jabatan','status_kepegawaian','tmt','nomor_sk','tanggal_sk'];
const COL_MAP = {
    'name':'name','nama':'name','nama_lengkap':'name','email':'email','nik':'nik',
    'no_kk':'no_kk','tempat_lahir':'tempat_lahir','tanggal_lahir':'tanggal_lahir',
    'tgl_lahir':'tanggal_lahir','jenis_kelamin':'jenis_kelamin','jk':'jenis_kelamin','golongan_darah':'golongan_darah',
    'agama':'agama','status_perkawinan':'status_perkawinan','npwp':'npwp',
    'no_hp':'no_hp','no_whatsapp':'no_whatsapp','nupy':'nupy','jenis_gtk':'jenis_gtk',
    'jabatan':'jabatan','status_kepegawaian':'status_kepegawaian','tmt':'tmt',
    'nomor_sk':'nomor_sk','tanggal_sk':'tanggal_sk','pangkat_golongan':'pangkat_golongan',
    'jenjang_pendidikan':'jenjang_pendidikan','nama_sekolah':'nama_sekolah',
    'jurusan':'jurusan','tahun_lulus':'tahun_lulus',
};
let selectedWuId = '', selectedWuName = '';
let parsedRows = [], validRows = [];

// Select2 work unit
$('#workUnitSelect').select2({ placeholder: '— Ketik nama unit kerja —', allowClear: true })
.on('change', function () {
    const opt = $(this).find(':selected');
    selectedWuId   = $(this).val();
    selectedWuName = opt.data('name') || '';
    $('#workUnitId').val(selectedWuId);
    if (selectedWuId) {
        $('#selUnitName').text(selectedWuName);
        $('#selUnitCode').text(opt.data('code') || '');
        $('#unitInfoBox').removeClass('d-none');
        $('#btnTemplate').removeClass('disabled').attr('onclick', '').css('pointer-events','auto')
            .off('click').on('click', () => {
                window.location.href = '{{ "/$userId" }}/gtk/import/template/' + selectedWuId;
            });
        $('#importBtn').prop('disabled', !validRows.length);
    } else {
        $('#unitInfoBox').addClass('d-none');
        $('#btnTemplate').addClass('disabled').attr('onclick','return false;').css('pointer-events','none');
        $('#importBtn').prop('disabled', true);
    }
});

// File handling
const fileInput = document.getElementById('excelFile');
const dropZone  = document.getElementById('dropZone');
['dragenter','dragover'].forEach(function(evt) {
    dropZone.addEventListener(evt, function(e) { e.preventDefault(); dropZone.classList.add('dragover'); });
});
['dragleave','drop'].forEach(function(evt) {
    dropZone.addEventListener(evt, function(e) { e.preventDefault(); dropZone.classList.remove('dragover'); });
});
dropZone.addEventListener('drop', function(e) {
    e.preventDefault(); dropZone.classList.remove('dragover');
    if (e.dataTransfer.files[0]) {
        fileInput.files = e.dataTransfer.files;
        handleFile(e.dataTransfer.files[0]);
    }
});
function handleFileSelect(input) { if (input.files[0]) handleFile(input.files[0]); }
function handleFile(file) {
    if (!file.name.match(/\.(xlsx|xls)$/i)) { Swal.fire({icon:'error',title:'Format Salah',text:'Hanya .xlsx/.xls'}); return; }
    if (file.size > 10*1024*1024) { Swal.fire({icon:'error',title:'File Besar',text:'Maks. 10MB'}); return; }
    if (!selectedWuId) { Swal.fire({icon:'warning',title:'Pilih Unit Kerja',text:'Pilih unit kerja terlebih dahulu'}); fileInput.value=''; return; }
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = formatBytes(file.size);
    document.getElementById('fileInfo').style.display = 'flex';
    document.getElementById('dropZoneText').textContent = file.name;
    document.getElementById('previewBtn').disabled = false;
    parsedRows = []; validRows = [];
    document.getElementById('previewCard').style.display = 'none';
    document.getElementById('previewPlaceholder').style.display = 'block';
}
function clearFile() {
    fileInput.value = '';
    document.getElementById('fileInfo').style.display = 'none';
    document.getElementById('dropZoneText').textContent = 'Klik atau seret file Excel ke sini';
    document.getElementById('previewBtn').disabled = true;
    document.getElementById('previewCard').style.display = 'none';
    document.getElementById('previewPlaceholder').style.display = 'block';
    document.getElementById('importResultCard').style.display = 'none';
    parsedRows = []; validRows = [];
    document.getElementById('importBtn').disabled = !selectedWuId;
    document.getElementById('importCountLabel').textContent = '0';
}

// Preview
function previewFile() {
    const file = fileInput.files[0]; if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        try {
            const wb = XLSX.read(e.target.result, {type:'array',cellDates:true});
            const ws = wb.Sheets[wb.SheetNames[0]];
            const raw = XLSX.utils.sheet_to_json(ws, {raw:false,defval:''});
            if (!raw.length) { Swal.fire({icon:'warning',title:'File Kosong'}); return; }
            parsedRows = raw.map((row,i) => {
                const m = {_rowNum:i+2};
                Object.keys(row).forEach(k => {
                    const nk = k.toLowerCase().trim().replace(/\s+/g,'_').replace(/[^a-z0-9_]/g,'');
                    const fn = COL_MAP[nk]; if (fn) m[fn] = String(row[k]).trim();
                });
                return m;
            });
            parsedRows.forEach(r => {
                r._errors = [];
                REQUIRED.forEach(c => { if (!r[c]) r._errors.push(`"${c}" wajib`); });
                if (r.nik && !/^\d{16}$/.test(r.nik)) r._errors.push('NIK 16 digit');
                if (r.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(r.email)) r._errors.push('Email tidak valid');
                if (r.jenis_kelamin && !['L','P'].includes(r.jenis_kelamin.toUpperCase())) r._errors.push('JK harus L/P');
                r._status = r._errors.length ? 'error' : 'ok';
            });
            validRows = parsedRows.filter(r => r._status !== 'error');
            renderTable(parsedRows);
            document.getElementById('countValid').textContent = parsedRows.filter(r=>r._status==='ok').length;
            document.getElementById('countError').textContent = parsedRows.filter(r=>r._status==='error').length;
            document.getElementById('previewCount').textContent = parsedRows.length + ' baris';
            document.getElementById('previewCard').style.display = 'block';
            document.getElementById('previewPlaceholder').style.display = 'none';
            document.getElementById('importBtn').disabled = !validRows.length;
            document.getElementById('importCountLabel').textContent = validRows.length;
        } catch(err) { console.error(err); Swal.fire({icon:'error',title:'Gagal Baca File'}); }
    };
    reader.readAsArrayBuffer(file);
}
function renderTable(rows) {
    const cols = [
        {k:'_rowNum',l:'#'}, {k:'name',l:'Nama'}, {k:'email',l:'Email'},
        {k:'nik',l:'NIK'}, {k:'nupy',l:'NUPY'}, {k:'jenis_kelamin',l:'JK'},
        {k:'tanggal_lahir',l:'Tgl Lahir'}, {k:'jenis_gtk',l:'Jenis GTK'},
        {k:'jabatan',l:'Jabatan'}, {k:'_status',l:'Status'}
    ];
    let html = '<table class="table table-hover align-middle"><thead><tr>';
    cols.forEach(c => { html += `<th>${c.l}</th>`; });
    html += '</tr></thead><tbody>';
    rows.forEach(r => {
        const sc = r._status === 'error' ? 'row-status-error' : '';
        html += `<tr class="${sc}">`;
        cols.forEach(c => {
            if (c.k === '_status') {
                if (r._status === 'error') {
                    html += `<td><span class="badge bg-danger">Error</span><ul class="mb-0 ps-2 small text-danger">${r._errors.map(e=>`<li>${escHtml(e)}</li>`).join('')}</ul></td>`;
                } else {
                    html += `<td><span class="badge bg-success"><i class="ri-checkbox-circle-line me-1"></i>Valid</span></td>`;
                }
            } else if (c.k === '_rowNum') {
                html += `<td class="text-muted">${r[c.k]||''}</td>`;
            } else {
                html += `<td class="text-nowrap">${escHtml(r[c.k]??'-')}</td>`;
            }
        });
        html += '</tr>';
    });
    html += '</tbody></table>';
    document.getElementById('previewTableWrap').innerHTML = html;
}

// Import
async function startImport() {
    if (!validRows.length || !selectedWuId) return;
    const c = await Swal.fire({icon:'question',title:`Import ${validRows.length} GTK?`,showCancelButton:true,confirmButtonText:'Ya, Import!',cancelButtonText:'Batal',confirmButtonColor:'#198754'});
    if (!c.isConfirmed) return;
    const ov = document.getElementById('importProgressOverlay');
    const bar = document.getElementById('importProgressBar');
    const txt = document.getElementById('importProgressText');
    ov.classList.add('show');
    let imp = 0, fail = 0, failRows = [];
    const BATCH = 10, total = validRows.length;
    for (let i = 0; i < total; i += BATCH) {
        const batch = validRows.slice(i, i + BATCH);
        bar.style.width = Math.round((i/total)*100) + '%';
        txt.textContent = `Baris ${i+1}–${Math.min(i+BATCH,total)} dari ${total}...`;
        try {
            const res = await fetch('/{{ $userId }}/gtk/import/store', {
                method:'POST',
                headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                body: JSON.stringify({work_unit_id: selectedWuId, rows: batch})
            });
            const d = await res.json();
            if (d.success) {
                imp += d.imported ?? batch.length;
                if (d.failed?.length) { fail += d.failed.length; failRows = failRows.concat(d.failed); }
            } else { fail += batch.length; batch.forEach(r => failRows.push({row:r._rowNum, reason:d.message||'Gagal'})); }
        } catch(e) { fail += batch.length; batch.forEach(r => failRows.push({row:r._rowNum, reason:'Network error'})); }
    }
    bar.style.width = '100%';
    setTimeout(() => { ov.classList.remove('show'); showResult(total, imp, fail, failRows); }, 400);
}
function showResult(total, imp, fail, failRows) {
    document.getElementById('importResultStats').innerHTML = `
        <div class="col-4"><div class="card border-0 bg-primary-subtle p-3"><div class="fs-3 fw-bold text-primary">${total}</div><div class="small text-muted">Total</div></div></div>
        <div class="col-4"><div class="card border-0 bg-success-subtle p-3"><div class="fs-3 fw-bold text-success">${imp}</div><div class="small text-muted">Berhasil</div></div></div>
        <div class="col-4"><div class="card border-0 bg-danger-subtle p-3"><div class="fs-3 fw-bold text-danger">${fail}</div><div class="small text-muted">Gagal</div></div></div>`;
    let detail = '';
    if (failRows.length) {
        detail = `<div class="alert alert-warning mb-0"><strong>Baris Gagal:</strong><ul class="mb-0 mt-1">${failRows.slice(0,15).map(f=>`<li>Baris ${f.row}: ${escHtml(f.reason)}</li>`).join('')}${failRows.length>15?`<li>...dan ${failRows.length-15} lainnya</li>`:''}</ul></div>`;
    }
    document.getElementById('importResultDetail').innerHTML = detail;
    document.getElementById('importResultCard').style.display = 'block';
    document.getElementById('previewCard').style.display = 'none';
    document.getElementById('previewPlaceholder').style.display = 'none';
    if (imp > 0) {
        Swal.fire({icon:'success',title:'Import Selesai!',html:`<strong>${imp}</strong> GTK berhasil diimport ke <strong>${escHtml(selectedWuName)}</strong>.`,confirmButtonColor:'#198754'});
    }
}
function formatBytes(b) { return b < 1024*1024 ? (b/1024).toFixed(1)+' KB' : (b/(1024*1024)).toFixed(1)+' MB'; }
function escHtml(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
</script>
@endsection