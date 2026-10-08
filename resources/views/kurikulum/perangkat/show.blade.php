@extends('layouts.master')
@section('title', 'Detail Perangkat Pembelajaran')

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $sections = \App\Models\PerangkatPembelajaran::DESAIN_SECTIONS;
        $groups = [
            'Berkesadaran (Mindful)' => [
                'icon' => 'ri-focus-3-line',
                'desc' => 'Membangun kesadaran belajar: pertanyaan pemantik dan pemahaman bermakna.',
                'keys' => ['pertanyaan_pemantik', 'pemahaman_bermakna'],
            ],
            'Pengalaman Belajar Mendalam' => [
                'icon' => 'ri-compasses-2-line',
                'desc' => 'Siklus memahami → mengaplikasi → merefleksi.',
                'keys' => ['pengalaman_memahami', 'pengalaman_mengaplikasi', 'pengalaman_refleksi'],
            ],
            'Bermakna (Meaningful)' => [
                'icon' => 'ri-links-line',
                'desc' => 'Koneksi konteks nyata serta asesmen formatif &amp; sumatif.',
                'keys' => ['konteks_nyata', 'asesmen_formatif', 'asesmen_sumatif'],
            ],
            'Menggembirakan &amp; Dukungan Belajar' => [
                'icon' => 'ri-emotion-happy-line',
                'desc' => 'Diferensiasi dan media/sumber belajar.',
                'keys' => ['diferensiasi', 'media_sumber'],
            ],
        ];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') Perangkat @endslot
        @slot('title') {{ $perangkat->judul }} @endslot
    @endcomponent

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ $perangkat->judul }}</h4>
            <p class="text-muted mb-0 small">
                {{ $perangkat->subject?->name }}
                · {{ $perangkat->studyGroup?->name ?? $perangkat->gradeLevel?->name ?? 'Semua Kelas' }}
                · {{ $perangkat->academicYear?->name }}
                · Semester {{ ucfirst($perangkat->semester) }}
                · Guru: {{ $perangkat->teacher?->name ?? '—' }}
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge {{ $perangkat->status === 'final' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} p-2">
                {{ \App\Models\PerangkatPembelajaran::STATUS_OPTIONS[$perangkat->status] ?? $perangkat->status }}
            </span>
            @if($perangkat->atp)
                <a href="{{ route('user.kurikulum.atp.show', ['userId' => $userId, 'id' => $perangkat->atp->id]) }}" class="btn btn-soft-primary btn-sm">
                    <i class="ri-link align-bottom me-1"></i> ATP ({{ $perangkat->atp->total_jp }} JP)
                </a>
            @endif
            <a href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId, 'academic_year_id' => $perangkat->academic_year_id, 'semester' => $perangkat->semester]) }}" class="btn btn-light btn-sm">
                <i class="ri-arrow-left-line align-bottom me-1"></i> Kembali
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.kurikulum.perangkat.update', ['userId' => $userId, 'id' => $perangkat->id]) }}">
        @csrf @method('PUT')

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-information-line text-primary me-1"></i> Informasi Perangkat</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-5">
                        <label class="form-label">Judul <span class="text-danger">*</span></label>
                        <input type="text" name="judul" class="form-control" value="{{ $perangkat->judul }}" required maxlength="255">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Kelas</label>
                        <select name="study_group_id" class="form-select">
                            <option value="">— Semua Kelas —</option>
                            @foreach($studyGroups as $group)
                                <option value="{{ $group->id }}" {{ $perangkat->study_group_id === $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft" {{ $perangkat->status === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="final" {{ $perangkat->status === 'final' ? 'selected' : '' }}>Final</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea name="catatan" rows="2" class="form-control" maxlength="2000">{{ $perangkat->catatan }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        @foreach($groups as $groupLabel => $group)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="{{ $group['icon'] }} text-primary me-1"></i> {!! $groupLabel !!}</h5>
                    <p class="text-muted mb-0 small mt-1">{!! $group['desc'] !!}</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($group['keys'] as $key)
                            <div class="col-md-6">
                                <label class="form-label">{{ $sections[$key] }}</label>
                                <textarea name="desain[{{ $key }}]" rows="3" class="form-control" maxlength="5000">{{ $perangkat->desainValue($key) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-success">
                <i class="ri-save-line align-bottom me-1"></i> Simpan Perangkat
            </button>
            <a href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId, 'academic_year_id' => $perangkat->academic_year_id, 'semester' => $perangkat->semester]) }}" class="btn btn-light">Batal</a>
        </div>
    </form>

    <form method="POST" action="{{ route('user.kurikulum.perangkat.destroy', ['userId' => $userId, 'id' => $perangkat->id]) }}"
          onsubmit="return confirm('Hapus perangkat ini?');">
        @csrf @method('DELETE')
        <button class="btn btn-soft-danger btn-sm"><i class="ri-delete-bin-line align-bottom me-1"></i> Hapus Perangkat</button>
    </form>
@endsection
