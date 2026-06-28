@extends('layouts.app')

@section('title', 'Jadwal Mengajar — ' . e($teacher->name))

@section('content')

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Jadwal Mengajar</h4>
        <div>
            <small class="text-muted">{{ $activeAy?->name ?? 'TA Aktif' }}</small>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body bg-light">
            <div class="row g-3">
                <div class="col-md-6">
                    <strong>{{ $teacher->name }}</strong>
                </div>
                <div class="col-md-6">
                    @if($teacher->roles->first())
                    Role: <span class="badge bg-primary">{{ $teacher->roles->first()->name }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($jadwals->isEmpty())
    <div class="alert alert-info">Tidak ada jadwal mengajar untuk guru ini pada TA aktif.</div>
    @else
    <div class="row g-3">
        @foreach($jadwals as $dayNum => $items)
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <strong>{{ $days[$dayNum] ?? '-' }}</strong>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($items as $j)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <small class="fw-bold text-primary">
                                Jam ke {{ $j->slot_index }} ({{ $j->start_time }} — {{ $j->end_time }})
                            </small>
                        </div>
                        <div class="mt-1">
                            {{ $j->studyGroup->full_name }}
                            <small class="text-muted">— Rom{{ $j->studyGroup->name }}</small>
                        </div>
                        <div class="small text-success">
                            {{ $j->subject->name }}
                            @if($j->subject->group)
                            <span class="badge bg-secondary">{{ $j->subject->group }}</span>
                            @endif
                        </div>
                        @if($j->room)
                        <div class="small text-muted">Ruang: {{ $j->room }}</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

@endsection
