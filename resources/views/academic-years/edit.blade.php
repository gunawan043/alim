@extends('layouts.master')
@section('title') Edit Tahun Ajaran @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Tahun Ajaran @endslot
        @slot('title') Edit Tahun Ajaran @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-pencil-line me-2"></i>Edit Tahun Ajaran</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.academic-years.update', $academicYear->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Tahun Ajaran <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                    placeholder="Contoh: 2025/2026" value="{{ old('name', $academicYear->name) }}" required>
                                <small class="text-muted">Format: YYYY/YYYY, contoh: 2025/2026</small>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <select name="semester" class="form-control @error('semester') is-invalid @enderror" required>
                                    <option value="">-- Pilih Semester --</option>
                                    <option value="ganjil" {{ old('semester', $academicYear->semester) === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                    <option value="genap" {{ old('semester', $academicYear->semester) === 'genap' ? 'selected' : '' }}>Genap</option>
                                </select>
                                @error('semester')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tanggal Mulai</label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ old('start_date', $academicYear->start_date?->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tanggal Selesai</label>
                                <input type="date" name="end_date" class="form-control"
                                    value="{{ old('end_date', $academicYear->end_date?->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Mulai Pendaftaran</label>
                                <input type="date" name="registration_start" class="form-control"
                                    value="{{ old('registration_start', $academicYear->registration_start?->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Akhir Pendaftaran</label>
                                <input type="date" name="registration_end" class="form-control"
                                    value="{{ old('registration_end', $academicYear->registration_end?->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="isActiveSwitch"
                                        value="1" {{ old('is_active', $academicYear->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActiveSwitch">Jadikan tahun ajaran aktif</label>
                                </div>
                                <small class="text-muted">Hanya satu tahun ajaran yang boleh aktif per sekolah.</small>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Simpan Perubahan
                            </button>
                            <a href="{{ route('user.academic-years.show', $academicYear->id) }}" class="btn btn-light">
                                <i class="ri-close-line me-1"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
