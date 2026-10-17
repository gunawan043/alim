@extends('layouts.master')
@section('title') Bank Soal - Edit @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.bank-soal.index', ['userId' => $userId]) }}">Bank Soal</a> @endslot
        @slot('title') Edit: {{ $bank->nama }} @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit Bank Soal</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.bank-soal.update', ['userId' => $userId, 'id' => $bank->id]) }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Nama Bank Soal <span class="text-danger">*</span></label>
                                <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                                       value="{{ old('nama', $bank->nama) }}" required>
                                @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="subject_id" class="form-control @error('subject_id') is-invalid @enderror" required>
                                    <option value="">Pilih Mapel</option>
                                    @foreach($subjects as $s)
                                        <option value="{{ $s->id }}" {{ old('subject_id', $bank->subject_id) == $s->id ? 'selected' : '' }}>
                                            {{ $s->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('subject_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror"
                                          rows="3">{{ old('deskripsi', $bank->deskripsi) }}</textarea>
                                @error('deskripsi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Fase (opsional)</label>
                                <input type="text" name="fase" class="form-control" placeholder="E, F"
                                       value="{{ old('fase', $bank->fase) }}" maxlength="5">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Jenis Soal <span class="text-danger">*</span></label>
                                <select name="jenis_soal" class="form-control" required>
                                    @foreach($jenisSoal as $key => $label)
                                        <option value="{{ $key }}" {{ old('jenis_soal', $bank->jenis_soal) === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Tingkat Kesulitan <span class="text-danger">*</span></label>
                                <select name="tingkat_kesulitan_target" class="form-control" required>
                                    @foreach($tingkatKesulitan as $key => $label)
                                        <option value="{{ $key }}" {{ old('tingkat_kesulitan_target', $bank->tingkat_kesulitan_target) === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Jangkauan Akses <span class="text-danger">*</span></label>
                                <select name="shared_scope" class="form-control" required>
                                    @foreach($sharedScopes as $key => $label)
                                        <option value="{{ $key }}" {{ old('shared_scope', $bank->shared_scope) === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <div class="form-check form-switch mt-4">
                                    <input type="checkbox" name="is_public" class="form-check-input" id="is_public"
                                           value="1" {{ old('is_public', $bank->is_public) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_public">Tampilkan Publik</label>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-check form-switch mt-4">
                                    <input type="checkbox" name="allow_cross_teacher_clone" class="form-check-input"
                                           id="allow_clone" value="1" {{ old('allow_cross_teacher_clone', $bank->allow_cross_teacher_clone) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allow_clone">Izinkan Clone Guru</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}" class="btn btn-light">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Perbarui Bank Soal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection