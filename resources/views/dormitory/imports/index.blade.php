@extends('layouts.master')
@section('title', 'Import Data Asrama')
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('title') Import Data @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="ri-alert-line me-1"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('import_errors') && count(session('import_errors')))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="ri-alert-line me-1"></i> <strong>Catatan baris yang dilewati:</strong>
            <ul class="mb-0 mt-2 small">
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- ============================================================
         STATS CARDS
    ============================================================ --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded">
                                <i class="ri-building-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Gedung</p>
                            <h3 class="stat-value">{{ number_format($wingCount) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Gedung terdaftar pada asrama</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <i class="ri-door-open-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Kamar</p>
                            <h3 class="stat-value">{{ number_format($roomCount) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-home-4-line me-1"></i>Kamar terdaftar pada asrama</p>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-90">
                <div class="card-body py-3 d-flex align-items-center gap-2">
                    <i class="ri-information-line text-info fs-20"></i>
                    <span class="small text-muted">Data santri tidak diimpor dari sini. Data santri tetap berasal dari Satuan Pendidikan. Import ini untuk kebutuhan operasional asrama: gedung dan kamar.</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- ============================================================
             KOLOM KIRI: FORM IMPORT
        ============================================================ --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-upload-cloud-2-line me-1 text-primary"></i>Import Kamar &amp; Gedung</h5>
                    <p class="text-muted mb-0 small">Gunakan template agar kode gedung, kode kamar, dan kapasitas valid.</p>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <a href="{{ route('user.asrama.imports.rooms.template', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                           class="btn btn-outline-success btn-sm">
                            <i class="ri-file-excel-2-line me-1"></i> Unduh Template XLSX
                        </a>
                        <a href="{{ route('user.asrama.rooms.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                           class="btn btn-light btn-sm">
                            <i class="ri-door-open-line me-1"></i> Lihat Kamar
                        </a>
                    </div>

                    <form method="POST"
                          action="{{ route('user.asrama.imports.rooms.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                          enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="roomFile" class="form-label fs-12 text-muted mb-1">
                                File Import <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="file" id="roomFile"
                                   class="form-control @error('file') is-invalid @enderror"
                                   accept=".xlsx,.xls,.csv" required>
                            <div class="form-text">Format XLSX, XLS, atau CSV. Maksimal 5 MB.</div>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ri-upload-2-line me-1"></i> Proses Import
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============================================================
             KOLOM KANAN: PANDUAN KOLOM
        ============================================================ --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-list-check me-1 text-primary"></i>Kolom Template</h5>
                    <p class="text-muted mb-0 small">Pastikan header kolom sesuai template.</p>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Kolom</th>
                                    <th class="text-center">Wajib</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code class="text-dark">kode_gedung</code></td>
                                    <td class="text-center"><span class="badge bg-danger-subtle text-danger">Ya*</span></td>
                                    <td>Kode gedung atau nama gedung</td>
                                </tr>
                                <tr>
                                    <td><code class="text-dark">nama_gedung</code></td>
                                    <td class="text-center"><span class="badge bg-secondary-subtle text-secondary">Opsional</span></td>
                                    <td>Nama gedung</td>
                                </tr>
                                <tr>
                                    <td><code class="text-dark">kode_kamar</code></td>
                                    <td class="text-center"><span class="badge bg-danger-subtle text-danger">Ya</span></td>
                                    <td>Unik per asrama</td>
                                </tr>
                                <tr>
                                    <td><code class="text-dark">nama_kamar</code></td>
                                    <td class="text-center"><span class="badge bg-danger-subtle text-danger">Ya</span></td>
                                    <td>Nama tampil kamar</td>
                                </tr>
                                <tr>
                                    <td><code class="text-dark">kapasitas</code></td>
                                    <td class="text-center"><span class="badge bg-danger-subtle text-danger">Ya</span></td>
                                    <td>Jumlah ranjang</td>
                                </tr>
                                <tr>
                                    <td><code class="text-dark">lantai</code></td>
                                    <td class="text-center"><span class="badge bg-secondary-subtle text-secondary">Opsional</span></td>
                                    <td>Angka lantai</td>
                                </tr>
                                <tr>
                                    <td><code class="text-dark">tipe_kamar</code></td>
                                    <td class="text-center"><span class="badge bg-secondary-subtle text-secondary">Opsional</span></td>
                                    <td>Reguler / Tahfidz / dll</td>
                                </tr>
                                <tr>
                                    <td><code class="text-dark">gender</code></td>
                                    <td class="text-center"><span class="badge bg-secondary-subtle text-secondary">Opsional</span></td>
                                    <td>putra / putri</td>
                                </tr>
                                <tr>
                                    <td><code class="text-dark">aktif</code></td>
                                    <td class="text-center"><span class="badge bg-secondary-subtle text-secondary">Opsional</span></td>
                                    <td>ya / tidak (default ya)</td>
                                </tr>
                                <tr>
                                    <td><code class="text-dark">catatan</code></td>
                                    <td class="text-center"><span class="badge bg-secondary-subtle text-secondary">Opsional</span></td>
                                    <td>Catatan fasilitas</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mt-3 mb-0">
                        *Isi salah satu: <code>kode gedung</code> atau <code>nama gedung</code>.
                        Gedung baru akan dibuat otomatis jika belum ada.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection