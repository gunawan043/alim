@extends('waka.master')
@section('title') Tambah Surat Keluar @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Surat Menyurat @endslot
        @slot('title') Tambah Surat Keluar @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Form Surat Keluar</h5></div>
                <div class="card-body">
                    <form action="{{ route('waka.surat-keluar.store') }}" method="POST" autocomplete="off">
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
                            <label class="col-sm-3 col-form-label">Tanggal Kirim</label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_kirim" class="form-control" value="{{ old('tanggal_kirim') }}">
                                @error('tanggal_kirim')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tujuan <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="tujuan" class="form-control" value="{{ old('tujuan') }}" required>
                                @error('tujuan')<span class="text-danger">{{ $message }}</span>@enderror
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
                            <label class="col-sm-3 col-form-label">Keterangan</label>
                            <div class="col-sm-9">
                                <textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan') }}</textarea>
                                @error('keterangan')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('waka.surat-keluar.index') }}" class="btn btn-secondary me-1"><i class="ri-arrow-go-back-line"></i> Kembali</a>
                            <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection