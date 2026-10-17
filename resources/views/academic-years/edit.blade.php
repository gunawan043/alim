@extends('layouts.master')
@section('title') Edit Tahun Ajaran @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.academic-years.index', ['userId' => $userId]) }}">Tahun Ajaran</a> @endslot
        @slot('title') Edit {{ $academicYear->name }} @endslot
    @endcomponent

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-0"><i class="ri-pencil-line text-primary me-1"></i>Edit Tahun Ajaran</h5>
                        <p class="text-muted mb-0 small">{{ $academicYear->name }} · {{ $academicYear->semester_text }}</p>
                    </div>
                    @if($academicYear->is_active)
                        <span class="badge bg-success-subtle text-success">Aktif</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                    @endif
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.academic-years.update', ['userId' => $userId, 'id' => $academicYear->id]) }}">
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
                                <select name="semester" class="form-select @error('semester') is-invalid @enderror" required>
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
                            <a href="{{ route('user.academic-years.show', ['userId' => $userId, 'id' => $academicYear->id]) }}" class="btn btn-light">
                                <i class="ri-close-line me-1"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
