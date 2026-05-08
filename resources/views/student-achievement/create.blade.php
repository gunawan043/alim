@extends('layouts.master')
@section('title') Tambah Prestasi — {{ $typeLabel }} @endsection

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Akademik @endslot
    @slot('li_2')
        <a href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => $achievementType]) }}">
            {{ $typeLabel }}
        </a>
    @endslot
    @slot('title') Tambah Prestasi @endslot
@endcomponent

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <div class="col-xl-9">
        <form method="POST" action="{{ route('user.student-achievement.store', ['userId' => $userId, 'type' => $achievementType]) }}"
              enctype="multipart/form-data" id="achievementForm">
            @csrf
            <input type="hidden" name="type" value="{{ $achievementType }}">

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ri-trophy-line me-1"></i> Form Tambah {{ $typeLabel }}
                    </h5>
                </div>
                <div class="card-body">
                    {{-- Student Select2 --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Siswa <span class="text-danger">*</span>
                        </label>
                        <select name="student_id" id="student-select" class="form-select" required>
                            <option value="">-- Ketik nama atau NISN —</option>
                            @foreach($groupedStudents as $sgName => $students)
                                @foreach($students as $s)
                                    <option
                                        value="{{ $s->id }}"
                                        data-nisn="{{ $s->nisn }}"
                                        data-gender="{{ $s->gender }}"
                                        data-gender-text="{{ $s->gender_text }}"
                                        data-birth-place="{{ $s->birth_place }}"
                                        data-birth-date="{{ $s->birth_date?->format('d/m/Y') }}"
                                        data-address="{{ $s->address }}"
                                        {{ old('student_id') == $s->id ? 'selected' : '' }}
                                    >{{ $s->name }} - {{ $sgName }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        @error('student_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        @if(old('student_id'))
                            @php $oldS = \App\Models\Student::find(old('student_id')) @endphp
                            @if($oldS)
                                <div id="selectedStudentInfo" class="mt-2">
                                    <div class="alert alert-success py-2 mb-0 d-flex align-items-center gap-2">
                                        <i class="ri-user-follow-line"></i>
                                        <span>{{ $oldS->name }} &mdash; NISN: {{ $oldS->nisn }}</span>
                                    </div>
                                </div>
                            @endif
                        @else
                            <div id="selectedStudentInfo" class="mt-2 d-none"></div>
                        @endif
                    </div>

                    <div class="row g-3">
                        {{-- Tahun Ajaran --}}
                        <div class="col-md-6">
                            <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                            <select name="academic_year_id" class="form-select" required>
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ $activeYear && $ay->id === $activeYear->id ? 'selected' : '' }}>
                                        {{ $ay->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Event Name --}}
                        <div class="col-12">
                            <label class="form-label">Nama Kegiatan / Kompetisi <span class="text-danger">*</span></label>
                            <input type="text" name="event_name" class="form-control"
                                   value="{{ old('event_name') }}" placeholder="Contoh: Olimpiate Matematika Tingkat Provinsi"
                                   required maxlength="191">
                        </div>

                        {{-- Organizer --}}
                        <div class="col-md-6">
                            <label class="form-label">Penyelenggara</label>
                            <input type="text" name="organizer" class="form-control"
                                   value="{{ old('organizer') }}" placeholder="Contoh: Dinas Pendidikan Prov. Jawa Barat" maxlength="191">
                        </div>

                        {{-- Event Date --}}
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Kegiatan <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" class="form-control"
                                   value="{{ old('event_date') }}" required>
                        </div>

                        {{-- Event Location --}}
                        <div class="col-md-3">
                            <label class="form-label">Lokasi</label>
                            <input type="text" name="event_location" class="form-control"
                                   value="{{ old('event_location') }}" placeholder="Contoh: Jakarta" maxlength="191">
                        </div>

                        {{-- Level --}}
                        <div class="col-md-4">
                            <label class="form-label">Tingkat <span class="text-danger">*</span></label>
                            <select name="level" class="form-select" required>
                                <option value="">— Pilih Tingkat —</option>
                                <option value="internal" {{ old('level')=='internal' ? 'selected' : '' }}>Internal / Sekolah</option>
                                <option value="kecamatan" {{ old('level')=='kecamatan' ? 'selected' : '' }}>Kecamatan</option>
                                <option value="kabupaten_kota" {{ old('level')=='kabupaten_kota' ? 'selected' : '' }}>Kabupaten/Kota</option>
                                <option value="provinsi" {{ old('level')=='provinsi' ? 'selected' : '' }}>Provinsi</option>
                                <option value="nasional" {{ old('level')=='nasional' ? 'selected' : '' }}>Nasional</option>
                                <option value="internasional" {{ old('level')=='internasional' ? 'selected' : '' }}>Internasional</option>
                            </select>
                        </div>

                        {{-- Position --}}
                        <div class="col-md-4">
                            <label class="form-label">Predikat <span class="text-danger">*</span></label>
                            <select name="position" class="form-select" required>
                                <option value="">— Pilih Predikat —</option>
                                <option value="mumtaz_murtafi" {{ old('position')=='mumtaz_murtafi' ? 'selected' : '' }}>Mumtaz Murtafi'</option>
                                <option value="juara_1" {{ old('position')=='juara_1' ? 'selected' : '' }}>Juara 1</option>
                                <option value="juara_2" {{ old('position')=='juara_2' ? 'selected' : '' }}>Juara 2</option>
                                <option value="juara_3" {{ old('position')=='juara_3' ? 'selected' : '' }}>Juara 3</option>
                                <option value="harapan_1" {{ old('position')=='harapan_1' ? 'selected' : '' }}>Harapan 1</option>
                                <option value="harapan_2" {{ old('position')=='harapan_2' ? 'selected' : '' }}>Harapan 2</option>
                                <option value="harapan_3" {{ old('position')=='harapan_3' ? 'selected' : '' }}>Harapan 3</option>
                                <option value="peserta" {{ old('position')=='peserta' ? 'selected' : '' }}>Peserta</option>
                                <option value="lainnya" {{ old('position')=='lainnya' ? 'selected' : '' }}>Lainnya</option>
                            </select>
                        </div>

                        {{-- Position Detail --}}
                        <div class="col-md-4">
                            <label class="form-label">Detail Peringkat</label>
                            <input type="text" name="position_detail" class="form-control"
                                   value="{{ old('position_detail') }}" placeholder="Contoh: Medali Emas">
                        </div>

                        {{-- Coach --}}
                        <div class="col-md-6">
                            <label class="form-label">Guru Pembimbing</label>
                            <select name="coach_id" class="form-select">
                                <option value="">— Tidak ada —</option>
                                @foreach($coaches as $coach)
                                    <option value="{{ $coach->id }}" {{ old('coach_id')==$coach->id ? 'selected' : '' }}>
                                        {{ $coach->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Certificate Upload --}}
                        <div class="col-md-6">
                            <label class="form-label">File Piagam / Sertifikat</label>
                            <input type="file" name="certificate" class="form-control"
                                   accept=".jpg,.jpeg,.png,.pdf" max="5120">
                            <div class="form-text">Format: JPG, PNG, PDF — Maksimal 5 MB</div>
                        </div>

                        {{-- Notes --}}
                        <div class="col-12">
                            <label class="form-label">Keterangan / Catatan</label>
                            <textarea name="notes" class="form-control" rows="2"
                                      placeholder="Tambahkan keterangan tambahan...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => $achievementType]) }}"
                       class="btn btn-light">
                        <i class="ri-arrow-left-line me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="ri-save-line me-1"></i> Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Sidebar: info --}}
    <div class="col-xl-3">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Petunjuk Pengisian</h6></div>
            <div class="card-body small">
                <ul class="mb-0 ps-3 text-muted">
                    <li class="mb-2">Cari siswa dengan mengetik <strong>NISN</strong> atau <strong>Nama</strong>.</li>
                    <li class="mb-2">Pilih lomba/kompetisi yang diikuti.</li>
                    <li class="mb-2">Pilih tingkat sesuai tingkatan kompetisi.</li>
                    <li class="mb-2">Unggah piagam dalam format JPG/PNG/PDF.</li>
                    <li>Gunakan menu <strong>Import Massal</strong> untuk input banyak data sekaligus.</li>
                </ul>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0">Tipe Prestasi</h6></div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-trophy-line text-warning fs-4"></i>
                    <div>
                        <div class="fw-semibold">{{ $typeLabel }}</div>
                        <div class="text-muted small">{{ $achievementType === 'akademik' ? 'Olimpiade, Lomba Sains, dll' : 'Hafalan Al-Quran / Hadits' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(function() {
    'use strict';

    // ── Student Select2 ────────────────────────────────────────
    $('#student-select').select2({
        placeholder: '-- Ketik nama atau NISN —',
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() { return 'Siswa tidak ditemukan'; },
        },
    });

    $('#student-select').on('select2:select', function(e) {
        var opt = e.params.data.element;
        var el = opt ? opt : this.options[this.selectedIndex];
        var info = document.getElementById('selectedStudentInfo');
        if (info) {
            var name = e.params.data.text.split(' - ')[0];
            var nisn = el ? (el.getAttribute('data-nisn') || '') : '';
            info.innerHTML = '<div class="alert alert-success py-2 mb-0 d-flex align-items-center gap-2">' +
                '<i class="ri-user-follow-line"></i>' +
                '<span>' + name + ' &mdash; NISN: ' + nisn + '</span></div>';
            info.classList.remove('d-none');
        }
    });

    $('#student-select').on('select2:clear', function(e) {
        var info = document.getElementById('selectedStudentInfo');
        if (info) { info.classList.add('d-none'); }
    });

})();
</script>
@endsection