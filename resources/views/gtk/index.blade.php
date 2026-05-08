@extends('layouts.master')
@section('title')
    @lang('Data GTK')
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .table-container {
            position: relative;
            width: 100%;
            overflow-x: auto;
        }
        .table-freeze {
            table-layout: auto;
            min-width: max-content;
            margin-bottom: 0;
            width: 100%;
        }
        .table-freeze th,
        .table-freeze td {
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            vertical-align: middle;
            padding: 12px 16px;
            word-break: break-word;
        }
        .table-freeze th:first-child,
        .table-freeze td:first-child {
            position: sticky;
            left: 0;
            z-index: 100;
            min-width: 150px;
            max-width: 200px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            white-space: normal;
            word-wrap: break-word;
        }
        .table-freeze thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        /* Kolom non-default disembunyikan via CSS class */
        .col-hidden {
            display: none !important;
        }
        .card-animate { transition: all 0.3s ease; }
        .card-animate:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            font-size: 13px;
            transition: all 0.2s;
            margin: 4px;
            cursor: pointer;
        }
        .filter-badge:hover { background: #405189; border-color: #94a3b8; color: #fff; }
        .filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }
        .filter-badge .remove-filter { cursor: pointer; margin-left: 4px; opacity: 0.7; }
        .filter-badge .remove-filter:hover { opacity: 1; }
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 5px 12px;
        }
        .column-visibility-dropdown { max-height: 400px; overflow-y: auto; padding: 12px; width: 260px; }
        .filter-group { background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
        .filter-group-title { font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .filter-group-title i { color: #0a5f9e; font-size: 18px; }
    </style>
@endsection

@section('content')
    @php $userId = request()->route('userId') ?? Auth::id(); @endphp
    @component('components.breadcrumb')
        @slot('li_1') Data GTK @endslot
        @slot('title')
            @if (isset($satuanKerja)) {{ $satuanKerja->name }} @else Semua GTK @endif
        @endslot
    @endcomponent

    <!-- STATISTICS CARDS -->
    @php
        $total     = $statistics['total'] ?? 0;
        $aktif     = $statistics['aktif'] ?? 0;
        $nonaktif  = $statistics['nonaktif'] ?? 0;
        $genderL   = $statistics['gender_l'] ?? 0;
        $genderP   = $statistics['gender_p'] ?? 0;
        $totalJk   = $genderL + $genderP;
        $pctL      = $totalJk > 0 ? round($genderL / $totalJk * 100) : 0;
        $pctP      = $totalJk > 0 ? round($genderP / $totalJk * 100) : 0;
    @endphp

    <div class="row g-3 mb-3">

        {{-- 1. Total GTK --}}
        <div class="col-xl-4 col-md-4">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2">
                                <i class="bx bx-group text-primary"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total GTK</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($total) }}</h3>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <small class="text-muted"><i class="ri-checkbox-circle-fill text-success me-1"></i>{{ number_format($aktif) }} Aktif</small>
                        <small class="text-muted"><i class="ri-close-circle-fill text-danger me-1"></i>{{ number_format($nonaktif) }} Nonaktif</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Perbandingan Jenis Kelamin --}}
        <div class="col-xl-4 col-md-4">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2">
                                <i class="bx bx-user text-info"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Jenis Kelamin</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($genderL) }} <small class="fw-normal text-muted">/</small> {{ number_format($genderP) }}</h3>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary" style="font-size:10px;">
                            <i class="ri-men-line me-1"></i>L {{ $pctL }}%
                        </span>
                        <div class="progress flex-grow-1" style="height:6px;">
                            <div class="progress-bar bg-primary" style="width:{{ $pctL }}%"></div>
                            <div class="progress-bar bg-danger" style="width:{{ $pctP }}%"></div>
                        </div>
                        <span class="badge bg-danger-subtle text-danger" style="font-size:10px;">
                            P {{ $pctP }}% <i class="ri-women-line ms-1"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Aktif / Nonaktif --}}
        <div class="col-xl-4 col-md-4">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="bx bx-check-circle text-success"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Aktif & Nonaktif</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($aktif) }} <small class="fw-normal text-muted">/</small> {{ number_format($nonaktif) }}</h3>
                        </div>
                    </div>
                    <div class="progress" style="height:6px;">
                        @php $ratioAktif = $total > 0 ? round($aktif / $total * 100) : 0; @endphp
                        <div class="progress-bar bg-success" style="width:{{ $ratioAktif }}%"></div>
                        <div class="progress-bar bg-danger" style="width:{{ 100 - $ratioAktif }}%"></div>
                    </div>
                    <small class="text-muted">{{ $ratioAktif }}% aktif &middot; {{ 100 - $ratioAktif }}% nonaktif</small>
                </div>
            </div>
        </div>

    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="customerList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">
                                    @if (isset($satuanKerja)) Daftar GTK - {{ $satuanKerja->name }} @else Daftar GTK @endif
                                </h5>
                                <p class="text-muted mb-0">
                                    @if (isset($satuanKerja))
                                        <span class="badge bg-info-subtle text-info">{{ $satuanKerja->kode }}</span>
                                    @endif
                                    <span id="activeFilterBadge" class="badge bg-secondary-subtle text-secondary ms-2" style="display: none;"></span>
                                </p>
                            </div>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-start gap-2">
                                <div class="d-flex gap-2">
                                    <input type="text" class="form-control" id="globalSearch"
                                        placeholder="Cari Nama, NIK, NUPY, No HP, Alamat..."
                                        value="{{ request('search') }}" style="width: 280px;">
                                    <button type="button" class="btn btn-primary" onclick="performSearch()">
                                        <i class="ri-search-line"></i> Cari
                                    </button>
                                </div>

                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#filterModal">
                                    <i class="bx bx-filter-alt align-bottom me-1"></i> Filter Lanjutan
                                    <span id="activeFilterCount" class="badge bg-light text-dark ms-1" style="display: none;">0</span>
                                </button>

                                <!-- COLUMN VISIBILITY -->
                                <div class="dropdown">
                                    <button type="button" class="btn btn-soft-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-table-line align-bottom me-1"></i> Kolom
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end column-visibility-dropdown" style="width:260px;">
                                        <h6 class="dropdown-header">Tampilkan Kolom</h6>
                                        <div class="px-2">
                                            <div class="fw-semibold text-primary mb-1 mt-1">Data Pribadi</div>
                                            {{-- nama selalu checked (sticky) --}}
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="nik" id="colNik">
                                                <label class="form-check-label" for="colNik">NIK</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="no_kk" id="colNoKK">
                                                <label class="form-check-label" for="colNoKK">No KK</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="tempat_lahir" id="colTempatLahir">
                                                <label class="form-check-label" for="colTempatLahir">Tempat Lahir</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="tanggal_lahir" id="colTanggalLahir">
                                                <label class="form-check-label" for="colTanggalLahir">Tanggal Lahir</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="jenis_kelamin" id="colJenisKelamin">
                                                <label class="form-check-label" for="colJenisKelamin">Jenis Kelamin</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="golongan_darah" id="colGolonganDarah">
                                                <label class="form-check-label" for="colGolonganDarah">Golongan Darah</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="status_perkawinan" id="colStatusPerkawinan">
                                                <label class="form-check-label" for="colStatusPerkawinan">Status Perkawinan</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="agama" id="colAgama">
                                                <label class="form-check-label" for="colAgama">Agama</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="npwp" id="colNPWP">
                                                <label class="form-check-label" for="colNPWP">NPWP</label>
                                            </div>
                                            <hr class="my-2">
                                            <div class="fw-semibold text-primary mb-1">Kontak</div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="email" id="colEmail" checked>
                                                <label class="form-check-label" for="colEmail">Email</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="no_hp" id="colNoHp" checked>
                                                <label class="form-check-label" for="colNoHp">No HP</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="no_whatsapp" id="colNoWhatsapp">
                                                <label class="form-check-label" for="colNoWhatsapp">No WhatsApp</label>
                                            </div>
                                            <hr class="my-2">
                                            <div class="fw-semibold text-primary mb-1">Kepegawaian</div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="nupy" id="colNupy">
                                                <label class="form-check-label" for="colNupy">NUPY</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="jenis_gtk" id="colJenisGtk">
                                                <label class="form-check-label" for="colJenisGtk">Jenis GTK</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="jabatan" id="colJabatan" checked>
                                                <label class="form-check-label" for="colJabatan">Jabatan</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="status_kepegawaian" id="colStatus" checked>
                                                <label class="form-check-label" for="colStatus">Status</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="tmt" id="colTmt" checked>
                                                <label class="form-check-label" for="colTmt">TMT</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="nomor_sk" id="colNomorSK">
                                                <label class="form-check-label" for="colNomorSK">Nomor SK</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="tanggal_sk" id="colTanggalSK">
                                                <label class="form-check-label" for="colTanggalSK">Tanggal SK</label>
                                            </div>
                                            <hr class="my-2">
                                            <div class="fw-semibold text-primary mb-1">Pendidikan</div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="jenjang_pendidikan" id="colJenjangPendidikan">
                                                <label class="form-check-label" for="colJenjangPendidikan">Jenjang Pendidikan</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="pendidikan_nama_satuan_pendidikan" id="colPendidikanSekolah">
                                                <label class="form-check-label" for="colPendidikanSekolah">Nama Sekolah</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="pendidikan_jurusan" id="colPendidikanJurusan">
                                                <label class="form-check-label" for="colPendidikanJurusan">Jurusan</label>
                                            </div>
                                            <hr class="my-2">
                                            <div class="fw-semibold text-primary mb-1">Alamat</div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="alamat_domisili" id="colAlamatDomisili">
                                                <label class="form-check-label" for="colAlamatDomisili">Alamat Domisili</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="alamat_ktp" id="colAlamatKTP">
                                                <label class="form-check-label" for="colAlamatKTP">Alamat KTP</label>
                                            </div>
                                            <hr class="my-2">
                                            <div class="fw-semibold text-primary mb-1">Lainnya</div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="satuan_kerja" id="colSatuanKerja" checked>
                                                <label class="form-check-label" for="colSatuanKerja">Satuan Kerja</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input column-toggle" type="checkbox" value="status_aktif" id="colStatusAktif" checked>
                                                <label class="form-check-label" for="colStatusAktif">Aktif</label>
                                            </div>
                                            <hr>
                                            <div class="d-flex justify-content-between">
                                                <button class="btn btn-sm btn-link" onclick="resetColumnVisibility()">Reset</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if (Auth::user()->hasRole(['Personalia', 'Super Admin', 'Administrator']))
                                    <a href="{{ route('user.gtk.import', ['userId' => $userId]) }}" class="btn btn-success">
                                        <i class="bx bx-add-to-queue ri-upload-2-line align-bottom me-1"></i> Import
                                    </a>

                                    <a href="{{ route('user.gtk.create', ['userId' => $userId]) }}" class="btn btn-warning">
                                        <i class="bx bx-add-to-queue align-bottom me-1"></i> Tambah GTK
                                    </a>
                                @endif

                                <div class="dropdown">
                                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="bx bx-export align-bottom me-1"></i> Export
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end p-3" style="width: 250px;">
                                        <li><h6 class="dropdown-header">Export Data GTK</h6></li>
                                        <li>
                                            <div class="px-2">
                                                <div class="mb-2">
                                                    <label class="form-label fw-semibold">Format</label>
                                                    <select class="form-select form-select-sm" id="exportFormat">
                                                        <option value="excel">Excel (.xlsx)</option>
                                                        <option value="pdf">PDF</option>
                                                        <option value="csv">CSV</option>
                                                    </select>
                                                </div>
                                                <div class="mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="exportScope" id="exportAll" value="all" checked>
                                                        <label class="form-check-label" for="exportAll">Semua data</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="exportScope" id="exportFiltered" value="filtered">
                                                        <label class="form-check-label" for="exportFiltered">Data terfilter</label>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-primary w-100" onclick="exportData()">
                                                    <i class="ri-download-line me-1"></i> Download
                                                </button>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTIVE FILTERS BADGE ROW -->
                <div class="card-header py-2 bg-light border-bottom" id="activeFiltersRow" style="display: none;">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="fw-semibold me-2"><i class="ri-filter-3-line"></i> Filter Aktif:</span>
                        <div id="activeFilterBadges" class="d-flex flex-wrap gap-2"></div>
                        <button class="btn btn-sm btn-link text-danger ms-auto" onclick="clearAllFilters()">
                            <i class="ri-close-circle-line me-1"></i> Hapus Semua
                        </button>
                    </div>
                </div>

                <!-- QUICK FILTER -->
                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted me-2"><i class="ri-flashlight-line"></i> Quick Filter:</span>
                        <button class="filter-badge" onclick="quickFilter('status_aktif', '1')"><i class="ri-checkbox-circle-line"></i> Aktif</button>
                        <button class="filter-badge" onclick="quickFilter('status_kepegawaian', 'Tetap')"><i class="ri-user-star-line"></i> Tetap</button>
                        <button class="filter-badge" onclick="quickFilter('status_kepegawaian', 'PTT')"><i class="ri-user-settings-line"></i> PTT</button>
                        <button class="filter-badge" onclick="quickFilter('status_kepegawaian', 'GTT')"><i class="ri-user-settings-line"></i> GTT</button>
                        <button class="filter-badge" onclick="quickFilter('status_kepegawaian', 'Percobaan')"><i class="ri-timer-line"></i> Percobaan</button>
                        <button class="filter-badge" onclick="quickFilter('jenis_kelamin', 'L')"><i class="ri-men-line"></i> Laki-laki</button>
                        <button class="filter-badge" onclick="quickFilter('jenis_kelamin', 'P')"><i class="ri-women-line"></i> Perempuan</button>
                    </div>
                </div>

                @if(session('error'))
                    <div class="alert alert-danger m-3">{{ session('error') }}</div>
                @endif

                <div class="card-body">
                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze" id="gtkTable">
                            <thead class="table-light">
                                <tr>
                                    {{-- ============================================================
                                         PENTING: Kolom NON-DEFAULT diberi class "col-hidden"
                                         langsung di HTML. Ini yang membuatnya tersembunyi saat
                                         halaman pertama kali dimuat, TANPA mengandalkan JavaScript.
                                         Kolom DEFAULT (8): nama, email, no_hp, jabatan,
                                         status_kepegawaian, tmt, satuan_kerja, status_aktif
                                    ============================================================ --}}

                                    {{-- DEFAULT --}}
                                    <th data-column="nama">Nama GTK</th>

                                    {{-- NON-DEFAULT: Data Pribadi --}}
                                    <th data-column="nik" class="col-hidden">NIK</th>
                                    <th data-column="no_kk" class="col-hidden">No KK</th>
                                    <th data-column="tempat_lahir" class="col-hidden">Tempat Lahir</th>
                                    <th data-column="tanggal_lahir" class="col-hidden">Tanggal Lahir</th>
                                    <th data-column="jenis_kelamin" class="col-hidden">JK</th>
                                    <th data-column="golongan_darah" class="col-hidden">Gol Darah</th>
                                    <th data-column="status_perkawinan" class="col-hidden">Status Kawin</th>
                                    <th data-column="agama" class="col-hidden">Agama</th>
                                    <th data-column="npwp" class="col-hidden">NPWP</th>

                                    {{-- DEFAULT --}}
                                    <th data-column="email">Email</th>
                                    <th data-column="no_hp">No HP</th>

                                    {{-- NON-DEFAULT: Kontak --}}
                                    <th data-column="no_whatsapp" class="col-hidden">No WhatsApp</th>

                                    {{-- NON-DEFAULT: Kepegawaian --}}
                                    <th data-column="nupy" class="col-hidden">NUPY</th>
                                    <th data-column="jenis_gtk" class="col-hidden">Jenis GTK</th>

                                    {{-- DEFAULT --}}
                                    <th data-column="jabatan">Jabatan</th>
                                    <th data-column="status_kepegawaian">Status</th>
                                    <th data-column="tmt">TMT</th>

                                    {{-- NON-DEFAULT: Kepegawaian --}}
                                    <th data-column="nomor_sk" class="col-hidden">Nomor SK</th>
                                    <th data-column="tanggal_sk" class="col-hidden">Tanggal SK</th>

                                    {{-- NON-DEFAULT: Pendidikan --}}
                                    <th data-column="jenjang_pendidikan" class="col-hidden">Jenjang</th>
                                    <th data-column="pendidikan_nama_satuan_pendidikan" class="col-hidden">Sekolah</th>
                                    <th data-column="pendidikan_jurusan" class="col-hidden">Jurusan</th>

                                    {{-- NON-DEFAULT: Alamat --}}
                                    <th data-column="alamat_domisili" class="col-hidden">Alamat Domisili</th>
                                    <th data-column="alamat_ktp" class="col-hidden">Alamat KTP</th>

                                    {{-- DEFAULT --}}
                                    <th data-column="satuan_kerja">Satuan Kerja</th>
                                    <th data-column="status_aktif">Aktif</th>

                                    {{-- SELALU TAMPIL --}}
                                    <th data-column="action">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="list">
                                @forelse($gtkList as $gtk)
                                    <tr>
                                        {{-- DEFAULT --}}
                                        <td data-column="nama">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <div class="avatar-xs">
                                                        <div class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                                            {{ isset($gtk['name']) ? strtoupper(substr($gtk['name'], 0, 1)) : 'N' }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-2">
                                                    <a href="{{ route('user.gtk.show', ['userId' => $userId, 'uuid' => $gtk->id]) }}" class="text-reset fw-semibold">
                                                        {{ $gtk->name }}
                                                    </a>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- NON-DEFAULT: Data Pribadi --}}
                                        <td data-column="nik" class="col-hidden">{{ $gtk->gtkProfile?->nik ?? '-' }}</td>
                                        <td data-column="no_kk" class="col-hidden">{{ $gtk->gtkProfile?->no_kk ?? '-' }}</td>
                                        <td data-column="tempat_lahir" class="col-hidden">{{ $gtk->gtkProfile?->tempat_lahir ?? '-' }}</td>
                                        <td data-column="tanggal_lahir" class="col-hidden">
                                            {{ $gtk->gtkProfile?->tanggal_lahir ? \Carbon\Carbon::parse($gtk->gtkProfile->tanggal_lahir)->format('d/m/Y') : '-' }}
                                        </td>
                                        <td data-column="jenis_kelamin" class="col-hidden">
                                            @if($gtk->gtkProfile?->jenis_kelamin == 'L')
                                                <span class="badge bg-primary-subtle text-primary">Laki-laki</span>
                                            @elseif($gtk->gtkProfile?->jenis_kelamin == 'P')
                                                <span class="badge bg-danger-subtle text-danger">Perempuan</span>
                                            @else -
                                            @endif
                                        </td>
                                        <td data-column="golongan_darah" class="col-hidden">{{ $gtk->gtkProfile?->golongan_darah ?? '-' }}</td>
                                        <td data-column="status_perkawinan" class="col-hidden">
                                            @php
                                                $statusKawin = $gtk->gtkProfile?->status_perkawinan;
                                                $statusLabel = match ($statusKawin) {
                                                    'belum_kawin' => 'Belum Kawin',
                                                    'kawin'       => 'Kawin',
                                                    'cerai_hidup' => 'Cerai Hidup',
                                                    'cerai_mati'  => 'Cerai Mati',
                                                    default       => '-'
                                                };
                                            @endphp
                                            {{ $statusLabel }}
                                        </td>
                                        <td data-column="agama" class="col-hidden">{{ ucfirst($gtk->gtkProfile?->agama ?? '-') }}</td>
                                        <td data-column="npwp" class="col-hidden">{{ $gtk->gtkProfile?->npwp ?? '-' }}</td>

                                        {{-- DEFAULT: Kontak --}}
                                        <td data-column="email">
                                            <a href="mailto:{{ $gtk->email }}" class="text-reset">{{ $gtk->email }}</a>
                                        </td>
                                        <td data-column="no_hp">
                                            @if ($gtk->gtkContact?->no_hp)
                                                <a href="tel:{{ $gtk->gtkContact->no_hp }}" class="text-reset">{{ $gtk->gtkContact->no_hp }}</a>
                                            @else -
                                            @endif
                                        </td>

                                        {{-- NON-DEFAULT: Kontak --}}
                                        <td data-column="no_whatsapp" class="col-hidden">{{ $gtk->gtkContact?->no_whatsapp ?? '-' }}</td>

                                        {{-- NON-DEFAULT: Kepegawaian --}}
                                        <td data-column="nupy" class="col-hidden">{{ $gtk->employment?->nupy ?? '-' }}</td>
                                        <td data-column="jenis_gtk" class="col-hidden">{{ $gtk->employment?->jenis_gtk ?? '-' }}</td>

                                        {{-- DEFAULT: Kepegawaian --}}
                                        <td data-column="jabatan">{{ $gtk->employment?->jabatan ?? '-' }}</td>
                                        <td data-column="status_kepegawaian">
                                            @php
                                                $statusClass = match ($gtk->employment?->status_kepegawaian) {
                                                    'GTT', 'PTT'           => 'success',
                                                    'GTY', 'PTY'           => 'secondary',
                                                    'Percobaan', 'Magang'  => 'info',
                                                    'Tetap'                => 'primary',
                                                    'KONTRAK'              => 'warning',
                                                    default                => 'light',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }}">
                                                {{ $gtk->employment?->status_kepegawaian ?? '-' }}
                                            </span>
                                        </td>
                                        <td data-column="tmt">
                                            {{ $gtk->employment?->tmt ? \Carbon\Carbon::parse($gtk->employment->tmt)->format('d/m/Y') : '-' }}
                                        </td>

                                        {{-- NON-DEFAULT: Kepegawaian --}}
                                        <td data-column="nomor_sk" class="col-hidden">{{ $gtk->employment?->nomor_sk ?? '-' }}</td>
                                        <td data-column="tanggal_sk" class="col-hidden">
                                            {{ $gtk->employment?->tanggal_sk ? \Carbon\Carbon::parse($gtk->employment->tanggal_sk)->format('d/m/Y') : '-' }}
                                        </td>

                                        {{-- NON-DEFAULT: Pendidikan --}}
                                        <td data-column="jenjang_pendidikan" class="col-hidden">
                                            @if($gtk->educations->isNotEmpty())
                                                @foreach($gtk->educations as $education)
                                                    <span class="badge bg-info-subtle text-info">{{ $education->jenjang_pendidikan }}</span>
                                                @endforeach
                                            @else -
                                            @endif
                                        </td>
                                        <td data-column="pendidikan_nama_satuan_pendidikan" class="col-hidden">
                                            @if($gtk->educations->isNotEmpty())
                                                @foreach($gtk->educations as $education){{ $education->nama_satuan_pendidikan }} @endforeach
                                            @else -
                                            @endif
                                        </td>
                                        <td data-column="pendidikan_jurusan" class="col-hidden">
                                            @if($gtk->educations->isNotEmpty())
                                                @foreach($gtk->educations as $education){{ $education->jurusan ?? '-' }} @endforeach
                                            @else -
                                            @endif
                                        </td>

                                        {{-- NON-DEFAULT: Alamat --}}
                                        <td data-column="alamat_domisili" class="col-hidden">
                                            @php $alamatDomisili = $gtk->gtkProfile?->addresses?->where('type', 'domisili')->first(); @endphp
                                            @if($alamatDomisili) {{ $alamatDomisili->jalan }}, {{ $alamatDomisili->desa }}, {{ $alamatDomisili->kecamatan }}
                                            @else - @endif
                                        </td>
                                        <td data-column="alamat_ktp" class="col-hidden">
                                            @php $alamatKTP = $gtk->gtkProfile?->addresses?->where('type', 'ktp')->first(); @endphp
                                            @if($alamatKTP) {{ $alamatKTP->jalan }}, {{ $alamatKTP->desa }}, {{ $alamatKTP->kecamatan }}
                                            @else - @endif
                                        </td>

                                        {{-- DEFAULT --}}
                                        <td data-column="satuan_kerja">
                                            @if ($gtk->gtkWorkUnits->isNotEmpty())
                                                @foreach ($gtk->gtkWorkUnits as $gtkWorkUnit)
                                                    @php $workUnitData = \App\Models\WorkUnit::find($gtkWorkUnit->work_unit_id); @endphp
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $workUnitData->name ?? 'N/A' }}</span>
                                                @endforeach
                                            @else - @endif
                                        </td>
                                        <td data-column="status_aktif">
                                            @if ($gtk->is_active)
                                                <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-fill me-1"></i>Aktif</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger"><i class="ri-close-circle-fill me-1"></i>Nonaktif</span>
                                            @endif
                                        </td>

                                        {{-- SELALU TAMPIL --}}
                                        <td data-column="action">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.gtk.show', ['userId' => $userId, 'uuid' => $gtk->id]) }}">
                                                            <i class="ri-eye-fill text-info me-2"></i> Lihat Detail
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.gtk.edit', ['userId' => $userId, 'uuid' => $gtk->id]) }}">
                                                            <i class="ri-pencil-fill text-primary me-2"></i> Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item reset-password" data-id="{{ $gtk->id }}" data-email="{{ $gtk->email }}">
                                                            <i class="ri-lock-password-line text-secondary me-2"></i> Reset Password
                                                        </button>
                                                    </li>
                                                    @if (Auth::user()->hasRole(['Personalia', 'Super Admin', 'Administrator']))
                                                        <li>
                                                            <button class="dropdown-item toggle-status" data-id="{{ $gtk->id }}" data-status="{{ $gtk->is_active }}">
                                                                <i class="ri-toggle-{{ $gtk->is_active ? 'fill' : 'line' }} text-warning me-2"></i>
                                                                {{ $gtk->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                            </button>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item text-danger delete-btn" data-id="{{ $gtk->id }}" data-name="{{ $gtk->name }}">
                                                                <i class="ri-delete-bin-line text-danger me-2"></i> Hapus
                                                            </button>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="28" class="text-center py-5">
                                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop"
                                                colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                            <h5 class="mt-2">Belum ada data GTK</h5>
                                            <p class="text-muted mb-0">Tambahkan GTK untuk memulai</p>
                                            @if (Auth::user()->hasRole(['Personalia', 'Super Admin', 'Administrator']))
                                                <a href="{{ route('user.gtk.create', ['userId' => $userId]) }}" class="btn btn-primary mt-3">
                                                    <i class="ri-add-line me-1"></i> Tambah GTK
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($gtkList instanceof \Illuminate\Pagination\LengthAwarePaginator && $gtkList->hasPages())
                        @include('shared._pagination', ['paginator' => $gtkList])
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER MODAL -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">
                        <i class="ri-filter-3-fill me-2 text-primary"></i> Filter Data GTK
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="filterForm">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="filter-group">
                                    <div class="filter-group-title"><i class="ri-user-line"></i> Data Pribadi</div>
                                    <div class="mb-2">
                                        <label class="form-label">Jenis Kelamin</label>
                                        <select class="form-control" name="jenis_kelamin">
                                            <option value="">Semua</option>
                                            <option value="L" {{ request('jenis_kelamin') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                            <option value="P" {{ request('jenis_kelamin') == 'P' ? 'selected' : '' }}>Perempuan</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Golongan Darah</label>
                                        <select class="form-control" name="golongan_darah">
                                            <option value="">Semua</option>
                                            <option value="A" {{ request('golongan_darah') == 'A' ? 'selected' : '' }}>A</option>
                                            <option value="B" {{ request('golongan_darah') == 'B' ? 'selected' : '' }}>B</option>
                                            <option value="AB" {{ request('golongan_darah') == 'AB' ? 'selected' : '' }}>AB</option>
                                            <option value="O" {{ request('golongan_darah') == 'O' ? 'selected' : '' }}>O</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Status Perkawinan</label>
                                        <select class="form-control" name="status_perkawinan">
                                            <option value="">Semua</option>
                                            <option value="belum_kawin" {{ request('status_perkawinan') == 'belum_kawin' ? 'selected' : '' }}>Belum Kawin</option>
                                            <option value="kawin" {{ request('status_perkawinan') == 'kawin' ? 'selected' : '' }}>Kawin</option>
                                            <option value="cerai_hidup" {{ request('status_perkawinan') == 'cerai_hidup' ? 'selected' : '' }}>Cerai Hidup</option>
                                            <option value="cerai_mati" {{ request('status_perkawinan') == 'cerai_mati' ? 'selected' : '' }}>Cerai Mati</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Agama</label>
                                        <select class="form-control" name="agama">
                                            <option value="">Semua</option>
                                            <option value="islam" {{ request('agama') == 'islam' ? 'selected' : '' }}>Islam</option>
                                            <option value="kristen" {{ request('agama') == 'kristen' ? 'selected' : '' }}>Kristen</option>
                                            <option value="katolik" {{ request('agama') == 'katolik' ? 'selected' : '' }}>Katolik</option>
                                            <option value="hindu" {{ request('agama') == 'hindu' ? 'selected' : '' }}>Hindu</option>
                                            <option value="buddha" {{ request('agama') == 'buddha' ? 'selected' : '' }}>Buddha</option>
                                            <option value="konghucu" {{ request('agama') == 'konghucu' ? 'selected' : '' }}>Konghucu</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">NIK</label>
                                        <input type="text" class="form-control" name="nik" placeholder="NIK" value="{{ request('nik') }}">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">No KK</label>
                                        <input type="text" class="form-control" name="no_kk" placeholder="No KK" value="{{ request('no_kk') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="filter-group">
                                    <div class="filter-group-title"><i class="ri-briefcase-line"></i> Kepegawaian</div>
                                    <div class="mb-2">
                                        <label class="form-label">Status Kepegawaian</label>
                                        <select class="form-control" name="status_kepegawaian">
                                            <option value="">Semua Status</option>
                                            <option value="Percobaan" {{ request('status_kepegawaian') == 'Percobaan' ? 'selected' : '' }}>Percobaan</option>
                                            <option value="Magang" {{ request('status_kepegawaian') == 'Magang' ? 'selected' : '' }}>Magang</option>
                                            <option value="Tetap" {{ request('status_kepegawaian') == 'Tetap' ? 'selected' : '' }}>Tetap</option>
                                            <option value="PTT" {{ request('status_kepegawaian') == 'PTT' ? 'selected' : '' }}>PTT</option>
                                            <option value="PTY" {{ request('status_kepegawaian') == 'PTY' ? 'selected' : '' }}>PTY</option>
                                            <option value="GTT" {{ request('status_kepegawaian') == 'GTT' ? 'selected' : '' }}>GTT</option>
                                            <option value="GTY" {{ request('status_kepegawaian') == 'GTY' ? 'selected' : '' }}>GTY</option>
                                            <option value="KONTRAK" {{ request('status_kepegawaian') == 'KONTRAK' ? 'selected' : '' }}>Kontrak</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Satuan Kerja</label>
                                        <select class="form-control" name="satuan_kerja">
                                            <option value="">Semua Satuan Kerja</option>
                                            @foreach ($workUnits as $workUnit)
                                                <option value="{{ $workUnit->id }}" {{ request('satuan_kerja') == $workUnit->id ? 'selected' : '' }}>
                                                    {{ $workUnit->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Jenis GTK</label>
                                        <select class="form-control" name="jenis_gtk">
                                            <option value="">Semua Jenis</option>
                                            <option value="Guru" {{ request('jenis_gtk') == 'Guru' ? 'selected' : '' }}>Guru</option>
                                            <option value="Tenaga Kependidikan" {{ request('jenis_gtk') == 'Tenaga Kependidikan' ? 'selected' : '' }}>Tenaga Kependidikan</option>
                                            <option value="Kepala Sekolah" {{ request('jenis_gtk') == 'Kepala Sekolah' ? 'selected' : '' }}>Kepala Sekolah</option>
                                            <option value="Staf" {{ request('jenis_gtk') == 'Staf' ? 'selected' : '' }}>Staf</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Jabatan</label>
                                        <input type="text" class="form-control" name="jabatan" placeholder="Cari Jabatan" value="{{ request('jabatan') }}">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">NUPY</label>
                                        <input type="text" class="form-control" name="nupy" placeholder="NUPY" value="{{ request('nupy') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="filter-group">
                                    <div class="filter-group-title"><i class="ri-graduation-cap-line"></i> Pendidikan</div>
                                    <div class="mb-2">
                                        <label class="form-label">Jenjang Pendidikan</label>
                                        <select class="form-control" name="jenjang_pendidikan">
                                            <option value="">Semua</option>
                                            <option value="SD" {{ request('jenjang_pendidikan') == 'SD' ? 'selected' : '' }}>SD</option>
                                            <option value="SMP" {{ request('jenjang_pendidikan') == 'SMP' ? 'selected' : '' }}>SMP</option>
                                            <option value="SMA" {{ request('jenjang_pendidikan') == 'SMA' ? 'selected' : '' }}>SMA</option>
                                            <option value="D1" {{ request('jenjang_pendidikan') == 'D1' ? 'selected' : '' }}>D1</option>
                                            <option value="D2" {{ request('jenjang_pendidikan') == 'D2' ? 'selected' : '' }}>D2</option>
                                            <option value="D3" {{ request('jenjang_pendidikan') == 'D3' ? 'selected' : '' }}>D3</option>
                                            <option value="D4" {{ request('jenjang_pendidikan') == 'D4' ? 'selected' : '' }}>D4</option>
                                            <option value="S1" {{ request('jenjang_pendidikan') == 'S1' ? 'selected' : '' }}>S1</option>
                                            <option value="S2" {{ request('jenjang_pendidikan') == 'S2' ? 'selected' : '' }}>S2</option>
                                            <option value="S3" {{ request('jenjang_pendidikan') == 'S3' ? 'selected' : '' }}>S3</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Nama Sekolah</label>
                                        <input type="text" class="form-control" name="nama_satuan_pendidikan" placeholder="Nama Sekolah" value="{{ request('nama_satuan_pendidikan') }}">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Jurusan</label>
                                        <input type="text" class="form-control" name="jurusan" placeholder="Jurusan" value="{{ request('jurusan') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="filter-group">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="form-label">Status Aktif</label>
                                            <select class="form-control" name="status_aktif">
                                                <option value="">Semua</option>
                                                <option value="1" {{ request('status_aktif') == '1' ? 'selected' : '' }}>Aktif</option>
                                                <option value="0" {{ request('status_aktif') == '0' ? 'selected' : '' }}>Nonaktif</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">TMT dari</label>
                                            <input type="date" class="form-control" name="tmt_from" value="{{ request('tmt_from') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">TMT sampai</label>
                                            <input type="date" class="form-control" name="tmt_to" value="{{ request('tmt_to') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" placeholder="Email" value="{{ request('email') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" onclick="resetFilters()">
                        <i class="ri-refresh-line me-1"></i> Reset
                    </button>
                    <button type="button" class="btn btn-primary" onclick="applyFilters()">
                        <i class="ri-filter-3-fill me-1"></i> Terapkan Filter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- DELETE MODAL -->
    <div class="modal fade zoomIn" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mt-2 text-center">
                        <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                            colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                            <h4>Apakah Anda yakin?</h4>
                            <p class="text-muted mx-4 mb-0">Anda akan menghapus data GTK <strong id="deleteGtkName"></strong> secara permanen</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                        <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn w-sm btn-danger" id="delete-confirm-btn">Ya, Hapus!</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RESET PASSWORD MODAL -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <lord-icon src="https://cdn.lordicon.com/hrqwmuin.json" trigger="loop"
                            colors="primary:#0a5f9e,secondary:#00b09b" style="width:80px;height:80px"></lord-icon>
                        <h5 class="mt-3">Reset Password GTK</h5>
                        <p class="text-muted mt-2">Password akan direset ke NUPY + @12345</p>
                        <div class="alert alert-info mt-3">
                            <strong>GTK:</strong> <span id="resetGtkName"></span><br>
                            <strong>Email:</strong> <span id="resetGtkEmail"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="confirmResetPasswordBtn">
                        <i class="ri-lock-password-line me-1"></i> Reset Password
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.lordicon.com/lordicon.js"></script>
    <script>
    // ============================================================
    // PENDEKATAN BARU: Kolom non-default sudah disembunyikan
    // lewat class "col-hidden" di HTML. JavaScript hanya
    // mengelola toggle saat user klik checkbox di dropdown Kolom.
    // Tidak ada lagi ketergantungan pada localStorage untuk
    // menentukan tampilan awal — sudah pasti 8 kolom default.
    // ============================================================

    let currentDeleteUuid  = null;
    let currentResetUserId = null;
    let activeFilters      = {};

    // Kolom default yang selalu tampil (tidak ada di dropdown, atau sudah checked)
    // Ini hanya untuk referensi fungsi reset.
    const DEFAULT_COLUMNS = ['nama', 'email', 'no_hp', 'jabatan', 'status_kepegawaian', 'tmt', 'satuan_kerja', 'status_aktif'];

    // ── INIT ──────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        // Bersihkan localStorage lama agar tidak ada konflik
        localStorage.removeItem('gtk_column_visibility');
        localStorage.removeItem('gtk_column_visibility_version');

        // Sync checkbox dengan state visual kolom yang sudah diset di HTML
        syncCheckboxWithDOM();

        loadActiveFiltersFromUrl();
        setupEventListeners();
    });

    // Baca state kolom dari DOM (class col-hidden) lalu set checkbox sesuai itu
    function syncCheckboxWithDOM() {
        document.querySelectorAll('.column-toggle').forEach(cb => {
            const column  = cb.value;
            // Cek apakah th kolom ini visible atau hidden
            const th = document.querySelector(`th[data-column="${column}"]`);
            if (th) {
                cb.checked = !th.classList.contains('col-hidden');
            }
        });
    }

    // ── COLUMN TOGGLE (real-time, langsung saat checkbox diklik) ──
    function toggleColumnVisibility(checkbox) {
        const column    = checkbox.value;
        const isChecked = checkbox.checked;

        document.querySelectorAll(`th[data-column="${column}"], td[data-column="${column}"]`).forEach(el => {
            if (isChecked) {
                el.classList.remove('col-hidden');
            } else {
                el.classList.add('col-hidden');
            }
        });
    }

    // Reset ke 8 kolom default
    function resetColumnVisibility() {
        // Sembunyikan semua kolom yang bisa di-toggle
        document.querySelectorAll('.column-toggle').forEach(cb => {
            const column    = cb.value;
            const isDefault = DEFAULT_COLUMNS.includes(column);
            cb.checked      = isDefault;

            document.querySelectorAll(`th[data-column="${column}"], td[data-column="${column}"]`).forEach(el => {
                if (isDefault) {
                    el.classList.remove('col-hidden');
                } else {
                    el.classList.add('col-hidden');
                }
            });
        });

        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: 'Tampilan kolom direset ke 8 kolom default',
            timer: 1500,
            showConfirmButton: false
        });
    }

    // ── SEARCH ────────────────────────────────────────────────────
    @php
        $searchBaseUrl = isset($satuanKerja)
            ? route('user.gtk.by-work-unit', ['userId' => $userId, 'satuanKerja' => $satuanKerja->id])
            : route('user.gtk.index', ['userId' => $userId]);
    @endphp
    function performSearch() {
        const searchValue = document.getElementById('globalSearch').value;
        let params = new URLSearchParams(window.location.search);
        if (searchValue) { params.set('search', searchValue); } else { params.delete('search'); }
        params.delete('page');
        window.location.href = '{!! $searchBaseUrl !!}' + '?' + params.toString();
    }

    // ── FILTER ────────────────────────────────────────────────────
    function loadActiveFiltersFromUrl() {
        const urlParams  = new URLSearchParams(window.location.search);
        let filterCount  = 0;
        urlParams.forEach((value, key) => {
            if (value && key !== 'page' && key !== 'sort' && key !== 'direction') {
                activeFilters[key] = value;
                filterCount++;
            }
        });
        const countEl = document.getElementById('activeFilterCount');
        const rowEl   = document.getElementById('activeFiltersRow');
        if (filterCount > 0) {
            if (countEl) { countEl.style.display = 'inline'; countEl.innerText = filterCount; }
            if (rowEl)   { rowEl.style.display = 'block'; }
            renderActiveFilterBadges();
        } else {
            if (countEl) countEl.style.display = 'none';
            if (rowEl)   rowEl.style.display = 'none';
        }
    }

    function applyFilters() {
        const form     = document.getElementById('filterForm');
        const formData = new FormData(form);
        let params     = new URLSearchParams();
        for (let [key, value] of formData) {
            if (value && value.trim() !== '') { params.append(key, value.trim()); }
        }
        const searchValue = document.getElementById('globalSearch').value;
        if (searchValue) params.set('search', searchValue);
        window.location.href = '{!! $searchBaseUrl !!}' + '?' + params.toString();
    }

    function resetFilters() {
        document.getElementById('filterForm').reset();
        document.getElementById('globalSearch').value = '';
        window.location.href = '{!! $searchBaseUrl !!}';
    }

    function quickFilter(field, value) {
        let params = new URLSearchParams(window.location.search);
        if (params.get(field) === value) { params.delete(field); } else { params.set(field, value); }
        params.delete('page');
        window.location.href = '{!! $searchBaseUrl !!}' + '?' + params.toString();
    }

    function clearAllFilters() {
        window.location.href = '{!! $searchBaseUrl !!}';
    }

    function removeFilter(key) {
        let params = new URLSearchParams(window.location.search);
        params.delete(key);
        window.location.href = '{!! $searchBaseUrl !!}' + '?' + params.toString();
    }

    function renderActiveFilterBadges() {
        const container = document.getElementById('activeFilterBadges');
        if (!container) return;
        container.innerHTML = '';
        Object.keys(activeFilters).forEach(key => {
            if (!activeFilters[key] || key === 'page' || key === 'sort' || key === 'direction') return;
            let displayValue = activeFilters[key];
            if (key === 'status_aktif')     displayValue = displayValue === '1' ? 'Aktif' : 'Nonaktif';
            if (key === 'jenis_kelamin')    displayValue = displayValue === 'L' ? 'Laki-laki' : 'Perempuan';
            if (key === 'status_perkawinan') {
                const map = { belum_kawin: 'Belum Kawin', kawin: 'Kawin', cerai_hidup: 'Cerai Hidup', cerai_mati: 'Cerai Mati' };
                displayValue = map[displayValue] || displayValue;
            }
            const badge = document.createElement('span');
            badge.className = 'filter-badge active';
            badge.innerHTML = `<i class="${getFilterIcon(key)}"></i>${getFilterLabel(key)}: ${displayValue}
                <span class="remove-filter" onclick="removeFilter('${key}')"><i class="ri-close-line"></i></span>`;
            container.appendChild(badge);
        });
    }

    function getFilterIcon(key) {
        const icons = { search:'ri-search-line', status_kepegawaian:'ri-id-card-line', satuan_kerja:'ri-building-line',
            status_aktif:'ri-checkbox-circle-line', jenis_kelamin:'ri-user-line', jenis_gtk:'ri-briefcase-line',
            jabatan:'ri-briefcase-4-line', nik:'ri-fingerprint-line', nupy:'ri-fingerprint-line',
            no_kk:'ri-file-copy-line', email:'ri-mail-line', tmt_from:'ri-calendar-line', tmt_to:'ri-calendar-line',
            golongan_darah:'ri-droplet-line', status_perkawinan:'ri-heart-line', agama:'ri-church-line',
            jenjang_pendidikan:'ri-graduation-cap-line', nama_satuan_pendidikan:'ri-school-line', jurusan:'ri-book-open-line' };
        return icons[key] || 'ri-filter-line';
    }

    function getFilterLabel(key) {
        const labels = { search:'Pencarian', status_kepegawaian:'Status', satuan_kerja:'Satker',
            status_aktif:'Aktif', jenis_kelamin:'JK', jenis_gtk:'Jenis GTK', jabatan:'Jabatan',
            nik:'NIK', nupy:'NUPY', no_kk:'No KK', email:'Email', tmt_from:'TMT Dari', tmt_to:'TMT Sampai',
            golongan_darah:'Gol Darah', status_perkawinan:'Status Kawin', agama:'Agama',
            jenjang_pendidikan:'Jenjang', nama_satuan_pendidikan:'Sekolah', jurusan:'Jurusan' };
        return labels[key] || key;
    }

    // ── EXPORT ────────────────────────────────────────────────────
    function exportData() {
        const format = document.getElementById('exportFormat').value;
        const scope  = document.querySelector('input[name="exportScope"]:checked').value;
        let params   = new URLSearchParams();
        params.append('format', format);
        params.append('scope', scope);
        if (scope === 'filtered') {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.forEach((value, key) => {
                if (key !== 'page' && key !== 'sort' && key !== 'direction') params.append(key, value);
            });
        }
        window.location.href = '{!! route('user.gtk.export', ['userId' => $userId]) !!}' + '?' + params.toString();
    }

    // ── DELETE ────────────────────────────────────────────────────
    function setupDeleteListeners() {
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                currentDeleteUuid = this.getAttribute('data-id');
                const userName    = this.getAttribute('data-name') || this.closest('tr').querySelector('.fw-semibold').innerText;
                document.getElementById('deleteGtkName').innerText = userName;
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
            });
        });
        document.getElementById('delete-confirm-btn').addEventListener('click', async function () {
            if (!currentDeleteUuid) return;
            try {
                const response = await fetch(`/personalia/gtk/${currentDeleteUuid}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: data.message, timer: 1500, showConfirmButton: false })
                        .then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Terjadi kesalahan', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
            } finally {
                bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                currentDeleteUuid = null;
            }
        });
    }

    // ── RESET PASSWORD ────────────────────────────────────────────
    function setupResetPasswordListeners() {
        document.querySelectorAll('.reset-password').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const userId   = this.getAttribute('data-id');
                const userName = this.closest('tr').querySelector('.fw-semibold').innerText;
                const email    = this.getAttribute('data-email');
                document.getElementById('resetGtkName').innerText  = userName;
                document.getElementById('resetGtkEmail').innerText = email;
                currentResetUserId = userId;
                new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
            });
        });
        document.getElementById('confirmResetPasswordBtn').addEventListener('click', async function () {
            if (!currentResetUserId) return;
            try {
                const response = await fetch(`/personalia/gtk/${currentResetUserId}/reset-password`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    Swal.fire({
                        icon: 'success', title: 'Berhasil!',
                        html: `Password berhasil direset ke: <strong>${data.password}</strong>`,
                        confirmButtonText: 'Salin Password', showCancelButton: true, cancelButtonText: 'Tutup'
                    }).then(result => {
                        if (result.isConfirmed) {
                            navigator.clipboard.writeText(data.password);
                            Swal.fire('Tersalin!', 'Password telah disalin ke clipboard', 'success');
                        }
                    });
                } else {
                    Swal.fire('Error', data.message || 'Terjadi kesalahan', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
            } finally {
                bootstrap.Modal.getInstance(document.getElementById('resetPasswordModal')).hide();
                currentResetUserId = null;
            }
        });
    }

    // ── TOGGLE STATUS ─────────────────────────────────────────────
    async function handleToggleStatus(e) {
        e.preventDefault();
        const button        = this;
        const id            = button.getAttribute('data-id');
        const currentStatus = button.getAttribute('data-status') === '1' ? 'Aktif' : 'Nonaktif';
        const newStatus     = currentStatus === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan';
        const result = await Swal.fire({
            title: 'Konfirmasi', icon: 'question',
            text: `Apakah Anda yakin ingin ${newStatus.toLowerCase()} GTK ini?`,
            showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, ' + newStatus, cancelButtonText: 'Batal'
        });
        if (result.isConfirmed) {
            try {
                const response = await fetch(`/personalia/gtk/${id}/toggle-status`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: data.message, timer: 1500, showConfirmButton: false })
                        .then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Terjadi kesalahan', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
            }
        }
    }

    // ── SETUP EVENT LISTENERS ─────────────────────────────────────
    function setupEventListeners() {
        document.querySelectorAll('.toggle-status').forEach(btn => btn.addEventListener('click', handleToggleStatus));
        setupDeleteListeners();
        setupResetPasswordListeners();

        const searchInput = document.getElementById('globalSearch');
        if (searchInput) {
            searchInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); performSearch(); }
            });
        }

        // Checkbox kolom: toggle real-time langsung tanpa tombol Terapkan
        document.querySelectorAll('.column-toggle').forEach(cb => {
            cb.addEventListener('change', function () { toggleColumnVisibility(this); });
        });
    }
    </script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection