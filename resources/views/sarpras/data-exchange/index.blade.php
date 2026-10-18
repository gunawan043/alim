@extends('layouts.master')
@section('title') Import & Export Data Sarpras @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Import &amp; Export Data
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="ri-check-double-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show"><i class="ri-error-warning-line me-1"></i>{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('import_errors') && count(session('import_errors')))
        <div class="alert alert-warning alert-dismissible fade show">
            <div class="fw-semibold mb-1"><i class="ri-alert-line me-1"></i>Sebagian baris tidak diimpor:</div>
            <ul class="mb-0 small">@foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="card-title mb-1"><i class="ri-file-excel-2-line me-2 text-success"></i>Pusat Import &amp; Export</h5>
                <p class="text-muted small mb-0">Unduh template, ekspor data, lalu impor kembali. Data dengan kode yang sama akan diperbarui; kode baru akan dibuat.</p>
            </div>
            <span class="badge bg-success-subtle text-success"><i class="ri-shield-check-line me-1"></i>Format Excel / CSV</span>
        </div>
    </div>

    <div class="row g-3">
        @foreach($datasets as $key => $dataset)
            <div class="col-xl-4 col-md-6">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <span class="avatar-sm flex-shrink-0"><span class="avatar-title rounded bg-{{ $dataset['color'] }}-subtle text-{{ $dataset['color'] }} fs-4"><i class="{{ $dataset['icon'] }}"></i></span></span>
                            <div><h5 class="card-title mb-1">{{ $dataset['label'] }}</h5><p class="text-muted small mb-0">{{ $dataset['description'] }}</p></div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <a href="{{ route('sarpras.data-exchange.template', ['dataset' => $key]) }}" class="btn btn-sm btn-outline-secondary"><i class="ri-file-download-line me-1"></i>Template</a>
                            <a href="{{ route('sarpras.data-exchange.export', ['dataset' => $key]) }}" class="btn btn-sm btn-success"><i class="ri-file-excel-2-line me-1"></i>Export</a>
                        </div>

                        <div class="mt-auto border-top pt-3">
                            @if($dataset['can_import'])
                                <form action="{{ route('sarpras.data-exchange.import', ['dataset' => $key]) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <label class="form-label small fw-semibold">Import {{ $dataset['label'] }}</label>
                                    <input type="file" name="file" class="form-control form-control-sm mb-2" accept=".xlsx,.xls,.csv,.txt" required>
                                    <button class="btn btn-sm btn-primary w-100"><i class="ri-upload-2-line me-1"></i>Import Sekarang</button>
                                </form>
                            @elseif(!empty($dataset['import_route']))
                                <a href="{{ route($dataset['import_route']) }}" class="btn btn-sm btn-primary w-100"><i class="ri-upload-2-line me-1"></i>Import {{ $dataset['label'] }} (Lengkap)</a>
                                <p class="text-muted small mb-0 mt-2">Import aset memakai validasi ruang, kategori, dan mode audit yang lebih lengkap.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mt-3 border-info">
        <div class="card-body small text-muted">
            <div class="fw-semibold text-info mb-2"><i class="ri-information-line me-1"></i>Catatan</div>
            <ul class="mb-0 ps-3">
                <li class="mb-1">Selalu unduh template terbaru sebelum import agar nama kolom sesuai.</li>
                <li class="mb-1">Kolom kode dipakai sebagai kunci pembaruan. Kosongkan kode untuk membuat data baru.</li>
                <li class="mb-1">Gedung dan ruang mengikuti satuan pendidikan pada konteks login; pengguna pusat memakai seluruh data.</li>
                <li>Import aset tetap memakai halaman import aset lengkap dengan mode dry-run/audit.</li>
            </ul>
        </div>
    </div>
@endsection
