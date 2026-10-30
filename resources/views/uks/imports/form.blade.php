@extends('layouts.master')
@section('title', 'Import '.$config['label'])
@section('css')
    @include('uks._ui-styles')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') Import Data @endslot
        @slot('title') {{ $config['label'] }} @endslot
    @endcomponent

    <div class="row g-3 mb-3">
        @foreach([
            'medicine' => ['Inventaris Obat', 'ri-medicine-bottle-line'],
            'metrics' => ['Antropometri Santri', 'ri-ruler-line'],
            'gtk-health' => ['Kesehatan GTK', 'ri-heart-pulse-line'],
        ] as $key => $item)
            <div class="col-md-4">
                <a href="{{ route('user.uks.imports.form', ['userId' => $userId, 'dataset' => $key]) }}"
                   class="card h-100 text-decoration-none {{ $dataset === $key ? 'border-primary' : '' }}">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-sm rounded bg-primary-subtle d-flex align-items-center justify-content-center">
                            <i class="{{ $item[1] }} text-primary"></i>
                        </span>
                        <div><strong>{{ $item[0] }}</strong><div class="text-muted small">Import data massal</div></div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('import_errors'))
        <div class="alert alert-warning alert-dismissible fade show">
            <strong>{{ count(session('import_errors')) }} baris tidak diimpor:</strong>
            <ul class="mb-0 mt-2 small">
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="ri-upload-cloud-2-line me-1"></i>Upload File</h5></div>
                <div class="card-body">
                    <p class="text-muted small">{{ $config['description'] }}</p>
                    <a href="{{ route('user.uks.imports.template', ['userId' => $userId, 'dataset' => $dataset]) }}"
                       class="btn btn-sm btn-soft-success mb-3">
                        <i class="ri-file-excel-2-line me-1"></i>Download Template CSV
                    </a>

                    <form method="POST" action="{{ route('user.uks.imports.store', ['userId' => $userId, 'dataset' => $dataset]) }}"
                          enctype="multipart/form-data">
                        @csrf
                        <label class="form-label">File Excel/CSV</label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror"
                               accept=".xlsx,.xls,.csv" required>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Maksimal 10 MB. Gunakan template agar nama kolom sesuai.</div>
                        <button class="btn btn-primary w-100 mt-3">
                            <i class="ri-upload-2-line me-1"></i>Mulai Import
                        </button>
                    </form>

                    <div class="alert alert-info small mt-3 mb-0">
                        Data yang sudah ada akan diperbarui berdasarkan kunci unik:
                        <strong>{{ $dataset === 'medicine' ? 'kode obat / nama + satuan' : ($dataset === 'metrics' ? 'santri + tanggal + sesi' : 'GTK + tanggal pemeriksaan') }}</strong>.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="ri-table-line me-1"></i>Format Kolom</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light"><tr><th>Kolom</th><th>Contoh</th></tr></thead>
                            <tbody>
                                @foreach($config['headers'] as $i => $header)
                                    <tr>
                                        <td><code>{{ $header }}</code></td>
                                        <td class="text-muted small">{{ $config['examples'][$i] ?? '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
