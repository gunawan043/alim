@extends('layouts.master')
@section('title') Edit Prestasi — {{ $achievement->type_label }} @endsection

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@php $typeLabel = $achievement->type_label; @endphp

@component('components.breadcrumb')
    @slot('li_1') Akademik @endslot
    @slot('li_2')
        <a href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => request('type', 'akademik')]) }}">
            {{ $typeLabel }}
        </a>
    @endslot
    @slot('li_3') <a href="{{ route('user.student-achievement.show', ['userId' => $userId, 'id' => $achievement->id]) }}">{{ $achievement->event_name }}</a> @endslot
    @slot('title') Edit @endslot
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
        <form method="POST"
              action="{{ route('user.student-achievement.update', ['userId' => $userId, 'id' => $achievement->id, 'type' => request('type', 'akademik')]) }}"
              enctype="multipart/form-data" id="achievementForm">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-pencil-line me-1"></i> Edit {{ $typeLabel }}</h5>
                </div>
                <div class="card-body">
                    {{-- Student Info (readonly) --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Siswa</label>
                        <div class="p-2 bg-light rounded d-flex align-items-center gap-2">
                            <i class="ri-user-line text-muted"></i>
                            <div>
                                <div class="fw-semibold">{{ $achievement->student->name ?? '-' }}</div>
                                <div class="small text-muted">
                                    NISN: {{ $achievement->student->nisn ?? '-' }}
                                    | Kelas: {{ $achievement->student->classHistories->firstWhere('is_active', true)?->studyGroup?->full_name ?? '-' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                            <select name="academic_year_id" class="form-select" required>
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ $achievement->academic_year_id == $ay->id ? 'selected' : '' }}>
                                        {{ $ay->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Nama Kegitan / Kompetisi <span class="text-danger">*</span></label>
                            <input type="text" name="event_name" class="form-control"
                                   value="{{ old('event_name', $achievement->event_name) }}" required maxlength="191">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Penyelenggara</label>
                            <input type="text" name="organizer" class="form-control"
                                   value="{{ old('organizer', $achievement->organizer) }}" maxlength="191">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Tanggal Kegiatan <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" class="form-control"
                                   value="{{ old('event_date', $achievement->event_date?->format('Y-m-d')) }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Lokasi</label>
                            <input type="text" name="event_location" class="form-control"
                                   value="{{ old('event_location', $achievement->event_location) }}" maxlength="191">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tingkat <span class="text-danger">*</span></label>
                            <select name="level" class="form-select" required>
                                @foreach(['internal','kecamatan','kabupaten_kota','provinsi','nasional','internasional'] as $lvl)
                                    <option value="{{ $lvl }}" {{ old('level', $achievement->level) == $lvl ? 'selected' : '' }}>
                                        {{ match($lvl){'internal'=>'Internal','kecamatan'=>'Kecamatan','kabupaten_kota'=>'Kabupaten/Kota','provinsi'=>'Provinsi','nasional'=>'Nasional','internasional'=>'Internasional'} }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Predikat <span class="text-danger">*</span></label>
                            <select name="position" class="form-select" required>
                                @foreach(['mumtaz_murtafi', 'juara_1','juara_2','juara_3','harapan_1','harapan_2','harapan_3','peserta','lainnya'] as $pos)
                                    <option value="{{ $pos }}" {{ old('position', $achievement->position) == $pos ? 'selected' : '' }}>
                                        {{ match($pos){
                                'mumtaz_murtafi' => 'Mumtaz Murtafi',
                                'juara_1' => 'Juara 1',
                                'juara_2' => 'Juara 2',
                                'juara_3' => 'Juara 3',
                                'harapan_1' => 'Harapan 1',
                                'harapan_2' => 'Harapan 2',
                                'harapan_3' => 'Harapan 3',
                                'peserta' => 'Peserta',
                                'lainnya' => 'Lainnya',
                            } }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Detail Peringkat</label>
                            <input type="text" name="position_detail" class="form-control"
                                   value="{{ old('position_detail', $achievement->position_detail) }}" placeholder="Contoh: Medali Emas">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Guru Pembimbing</label>
                            <select name="coach_id" class="form-select">
                                <option value="">— Tidak ada —</option>
                                @foreach($coaches as $coach)
                                    <option value="{{ $coach->id }}" {{ $achievement->coach_id == $coach->id ? 'selected' : '' }}>
                                        {{ $coach->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Ganti Piagam / Sertifikat</label>
                            <input type="file" name="certificate" class="form-control"
                                   accept=".jpg,.jpeg,.png,.pdf" max="5120">
                            @if($achievement->certificate_path)
                                <div class="form-text">
                                    Piagam saat ini:
                                    <a href="{{ $achievement->certificate_url }}" target="_blank" class="text-decoration-none">
                                        <i class="ri-image-line me-1"></i> Lihat piagam tersimpan
                                    </a>
                                </div>
                            @else
                                <div class="form-text">Belum ada piagam tersimpan.</div>
                            @endif
                        </div>

                        <div class="col-12">
                            <label class="form-label">Keterangan / Catatan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $achievement->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('user.student-achievement.show', ['userId' => $userId, 'id' => $achievement->id, 'type' => request('type', 'akademik')]) }}"
                       class="btn btn-light">
                        <i class="ri-arrow-left-line me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line me-1"></i> Perbarui
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="col-xl-3">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Info Prestasi</h6></div>
            <div class="card-body small">
                <dl class="mb-0">
                    <dt class="text-muted">Jenis</dt>
                    <dd class="fw-semibold">{{ $typeLabel }}</dd>
                    <dt class="text-muted mt-2">Dibuat</dt>
                    <dd>{{ $achievement->created_at->locale('id')->format('d M Y H:i') }}</dd>
                    @if($achievement->academicYear)
                        <dt class="text-muted mt-2">Tahun Ajaran</dt>
                        <dd>{{ $achievement->academicYear->name }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection