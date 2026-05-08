@extends('layouts.master')
@section('title') Tambah Kandidat @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Candidates @endslot
@slot('li_2') Daftar Kandidat @endslot
@slot('title') Tambah Kandidat @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Form Tambah Kandidat</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('user.ats.candidates.store', ['userId' => $userId]) }}" method="POST">
                    @csrf
                    
                    <div class="row g-4">
                        <h6 class="text-muted">Informasi Akun</h6>
                        <div class="col-md-6">
                            <div>
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       name="password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <hr class="my-3">
                        <h6 class="text-muted">Data Pribadi</h6>
                        
                        <div class="col-md-6">
                            <div>
                                <label class="form-label">NIK</label>
                                <input type="text" class="form-control @error('nik') is-invalid @enderror" 
                                       name="nik" value="{{ old('nik') }}">
                                @error('nik')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <label class="form-label">No. HP</label>
                                <input type="text" class="form-control @error('no_hp') is-invalid @enderror" 
                                       name="no_hp" value="{{ old('no_hp') }}">
                                @error('no_hp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <label class="form-label">Tempat Lahir</label>
                                <input type="text" class="form-control @error('tempat_lahir') is-invalid @enderror" 
                                       name="tempat_lahir" value="{{ old('tempat_lahir') }}">
                                @error('tempat_lahir')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <label class="form-label">Tanggal Lahir</label>
                                <input type="date" class="form-control @error('tanggal_lahir') is-invalid @enderror" 
                                       name="tanggal_lahir" value="{{ old('tanggal_lahir') }}">
                                @error('tanggal_lahir')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <label class="form-label">Jenis Kelamin</label>
                                <select class="form-control @error('jenis_kelamin') is-invalid @enderror" 
                                        data-choices name="jenis_kelamin">
                                    <option value="">Pilih</option>
                                    <option value="L" {{ old('jenis_kelamin') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ old('jenis_kelamin') == 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                                @error('jenis_kelamin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <label class="form-label">Agama</label>
                                <select class="form-control @error('agama') is-invalid @enderror" 
                                        data-choices name="agama">
                                    <option value="">Pilih</option>
                                    <option value="islam" {{ old('agama') == 'islam' ? 'selected' : '' }}>Islam</option>
                                    <option value="kristen" {{ old('agama') == 'kristen' ? 'selected' : '' }}>Kristen</option>
                                    <option value="katolik" {{ old('agama') == 'katolik' ? 'selected' : '' }}>Katolik</option>
                                    <option value="hindu" {{ old('agama') == 'hindu' ? 'selected' : '' }}>Hindu</option>
                                    <option value="buddha" {{ old('agama') == 'buddha' ? 'selected' : '' }}>Buddha</option>
                                    <option value="konghucu" {{ old('agama') == 'konghucu' ? 'selected' : '' }}>Konghucu</option>
                                </select>
                                @error('agama')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <label class="form-label">Status Perkawinan</label>
                                <select class="form-control @error('status_perkawinan') is-invalid @enderror" 
                                        data-choices name="status_perkawinan">
                                    <option value="">Pilih</option>
                                    <option value="belum_kawin" {{ old('status_perkawinan', 'belum_kawin') == 'belum_kawin' ? 'selected' : '' }}>Belum Kawin</option>
                                    <option value="kawin" {{ old('status_perkawinan') == 'kawin' ? 'selected' : '' }}>Kawin</option>
                                    <option value="cerai_hidup" {{ old('status_perkawinan') == 'cerai_hidup' ? 'selected' : '' }}>Cerai Hidup</option>
                                    <option value="cerai_mati" {{ old('status_perkawinan') == 'cerai_mati' ? 'selected' : '' }}>Cerai Mati</option>
                                </select>
                                @error('status_perkawinan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div>
                                <label class="form-label">Alamat</label>
                                <textarea class="form-control @error('alamat') is-invalid @enderror" 
                                          name="alamat" rows="3">{{ old('alamat') }}</textarea>
                                @error('alamat')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-lg-12">
                            <div class="hstack justify-content-end gap-2">
                                <a href="{{ route('user.ats.candidates.index', ['userId' => $userId]) }}" class="btn btn-ghost-danger">
                                    <i class="ri-close-line align-bottom"></i> Batal
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line align-bottom"></i> Simpan
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/job-list.init.js') }}"></script>
    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection