@extends('waka.master')
@section('title') Edit Surat Keluar @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Surat Menyurat @endslot
        @slot('li_2') <a href="{{ route('waka.surat-keluar.index') }}">Surat Keluar</a> @endslot
        @slot('title') Edit {{ $surat->nomor_surat }} @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Edit Surat Keluar</h5></div>
                <div class="card-body">
                    <form action="{{ route('waka.surat-keluar.update', $surat->id) }}" method="POST" autocomplete="off">
                        @csrf @method('PUT')

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Nomor Surat <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="nomor_surat" class="form-control" value="{{ old('nomor_surat', $surat->nomor_surat) }}" required>
                                @error('nomor_surat')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tanggal Surat <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_surat" class="form-control" value="{{ old('tanggal_surat', $surat->tanggal_surat?->format('Y-m-d')) }}" required>
                                @error('tanggal_surat')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tanggal Kirim</label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_kirim" class="form-control" value="{{ old('tanggal_kirim', $surat->tanggal_kirim?->format('Y-m-d')) }}">
                                @error('tanggal_kirim')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tujuan <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="tujuan" class="form-control" value="{{ old('tujuan', $surat->tujuan) }}" required>
                                @error('tujuan')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Perihal <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="perihal" class="form-control" value="{{ old('perihal', $surat->perihal) }}" required>
                                @error('perihal')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Sifat <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="sifat" class="form-select" required>
                                    @foreach(['biasa'=>'Biasa','penting'=>'Penting','rahasia'=>'Rahasia'] as $val=>$label)
                                        <option value="{{ $val }}" {{ old('sifat', $surat->sifat)===$val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('sifat')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row">
                            <label class="col-sm-3 col-form-label">Keterangan</label>
                            <div class="col-sm-9">
                                <textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan', $surat->keterangan) }}</textarea>
                                @error('keterangan')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('waka.surat-keluar.index') }}" class="btn btn-secondary me-1"><i class="ri-arrow-go-back-line"></i> Kembali</a>
                            <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection