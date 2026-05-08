@extends('layouts.master')
@section('title') Tracer Study — {{ $alumni->student->name ?? '' }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.alumni.index', ['userId' => $userId]) }}">Data Alumni</a> @endslot
        @slot('li_3') <a href="{{ route('user.alumni.show', ['userId' => $userId, 'alumniUuid' => $alumni->id]) }}">{{ $alumni->student->name ?? '' }}</a> @endslot
        @slot('title') Tracer Study @endslot
    @endcomponent

    <form method="POST" action="{{ route('user.alumni.update', ['userId' => $userId, 'alumniUuid' => $alumni->id]) }}">
        @csrf
        @method('PUT')

        <div class="row">
            {{-- Left: Info + Contact --}}
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0"><i class="ri-user-line me-2"></i>Info Alumni</h6></div>
                    <div class="card-body">
                        <h5>{{ $alumni->student->name ?? '-' }}</h5>
                        <p class="text-muted mb-1">{{ $alumni->school->name ?? '-' }}</p>
                        <span class="badge bg-info-subtle text-info">
                            <i class="ri-graduation-cap-line me-1"></i>Lulus {{ $alumni->graduation_year }}
                        </span>
                        <hr>
                        <table class="table table-sm table-borderless mb-0">
                            <tr><th class="text-muted">NISN</th><td>{{ $alumni->student->nisn ?? '-' }}</td></tr>
                            <tr><th class="text-muted">JK</th><td>{{ $alumni->student->gender_text ?? '-' }}</td></tr>
                            <tr><th class="text-muted">TTL</th>
                                <td>{{ $alumni->student->birth_place ?? '' }}, {{ $alumni->student->birth_date?->format('d/m/Y') ?? '-' }}
                                </td>
                            </tr>
                            <tr><th class="text-muted">No. HP</th><td>{{ $alumni->student->mobile_phone ?: '-' }}</td></tr>
                        </table>
                    </div>
                </div>

                {{-- Contact Info --}}
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0"><i class="ri-phone-line me-2"></i>Kontak &amp; Lainnya</h6></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Dapat Dihubungi</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_contactable"
                                       id="is_contactable" value="1"
                                       {{ old('is_contactable', $alumni->is_contactable) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_contactable">Ya, alumni masih bisa dihubungi</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prestasi / Penghargaan</label>
                            <textarea name="achievements" class="form-control" rows="3"
                                      placeholder="Prestasi yang pernah diraih...">{{ old('achievements', $alumni->achievements) }}</textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Catatan Tambahan</label>
                            <textarea name="tracer_notes" class="form-control" rows="3"
                                      placeholder="Catatan atau informasi tambahan...">{{ old('tracer_notes', $alumni->tracer_notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Tracer Study Form --}}
            <div class="col-lg-8">

                {{-- Melanjutkan Studi --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="ri-book-open-line me-2"></i>Melanjutkan Studi</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="continuing_study_status" class="form-control" required>
                                    <option value="belum" {{ old('continuing_study_status', $alumni->continuing_study_status) === 'belum' ? 'selected' : '' }}>Belum</option>
                                    <option value="sedang" {{ old('continuing_study_status', $alumni->continuing_study_status) === 'sedang' ? 'selected' : '' }}>Sedang</option>
                                    <option value="sudah" {{ old('continuing_study_status', $alumni->continuing_study_status) === 'sudah' ? 'selected' : '' }}>Sudah</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nama Kampus / Institution</label>
                                <input type="text" name="higher_education_institution" class="form-control"
                                       value="{{ old('higher_education_institution', $alumni->higher_education_institution) }}"
                                       placeholder="Contoh: Universitas XYZ">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Program Studi / Jurusan</label>
                                <input type="text" name="study_program" class="form-control"
                                       value="{{ old('study_program', $alumni->study_program) }}"
                                       placeholder="Contoh: Teknik Informatika">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kota</label>
                                <input type="text" name="higher_education_city" class="form-control"
                                       value="{{ old('higher_education_city', $alumni->higher_education_city) }}"
                                       placeholder="Contoh: Jakarta">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tahun Masuk</label>
                                <input type="number" name="higher_education_year_start" class="form-control"
                                       value="{{ old('higher_education_year_start', $alumni->higher_education_year_start) }}"
                                       min="1990" max="2100" placeholder="2020">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bekerja --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="ri-briefcase-line me-2"></i>Bekerja</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="working_status" class="form-control" required>
                                    <option value="belum" {{ old('working_status', $alumni->working_status) === 'belum' ? 'selected' : '' }}>Belum</option>
                                    <option value="sedang" {{ old('working_status', $alumni->working_status) === 'sedang' ? 'selected' : '' }}>Sedang</option>
                                    <option value="sudah" {{ old('working_status', $alumni->working_status) === 'sudah' ? 'selected' : '' }}>Sudah</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jabatan / Posisi</label>
                                <input type="text" name="occupation" class="form-control"
                                       value="{{ old('occupation', $alumni->occupation) }}"
                                       placeholder="Contoh: Software Engineer">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nama Perusahaan / Instansi</label>
                                <input type="text" name="company_name" class="form-control"
                                       value="{{ old('company_name', $alumni->company_name) }}"
                                       placeholder="Contoh: PT ABC Indonesia">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Alamat Kantor</label>
                                <textarea name="company_address" class="form-control" rows="2"
                                          placeholder="Alamat lengkap perusahaan...">{{ old('company_address', $alumni->company_address) }}</textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kota</label>
                                <input type="text" name="company_city" class="form-control"
                                       value="{{ old('company_city', $alumni->company_city) }}"
                                       placeholder="Contoh: Bandung">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">No. Telepon Kantor</label>
                                <input type="text" name="company_phone" class="form-control"
                                       value="{{ old('company_phone', $alumni->company_phone) }}"
                                       placeholder="021-xxxxx">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gaji per Bulan (Rp)</label>
                                <input type="number" name="monthly_income" class="form-control"
                                       value="{{ old('monthly_income', $alumni->monthly_income) }}"
                                       min="0" placeholder="5000000">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tahun Mulai Bekerja</label>
                                <input type="number" name="working_year_start" class="form-control"
                                       value="{{ old('working_year_start', $alumni->working_year_start) }}"
                                       min="1990" max="2100" placeholder="2022">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="card">
                    <div class="card-body d-flex gap-2 justify-content-between">
                        <a href="{{ route('user.alumni.show', ['userId' => $userId, 'alumniUuid' => $alumni->id]) }}"
                           class="btn btn-light">
                            <i class="ri-arrow-left-line me-1"></i>Batal
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="ri-save-line me-1"></i>Simpan Tracer Study
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
