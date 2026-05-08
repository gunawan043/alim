@extends('layouts.master')
@section('title')
    Import GTK via Excel{{ isset($workUnit) && $workUnit ? ' — ' . $workUnit->name : '' }}
@endsection
@php $userId = request()->route('userId') ?? Auth::id(); @endphp

@section('css')
<style>
/* ============================================================
   IMPORT PAGE STYLES
   ============================================================ */
.import-hero {
    background: linear-gradient(135deg, #0d6efd08 0%, #19875408 100%);
    border: 2px dashed #dee2e6;
    border-radius: 16px;
    transition: all 0.3s ease;
    cursor: pointer;
}
.import-hero:hover,
.import-hero.dragover {
    border-color: #0d6efd;
    background: linear-gradient(135deg, #0d6efd15 0%, #198754 0 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(13,110,253,0.12);
}
.import-hero .upload-icon {
    width: 80px; height: 80px;
    background: linear-gradient(135deg, #0d6efd20, #198754 20);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1rem;
    font-size: 2rem;
    color: #0d6efd;
}
.step-card {
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1.25rem;
    height: 100%;
    transition: box-shadow 0.2s;
}
.step-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
.step-number {
    width: 36px; height: 36px;
    background: #0d6efd;
    color: #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 15px;
    flex-shrink: 0;
}
.preview-table-wrap {
    max-height: 420px;
    overflow: auto;
    border-radius: 10px;
    border: 1px solid #dee2e6;
}
.preview-table-wrap table { margin-bottom: 0; font-size: 12.5px; }
.preview-table-wrap thead th {
    position: sticky; top: 0; z-index: 10;
    background: #f8f9fa; font-size: 11px;
    white-space: nowrap; padding: 8px 12px;
}
.preview-table-wrap tbody td { padding: 6px 12px; vertical-align: middle; }
.badge-col { font-size: 10px; }

/* Progress overlay */
#importProgressOverlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.55);
    z-index: 9999;
    align-items: center; justify-content: center;
}
#importProgressOverlay.show { display: flex; }
.progress-card {
    background: #fff; border-radius: 16px;
    padding: 2rem 2.5rem; width: 420px; text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

/* Error/Warning badge row */
.row-status-error  td { background: #fff5f5 !important; }
.row-status-warn   td { background: #fffbf0 !important; }
.row-status-ok     td {}

.required-col-hint { font-size: 10px; color: #dc3545; }
.optional-col-hint { font-size: 10px; color: #6c757d; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK @endslot
        @slot('title') Import GTK via Excel @endslot
    @endcomponent

    {{-- INFO UNIT KERJA --}} 
    @if($workUnit)
    <div class="alert alert-info d-flex align-items-center gap-3 mb-4">
        <i class="ri-building-2-line fs-3 text-info"></i>
        <div>
            <strong>Unit Kerja Tujuan:</strong> {{ $workUnit->name }}
            <span class="badge bg-info-subtle text-info ms-2">{{ $workUnit->code }}</span>
            <br>
            <small class="text-muted">Semua data yang diimport akan otomatis terdaftar ke unit kerja ini.</small>
        </div>
    </div>
    @endif

    <div class="row g-4">

        {{-- ============================================================
             KOLOM KIRI: Upload & Panduan
        ============================================================ --}}
        <div class="col-lg-5">

            {{-- LANGKAH-LANGKAH --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-guide-line me-2 text-primary"></i>Cara Import</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-3">
                        <div class="step-card">
                            <div class="d-flex align-items-start gap-3">
                                <div class="step-number">1</div>
                                <div>
                                    <div class="fw-semibold mb-1">Unduh Template Excel</div>
                                    <p class="text-muted small mb-2">Download template yang sudah berisi kolom yang benar dan contoh data.</p>
@if($workUnit)
                                    <a href="{{ route('user.gtk.import.template', ['userId' => $userId, 'workUnitId' => $workUnit->id]) }}"
                                       class="btn btn-sm btn-outline-success">
                                        <i class="ri-download-2-line me-1"></i> Download Template
                                    </a>
@endif
                                </div>
                            </div>
                        </div>

                        <div class="step-card">
                            <div class="d-flex align-items-start gap-3">
                                <div class="step-number">2</div>
                                <div>
                                    <div class="fw-semibold mb-1">Isi Data di Template</div>
                                    <p class="text-muted small mb-0">
                                        Isi data sesuai format. Kolom bertanda <span class="text-danger fw-bold">*</span> wajib diisi.<br>
                                        Baris 1 = header, mulai isi dari baris 2.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="step-card">
                            <div class="d-flex align-items-start gap-3">
                                <div class="step-number">3</div>
                                <div>
                                    <div class="fw-semibold mb-1">Upload & Preview</div>
                                    <p class="text-muted small mb-0">Upload file di sini, cek preview data, lalu klik Import.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- UPLOAD AREA --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-upload-cloud-2-line me-2 text-primary"></i>Upload File Excel</h5>
                </div>
                <div class="card-body">
                    <form id="importForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="work_unit_id" value="{{ $workUnit->id ?? '' }}">

                        {{-- DROP ZONE --}}
                        <div class="import-hero text-center p-5 mb-3" id="dropZone" onclick="document.getElementById('excelFile').click()">
                            <div class="upload-icon">
                                <i class="ri-file-excel-2-line"></i>
                            </div>
                            <h6 class="mb-1" id="dropZoneText">Klik atau Drag & Drop file Excel di sini</h6>
                            <p class="text-muted small mb-0">Format: .xlsx atau .xls · Maks. 10MB</p>
                        </div>
                        <input type="file" id="excelFile" name="file" accept=".xlsx,.xls" class="d-none">

                        {{-- INFO FILE TERPILIH --}}
                        <div id="fileInfo" class="alert alert-success d-flex align-items-center gap-2 py-2" style="display:none!important;">
                            <i class="ri-file-excel-2-fill fs-5"></i>
                            <div class="flex-grow-1">
                                <div class="fw-semibold" id="fileName">-</div>
                                <small class="text-muted" id="fileSize">-</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearFile()">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>

                        <div class="d-grid gap-2 mt-3">
                            <button type="button" class="btn btn-primary" id="previewBtn" onclick="previewFile()" disabled>
                                <i class="ri-eye-line me-1"></i> Preview Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- KETENTUAN KOLOM --}}
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-list-check me-2 text-warning"></i>Ketentuan Kolom</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kolom</th>
                                    <th>Keterangan</th>
                                    <th class="text-center">Wajib</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @foreach([
                                    ['name',              'Nama Lengkap',           true],
                                    ['email',             'Alamat Email',            true],
                                    ['nik',               'NIK (16 digit)',          true],
                                    ['no_kk',             'Nomor KK (16 digit)',     false],
                                    ['tempat_lahir',      'Tempat Lahir',            true],
                                    ['tanggal_lahir',     'Tanggal Lahir (YYYY-MM-DD)', true],
                                    ['jenis_kelamin',     'L atau P',               true],
                                    ['golongan_darah',    'A/B/AB/O',               false],
                                    ['agama',             'islam/kristen/dst',      false],
                                    ['status_perkawinan', 'belum_kawin/kawin/dst',  false],
                                    ['npwp',              'Nomor NPWP',             false],
                                    ['no_hp',             'Nomor HP',               true],
                                    ['no_whatsapp',       'Nomor WhatsApp',         false],
                                    ['nupy',              'NUPY (dipakai sbg password)', true],
                                    ['jenis_gtk',         'Lihat pilihan template', true],
                                    ['jabatan',           'Sesuai jenis GTK',       true],
                                    ['status_kepegawaian','PTT/PTY/GTT/GTY/dst',   true],
                                    ['tmt',               'Tanggal TMT (YYYY-MM-DD)', true],
                                    ['nomor_sk',          'Nomor SK',               true],
                                    ['tanggal_sk',        'Tanggal SK (YYYY-MM-DD)',true],
                                    ['pangkat_golongan',  'Mis: III/A',             false],
                                    ['jenjang_pendidikan','SD/SMP/SMA/S1/dst',      false],
                                    ['nama_sekolah',      'Nama institusi pendidikan', false],
                                    ['jurusan',           'Program studi/jurusan',  false],
                                    ['tahun_lulus',       'Tahun lulus pendidikan', false],
                                    ['alamat_jalan',      'Nama jalan rumah',       false],
                                    ['alamat_rt_rw',      'Format: 001/002',        false],
                                    ['alamat_desa',       'Nama desa/kelurahan',    false],
                                    ['alamat_kecamatan',  'Nama kecamatan',         false],
                                    ['alamat_kota',       'Nama kab/kota',          false],
                                    ['alamat_provinsi',   'Nama provinsi',          false],
                                    ['kode_pos',          'Kode pos',               false],
                                ] as [$col, $ket, $wajib])
                                <tr>
                                    <td><code class="text-nowrap">{{ $col }}</code></td>
                                    <td>{{ $ket }}</td>
                                    <td class="text-center">
                                        @if($wajib)
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

        {{-- ============================================================
             KOLOM KANAN: Preview & Hasil
        ============================================================ --}}
        <div class="col-lg-7">

            {{-- PANEL PREVIEW --}}
            <div class="card" id="previewCard" style="display:none;">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ri-table-line me-2 text-primary"></i>
                        Preview Data
                        <span id="previewCount" class="badge bg-primary ms-2">0 baris</span>
                    </h5>
                    <div class="d-flex gap-2">
                        <span class="badge bg-success-subtle text-success" id="badgeValid">
                            <i class="ri-checkbox-circle-line me-1"></i><span id="countValid">0</span> Valid
                        </span>
                        <span class="badge bg-danger-subtle text-danger" id="badgeError">
                            <i class="ri-close-circle-line me-1"></i><span id="countError">0</span> Error
                        </span>
                        <span class="badge bg-warning-subtle text-warning" id="badgeWarn">
                            <i class="ri-alert-line me-1"></i><span id="countWarn">0</span> Peringatan
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="preview-table-wrap" id="previewTableWrap">
                        {{-- Diisi JS --}}
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="ri-information-line me-1"></i>
                        Hanya baris valid yang akan diimport. Baris error dilewati.
                    </small>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light" onclick="clearFile()">
                            <i class="ri-refresh-line me-1"></i> Reset
                        </button>
                        <button type="button" class="btn btn-success" id="importBtn" onclick="startImport()" disabled>
                            <i class="ri-upload-cloud-2-line me-1"></i>
                            Import <span id="importCountLabel">0</span> Data GTK
                        </button>
                    </div>
                </div>
            </div>

            {{-- PLACEHOLDER SEBELUM UPLOAD --}}
            <div id="previewPlaceholder" class="card">
                <div class="card-body text-center py-5 text-muted">
                    <lord-icon src="https://cdn.lordicon.com/jdsvypqr.json" trigger="loop"
                               colors="primary:#0ab39c,secondary:#405189"
                               style="width:100px;height:100px"></lord-icon>
                    <h6 class="mt-3">Belum ada file yang diupload</h6>
                    <p class="mb-0">Upload file Excel dan klik "Preview Data" untuk menampilkan isi file.</p>
                </div>
            </div>

            {{-- HASIL IMPORT --}}
            <div class="card mt-4" id="importResultCard" style="display:none;">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-bar-chart-line me-2 text-success"></i>Hasil Import
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 text-center" id="importResultStats"></div>
                    <div id="importResultDetail" class="mt-3"></div>
                    <div class="d-flex gap-2 mt-3">
                        <a href="{{ route('user.gtk.index', ['userId' => $userId]) }}" class="btn btn-primary">
                            <i class="ri-list-check me-1"></i> Lihat Daftar GTK
                        </a>
                        <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                            <i class="ri-refresh-line me-1"></i> Import Lagi
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- PROGRESS OVERLAY --}}
    <div id="importProgressOverlay">
        <div class="progress-card">
            <lord-icon src="https://cdn.lordicon.com/wloilxuq.json" trigger="loop"
                       colors="primary:#0d6efd,secondary:#0ab39c"
                       style="width:80px;height:80px"></lord-icon>
            <h5 class="mt-3 mb-1">Sedang Mengimport...</h5>
            <p class="text-muted small mb-3">Harap tunggu, proses import sedang berjalan.</p>
            <div class="progress mb-2" style="height:10px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                     id="importProgressBar" style="width:0%"></div>
            </div>
            <div id="importProgressText" class="small text-muted">Memproses baris 0 dari 0...</div>
        </div>
    </div>

@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script>
/* ==========================================================================
   WAJIB DIISI (untuk validasi preview)
   ========================================================================== */
const REQUIRED_COLUMNS = ['name','email','nik','tempat_lahir','tanggal_lahir','jenis_kelamin','no_hp','nupy','jenis_gtk','jabatan','status_kepegawaian','tmt','nomor_sk','tanggal_sk'];

// Map kolom Excel (header) ke field internal
const COLUMN_MAP = {
    'name':              'name',
    'nama':              'name',
    'nama_lengkap':      'name',
    'email':             'email',
    'nik':               'nik',
    'no_kk':             'no_kk',
    'tempat_lahir':      'tempat_lahir',
    'tanggal_lahir':     'tanggal_lahir',
    'tgl_lahir':         'tanggal_lahir',
    'jenis_kelamin':     'jenis_kelamin',
    'golongan_darah':    'golongan_darah',
    'agama':             'agama',
    'status_perkawinan': 'status_perkawinan',
    'npwp':              'npwp',
    'no_hp':             'no_hp',
    'no_whatsapp':       'no_whatsapp',
    'nupy':              'nupy',
    'jenis_gtk':         'jenis_gtk',
    'jabatan':           'jabatan',
    'status_kepegawaian':'status_kepegawaian',
    'tmt':               'tmt',
    'nomor_sk':          'nomor_sk',
    'tanggal_sk':        'tanggal_sk',
    'pangkat_golongan':  'pangkat_golongan',
    'jenjang_pendidikan':'jenjang_pendidikan',
    'nama_sekolah':      'nama_sekolah',
    'jurusan':           'jurusan',
    'tahun_lulus':       'tahun_lulus',
    'alamat_jalan':      'alamat_jalan',
    'alamat_rt_rw':      'alamat_rt_rw',
    'alamat_desa':       'alamat_desa',
    'alamat_kecamatan':  'alamat_kecamatan',
    'alamat_kota':       'alamat_kota',
    'alamat_provinsi':   'alamat_provinsi',
    'kode_pos':          'kode_pos',
};

let parsedRows = [];
let validRows  = [];

/* ==========================================================================
   FILE HANDLING
   ========================================================================== */
const fileInput = document.getElementById('excelFile');
const dropZone  = document.getElementById('dropZone');

fileInput.addEventListener('change', function () {
    if (this.files[0]) handleFileSelected(this.files[0]);
});

// Drag & Drop
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) { fileInput.files = e.dataTransfer.files; handleFileSelected(file); }
});

function handleFileSelected(file) {
    if (!file.name.match(/\.(xlsx|xls)$/i)) {
        Swal.fire({ icon: 'error', title: 'Format Tidak Valid', text: 'Hanya file .xlsx dan .xls yang diperbolehkan.' });
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        Swal.fire({ icon: 'error', title: 'File Terlalu Besar', text: 'Ukuran file maksimal 10MB.' });
        return;
    }

    document.getElementById('fileName').textContent   = file.name;
    document.getElementById('fileSize').textContent   = formatBytes(file.size);
    document.getElementById('fileInfo').style.display = 'flex';
    document.getElementById('dropZoneText').textContent = file.name;
    document.getElementById('previewBtn').disabled = false;

    // Reset preview
    parsedRows = [];
    validRows  = [];
    document.getElementById('previewCard').style.display        = 'none';
    document.getElementById('previewPlaceholder').style.display = 'block';
    document.getElementById('importBtn').disabled = true;
}

function clearFile() {
    fileInput.value = '';
    document.getElementById('fileInfo').style.display    = 'none';
    document.getElementById('dropZoneText').textContent   = 'Klik atau Drag & Drop file Excel di sini';
    document.getElementById('previewBtn').disabled         = true;
    document.getElementById('previewCard').style.display  = 'none';
    document.getElementById('previewPlaceholder').style.display = 'block';
    document.getElementById('importResultCard').style.display   = 'none';
    parsedRows = []; validRows = [];
}

/* ==========================================================================
   PREVIEW
   ========================================================================== */
function previewFile() {
    const file = fileInput.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        try {
            const wb   = XLSX.read(e.target.result, { type: 'array', cellDates: true });
            const ws   = wb.Sheets[wb.SheetNames[0]];
            const raw  = XLSX.utils.sheet_to_json(ws, { raw: false, defval: '' });

            if (!raw.length) {
                Swal.fire({ icon: 'warning', title: 'File Kosong', text: 'Tidak ada data di sheet pertama.' });
                return;
            }

            // Normalize headers
            parsedRows = raw.map((row, rowIdx) => {
                const mapped = { _rowNum: rowIdx + 2 };
                Object.keys(row).forEach(key => {
                    const normalKey = key.toLowerCase().trim().replace(/\s+/g,'_').replace(/[^a-z0-9_]/g,'');
                    const fieldName = COLUMN_MAP[normalKey];
                    if (fieldName) mapped[fieldName] = String(row[key]).trim();
                });
                return mapped;
            });

            // Validate each row
            parsedRows.forEach(row => {
                row._errors   = [];
                row._warnings = [];

                REQUIRED_COLUMNS.forEach(col => {
                    if (!row[col]) row._errors.push(`Kolom "${col}" wajib diisi`);
                });

                if (row.nik && !/^\d{16}$/.test(row.nik)) row._errors.push('NIK harus 16 digit angka');
                if (row.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(row.email)) row._errors.push('Format email tidak valid');
                if (row.jenis_kelamin && !['L','P'].includes(row.jenis_kelamin.toUpperCase()))
                    row._errors.push('Jenis kelamin harus L atau P');

                // Warnings
                if (!row.no_whatsapp) row._warnings.push('No WhatsApp kosong');
                if (!row.jenjang_pendidikan) row._warnings.push('Data pendidikan tidak diisi');
                if (!row.alamat_jalan) row._warnings.push('Alamat tidak diisi');

                row._status = row._errors.length ? 'error' : (row._warnings.length ? 'warn' : 'ok');
            });

            validRows = parsedRows.filter(r => r._status !== 'error');

            renderPreviewTable(parsedRows);
            updatePreviewStats();

            document.getElementById('previewCard').style.display        = 'block';
            document.getElementById('previewPlaceholder').style.display = 'none';
            document.getElementById('importBtn').disabled = (validRows.length === 0);
            document.getElementById('importCountLabel').textContent = validRows.length;
            document.getElementById('previewCount').textContent = `${parsedRows.length} baris`;

        } catch (err) {
            console.error(err);
            Swal.fire({ icon: 'error', title: 'Gagal Membaca File', text: 'Pastikan file adalah Excel yang valid.' });
        }
    };
    reader.readAsArrayBuffer(file);
}

function renderPreviewTable(rows) {
    const DISPLAY_COLS = [
        { key: '_rowNum',           label: '#' },
        { key: 'name',              label: 'Nama' },
        { key: 'email',             label: 'Email' },
        { key: 'nik',               label: 'NIK' },
        { key: 'nupy',              label: 'NUPY' },
        { key: 'jenis_kelamin',     label: 'JK' },
        { key: 'tanggal_lahir',     label: 'Tgl Lahir' },
        { key: 'jenis_gtk',         label: 'Jenis GTK' },
        { key: 'jabatan',           label: 'Jabatan' },
        { key: 'status_kepegawaian',label: 'Status' },
        { key: 'no_hp',             label: 'No HP' },
        { key: '_status',           label: 'Status Baris' },
    ];

    let html = '<table class="table table-hover align-middle"><thead><tr>';
    DISPLAY_COLS.forEach(c => { html += `<th>${c.label}</th>`; });
    html += '</tr></thead><tbody>';

    rows.forEach(row => {
        const statusClass = row._status === 'error' ? 'row-status-error' : (row._status === 'warn' ? 'row-status-warn' : '');
        html += `<tr class="${statusClass}">`;
        DISPLAY_COLS.forEach(c => {
            if (c.key === '_status') {
                if (row._status === 'error') {
                    html += `<td>
                        <span class="badge bg-danger d-block mb-1">Error</span>
                        <ul class="mb-0 ps-3 small text-danger">
                            ${row._errors.map(e => `<li>${escHtml(e)}</li>`).join('')}
                        </ul>
                    </td>`;
                } else if (row._status === 'warn') {
                    html += `<td>
                        <span class="badge bg-warning d-block mb-1">Peringatan</span>
                        <ul class="mb-0 ps-3 small text-warning">
                            ${row._warnings.map(w => `<li>${escHtml(w)}</li>`).join('')}
                        </ul>
                    </td>`;
                } else {
                    html += `<td><span class="badge bg-success"><i class="ri-checkbox-circle-line me-1"></i>Valid</span></td>`;
                }
            } else if (c.key === '_rowNum') {
                html += `<td class="text-muted">${row[c.key] || ''}</td>`;
            } else {
                const val = row[c.key] ?? '-';
                html += `<td class="text-nowrap">${escHtml(val)}</td>`;
            }
        });
        html += '</tr>';
    });

    html += '</tbody></table>';
    document.getElementById('previewTableWrap').innerHTML = html;
}

function updatePreviewStats() {
    const total   = parsedRows.length;
    const errors  = parsedRows.filter(r => r._status === 'error').length;
    const warns   = parsedRows.filter(r => r._status === 'warn').length;
    const valid   = total - errors;
    document.getElementById('countValid').textContent = valid;
    document.getElementById('countError').textContent = errors;
    document.getElementById('countWarn').textContent  = warns;
}

/* ==========================================================================
   IMPORT (kirim ke server)
   ========================================================================== */
const importWorkUnitName = @json($workUnit ? $workUnit->name : '');
const importWorkUnitId = @json($workUnit ? $workUnit->id : '');

async function startImport() {
    if (!validRows.length) return;

    const confirm = await Swal.fire({
        icon: 'question',
        title: `Import ${validRows.length} Data GTK?`,
        html: `Baris dengan error akan dilewati.<br>Unit kerja: <strong>${importWorkUnitName}</strong>`,
        showCancelButton: true,
        confirmButtonText: 'Ya, Import!',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#198754',
    });
    if (!confirm.isConfirmed) return;

    // Tampilkan overlay progress
    const overlay  = document.getElementById('importProgressOverlay');
    const bar      = document.getElementById('importProgressBar');
    const progText = document.getElementById('importProgressText');
    overlay.classList.add('show');

    const BATCH_SIZE = 10;
    let imported = 0, failed = 0, failedRows = [];
    const total  = validRows.length;

    // Proses per batch
    for (let i = 0; i < total; i += BATCH_SIZE) {
        const batch = validRows.slice(i, i + BATCH_SIZE);

        // Update progress
        const pct = Math.round((i / total) * 100);
        bar.style.width     = pct + '%';
        progText.textContent = `Memproses baris ${i + 1}–${Math.min(i + BATCH_SIZE, total)} dari ${total}...`;

        try {
            const res  = await fetch('/{{ $userId }}/gtk/import/store', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    work_unit_id: importWorkUnitId,
                    rows: batch,
                }),
            });
            const data = await res.json();
            if (data.success) {
                imported += data.imported ?? batch.length;
                if (data.failed?.length) {
                    failed   += data.failed.length;
                    failedRows = failedRows.concat(data.failed);
                }
            } else {
                failed += batch.length;
                batch.forEach(r => failedRows.push({ row: r._rowNum, reason: data.message || 'Gagal' }));
            }
        } catch (err) {
            failed += batch.length;
            batch.forEach(r => failedRows.push({ row: r._rowNum, reason: 'Kesalahan jaringan' }));
        }
    }

    // Selesai
    bar.style.width = '100%';
    setTimeout(() => {
        overlay.classList.remove('show');
        showImportResult(total, imported, failed, failedRows);
    }, 400);
}

function showImportResult(total, imported, failed, failedRows) {
    const resultCard = document.getElementById('importResultCard');

    document.getElementById('importResultStats').innerHTML = `
        <div class="col-4">
            <div class="card border-0 bg-primary-subtle p-3">
                <div class="fs-3 fw-bold text-primary">${total}</div>
                <div class="small text-muted">Total Baris</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 bg-success-subtle p-3">
                <div class="fs-3 fw-bold text-success">${imported}</div>
                <div class="small text-muted">Berhasil Diimport</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 bg-danger-subtle p-3">
                <div class="fs-3 fw-bold text-danger">${failed}</div>
                <div class="small text-muted">Gagal</div>
            </div>
        </div>
    `;

    let detailHtml = '';
    if (failedRows.length) {
        detailHtml = `
            <div class="alert alert-warning">
                <strong><i class="ri-alert-line me-1"></i>Baris yang Gagal:</strong>
                <ul class="mb-0 mt-2">
                    ${failedRows.slice(0, 15).map(f => `<li>Baris ${f.row}: ${escHtml(f.reason)}</li>`).join('')}
                    ${failedRows.length > 15 ? `<li>... dan ${failedRows.length - 15} lainnya</li>` : ''}
                </ul>
            </div>
        `;
    }

    document.getElementById('importResultDetail').innerHTML = detailHtml;
    resultCard.style.display = 'block';
    resultCard.scrollIntoView({ behavior: 'smooth' });

    // Sembunyikan preview
    document.getElementById('previewCard').style.display = 'none';

    if (imported > 0) {
        Swal.fire({
            icon: 'success',
            title: 'Import Selesai!',
            html: `<strong>${imported}</strong> data GTK berhasil diimport ke unit kerja <strong>${importWorkUnitName}</strong>.`,
            confirmButtonColor: '#198754',
        });
    }
}

/* ==========================================================================
   UTILITIES
   ========================================================================== */
function formatBytes(bytes) {
    if (bytes < 1024)        return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function escHtml(str) {
    if (!str && str !== 0) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
</script>
@endsection