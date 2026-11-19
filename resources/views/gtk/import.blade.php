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
.preview-table-wrap { max-height: calc(100vh - 300px); min-height: 340px; overflow: auto; border-radius: 10px; border: 1px solid #dee2e6; }
.preview-table-wrap table { margin-bottom: 0; font-size: 12.5px; min-width: 1180px; }
.preview-table-wrap thead th { position: sticky; top: 0; z-index: 10; background: #f8f9fa; font-size: 11px; white-space: nowrap; padding: 8px 12px; }
.preview-table-wrap tbody td { padding: 6px 12px; vertical-align: middle; }
.row-status-error td { background: #fff5f5 !important; }
.cell-edit { min-width: 138px; font-size: 12.5px; }
#previewCard.preview-fullscreen {
    position: fixed; inset: 0; z-index: 2050; margin: 0; border-radius: 0;
    display: flex; flex-direction: column; background: #fff;
}
#previewCard.preview-fullscreen .card-body { flex: 1 1 auto; min-height: 0; overflow: hidden; }
#previewCard.preview-fullscreen .preview-table-wrap { max-height: none; height: 100%; border: 0; border-radius: 0; }
#previewCard.preview-fullscreen .card-footer { flex: 0 0 auto; }
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

{{-- Penetapan kepala/wakil dipisahkan dari import --}}
<div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2 py-2 px-3 small">
    <div>
        <i class="ri-information-line me-1"></i>
        <strong>Kepala &amp; wakil satuan kerja tidak diubah lewat import.</strong>
        Baris mereka akan dilewati agar data tidak salah.
    </div>
    <a href="{{ route('user.gtk.kepala-wakil.index', ['userId' => $userId]) }}" class="btn btn-sm btn-primary">
        <i class="ri-user-star-line me-1"></i>Atur Kepala &amp; Wakil
    </a>
</div>

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
                                    Hanya <strong>Nama</strong> dan <strong>NUPY</strong> yang wajib. NIK, No KK,
                                    email, dan No HP boleh dikosongkan.<br>
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
                                'Informasi pribadi' => [
                                    ['name','Nama Lengkap',true],['email','Email (kosong = dibuat otomatis)',false],['nik','NIK (16 digit)',false],
                                    ['no_kk','Nomor KK',false],['tempat_lahir','Tempat Lahir',true],
                                    ['tanggal_lahir','Tgl Lahir (YYYY-MM-DD)',true],['jenis_kelamin','L atau P',true],
                                    ['golongan_darah','A/B/AB/O',false],['agama','Islam/Kristen/dst',false],
                                    ['status_perkawinan','Belum Kawin/Kawin/Cerai Hidup/Cerai Mati (otomatis dari Excel)',false],['npwp','NPWP',false],
                                    ['no_hp','No HP',false],['no_whatsapp','WhatsApp',false],
                                    ['alamat_jalan','Alamat jalan',false],['alamat_rt_rw','RT/RW',false],
                                    ['alamat_desa','Desa/Kelurahan',false],['alamat_kecamatan','Kecamatan',false],
                                    ['alamat_kota','Kab/Kota',false],['alamat_provinsi','Provinsi',false],
                                    ['kode_pos','Kode pos',false],
                                ],
                                'Kepegawaian' => [
                                    ['nupy','NUPY (jd password awal)',true],['jenis_gtk','Jenis GTK',true],
                                    ['jabatan','Jabatan (dianjurkan; terisi otomatis dari kandidat teratas)',false],['satuan_kerja','Nama/kode satuan kerja (terisi otomatis bila cocok)',false],
                                    ['status_kepegawaian','PTT/PTY/GTT/GTY/Berhenti/Pensiun',true],
                                    ['tmt','Tgl TMT (YYYY-MM-DD)',true],['nomor_sk','No SK',true],
                                    ['tanggal_sk','Tgl SK (YYYY-MM-DD)',true],
                                ],
                                'Pendidikan' => [
                                    ['jenjang_pendidikan','SD/SMP/SMA/S1/dst',false],
                                    ['nama_sekolah','Nama sekolah',false],['jurusan','Jurusan',false],
                                    ['tahun_lulus','Tahun lulus',false],
                                ],
                                'Keluarga' => [
                                    ['nama_pasangan','Nama pasangan (NUPY terisi otomatis bila pasangan sudah jadi GTK)',false],
                                    ['nama_anak_1','Nama anak ke-1',false],['tanggal_lahir_anak_1','Tgl lahir anak ke-1',false],
                                    ['jenis_kelamin_anak_1','L atau P',false],
                                    ['nama_anak_2','Nama anak ke-2',false],['tanggal_lahir_anak_2','Tgl lahir anak ke-2',false],
                                    ['jenis_kelamin_anak_2','L atau P',false],
                                    ['nama_anak_3','Nama anak ke-3',false],['tanggal_lahir_anak_3','Tgl lahir anak ke-3',false],
                                    ['jenis_kelamin_anak_3','L atau P',false],
                                ],
                            ] as $group => $columns)
                                <tr class="table-light">
                                    <td colspan="3" class="fw-semibold text-uppercase small">{{ $group }}</td>
                                </tr>
                                @foreach($columns as [$c,$k,$w])
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
            nama dan NUPY tidak pernah ditimpa, dan sel kosong tidak menghapus data lama.
            NIK atau email yang sudah dipakai GTK lain dengan NUPY berbeda tetap dilaporkan sebagai gagal.
            <br>
            Hanya <strong>nama</strong> dan <strong>NUPY</strong> yang wajib. Email dibuat otomatis
            (<code>nupy@gtk.local</code>) bila kosong; NIK, No KK, No HP, dan jabatan boleh dikosongkan.
            <br>
            Jabatan dan satuan kerja diisi otomatis dari kandidat dengan persentase tertinggi —
            termasuk saat skornya rendah. Periksa nilainya di preview dan ubah bila perlu.
            <br>
            <strong>Kepala dan wakil satuan kerja tidak dapat diubah lewat upload.</strong>
            Data mereka dikelola di menu
            <a href="{{ route('user.gtk.kepala-wakil.index', ['userId' => $userId]) }}" class="fw-semibold">Kepala &amp; Wakil Satuan Kerja</a>.
        </div>
    </div>
</div>

{{-- Preview Card — full width supaya data terlihat maksimal --}}
<div class="row mt-4" id="previewRow" style="display:none;">
    <div class="col-12">
        <div class="card" id="previewCard">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="card-title mb-0">
                    <i class="ri-table-line me-1 text-primary"></i>
                    Preview Data <span id="previewCount" class="badge bg-primary ms-2">0</span>
                </h5>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success">
                        <i class="ri-checkbox-circle-line me-1"></i><span id="countValid">0</span> Valid
                    </span>
                    <span class="badge bg-danger-subtle text-danger">
                        <i class="ri-close-circle-line me-1"></i><span id="countError">0</span> Error
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="fullscreenBtn" onclick="togglePreviewFullscreen()">
                        <i class="ri-fullscreen-line me-1"></i>Layar Penuh
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="preview-table-wrap" id="previewTableWrap"></div>
            </div>
            <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">
                    NIK, No KK, dan Status Perkawinan bisa langsung diubah di tabel ini.
                    Baris valid akan diimport ke satuan kerja yang dipilih.
                </small>
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
const REQUIRED = ['name','nupy'];
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
    document.getElementById('previewRow').style.display = 'none';
    document.getElementById('previewPlaceholder').style.display = 'block';
}
function clearFile() {
    togglePreviewFullscreen(false);
    fileInput.value = '';
    document.getElementById('fileInfo').style.display = 'none';
    document.getElementById('dropZoneText').textContent = 'Klik atau seret file Excel ke sini';
    document.getElementById('previewBtn').disabled = true;
    document.getElementById('previewRow').style.display = 'none';
    document.getElementById('previewPlaceholder').style.display = 'block';
    document.getElementById('importResultCard').style.display = 'none';
    parsedRows = []; validRows = [];
    document.getElementById('importBtn').disabled = true;
    document.getElementById('importCountLabel').textContent = '0';
}

// ── Normalisasi nilai dari Excel ──────────────────────────────────────────
function normalizeKey(v) {
    return String(v ?? '').toLowerCase().replace(/[_\/]+/g, ' ').replace(/[^a-z0-9 ]/g, ' ').replace(/\s+/g, ' ').trim();
}
function normalizeStatusKawin(v) {
    const k = normalizeKey(v);
    if (!k) return '';
    const map = {
        'kawin': 'kawin', 'menikah': 'kawin', 'married': 'kawin',
        'belum kawin': 'belum_kawin', 'belum menikah': 'belum_kawin', 'single': 'belum_kawin', 'lajang': 'belum_kawin',
        'cerai hidup': 'cerai_hidup', 'cerai': 'cerai_hidup', 'janda': 'cerai_hidup', 'duda': 'cerai_hidup',
        'janda cerai': 'cerai_hidup', 'duda cerai': 'cerai_hidup',
        'cerai mati': 'cerai_mati', 'mati': 'cerai_mati',
    };
    if (map[k]) return map[k];
    // Variasi panjang seperti "Kawin (Menikah)" atau "Cerai Hidup (Janda)".
    if (k.startsWith('kawin')) return 'kawin';
    if (k.startsWith('belum')) return 'belum_kawin';
    if (k.includes('cerai mati') || k === 'mati') return 'cerai_mati';
    if (k.includes('cerai') || k.includes('janda') || k.includes('duda')) return 'cerai_hidup';
    return '';
}
function normalizeStatusKepegawaian(v) {
    const k = normalizeKey(v);
    if (!k) return '';
    const map = {
        'ptt': 'PTT', 'pty': 'PTY', 'percobaan': 'Percobaan', 'magang': 'Magang',
        'gtt': 'GTT', 'gty': 'GTY', 'kontrak': 'KONTRAK', 'tetap': 'PTY',
        'pensiun': 'Pensiun', 'wafat': 'Wafat', 'berhenti': 'Berhenti',
    };
    return map[k] || '';
}
function normalizeJenisKelamin(v) {
    const k = normalizeKey(v);
    if (!k) return '';
    if (['l', 'laki laki', 'lakilaki', 'pria', 'male'].includes(k)) return 'L';
    if (['p', 'perempuan', 'wanita', 'female'].includes(k)) return 'P';
    return String(v).trim();
}
function isHeadOrViceName(v) {
    const k = normalizeKey(v);
    return k === 'kepala' || k.startsWith('kepala ') || k === 'wakil' || k.startsWith('wakil ');
}
function statusKawinOptions(selected) {
    const opts = [
        ['', '— Pilih —'],
        ['belum_kawin', 'Belum Kawin'],
        ['kawin', 'Kawin'],
        ['cerai_hidup', 'Cerai Hidup'],
        ['cerai_mati', 'Cerai Mati'],
    ];
    return opts.map(([v, l]) => `<option value="${v}"${v === (selected || '') ? ' selected' : ''}>${l}</option>`).join('');
}
// Validasi dasar — dipakai ulang setiap kali sel di preview diubah.
function baseErrorsFor(r) {
    const errors = [];
    REQUIRED.forEach(c => { if (!r[c]) errors.push(`${c}: wajib diisi`); });
    if (r.nik && !/^\d{16}$/.test(r.nik)) errors.push('NIK: harus 16 digit angka (boleh dikosongkan)');
    if (r.no_kk && !/^\d{16}$/.test(r.no_kk)) errors.push('No KK: harus 16 digit angka (boleh dikosongkan)');
    if (r.nupy && !/^\d{10,20}$/.test(r.nupy)) errors.push('NUPY: harus 10-20 digit angka');
    if (r.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(r.email)) errors.push('Email: tidak valid');
    if (r.jenis_kelamin && !['L', 'P'].includes(r.jenis_kelamin)) errors.push('JK: harus L atau P');
    if (r.status_kepegawaian && !['PTT', 'PTY', 'GTT', 'GTY', 'KONTRAK', 'Percobaan', 'Magang', 'Pensiun', 'Wafat', 'Berhenti'].includes(r.status_kepegawaian)) {
        errors.push('Status Kepegawaian: tidak valid (PTT/PTY/GTT/GTY/KONTRAK/Magang/Percobaan)');
    }
    // Satuan kerja wajib diketahui: dari kolom Excel atau pilihan manual.
    if (!r.satuan_kerja && !selectedWuId) {
        errors.push('satuan_kerja: kosong — isi kolom ini atau pilih satuan kerja');
    }
    return errors;
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
            // Normalisasi nilai supaya cocok dengan master/DB, bukan menolak
            // variasi penulisan dari Excel.
            parsedRows.forEach(r => {
                r.nik = String(r.nik || '').replace(/\D/g, '');
                r.no_kk = String(r.no_kk || '').replace(/\D/g, '');
                r.status_perkawinan = normalizeStatusKawin(r.status_perkawinan);
                r.status_kepegawaian = normalizeStatusKepegawaian(r.status_kepegawaian);
                r.jenis_kelamin = normalizeJenisKelamin(r.jenis_kelamin);
            });
            await resolveRowMatches();
            applyMatchValidation();
            renderTable(parsedRows);
            document.getElementById('previewCount').textContent = parsedRows.length + ' baris';
            document.getElementById('previewRow').style.display = 'block';
            document.getElementById('previewPlaceholder').style.display = 'none';
            setTimeout(() => document.getElementById('previewRow').scrollIntoView({ behavior: 'smooth', block: 'start' }), 80);
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
            body: JSON.stringify({
                satuan_kerja: units,
                jabatan: jabatans,
                // Konteks per baris: jabatan dinilai relatif ke satuan kerjanya.
                rows: parsedRows.map(r => ({
                    satuan_kerja: r.satuan_kerja || '',
                    jabatan: r.jabatan || '',
                    jenis_gtk: r.jenis_gtk || ''
                }))
            })
        });
        const data = await res.json();

        parsedRows.forEach((r, i) => {
            const perRow = Array.isArray(data.rows) ? data.rows[i] : null;

            if (perRow && perRow.unit && r.satuan_kerja) r._unit = perRow.unit;
            else if (r.satuan_kerja && data.satuan_kerja && data.satuan_kerja[r.satuan_kerja]) r._unit = data.satuan_kerja[r.satuan_kerja];

            if (perRow && perRow.jabatan && r.jabatan) r._jabatan = perRow.jabatan;
            else if (r.jabatan && data.jabatan && data.jabatan[r.jabatan]) r._jabatan = data.jabatan[r.jabatan];
        });
    } catch (err) {
        // Gagal mencocokkan bukan alasan memblokir: server tetap mencocokkan
        // saat import dan menolak baris yang ragu.
        console.error('Pencocokan otomatis gagal', err);
    }
}

function applyMatchValidation() {
    parsedRows.forEach(r => {
        const errors = baseErrorsFor(r);

        // Satuan kerja harus punya tujuan: pilihan di preview, hasil pencocokan,
        // kolom Excel yang dikenali, atau unit yang dipilih di langkah 1.
        // Jabatan tidak pernah memblokir — kalau ragu, kandidat teratas dipakai.
        const unitResolved = !!r._unitChoice
            || !!(r._unit && (r._unit.match || (r._unit.candidates || []).length))
            || (!r.satuan_kerja && !!selectedWuName);

        if (!unitResolved) {
            errors.push('satuan_kerja: tidak dikenali — pilih dari daftar atau isi ulang kolomnya');
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

    // Jabatan kepala/wakil tidak diisi lewat import.
    if (!isUnit && isHeadOrViceName(value)) {
        return escHtml(value) + ' <span class="badge bg-warning-subtle text-warning" style="font-size:10px;">diatur di menu Kepala &amp; Wakil</span>';
    }

    if (choice) {
        const picked = ((match && match.candidates) || []).find(c => c.id === choice);
        return escHtml(picked ? picked.label : value) + ' <span class="badge bg-info-subtle text-info" style="font-size:10px;">dikonfirmasi</span>';
    }

    if (!match) return escHtml(value);

    if (match.status === 'auto') {
        return escHtml(match.match.label) + ` <span class="badge bg-success-subtle text-success" style="font-size:10px;">otomatis ${match.score}%</span>`;
    }

    const candidates = match.candidates || [];

    if (!candidates.length) {
        return escHtml(value) + ' <span class="badge bg-danger-subtle text-danger" style="font-size:10px;">tidak dikenali</span>';
    }

    // Kandidat dengan persentase tertinggi langsung terpilih; pengguna tetap
    // bisa menggantinya lewat dropdown.
    const options = candidates
        .map((c, i) => `<option value="${c.id}"${i === 0 ? ' selected' : ''}>${escHtml(c.label)} (${c.score}%)</option>`)
        .join('');

    return `<div class="d-flex flex-column gap-1">` +
        `<span class="badge bg-warning-subtle text-warning" style="font-size:10px;">dipilih otomatis ${match.score}% — bisa diubah</span>` +
        `<select class="form-select form-select-sm match-select" data-row="${r._rowNum}" data-kind="${kind}" style="min-width:190px">` +
        `${options}</select></div>`;
}

// Perubahan manual dari preview: konfirmasi unit/jabatan atau edit sel.
document.getElementById('previewTableWrap').addEventListener('change', function (event) {
    const select = event.target.closest('.match-select');
    const edit = event.target.closest('.cell-edit');
    const el = select || edit;
    if (!el) return;

    const row = parsedRows.find(r => r._rowNum === Number(el.dataset.row));
    if (!row) return;

    if (select) {
        if (select.dataset.kind === 'unit') row._unitChoice = select.value;
        else row._jabatanChoice = select.value;
    } else {
        const field = edit.dataset.field;
        let value = edit.value;
        if (field === 'nik' || field === 'no_kk') value = String(value).replace(/\D/g, '');
        if (field === 'status_perkawinan') value = normalizeStatusKawin(value);
        row[field] = value;
    }

    applyMatchValidation();
    renderTable(parsedRows);
});
function renderTable(rows) {
    const wrap = document.getElementById('previewTableWrap');
    const scrollTop = wrap.scrollTop;
    // Nama, jabatan, dan satuan kerja diletakkan berdampingan supaya mudah
    // diperiksa sekaligus saat mencocokkan hasil import.
    const cols = [
        {k:'_rowNum',l:'#'}, {k:'name',l:'Nama'},
        {k:'jabatan',l:'Jabatan'}, {k:'satuan_kerja',l:'Satuan Kerja'},
        {k:'nupy',l:'NUPY'}, {k:'email',l:'Email'},
        {k:'nik',l:'NIK'}, {k:'no_kk',l:'No KK'},
        {k:'jenis_kelamin',l:'JK'}, {k:'status_perkawinan',l:'Status Perkawinan'},
        {k:'tanggal_lahir',l:'Tgl Lahir'}, {k:'jenis_gtk',l:'Jenis GTK'},
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
            } else if (c.k === 'nik' || c.k === 'no_kk') {
                html += `<td><input type="text" inputmode="numeric" maxlength="16"
                    class="form-control form-control-sm cell-edit"
                    data-row="${r._rowNum}" data-field="${c.k}"
                    value="${escHtml(r[c.k] || '')}" placeholder="Opsional"></td>`;
            } else if (c.k === 'status_perkawinan') {
                html += `<td><select class="form-select form-select-sm cell-edit"
                    data-row="${r._rowNum}" data-field="status_perkawinan">
                    ${statusKawinOptions(r.status_perkawinan)}</select></td>`;
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
    wrap.innerHTML = html;
    wrap.scrollTop = scrollTop;
}

// Import
async function startImport() {
    if (!validRows.length) return;
    togglePreviewFullscreen(false);
    const c = await Swal.fire({icon:'question',title:`Import ${validRows.length} GTK?`,showCancelButton:true,confirmButtonText:'Ya, Import!',cancelButtonText:'Batal',confirmButtonColor:'#198754'});
    if (!c.isConfirmed) return;
    const ov = document.getElementById('importProgressOverlay');
    const bar = document.getElementById('importProgressBar');
    const txt = document.getElementById('importProgressText');
    ov.classList.add('show');
    let created = 0, updated = 0, fail = 0, failRows = [], warnings = [];
    const BATCH = 10, total = validRows.length;
    for (let i = 0; i < total; i += BATCH) {
        // Kirim id pilihan pengguna, atau kandidat teratas yang sudah tampil di
        // dropdown, supaya server tidak menebak ulang.
        const topId = m => (m && ((m.match && m.match.id) || (m.candidates && m.candidates[0] && m.candidates[0].id))) || null;
        const batch = validRows.slice(i, i + BATCH).map(r => Object.assign({}, r, {
            work_unit_id: r._unitChoice || topId(r._unit),
            jabatan_id: r._jabatanChoice || topId(r._jabatan),
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

    togglePreviewFullscreen(false);
    document.getElementById('importResultCard').style.display = 'block';
    document.getElementById('previewRow').style.display = 'none';
    document.getElementById('previewPlaceholder').style.display = 'none';
    setTimeout(() => document.getElementById('importResultCard').scrollIntoView({ behavior: 'smooth', block: 'start' }), 80);
    if (created > 0 || updated > 0) {
        Swal.fire({icon:'success',title:'Import Selesai!',html:`<strong>${created}</strong> GTK baru dan <strong>${updated}</strong> diperbarui.`,confirmButtonColor:'#198754'});
    }
}
// Layar penuh untuk preview — data lebar tetap terbaca tanpa scroll halaman.
function togglePreviewFullscreen(force) {
    const card = document.getElementById('previewCard');
    if (!card) return;
    const on = force === undefined ? !card.classList.contains('preview-fullscreen') : !!force;
    card.classList.toggle('preview-fullscreen', on);
    document.body.classList.toggle('overflow-hidden', on);
    const btn = document.getElementById('fullscreenBtn');
    if (btn) {
        btn.innerHTML = on
            ? '<i class="ri-fullscreen-exit-line me-1"></i>Tutup Layar Penuh'
            : '<i class="ri-fullscreen-line me-1"></i>Layar Penuh';
    }
}
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') togglePreviewFullscreen(false);
});
function formatBytes(b) { return b < 1024*1024 ? (b/1024).toFixed(1)+' KB' : (b/(1024*1024)).toFixed(1)+' MB'; }
function escHtml(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
</script>
@endsection