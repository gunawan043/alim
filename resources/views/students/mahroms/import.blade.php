@extends('layouts.master')
@section('title', 'Import Data Mahrom')
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Santri</a> @endslot
        @slot('li_2') <a href="{{ route('user.students.mahroms.global', ['userId' => $userId]) }}">Data Mahrom</a> @endslot
        @slot('title') Import Data Mahrom @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="ri-alert-line me-1"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong><i class="ri-error-warning-line me-1"></i>Baris yang dilewati:</strong>
            <ul class="mb-0 mt-1" style="max-height:220px;overflow:auto;">
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="row row-cols-1 row-cols-md-3 g-3 mb-3">
        <div class="col">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-2 py-3">
                    <span class="avatar-xs"><span class="avatar-title bg-primary-subtle rounded-circle"><i class="ri-team-line text-primary"></i></span></span>
                    <div><p class="text-muted mb-0 fs-12">Santri dalam akses Anda</p><h5 class="mb-0">{{ number_format($studentCount) }}</h5></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-2 py-3">
                    <span class="avatar-xs"><span class="avatar-title bg-info-subtle rounded-circle"><i class="ri-parent-line text-info"></i></span></span>
                    <div><p class="text-muted mb-0 fs-12">Data mahrom tersimpan</p><h5 class="mb-0">{{ number_format($mahromCount) }}</h5></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-2 py-3">
                    <span class="avatar-xs"><span class="avatar-title bg-warning-subtle rounded-circle"><i class="ri-phone-line text-warning"></i></span></span>
                    <div><p class="text-muted mb-0 fs-12">Mahrom tanpa No. HP</p><h5 class="mb-0">{{ number_format($withoutPhoneCount) }}</h5></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-download-2-line me-1 text-primary"></i>1. Unduh Template</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Format baru: <strong>satu baris = satu santri</strong>. Informasi santri terisi
                        otomatis, lalu data <strong>Mahrom 1 s/d Mahrom terakhir</strong> di kolom samping —
                        tidak ada baris santri berulang.
                    </p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('user.students.mahroms.import.template-filled', ['userId' => $userId]) }}"
                           class="btn btn-primary">
                            <i class="ri-file-excel-2-line me-1"></i>Unduh Data Mahrom (Terisi Otomatis)
                        </a>
                        <a href="{{ route('user.students.mahroms.import.template', ['userId' => $userId]) }}"
                           class="btn btn-outline-primary">
                            <i class="ri-file-download-line me-1"></i>Template Kosong
                        </a>
                    </div>
                    <div class="form-text mt-2">
                        Template terisi memuat seluruh santri dalam akses Anda beserta mahrom yang sudah ada.
                        Cukup lengkapi/ubah kolom mahrom lalu unggah kembali.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-upload-2-line me-1 text-success"></i>2. Unggah File</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.students.mahroms.import.store', ['userId' => $userId]) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="mahrom_file" class="form-label">File Excel / CSV <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="mahrom_file"
                                   class="form-control @error('file') is-invalid @enderror"
                                   accept=".xlsx,.xls,.csv" required>
                            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Format .xlsx, .xls, atau .csv — maksimal 5 MB.</div>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="ri-upload-cloud-2-line me-1"></i>Mulai Import
                        </button>
                        <a href="{{ route('user.students.mahroms.global', ['userId' => $userId]) }}" class="btn btn-light ms-1">
                            Kembali ke Data Mahrom
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header border-bottom-dashed">
            <h6 class="card-title mb-0"><i class="ri-information-line me-1 text-info"></i>Ketentuan Import</h6>
        </div>
        <div class="card-body small">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-3">
                    <thead class="table-light">
                        <tr>
                            <th style="width:150px;">Kolom</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><code>NISN / NIS</code></td><td>Identitas santri — <strong>sudah terisi otomatis</strong> pada template terisi. Wajib salah satu.</td></tr>
                        <tr><td><code>Nama Santri / Kelas / Kamar / Asrama</code></td><td>Terisi otomatis dari sistem; diabaikan saat import.</td></tr>
                        <tr><td><code>Mahrom 1 - Nama … Mahrom N - Nama</code></td><td>Nama mahrom. Kosongkan slot jika tidak ada.</td></tr>
                        <tr><td><code>Mahrom N - Hubungan</code></td><td>ayah, ibu, wali, kakak, adik, paman, bibi, kakek, nenek, suami, istri, sepupu, anak, lainnya.</td></tr>
                        <tr><td><code>Mahrom N - No. HP</code></td><td>Nomor WhatsApp wali (8–20 digit). Boleh diawali <code>+62</code>.</td></tr>
                        <tr><td><code>Mahrom N - NIK</code></td><td>Opsional, maks. 30 karakter. NIK yang sudah dipakai mahrom lain diabaikan.</td></tr>
                        <tr><td><code>Mahrom N - Utama</code></td><td>ya / tidak. Hanya satu mahrom utama per santri.</td></tr>
                    </tbody>
                </table>
            </div>

            <ul class="mb-0 text-muted">
                <li><strong>Satu baris = satu santri.</strong> Tambahkan mahrom ke-2/ke-3 pada kolom <code>Mahrom 2</code>, <code>Mahrom 3</code>, dst. — bukan baris baru.</li>
                <li>Peran yang sudah ada (mis. <code>ayah</code>) akan <strong>diperbarui</strong>, bukan diduplikasi.</li>
                <li>Kolom No. HP/alamat yang dikosongkan <strong>tidak menghapus</strong> data lama.</li>
                <li>Format file lama (satu baris per mahrom) tetap didukung.</li>
                <li>Perubahan langsung sinkron ke modul asrama, kartu izin, dan notifikasi wali.</li>
                <li>Baris tidak valid dilewati dan dilaporkan tanpa membatalkan baris lainnya.</li>
            </ul>
        </div>
    </div>
@endsection
