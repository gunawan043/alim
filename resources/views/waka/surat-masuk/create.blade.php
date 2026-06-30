@extends('waka.master')
@section('title') Tambah Surat Masuk @endsection

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Surat Menyurat @endslot
        @slot('title') Tambah Surat Masuk @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Form Surat Masuk</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('waka.surat-masuk.store') }}" method="POST" autocomplete="off">
                        @csrf

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Nomor Surat <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="nomor_surat" class="form-control" value="{{ old('nomor_surat') }}" required>
                                @error('nomor_surat')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tanggal Surat <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_surat" class="form-control" value="{{ old('tanggal_surat') }}" required>
                                @error('tanggal_surat')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tanggal Diterima <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_terima" class="form-control" value="{{ old('tanggal_terima') }}" required>
                                @error('tanggal_terima')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Pengirim <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="pengirim" class="form-control" value="{{ old('pengirim') }}" required>
                                @error('pengirim')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Perihal <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="perihal" class="form-control" value="{{ old('perihal') }}" required>
                                @error('perihal')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Sifat <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="sifat" class="form-select" required>
                                    <option value="">— Pilih sifat —</option>
                                    <option value="biasa"   {{ old('sifat')==='biasa'   ? 'selected' : '' }}>Biasa</option>
                                    <option value="penting" {{ old('sifat')==='penting' ? 'selected' : '' }}>Penting</option>
                                    <option value="rahasia" {{ old('sifat')==='rahasia' ? 'selected' : '' }}>Rahasia</option>
                                </select>
                                @error('sifat')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="row">
                            <label class="col-sm-3 col-form-label">Lampiran</label>
                            <div class="col-sm-9">
                                <textarea name="lampiran" class="form-control" rows="3">{{ old('lampiran') }}</textarea>
                                @error('lampiran')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('waka.surat-masuk.index') }}" class="btn btn-secondary me-1">
                                <i class="ri-arrow-go-back-line"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection