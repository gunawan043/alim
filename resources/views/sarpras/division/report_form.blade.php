@extends('layouts.master')
@section('title') Laporkan Kerusakan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.divisi.dashboard') }}">Portal Divisi</a> @endslot
    @slot('title') Laporkan Kerusakan @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="ri-tools-line text-warning me-1"></i>
                    Form Laporan Kerusakan
                </h5>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('sarpras.divisi.report_issue') }}" method="POST" id="reportForm">
                    @csrf
                    <input type="hidden" name="asset_id" value="{{ $asset->id }}">

                    <div class="mb-3">
                        <label class="form-label">Aset <span class="text-danger">*</span></label>
                        <div class="border rounded p-3 bg-light">
                            <strong>{{ $asset->asset_name }}</strong>
                            <code class="ms-2">{{ $asset->asset_code }}</code>
                            <div class="small text-muted">
                                {{ $asset->room?->room_name ?? '-' }} &middot;
                                {{ $asset->category->nama ?? $asset->category->name ?? '-' }}
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">Judul Laporan <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title"
                            class="form-control" required maxlength="255"
                            placeholder="Contoh: AC tidak dingin, lampu berkedip"
                            value="{{ old('title') }}">
                    </div>

                    <div class="mb-3">
                        <label for="severity" class="form-label">Tingkat Kerusakan <span class="text-danger">*</span></label>
                        <select name="severity" id="severity" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <option value="low" {{ old('severity') == 'low' ? 'selected' : '' }}>
                                Ringan &mdash; masih bisa digunakan
                            </option>
                            <option value="medium" {{ old('severity') == 'medium' ? 'selected' : '' }}>
                                Sedang &mdash; mengganggu operasional
                            </option>
                            <option value="high" {{ old('severity') == 'high' ? 'selected' : '' }}>
                                Berat &mdash; tidak bisa digunakan
                            </option>
                            <option value="critical" {{ old('severity') == 'critical' ? 'selected' : '' }}>
                                Kritis &mdash; mengganggu keselamatan
                            </option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi Kerusakan <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" rows="5"
                            class="form-control" required maxlength="2000"
                            placeholder="Jelaskan secara detail: kapan terjadi, gejala, dampak...">{{ old('description') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('sarpras.divisi.asset_show', $asset->id) }}" class="btn btn-light">
                            <i class="ri-arrow-left-line me-1"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="ri-send-plane-line me-1"></i> Kirim Laporan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-warning">
            <div class="card-body">
                <h6 class="card-title"><i class="ri-information-line me-1 text-warning"></i> Alur Pelaporan</h6>
                <ol class="ps-3 small mb-0">
                    <li class="mb-1">Anda mengirim laporan</li>
                    <li class="mb-1"><strong>PIC Aset</strong> memverifikasi</li>
                    <li class="mb-1"><strong>Kepala Sarpras</strong> menyetujui</li>
                    <li class="mb-1">Work Order otomatis dibuat</li>
                    <li>Teknisi mengerjakan &amp; dokumentasi</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
