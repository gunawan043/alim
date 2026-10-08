@extends('layouts.master')
@section('title') Detail Perangkat Pembelajaran @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') Perangkat @endslot
        @slot('title') {{ $perangkat->judul }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h5 class="mb-1">{{ $perangkat->judul }}</h5>
                    <div class="text-muted small">
                        {{ $perangkat->subject?->name }}
                        · {{ $perangkat->studyGroup?->name ?? $perangkat->gradeLevel?->name ?? 'Semua Kelas' }}
                        · {{ $perangkat->academicYear?->name }}
                        · Semester {{ ucfirst($perangkat->semester) }}
                        · Guru: {{ $perangkat->teacher?->name ?? '—' }}
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge {{ $perangkat->status === 'final' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                        {{ \App\Models\PerangkatPembelajaran::STATUS_OPTIONS[$perangkat->status] ?? $perangkat->status }}
                    </span>
                    @if($perangkat->atp)
                        <a href="{{ route('user.kurikulum.atp.show', ['userId' => $userId, 'id' => $perangkat->atp->id]) }}" class="btn btn-sm btn-outline-primary">
                            <i class="ri-link me-1"></i> ATP ({{ $perangkat->atp->total_jp }} JP)
                        </a>
                    @endif
                    <a href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId, 'academic_year_id' => $perangkat->academic_year_id, 'semester' => $perangkat->semester]) }}" class="btn btn-sm btn-light">Kembali</a>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('user.kurikulum.perangkat.update', ['userId' => $userId, 'id' => $perangkat->id]) }}">
        @csrf @method('PUT')

        <div class="row g-3 mb-3">
            <div class="col-md-5">
                <label class="form-label">Judul <span class="text-danger">*</span></label>
                <input type="text" name="judul" class="form-control" value="{{ $perangkat->judul }}" required maxlength="255">
            </div>
            <div class="col-md-4">
                <label class="form-label">Kelas</label>
                <select name="study_group_id" class="form-select">
                    <option value="">— Semua Kelas —</option>
                    @foreach($studyGroups as $group)
                        <option value="{{ $group->id }}" {{ $perangkat->study_group_id === $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="draft" {{ $perangkat->status === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="final" {{ $perangkat->status === 'final' ? 'selected' : '' }}>Final</option>
                </select>
            </div>
        </div>

        @php
            $sections = \App\Models\PerangkatPembelajaran::DESAIN_SECTIONS;
            $groups = [
                'Berkesadaran (Mindful)' => ['pertanyaan_pemantik', 'pemahaman_bermakna'],
                'Pengalaman Belajar Mendalam (Memahami → Mengaplikasi → Merefleksi)' => ['pengalaman_memahami', 'pengalaman_mengaplikasi', 'pengalaman_refleksi'],
                'Bermakna (Meaningful)' => ['konteks_nyata', 'asesmen_formatif', 'asesmen_sumatif'],
                'Menggembirakan & Dukungan Belajar' => ['diferensiasi', 'media_sumber'],
            ];
        @endphp

        @foreach($groups as $groupLabel => $keys)
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">{{ $groupLabel }}</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($keys as $key)
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">{{ $sections[$key] }}</label>
                                <textarea name="desain[{{ $key }}]" rows="3" class="form-control" maxlength="5000">{{ $perangkat->desainValue($key) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <div class="mb-3">
            <label class="form-label">Catatan</label>
            <textarea name="catatan" rows="2" class="form-control" maxlength="2000">{{ $perangkat->catatan }}</textarea>
        </div>

        <div class="d-flex gap-2 mb-4">
            <button class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan Perangkat</button>
            <a href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId, 'academic_year_id' => $perangkat->academic_year_id, 'semester' => $perangkat->semester]) }}" class="btn btn-light">Batal</a>
        </div>
    </form>

    <form method="POST" action="{{ route('user.kurikulum.perangkat.destroy', ['userId' => $userId, 'id' => $perangkat->id]) }}"
          onsubmit="return confirm('Hapus perangkat ini?');">
        @csrf @method('DELETE')
        <button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line me-1"></i> Hapus Perangkat</button>
    </form>
@endsection
