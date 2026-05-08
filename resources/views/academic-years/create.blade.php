@extends('layouts.master')
@section('title') Tambah Tahun Ajaran @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Tahun Ajaran @endslot
        @slot('title') Tambah Tahun Ajaran @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-calendar-event-line me-2"></i>Form Tahun Ajaran</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.academic-years.store') }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Tahun Ajaran <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                    placeholder="Contoh: 2025/2026" value="{{ old('name') }}" required>
                                <small class="text-muted">Format: YYYY/YYYY, contoh: 2025/2026</small>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <select name="semester" class="form-control @error('semester') is-invalid @enderror" required>
                                    <option value="">-- Pilih Semester --</option>
                                    <option value="ganjil" {{ old('semester') === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                    <option value="genap" {{ old('semester') === 'genap' ? 'selected' : '' }}>Genap</option>
                                </select>
                                @error('semester')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tanggal Mulai</label>
                                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                                    value="{{ old('start_date') }}">
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tanggal Selesai</label>
                                <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
                                    value="{{ old('end_date') }}">
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Mulai Pendaftaran</label>
                                <input type="date" name="registration_start" class="form-control"
                                    value="{{ old('registration_start') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Akhir Pendaftaran</label>
                                <input type="date" name="registration_end" class="form-control"
                                    value="{{ old('registration_end') }}">
                            </div>

                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="isActiveSwitch"
                                        value="1" {{ old('is_active') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActiveSwitch">Jadikan tahun ajaran aktif</label>
                                </div>
                                <small class="text-muted">Hanya satu tahun ajaran yang boleh aktif per sekolah.</small>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Simpan
                            </button>
                            <a href="{{ route('user.academic-years.index') }}" class="btn btn-light">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
