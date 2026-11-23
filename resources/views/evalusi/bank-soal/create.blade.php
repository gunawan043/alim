@extends('layouts.master')
@section('title') Bank Soal - Buat @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.bank-soal.index', ['userId' => $userId]) }}">Bank Soal</a> @endslot
        @slot('title') Buat Bank Soal @endslot
    @endcomponent

    <x-soal-flow current="bank" :scope="$scope ?? null" />

    @if(! empty($scope) && empty($scope['has_assignment']))
        <div class="alert alert-warning d-flex gap-3" role="alert">
            <i class="ri-error-warning-line fs-4 lh-1"></i>
            <div>
                <h6 class="alert-heading mb-1">Bank soal belum bisa dibuat</h6>
                <p class="mb-2 small">
                    Anda belum punya penugasan mengajar pada SK Pembagian Tugas yang aktif,
                    sehingga belum ada mata pelajaran yang bisa dipilih.
                </p>
                <a href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}" class="btn btn-sm btn-primary">
                    <i class="ri-file-list-3-line me-1"></i>Buka halaman SK &amp; Keputusan
                </a>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-10">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">1. Identitas bank soal</h5>
                    <p class="text-muted mb-0 small">
                        Mata pelajaran terbatas pada yang tercantum di SK aktif Anda.
                    </p>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.bank-soal.store', ['userId' => $userId]) }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                                    <option value="">Pilih mapel</option>
                                    @foreach($subjects as $s)
                                        <option value="{{ $s->id }}" {{ old('subject_id') == $s->id ? 'selected' : '' }}>
                                            {{ $s->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('subject_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Tingkat Kelas</label>
                                <select name="grade_level_id" class="form-select @error('grade_level_id') is-invalid @enderror">
                                    <option value="">Semua tingkat yang saya ajar</option>
                                    @foreach($gradeLevels as $gl)
                                        <option value="{{ $gl->id }}" {{ (string) old('grade_level_id') === (string) $gl->id ? 'selected' : '' }}>
                                            {{ $gl->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('grade_level_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Fase (opsional)</label>
                                <input type="text" name="fase" class="form-control" placeholder="E, F"
                                       value="{{ old('fase') }}" maxlength="5">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Nama Bank Soal <span class="text-danger">*</span></label>
                                <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                                       value="{{ old('nama') }}" placeholder="Contoh: Matematika Kelas 4 — Sumatif Akhir Semester" required>
                                @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror"
                                          rows="2" placeholder="Cakupan materi atau catatan singkat">{{ old('deskripsi') }}</textarea>
                                @error('deskripsi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3">2. Bentuk &amp; pengaturan soal</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Jenis Soal <span class="text-danger">*</span></label>
                                <select name="jenis_soal" class="form-select" required>
                                    @foreach($jenisSoal as $key => $label)
                                        <option value="{{ $key }}" {{ old('jenis_soal', 'campuran') === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Bentuk soal (PG, uraian, campuran), bukan bahasa.</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Bahasa Utama</label>
                                <select name="bahasa" class="form-select">
                                    @foreach (\App\Models\Soal::BAHASA_OPTIONS as $key => $label)
                                        <option value="{{ $key }}" {{ old('bahasa', 'latin') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Default saat input soal; masih bisa diubah per soal.</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Tingkat Kesulitan <span class="text-danger">*</span></label>
                                <select name="tingkat_kesulitan_target" class="form-select" required>
                                    @foreach($tingkatKesulitan as $key => $label)
                                        <option value="{{ $key }}" {{ old('tingkat_kesulitan_target', 'campuran') === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Jangkauan Akses <span class="text-danger">*</span></label>
                                <select name="shared_scope" class="form-select" required>
                                    @foreach($sharedScopes as $key => $label)
                                        <option value="{{ $key }}" {{ old('shared_scope', 'private') === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <div class="form-check form-switch mt-4">
                                    <input type="checkbox" name="is_public" class="form-check-input" id="is_public"
                                           value="1" {{ old('is_public') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_public">Tampilkan publik</label>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-check form-switch mt-4">
                                    <input type="checkbox" name="allow_cross_teacher_clone" class="form-check-input"
                                           id="allow_clone" value="1" {{ old('allow_cross_teacher_clone') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allow_clone">Izinkan clone guru</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                            <a href="{{ route('user.bank-soal.index', ['userId' => $userId]) }}" class="btn btn-light">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-success" @disabled(! empty($scope) && empty($scope['has_assignment']))>
                                <i class="ri-save-line me-1"></i> Simpan &amp; lanjut input soal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
