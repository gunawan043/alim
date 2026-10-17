@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Detail GTK Recruitment</h4>
        <a href="{{ route('user.recruitment.index', request('userId')) }}" class="btn btn-secondary">Kembali</a>
    </div>

    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Work Unit</dt>
                <dd class="col-sm-9">{{ $recruitment->workUnit?->name ?? '—' }}</dd>

                <dt class="col-sm-3">Jabatan</dt>
                <dd class="col-sm-9">{{ $recruitment->jabatan ?? '—' }}</dd>

                <dt class="col-sm-3">Kebutuhan</dt>
                <dd class="col-sm-9">{{ $recruitment->kebutuhan ?? '—' }} orang</dd>

                <dt class="col-sm-3">Kualifikasi</dt>
                <dd class="col-sm-9">{!! nl2br(e($recruitment->kualifikasi ?? '')) !!}</dd>

                <dt class="col-sm-3">Tanggal Dibutuhkan</dt>
                <dd class="col-sm-9">{{ optional($recruitment->tanggal_dibutuhkan)->format('d/m/Y') ?? '—' }}</dd>

                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9"><span class="badge bg-info">{{ strtoupper($recruitment->status ?? '—') }}</span></dd>

                <dt class="col-sm-3">Dibuat oleh</dt>
                <dd class="col-sm-9">{{ $recruitment->createdBy?->name ?? '—' }}</dd>

                <dt class="col-sm-3">Dibuat pada</dt>
                <dd class="col-sm-9">{{ optional($recruitment->created_at)->format('d/m/Y H:i') ?? '—' }}</dd>
            </dl>

            <div class="mt-3">
                <a href="{{ route('user.recruitment.edit', [request('userId'), $recruitment->id]) }}" class="btn btn-warning">Edit</a>
                <form action="{{ route('user.recruitment.destroy', [request('userId'), $recruitment->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection