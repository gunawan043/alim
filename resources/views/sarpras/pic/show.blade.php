@extends('layouts.master')
@section('title') Verifikasi Laporan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.pic.index') }}">PIC Approval</a> @endslot
    @slot('title') {{ $repair->request_number }} @endslot
@endcomponent

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-file-list-line me-1"></i> Detail Laporan</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">No. Laporan</dt>
                    <dd class="col-sm-8"><code>{{ $repair->request_number }}</code></dd>

                    <dt class="col-sm-4">Aset</dt>
                    <dd class="col-sm-8">
                        <strong>{{ $repair->asset?->asset_name }}</strong>
                        <div class="small text-muted"><code>{{ $repair->asset?->asset_code }}</code></div>
                    </dd>

                    <dt class="col-sm-4">Pelapor</dt>
                    <dd class="col-sm-8">{{ $repair->reportedBy?->name ?? '-' }}</dd>

                    <dt class="col-sm-4">Prioritas</dt>
                    <dd class="col-sm-8"><span class="badge bg-info text-dark">{{ ucfirst($repair->priority) }}</span></dd>

                    <dt class="col-sm-4">Judul</dt>
                    <dd class="col-sm-8">{{ $repair->title }}</dd>

                    <dt class="col-sm-4">Deskripsi</dt>
                    <dd class="col-sm-8">{{ $repair->description }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="ri-shield-check-line me-1"></i> Keputusan PIC</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('sarpras.pic.verify', $repair->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Rekomendasi <span class="text-danger">*</span></label>
                        <select name="recommendation" id="recommendation" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <option value="approved">Setujui &amp; buat Work Order</option>
                            <option value="rejected">Tolak laporan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="verification_notes" class="form-label">Catatan Verifikasi</label>
                        <textarea name="verification_notes" id="verification_notes" rows="4"
                            class="form-control" placeholder="Wajib diisi jika menolak..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ri-save-line me-1"></i> Simpan Keputusan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
