@extends('layouts.master')

@section('title') Tambah Kegiatan Asrama @endsection

@section('content')
    @php $userId = request()->route('userId') ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.kegiatan.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Kegiatan</a> @endslot
        @slot('title') Tambah Kegiatan @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row">
        <div class="col-xl-8">
            <form action="{{ route('user.asrama.kegiatan.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" method="POST">
                @csrf
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="ri-calendar-event-line me-2 text-primary"></i>Data Kegiatan</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Contoh: Muhadhoroh Malam">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="activity_date" class="form-control" value="{{ old('activity_date', now()->toDateString()) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Mulai</label>
                                <input type="time" name="start_time" class="form-control" value="{{ old('start_time') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Selesai</label>
                                <input type="time" name="end_time" class="form-control" value="{{ old('end_time') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Lokasi</label>
                                <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="Aula / Masjid / Kamar">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Keterangan kegiatan (opsional)">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <a href="{{ route('user.asrama.kegiatan.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light"><i class="ri-arrow-go-back-line me-1"></i>Kembali</a>
                    <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Kegiatan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
