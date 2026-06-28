@extends('layouts.master')
@section('title') Tambah ke Rombel @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
        @slot('li_3') <a href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $student->id]) }}">{{ $student->name }}</a> @endslot
        @slot('title') Tambah ke Rombel @endslot
    @endcomponent

    @if($existingHistory)
        <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
            <i class="ri-information-line fs-4"></i>
            <div>
                Santri ini sudah terdaftar di rombel
                <strong>{{ $existingHistory->studyGroup?->full_name ?? '-' }}</strong>
                ({{ $existingHistory->academicYear?->name ?? '-' }}) sebagai rombel aktif.
                Menyimpan perubahan akan memindahkan siswa ke rombel baru dan menonaktifkan history sebelumnya.
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Form Penempatan Rombel</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('user.student-class-histories.store', ['userId' => $userId, 'studentId' => $student->id]) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Santri</label>
                            <input type="text" class="form-control" value="{{ $student->name }} (NISN: {{ $student->nisn ?? '-' }})" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tahun Ajaran</label>
                            <input type="text" class="form-control"
                                   value="{{ $activeAcademicYear?->name ?? '-' }}@if($activeAcademicYear) ({{ $activeAcademicYear->semester }}) @endif"
                                   disabled>
                            <small class="text-muted">Tahun ajaran aktif diambil otomatis dari sistem.</small>
                        </div>

                        <div class="mb-3">
                            <label for="study_group_id" class="form-label">Pilih Rombel <span class="text-danger">*</span></label>
                            <select name="study_group_id" id="study_group_id" class="form-select @error('study_group_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Rombel --</option>
                                @forelse($studyGroups as $sg)
                                    @php
                                        $filled = $sg->studentClassHistories->where('is_active', true)->count();
                                        $full   = $filled >= ($sg->capacity ?? 0);
                                    @endphp
                                    <option value="{{ $sg->id }}" {{ $full ? 'disabled' : '' }} {{ (string) old('study_group_id') === $sg->id ? 'selected' : '' }}>
                                        {{ $sg->full_name }}
                                        (Kapasitas: {{ $filled }}/{{ $sg->capacity ?? '∞' }})
                                        {{ $full ? ' — PENUH' : '' }}
                                    </option>
                                @empty
                                    <option value="" disabled>Tidak ada rombel aktif untuk sekolah ini.</option>
                                @endforelse
                            </select>
                            @error('study_group_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if($studyGroups->isEmpty())
                                <small class="text-muted">
                                    Belum ada rombel aktif di sekolah ini untuk tahun ajaran aktif.
                                    Hubungi admin untuk membuat rombel terlebih dahulu.
                                </small>
                            @endif
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="attendance_number" class="form-label">No. Absen</label>
                                <input type="number" min="1" name="attendance_number" id="attendance_number"
                                       class="form-control @error('attendance_number') is-invalid @enderror"
                                       value="{{ old('attendance_number') }}"
                                       placeholder="Otomatis jika kosong">
                                @error('attendance_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="join_date" class="form-label">Tanggal Masuk</label>
                                <input type="date" name="join_date" id="join_date"
                                       class="form-control @error('join_date') is-invalid @enderror"
                                       value="{{ old('join_date', now()->toDateString()) }}">
                                @error('join_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan</label>
                            <textarea name="notes" id="notes" rows="2"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      placeholder="Catatan opsional">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $student->id]) }}"
                               class="btn btn-light">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary" {{ $studyGroups->isEmpty() ? 'disabled' : '' }}>
                                <i class="ri-save-line me-1"></i> Simpan Penempatan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h6 class="card-title mb-0">Informasi Santri</h6></div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th class="text-muted" style="width: 110px;">Nama</th><td>{{ $student->name }}</td></tr>
                        <tr><th class="text-muted">NISN</th><td>{{ $student->nisn ?? '-' }}</td></tr>
                        <tr><th class="text-muted">NIS</th><td>{{ $student->nis ?? '-' }}</td></tr>
                        <tr><th class="text-muted">JK</th><td>{{ $student->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td></tr>
                        <tr><th class="text-muted">Sekolah</th><td>{{ $student->school?->name ?? '-' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection