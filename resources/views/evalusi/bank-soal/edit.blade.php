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
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h5 class="card-title mb-0">Edit Bank Soal</h5>
                    <a href="{{ route('user.soal.manage', ['userId' => $userId, 'bankId' => $bank->id]) }}"
                       class="btn btn-sm btn-soft-primary">
                        <i class="ri-edit-2-line me-1"></i> Edit Soal
                    </a>
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
                                <label class="form-label mt-3">Tingkat Kelas</label>
                                <select name="grade_level_id" class="form-select">
                                    <option value="">Semua tingkat</option>
                                    @foreach($gradeLevels as $gl)
                                        <option value="{{ $gl->id }}" {{ (string) old('grade_level_id', $bank->grade_level_id) === (string) $gl->id ? 'selected' : '' }}>{{ $gl->name }}</option>
                                    @endforeach
                                </select>
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
                                <small class="text-muted">Bentuk soal (PG, uraian, campuran), bukan bahasa.</small>
                                <label class="form-label mt-3">Bahasa Utama</label>
                                <select name="bahasa" class="form-control">
                                    @foreach (\App\Models\Soal::BAHASA_OPTIONS as $key => $label)
                                        <option value="{{ $key }}" {{ old('bahasa', $bank->bahasa ?? 'latin') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Menentukan default bahasa saat input soal. Bahasa tetap bisa diubah per soal.</small>
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

    <div class="row">
        <div class="col-xl-10">
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h5 class="card-title mb-0"><i class="ri-list-check-2 me-1 text-primary"></i>Daftar Soal</h5>
                        <small class="text-muted">Edit bank dan kelola soal dalam satu halaman.</small>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="{{ route('user.soal.manage', ['userId' => $userId, 'bankId' => $bank->id]) }}"
                           class="btn btn-soft-primary btn-sm"><i class="ri-edit-2-line me-1"></i>Buka Halaman Edit Soal</a>
                        <a href="{{ route('user.soal.create', ['userId' => $userId, 'bankId' => $bank->id]) }}"
                           class="btn btn-success btn-sm"><i class="ri-add-line me-1"></i>Tambah Soal</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($bank->soal->isEmpty())
                        <div class="text-center text-muted py-4">Belum ada soal.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th class="ps-3" style="width:50px">#</th>
                                        <th>Level</th>
                                        <th>Pertanyaan</th>
                                        <th>Status</th>
                                        <th class="pe-3 text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bank->soal as $i => $soal)
                                        <tr>
                                            <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                                            <td>
                                                @if($soal->level_kognitif)
                                                    <span class="badge bg-primary-subtle text-primary">{{ $soal->level_kognitif_code }} · {{ $soal->level_kognitif_rumpun }}</span>
                                                @else
                                                    <span class="badge bg-light text-muted border">Belum</span>
                                                @endif
                                            </td>
                                            <td>{{ Str::limit(strip_tags($soal->pertanyaan ?? ''), 90) }}</td>
                                            <td><span class="badge {{ $soal->status === 'approved' ? 'bg-success' : ($soal->status === 'draft' ? 'bg-warning text-dark' : 'bg-info') }}">{{ ucfirst($soal->status ?? '-') }}</span></td>
                                            <td class="pe-3 text-end">
                                                <a href="{{ route('user.soal.edit', ['userId' => $userId, 'bankId' => $bank->id, 'id' => $soal->id]) }}"
                                                   class="btn btn-sm btn-soft-primary"><i class="ri-pencil-line me-1"></i>Edit Soal</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection