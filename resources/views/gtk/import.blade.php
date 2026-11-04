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
                <h6 class="mb-0"><i class="ri-error-warning-line me-1"></i> {{ count($result['errors']) }} Error — Perbaiki lalu import ulang</h6>
            </div>
            <div class="card-body p-0" style="max-height:250px;overflow-y:auto;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th style="width:40px">#</th><th>Baris</th><th>Alasan</th></tr></thead>
                    <tbody>
                        @foreach($result['errors'] as $i => $e)
                            <tr><td class="text-muted text-center">{{ $i+1 }}</td>
                                <td><code>{{ $e['row'] ?? '-' }}</code></td>
                                <td class="text-danger small">{{ is_array($e) ? ($e['reason'] ?? $e['message'] ?? json_encode($e)) : $e }}</td></tr>
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
                                <p class="text-muted small mb-2">
                                    Satuan kerja <strong>opsional</strong>. Kalau dikosongkan, penempatan dibaca dari kolom
                                    <code>satuan_kerja</code> di Excel — jadi satu file bisa memuat GTK dari beberapa satuan kerja.
                                </p>
                                <div class="mb-2">
                                    <select id="workUnitSelect" class="form-select form-select-sm">
                                        <option value="">— Opsional: pilih satuan kerja —</option>
                                        @foreach($workUnits as $wu)
                                            <option value="{{ $wu->id }}"
                                                data-name="{{ $wu->name }}"
                                                data-code="{{ $wu->code }}">
                                                {{ $wu->name }} ({{ $wu->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <!-- <div class="mb-2">
                                    <select id="jenisGtkSelect" class="form-select form-select-sm" disabled>
                                        <option value="">— Pilih Jenis GTK (untuk filter jabatan) —</option>
                                        @foreach($jenisGtk as $j)
                                            <option value="{{ $j->id }}" data-name="{{ $j->nama }}">{{ $j->nama }}</option>
                                        @endforeach
                                    </select>
                                </div> -->
                                <div class="mb-2" id="jabatanWrap" style="display:none">
                                    <select id="jabatanSelect" class="form-select form-select-sm">
                                        <option value="">— Pilih Jabatan —</option>
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
                                    <div class="mt-2 small text-muted" id="selectedJenisInfo" style="display:none">
                                        <i class="ri-checkbox-circle-line text-success me-1"></i>
                                        Jenis: <strong id="selJenisName">-</strong>
                                        &nbsp;|&nbsp;
                                        Jabatan: <strong id="selJabatanName">-</strong>
                                    </div>
                                </div>
                                <a id="btnTemplate" href="{{ route('user.gtk.import.template', ['userId' => $userId]) }}" class="btn btn-sm btn-success">
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
                                    <strong>Nama</strong>, <strong>Email</strong>, <strong>NIK</strong>, <strong>No HP</strong>, <strong>NUPY</strong> wajib diisi.<br>
                                    Isi <code>satuan_kerja</code> dengan nama satuan kerja persis seperti di sheet
                                    <em>Referensi: Satuan Kerja</em> (boleh juga kode unitnya).<br>
                                    NUPY yang sudah terdaftar akan <strong>diperbarui</strong>, bukan ditolak.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="step-card">
                        <div class="d-flex align-items-start gap-3">
                            <div class="step-number">3</div>
                            <div>
                                <div class="fw-semibold mb-1">Satuan Kerja &amp; Jabatan Dicocokkan Otomatis</div>
                                <p class="text-muted small mb-0">
                                    Tiap baris dicocokkan ke master, tidak peduli huruf besar/kecil, tanda baca,
                                    atau nama yang lebih panjang. Kalau kolom <code>satuan_kerja</code> kosong,
                                    dipakai satuan kerja yang dipilih di langkah 1.
                                    <strong>Kalau hasilnya meragukan, sistem tidak menebak</strong> — baris itu
                                    ditandai dan Anda memilih sendiri dari daftar kandidat di preview.
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
                                ['jabatan','Jabatan',true],['satuan_kerja','Nama/kode satuan kerja (opsional bila dipilih di langkah 1)',false],
                                ['status_kepegawaian','PTT/PTY/GTT/GTY/Berhenti/Pensiun',true],
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
                <h6 class="mt-3">Upload file Excel</h6>
                <p class="mb-0 small">Satuan kerja boleh dipilih dulu, boleh juga diisi per baris di dalam file.</p>
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
                <div id="importResultWarnings" class="mt-3 d-none">
                    <div class="alert alert-warning py-2 mb-0 small">
                        <strong><i class="ri-information-line me-1"></i>Perhatian:</strong>
                        <ul class="mb-0 mt-1" id="warningList"></ul>
                    </div>
                </div>
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
            <strong>Catatan:</strong> Baris dengan NUPY yang sudah terdaftar akan <strong>diperbarui</strong> —
            data wajib (nama, email, NIK, NUPY, No HP) tidak pernah ditimpa, dan sel kosong tidak menghapus data lama.
            NIK atau email yang sudah dipakai GTK lain dengan NUPY berbeda tetap dilaporkan sebagai gagal.
            <br>
            Pencocokan <strong>otomatis</strong> hanya dipakai kalau skornya ≥ 90%. Di bawah itu baris ditandai
            <em>perlu konfirmasi</em> dan baru bisa diimport setelah Anda memilih dari daftar.
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
const REQUIRED = ['name','email','nik','nupy','no_hp','jabatan'];
const COL_MAP = {
    'name':'name','nama':'name','nama_lengkap':'name','email':'email','nik':'nik',
    'no_kk':'no_kk',
    'nama_pasangan':'nama_pasangan','nupy_pasangan':'nupy_pasangan',
    'nama_anak_1':'nama_anak_1','tanggal_lahir_anak_1':'tanggal_lahir_anak_1','jenis_kelamin_anak_1':'jenis_kelamin_anak_1',
    'nama_anak_2':'nama_anak_2','tanggal_lahir_anak_2':'tanggal_lahir_anak_2','jenis_kelamin_anak_2':'jenis_kelamin_anak_2',
    'nama_anak_3':'nama_anak_3','tanggal_lahir_anak_3':'tanggal_lahir_anak_3','jenis_kelamin_anak_3':'jenis_kelamin_anak_3',
    'tempat_lahir':'tempat_lahir','tanggal_lahir':'tanggal_lahir',
    'tgl_lahir':'tanggal_lahir','jenis_kelamin':'jenis_kelamin','jk':'jenis_kelamin','golongan_darah':'golongan_darah',
    'agama':'agama','status_perkawinan':'status_perkawinan','npwp':'npwp',
    'no_hp':'no_hp','no_whatsapp':'no_whatsapp','nupy':'nupy','jenis_gtk':'jenis_gtk',
    'jabatan':'jabatan','satuan_kerja':'satuan_kerja','unit_kerja':'satuan_kerja','satuan_kerja_nama':'satuan_kerja',
    'status_kepegawaian':'status_kepegawaian','tmt':'tmt',
    'nomor_sk':'nomor_sk','tanggal_sk':'tanggal_sk','pangkat_golongan':'pangkat_golongan',
    'jenjang_pendidikan':'jenjang_pendidikan','nama_sekolah':'nama_sekolah',
    'jurusan':'jurusan','tahun_lulus':'tahun_lulus',
};
let selectedWuId = '', selectedWuName = '';

let parsedRows = [], validRows = [];

// Select2 work unit
$('#workUnitSelect').select2({ placeholder: '— Opsional: pilih satuan kerja —', allowClear: true })
.on('change', function () {
    const opt = $(this).find(':selected');
    selectedWuId   = $(this).val();
    selectedWuName = opt.data('name') || '';
    $('#workUnitId').val(selectedWuId);
    $('#btnTemplate').attr('href', '{{ "/$userId" }}/gtk/import/template' + (selectedWuId ? '/' + selectedWuId : ''));

    if (selectedWuId) {
        $('#selUnitName').text(selectedWuName);
        $('#selUnitCode').text(opt.data('code') || '');
        $('#unitInfoBox').removeClass('d-none');
    } else {
        $('#unitInfoBox').addClass('d-none');
    }

    // Pilihan manual hanya cadangan untuk baris yang kolom satuan_kerja-nya kosong,
    // jadi preview perlu diulang setiap kali pilihan berubah.
    if (parsedRows.length) previewFile();
});

// Select2 jenis GTK — trigger jabatan fetch
let allJabatanData = @json($jenisGtk->map(fn($j) => ['id' => $j->id, 'nama' => $j->nama]));
$('#jenisGtkSelect').select2({
    placeholder: '— Pilih Jenis GTK (opsional, untuk validasi) —',
    allowClear: true,
    width: '100%',
})
.on('change', async function () {
    const val = $(this).val();
    if (!val) {
        $('#jabatanWrap').hide();
        $('#selectedJenisInfo').hide();
        return;
    }
    $('#jabatanWrap').show();
    $('#selJenisName').text(allJabatanData.find(j => j.id === val)?.nama || val);
    // Fetch jabatan for this jenis_gtk
    try {
        const res = await fetch('{{ "/$userId" }}/master-data/jabatan-by-jenis?jenis_gtk_id=' + val);
        const data = await res.json();
        const sel = document.getElementById('jabatanSelect');
        sel.innerHTML = '<option value="">— Pilih Jabatan —</option>';
        (data.data || []).forEach(j => {
            sel.innerHTML += `<option value="${escHtml(j.name)}" data-id="${escHtml(j.id)}">${escHtml(j.name)}</option>`;
        });
        $('#selJabatanName').text(sel.options[sel.selectedIndex]?.textContent || '-');
        $('#selectedJenisInfo').show();
    } catch(e) {
        console.error(e);
    }
}).trigger('change');

$('#jabatanSelect').on('change', function() {
    const opt = $(this).find(':selected');
    $('#selJabatanName').text(opt.val() || '-');
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
    document.getElementById('importBtn').disabled = true;
    document.getElementById('importCountLabel').textContent = '0';
}

// Preview
function previewFile() {
    const file = fileInput.files[0]; if (!file) return;
    const reader = new FileReader();
    reader.onload = async e => {
        try {
            const wb = XLSX.read(e.target.result, {type:'array',cellDates:true});
            const ws = wb.Sheets[wb.SheetNames[0]];
            const raw = XLSX.utils.sheet_to_json(ws, {raw:false,defval:''});
            if (!raw.length) { Swal.fire({icon:'warning',title:'File Kosong'}); return; }
            // Excel numeric cells >15 digits become scientific notation; convert to clean digit string.
            const toCleanDigitStr = (v) => {
                if (v == null || v === '') return '';
                if (typeof v === 'number') return Number.isInteger(v) ? String(v) : v.toFixed(0);
                const s = String(v).trim();
                if (/^[0-9]+\.?[0-9]*[eE][+-]?[0-9]+$/.test(s)) return Number(s).toFixed(0);
                return /^\d+$/.test(s) ? s : s;
            };
            parsedRows = raw.map((row,i) => {
                const m = {_rowNum:i+2};
                Object.keys(row).forEach(k => {
                    const nk = k.toLowerCase().trim().replace(/\s+/g,'_').replace(/[^a-z0-9_]/g,'');
                    const fn = COL_MAP[nk];
                    if (fn && ['nik','nupy','no_kk','npwp'].includes(fn)) {
                        m[fn] = toCleanDigitStr(row[k]);
                    } else if (fn) {
                        m[fn] = String(row[k]).trim();
                    }
                });
                return m;
            });
            // Status normalization map for frontend validation hints.
            const statusKawinMap = { 'kawin':'kawin', 'menikah':'kawin', 'belum kawin':'belum_kawin', 'belum menikah':'belum_kawin', 'cerai hidup':'cerai_hidup', 'cerai mati':'cerai_mati', 'Janda/Cerai':'janda_cerai', 'Duda/Cerai':'duda_cerai' };
            const statusKepegawaianMap = { 'ptt':'PTT', 'pty':'PTY', 'percobaan':'Percobaan', 'magang':'Magang', 'gtt':'GTT', 'gty':'GTY', 'kontrak':'KONTRAK', 'tetap':'PTY', 'pensiun':'Pensiun', 'wafat':'Wafat', 'berhenti':'Berhenti' };
            const jkMap = { 'l':'L', 'laki-laki':'L', 'laki laki':'L', 'p':'P', 'perempuan':'P' };

            parsedRows.forEach(r => {
                r._errors = [];
                REQUIRED.forEach(c => { if (!r[c]) r._errors.push(`${c}: wajib diisi`); });
                if (r.nik && !/^\d{16}$/.test(r.nik)) r._errors.push('NIK: harus 16 digit angka');
                if (r.nupy && !/^\d{10,20}$/.test(r.nupy)) r._errors.push('NUPY: harus 10-20 digit angka');
                if (r.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(r.email)) r._errors.push('Email: tidak valid');
                if (r.jenis_kelamin) {
                    const upper = r.jenis_kelamin.toUpperCase().trim();
                    if (upper !== 'L' && upper !== 'P') r._errors.push('JK: harus L atau P');
                }
                if (r.status_kepegawaian) {
                    const lower = r.status_kepegawaian.toLowerCase().trim();
                    if (statusKepegawaianMap[lower] === undefined) r._errors.push('Status Kepegawaian: tidak valid (PTT/PTY/GTT/GTY/KONTRAK/Magang/Percobaan)');
                }
                if (r.status_perkawinan) {
                    const lower = r.status_perkawinan.toLowerCase().trim();
                    if (statusKawinMap[lower] === undefined) r._errors.push('Status Perkawinan: tidak valid (Kawin/Belum Kawin/Cerai Hidup/Cerai Mati)');
                }
                // Satuan kerja wajib diketahui: dari kolom Excel atau pilihan manual.
                if (!r.satuan_kerja && !selectedWuId) {
                    r._errors.push('satuan_kerja: kosong — isi kolom ini atau pilih satuan kerja');
                }
                r._status = r._errors.length ? 'error' : 'ok';
            });
            // Simpan error dasar dulu; error pencocokan ditambahkan setelah
            // server selesai mencocokkan nilai dari file.
            parsedRows.forEach(r => { r._baseErrors = r._errors.slice(); });
            await resolveRowMatches();
            applyMatchValidation();
            renderTable(parsedRows);
            document.getElementById('previewCount').textContent = parsedRows.length + ' baris';
            document.getElementById('previewCard').style.display = 'block';
            document.getElementById('previewPlaceholder').style.display = 'none';
        } catch(err) { console.error(err); Swal.fire({icon:'error',title:'Gagal Baca File'}); }
    };
    reader.readAsArrayBuffer(file);
}
// ── Pencocokan otomatis: yakin dipakai, ragu minta konfirmasi ──────────────
async function resolveRowMatches() {
    const units = [...new Set(parsedRows.map(r => r.satuan_kerja).filter(Boolean))];
    const jabatans = [...new Set(parsedRows.map(r => r.jabatan).filter(Boolean))];

    parsedRows.forEach(r => { r._unit = null; r._jabatan = null; });

    if (!units.length && !jabatans.length) return;

    try {
        const res = await fetch('{{ "/$userId" }}/gtk/import/resolve', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ satuan_kerja: units, jabatan: jabatans })
        });
        const data = await res.json();

        parsedRows.forEach(r => {
            if (r.satuan_kerja && data.satuan_kerja && data.satuan_kerja[r.satuan_kerja]) {
                r._unit = data.satuan_kerja[r.satuan_kerja];
            }
            if (r.jabatan && data.jabatan && data.jabatan[r.jabatan]) {
                r._jabatan = data.jabatan[r.jabatan];
            }
        });
    } catch (err) {
        // Gagal mencocokkan bukan alasan memblokir: server tetap mencocokkan
        // saat import dan menolak baris yang ragu.
        console.error('Pencocokan otomatis gagal', err);
    }
}

function applyMatchValidation() {
    parsedRows.forEach(r => {
        const errors = (r._baseErrors || []).slice();

        if (r._unit && !r._unitChoice) {
            if (r._unit.status === 'ambiguous') errors.push('satuan_kerja: perlu konfirmasi — pilih dari daftar');
            if (r._unit.status === 'unknown') errors.push('satuan_kerja: tidak dikenali — pilih dari daftar');
        }
        if (r._jabatan && !r._jabatanChoice) {
            if (r._jabatan.status === 'ambiguous') errors.push('jabatan: perlu konfirmasi — pilih dari daftar');
            if (r._jabatan.status === 'unknown') errors.push('jabatan: tidak dikenali — pilih dari daftar');
        }

        r._errors = errors;
        r._status = errors.length ? 'error' : 'ok';
    });

    validRows = parsedRows.filter(r => r._status !== 'error');
    document.getElementById('countValid').textContent = validRows.length;
    document.getElementById('countError').textContent = parsedRows.filter(r => r._status === 'error').length;
    document.getElementById('importBtn').disabled = !validRows.length;
    document.getElementById('importCountLabel').textContent = validRows.length;
}

function renderMatchCell(r, kind) {
    const isUnit = kind === 'unit';
    const value = isUnit ? r.satuan_kerja : r.jabatan;
    const match = isUnit ? r._unit : r._jabatan;
    const choice = isUnit ? r._unitChoice : r._jabatanChoice;

    if (!value) {
        const fallback = isUnit ? selectedWuName : '';
        return fallback
            ? escHtml(fallback) + ' <span class="badge bg-secondary-subtle text-secondary" style="font-size:10px;">dipilih</span>'
            : '<span class="text-danger">—</span>';
    }

    if (choice) {
        const picked = ((match && match.candidates) || []).find(c => c.id === choice);
        return escHtml(picked ? picked.label : value) + ' <span class="badge bg-info-subtle text-info" style="font-size:10px;">dikonfirmasi</span>';
    }

    if (!match) return escHtml(value);

    if (match.status === 'auto') {
        return escHtml(match.match.label) + ` <span class="badge bg-success-subtle text-success" style="font-size:10px;">otomatis ${match.score}%</span>`;
    }

    const options = (match.candidates || [])
        .map(c => `<option value="${c.id}">${escHtml(c.label)} (${c.score}%)</option>`)
        .join('');

    const label = match.status === 'ambiguous' ? 'perlu konfirmasi' : 'tidak dikenali';
    const color = match.status === 'ambiguous' ? 'warning' : 'danger';

    return `<div class="d-flex flex-column gap-1">` +
        `<span class="badge bg-${color}-subtle text-${color}" style="font-size:10px;">${label}</span>` +
        `<select class="form-select form-select-sm match-select" data-row="${r._rowNum}" data-kind="${kind}" style="min-width:190px">` +
        `<option value="">— pilih —</option>${options}</select></div>`;
}

// Konfirmasi manual dari dropdown di dalam preview.
document.getElementById('previewTableWrap').addEventListener('change', function (event) {
    const select = event.target.closest('.match-select');
    if (!select) return;

    const row = parsedRows.find(r => r._rowNum === Number(select.dataset.row));
    if (!row) return;

    if (select.dataset.kind === 'unit') row._unitChoice = select.value;
    else row._jabatanChoice = select.value;

    applyMatchValidation();
    renderTable(parsedRows);
});
function renderTable(rows) {
    const cols = [
        {k:'_rowNum',l:'#'}, {k:'name',l:'Nama'}, {k:'email',l:'Email'},
        {k:'nik',l:'NIK'}, {k:'nupy',l:'NUPY'}, {k:'jenis_kelamin',l:'JK'},
        {k:'tanggal_lahir',l:'Tgl Lahir'}, {k:'jenis_gtk',l:'Jenis GTK'},
        {k:'jabatan',l:'Jabatan'}, {k:'satuan_kerja',l:'Satuan Kerja'},
        {k:'_status',l:'Status'}
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
                    html += `<td><div class="d-flex flex-column align-items-start gap-1">`;
                    r._errors.forEach(e => {
                        const [field, ...rest] = e.split(':');
                        const msg = rest.join(':').trim();
                        html += `<span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-normal" style="font-size:11px;padding:2px 6px;">`;
                        if (msg) {
                            html += `<strong>${escHtml(field)}:</strong> ${escHtml(msg)}`;
                        } else {
                            html += escHtml(e);
                        }
                        html += `</span>`;
                    });
                    html += `</div></td>`;
                } else {
                    html += `<td><span class="badge bg-success-subtle text-success border border-success-subtle"><i class="ri-checkbox-circle-line me-1"></i>Valid</span></td>`;
                }
            } else if (c.k === '_rowNum') {
                html += `<td class="text-muted">${r[c.k]||''}</td>`;
            } else if (c.k === 'satuan_kerja') {
                html += `<td class="text-nowrap">${renderMatchCell(r, 'unit')}</td>`;
            } else if (c.k === 'jabatan') {
                html += `<td class="text-nowrap">${renderMatchCell(r, 'jabatan')}</td>`;
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
    if (!validRows.length) return;
    const c = await Swal.fire({icon:'question',title:`Import ${validRows.length} GTK?`,showCancelButton:true,confirmButtonText:'Ya, Import!',cancelButtonText:'Batal',confirmButtonColor:'#198754'});
    if (!c.isConfirmed) return;
    const ov = document.getElementById('importProgressOverlay');
    const bar = document.getElementById('importProgressBar');
    const txt = document.getElementById('importProgressText');
    ov.classList.add('show');
    let created = 0, updated = 0, fail = 0, failRows = [], warnings = [];
    const BATCH = 10, total = validRows.length;
    for (let i = 0; i < total; i += BATCH) {
        // Kirim id hasil konfirmasi/otomatis supaya server tidak menebak ulang.
        const batch = validRows.slice(i, i + BATCH).map(r => Object.assign({}, r, {
            work_unit_id: r._unitChoice || (r._unit && r._unit.match ? r._unit.match.id : null),
            jabatan_id: r._jabatanChoice || (r._jabatan && r._jabatan.match ? r._jabatan.match.id : null),
        }));
        bar.style.width = Math.round((i/total)*100) + '%';
        txt.textContent = `Baris ${i+1}–${Math.min(i+BATCH,total)} dari ${total}...`;
        try {
            const res = await fetch('/{{ $userId }}/gtk/import/store', {
                method:'POST',
                headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                body: JSON.stringify({work_unit_id: selectedWuId || null, rows: batch})
            });
            const d = await res.json();
            if (d.success) {
                created += d.imported ?? 0;
                updated += d.updated ?? 0;
                if (d.failed?.length) {
                    fail += d.failed.length;
                    failRows = failRows.concat(d.failed.map(f => ({row: f.row, reason: f.reason})));
                }
                if (d.skipped?.length) {
                    d.skipped.forEach(s => warnings.push(`Baris ${s.row}: ${s.reason}`));
                }
            } else {
                if (d.failed?.length) {
                    fail += d.failed.length;
                    failRows = failRows.concat(d.failed.map(f => ({row: f.row, reason: f.reason})));
                } else {
                    fail += batch.length;
                    batch.forEach(r => failRows.push({row:r._rowNum, reason:d.message||'Gagal'}));
                }
                if (d.skipped?.length) {
                    d.skipped.forEach(s => warnings.push(`Baris ${s.row}: ${s.reason}`));
                }
            }
        } catch(e) { fail += batch.length; batch.forEach(r => failRows.push({row:r._rowNum, reason:'Network error'})); }
    }
    bar.style.width = '100%';
    setTimeout(() => { ov.classList.remove('show'); showResult(total, created, updated, fail, failRows, warnings); }, 400);
}
function showResult(total, created, updated, fail, failRows, warnings) {
    document.getElementById('importResultStats').innerHTML = `
        <div class="col-6 col-md-3"><div class="card border-0 bg-primary-subtle p-3"><div class="fs-3 fw-bold text-primary">${total}</div><div class="small text-muted">Total Baris</div></div></div>
        <div class="col-6 col-md-3"><div class="card border-0 bg-success-subtle p-3"><div class="fs-3 fw-bold text-success">${created}</div><div class="small text-muted">GTK Baru</div></div></div>
        <div class="col-6 col-md-3"><div class="card border-0 bg-info-subtle p-3"><div class="fs-3 fw-bold text-info">${updated}</div><div class="small text-muted">Diperbarui</div></div></div>
        <div class="col-6 col-md-3"><div class="card border-0 bg-danger-subtle p-3"><div class="fs-3 fw-bold text-danger">${fail}</div><div class="small text-muted">Gagal</div></div></div>`;
    let detail = '';
    if (failRows.length) {
        detail = `<div class="alert alert-danger mb-0"><strong><i class="ri-error-warning-line me-1"></i>${failRows.length} Baris Gagal:</strong>
            <div class="mt-2" style="max-height:200px;overflow-y:auto;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Baris</th><th>Alasan</th></tr></thead>
                    <tbody>
                        ${failRows.slice(0,20).map((f,i)=>`<tr><td class="text-muted">${i+1}</td><td><code>${f.row}</code></td><td class="text-danger small">${escHtml(f.reason)}</td></tr>`).join('')}
                        ${failRows.length>20?`<tr><td colspan="3" class="text-muted text-center small">...dan ${failRows.length-20} baris lainnya</td></tr>`:''}
                    </tbody>
                </table>
            </div>
        </div>`;
    } else {
        detail = `<div class="alert alert-success mb-0 py-2"><i class="ri-checkbox-circle-line me-1"></i><strong>${created}</strong> GTK baru dan <strong>${updated}</strong> GTK diperbarui.</div>`;
    }
    document.getElementById('importResultDetail').innerHTML = detail;

    // Warnings (skipped rows)
    const warnContainer = document.getElementById('importResultWarnings');
    if (warnings && warnings.length) {
        document.getElementById('warningList').innerHTML = warnings.slice(0,10).map(w=>`<li class="text-warning-emphasis">${w}</li>`).join('') +
            (warnings.length>10?`<li class="text-muted small">...dan ${warnings.length-10} lainnya</li>`:'');
        warnContainer.classList.remove('d-none');
    } else {
        warnContainer.classList.add('d-none');
    }

    document.getElementById('importResultCard').style.display = 'block';
    document.getElementById('previewCard').style.display = 'none';
    document.getElementById('previewPlaceholder').style.display = 'none';
    if (created > 0 || updated > 0) {
        Swal.fire({icon:'success',title:'Import Selesai!',html:`<strong>${created}</strong> GTK baru dan <strong>${updated}</strong> diperbarui.`,confirmButtonColor:'#198754'});
    }
}
function formatBytes(b) { return b < 1024*1024 ? (b/1024).toFixed(1)+' KB' : (b/(1024*1024)).toFixed(1)+' MB'; }
function escHtml(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
</script>
@endsection