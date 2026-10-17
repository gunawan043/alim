@extends('layouts.master')
@section('title') Edit Riwayat Rombel @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
        @slot('li_3') <a href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $student->id]) }}">{{ $student->name }}</a> @endslot
        @slot('title') Edit Riwayat Rombel @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Form Edit Riwayat Rombel</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('user.student-class-histories.update', ['userId' => $userId, 'historyUuid' => $history->id]) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Santri</label>
                            <input type="text" class="form-control" value="{{ $student->name }} (NISN: {{ $student->nisn ?? '-' }})" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tahun Ajaran</label>
                            <input type="text" class="form-control"
                                   value="{{ $history->academicYear?->name ?? '-' }}@if($history->academicYear) ({{ $history->academicYear->semester }}) @endif"
                                   disabled>
                        </div>

                        <div class="mb-3">
                            <label for="study_group_id" class="form-label">Pilih Rombel <span class="text-danger">*</span></label>
                            <select name="study_group_id" id="study_group_id" class="form-select @error('study_group_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Rombel --</option>
                                @forelse($studyGroups as $sg)
                                    @php
                                        $filled = $sg->studentClassHistories->where('is_active', true)->count();
                                        $isCurrent = $sg->id === $history->study_group_id;
                                        $full   = ! $isCurrent && $filled >= ($sg->capacity ?? 0);
                                    @endphp
                                    <option value="{{ $sg->id }}" {{ $full ? 'disabled' : '' }} {{ (string) old('study_group_id', $history->study_group_id) === $sg->id ? 'selected' : '' }}>
                                        {{ $sg->full_name }}
                                        (Kapasitas: {{ $filled }}/{{ $sg->capacity ?? '∞' }})
                                        {{ $full ? ' — PENUH' : '' }}
                                        {{ $isCurrent ? ' (Rombel Saat Ini)' : '' }}
                                    </option>
                                @empty
                                    <option value="" disabled>Tidak ada rombel aktif untuk sekolah ini.</option>
                                @endforelse
                            </select>
                            @error('study_group_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="attendance_number" class="form-label">No. Absen</label>
                                <input type="number" min="1" name="attendance_number" id="attendance_number"
                                       class="form-control @error('attendance_number') is-invalid @enderror"
                                       value="{{ old('attendance_number', $history->attendance_number) }}">
                                @error('attendance_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="join_date" class="form-label">Tanggal Masuk</label>
                                <input type="date" name="join_date" id="join_date"
                                       class="form-control @error('join_date') is-invalid @enderror"
                                       value="{{ old('join_date', $history->join_date?->toDateString()) }}">
                                @error('join_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       name="is_active" id="is_active" value="1"
                                       {{ old('is_active', $history->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Aktif</label>
                            </div>
                            <small class="text-muted">Jika diaktifkan, history aktif lain milik siswa ini akan dinonaktifkan otomatis.</small>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan</label>
                            <textarea name="notes" id="notes" rows="2"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      placeholder="Catatan opsional">{{ old('notes', $history->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $student->id]) }}"
                               class="btn btn-light">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line me-1"></i> Simpan Perubahan
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
