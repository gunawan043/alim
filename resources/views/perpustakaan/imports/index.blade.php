@extends('layouts.master')
@section('title', $meta['title'])
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('perpustakaan._styles')
    @component('components.breadcrumb') @slot('li_1') Perpustakaan @endslot @slot('li_2') Import Data @endslot @slot('title') {{ $meta['title'] }} @endslot @endcomponent
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if(session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="row g-2 mb-3 library-stats">
            @foreach([['Baris Dibaca', $result['total'], 'ri-file-list-3-line', 'primary', session('import_unit')], ['Berhasil', $result['success'], 'ri-checkbox-circle-line', 'success', 'Data masuk ke database'], ['Gagal', count($result['failed']), 'ri-error-warning-line', 'danger', 'Baris dilewati, data lain tetap diproses']] as [$label, $value, $icon, $color, $hint])
                @include('perpustakaan._stat-card', compact('label', 'value', 'icon', 'color', 'hint'))
            @endforeach
        </div>
        @if(count($result['failed']))
            <div class="card mb-3 border-danger-subtle"><div class="card-header bg-danger-subtle border-bottom-dashed"><h5 class="card-title mb-0 text-danger"><i class="ri-error-warning-line me-1"></i>Baris yang Gagal</h5></div><div class="table-container"><table class="table table-sm align-middle mb-0 table-freeze"><thead class="table-light"><tr><th style="width:90px">Baris</th><th>Alasan</th></tr></thead><tbody>@foreach($result['failed'] as $failure)<tr><td>#{{ $failure['row'] }}</td><td>{{ $failure['reason'] }}</td></tr>@endforeach</tbody></table></div></div>
        @endif
    @endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="{{ $meta['icon'] }} text-primary me-1"></i>{{ $meta['title'] }}</h5><p class="text-muted mb-0 small">{{ $meta['description'] }}</p></div>
            <a href="{{ route($meta['template'], ['userId' => $userId]) }}" class="btn btn-success btn-sm"><i class="ri-download-2-line me-1"></i>Unduh Template</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route($meta['action'], ['userId' => $userId]) }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Unit Perpustakaan</label>
                        <select name="library_unit_id" class="form-select" required>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" @selected(old('library_unit_id', request('unit_id')) === $unit->id)>{{ $unit->name }} ({{ $unit->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">File Excel / CSV</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,.txt" required>
                        <div class="form-text">Maksimal 10 MB. Gunakan template agar nama kolom sesuai.</div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-info py-2 mb-0 small"><i class="ri-information-line me-1"></i>Baris yang gagal tidak menghentikan baris lain. Periksa alasan pada tabel hasil setelah import.</div>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ url()->previous() }}" class="btn btn-light">Kembali</a>
                        <button class="btn btn-primary"><i class="ri-upload-cloud-2-line me-1"></i>Mulai Import</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-table-line text-primary me-1"></i>Panduan Kolom</h5></div>
        <div class="table-container"><table class="table table-sm align-middle mb-0 table-freeze"><thead class="table-light"><tr><th>Kolom</th><th>Keterangan</th><th>Contoh</th></tr></thead><tbody>
            @foreach($meta['columns'] as $column)
                <tr><td><code>{{ $column[0] }}</code></td><td>{{ $column[1] }}</td><td class="text-muted small">{{ $column[2] }}</td></tr>
            @endforeach
        </tbody></table></div>
    </div>
@endsection
