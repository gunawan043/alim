@extends('layouts.horizontal')

@section('page-title', 'Jadwal KBM')

@section('page-heading')
    <!-- Page heading row -->
    <div class="row px-xl-1">
        <div class="col-xl-12">
            <ol class="d-flex flex-wrap list-inline list-inline-breadcrumb mb-1">
                <li class="list-inline-item"><a href="{{ route('root', ['userId' => $userId ?? auth()->user()->id]) }}">Home</a></li>
                <li class="list-inline-item"><span>Jadwal KBM</span></li>
            </ol>
            <h5 class="app-page-title h3">Jadwal Kegiatan Belajar</h5>
        </div>
    </div>
@endsection

@section('page-content')
<div class="app-card p-4">
    {{-- Filter: Tahun Ajaran --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <form method="GET">
                <label>Tahun Ajaran</label>
                <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($activeAy as $ay)
                        <option value="{{ $ay->id }}" {{ ($ay->id == request('academic_year_id')) ? 'selected' : '' }}>
                            {{ $ay->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    {{-- Button --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">Daftar Jadwal per Rombelong</h6>
        <a href="{{ route('jadwal-kbm.generate-index', ['userId' => $userId ?? auth()->user()->id]) }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-wand-magic-sparkles"></i> Generate Jadwal
        </a>
    </div>

    {{-- Cards --}}
    @forelse($jadwals as $sgId => $jadwalsGroup)
        @php
            $sg = $jadwalsGroup->first()?->studyGroup;
        @endphp
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>{{ $sg->full_name ?? $sg->name }}</strong>
                <small class="text-muted">{{ $sg->gradeLevel->name ?? '-' }}</small>
            </div>
            <div class="card-body">
                @if($jadwalsGroup->count() > 0)
                    <span class="badge bg-success">{{ $jadwalsGroup->count() }} slot</span>
                    <a href="{{ route('jadwal-kbm.show', ['userId' => $userId ?? auth()->user()->id, 'studyGroupId' => $sgId]) }}" class="btn btn-outline-primary btn-sm float-end">Lihat Jadwal</a>
                @else
                    <span class="text-muted">Belum ada jadwal</span>
                @endif
            </div>
        </div>
    @empty
        <p class="text-muted">Belum ada jadwal yang digenerate.</p>
    @endforelse
</div>
@endsection
