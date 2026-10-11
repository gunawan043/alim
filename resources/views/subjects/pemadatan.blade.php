@extends('layouts.master')
@section('title', 'Tambah Mapel Pemadatan')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.subjects.index', ['userId' => $userId]) }}">Mata Pelajaran</a> @endslot
        @slot('title') Mapel Pemadatan @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger small">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="alert alert-info small">
        <i class="ri-information-line me-1"></i>
        <strong>Mapel Pemadatan</strong> adalah pelajaran tambahan yang diatur sendiri oleh satuan pendidikan
        (mis. IPS). Alokasi jam untuk mapel ini <strong>bebas Anda atur</strong>; berbeda dengan mapel pondok yang jamnya terkunci.
    </div>

    <form method="POST" action="{{ route('user.subjects.pemadatan.store', ['userId' => $userId]) }}">
        @csrf
        <div class="card mb-3">
            <div class="card-header border-bottom-dashed"><h6 class="mb-0">Identitas Mapel</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Kode <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" value="{{ old('code') }}" required maxlength="20" placeholder="mis. IPS">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Nama Mapel <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required maxlength="100" placeholder="mis. Ilmu Pengetahuan Sosial">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Keterangan (opsional)</label>
                        <input type="text" name="description" class="form-control" value="{{ old('description') }}" maxlength="500">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header border-bottom-dashed">
                <h6 class="mb-0">Alokasi Jam per Pekan</h6>
                <p class="text-muted mb-0 small">Kosongkan/0 bila mapel tidak diajarkan di tingkat tersebut.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Tingkat</th><th style="width:160px">Jam per Pekan</th></tr></thead>
                        <tbody>
                            @forelse($grades as $grade)
                                <tr>
                                    <td>{{ $grade->name }} <span class="text-muted small">(Fase {{ $grade->fase }})</span></td>
                                    <td>
                                        <input type="number" min="0" max="40" class="form-control form-control-sm"
                                               name="allocations[{{ $grade->id }}]" value="{{ old('allocations.'.$grade->id) }}">
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-3">Belum ada tingkat pada satuan pendidikan ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan Mapel Pemadatan</button>
            <a href="{{ route('user.subjects.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
        </div>
    </form>
@endsection
