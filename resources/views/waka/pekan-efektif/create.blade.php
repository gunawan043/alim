@extends('waka.master')
@section('title') Tambah Pekan Efektif @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pekan Efektif @endslot
        @slot('title') Tambah Pekan @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Form Pekan Efektif</h5></div>
                <div class="card-body">
                    <form action="{{ route('waka.pekan-efektif.store') }}" method="POST" autocomplete="off">
                        @csrf

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="academic_year_id" class="form-select" required>
                                    <option value="">— Pilih Tahun Ajaran —</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ old('academic_year_id')==$ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                    @endforeach
                                </select>
                                @error('academic_year_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Semester <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="semester" class="form-select" required>
                                    <option value="">— Pilih —</option>
                                    <option value="1" {{ old('semester','1')=='1' ? 'selected' : '' }}>1 (Ganjil)</option>
                                    <option value="2" {{ old('semester')=='2' ? 'selected' : '' }}>2 (Genap)</option>
                                </select>
                                @error('semester')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Minggu Ke <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="number" name="minggu_ke" class="form-control" value="{{ old('minggu_ke') }}" min="1" max="52" required>
                                @error('minggu_ke')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_mulai" class="form-control" value="{{ old('tanggal_mulai') }}" required>
                                @error('tanggal_mulai')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_selesai" class="form-control" value="{{ old('tanggal_selesai') }}" required>
                                @error('tanggal_selesai')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Jenis <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="jenis" class="form-select" required>
                                    <option value="">— Pilih Jenis —</option>
                                    @foreach(['efektif'=>'Efektif (Pembelajaran)','libur'=>'Libur','ujian'=>'Ujian','kegiatan_sekolah'=>'Kegiatan Sekolah','lainnya'=>'Lainnya'] as $v => $l)
                                        <option value="{{ $v }}" {{ old('jenis')==$v ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                                @error('jenis')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row">
                            <label class="col-sm-3 col-form-label">Keterangan</label>
                            <div class="col-sm-9">
                                <input type="text" name="keterangan" class="form-control" value="{{ old('keterangan') }}" maxlength="255">
                                @error('keterangan')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('waka.pekan-efektif.index') }}" class="btn btn-secondary me-1"><i class="ri-arrow-go-back-line"></i> Kembali</a>
                            <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection