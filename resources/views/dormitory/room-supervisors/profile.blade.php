@extends('layouts.master')
@section('title') Profil Wali Kamar — {{ $supervisorUser->name }} @endsection
@section('css')
<style>
.profile-avatar {
    width: 96px; height: 96px; border-radius: 50%;
    background: linear-gradient(135deg, #405189, #5b6cb8);
    color: #fff; display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 36px;
}
.room-block {
    border-left: 4px solid #405189;
    padding-left: 1rem;
    margin-bottom: 1.5rem;
}
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.index', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.room-supervisors.index', ['userId' => $userId, 'asramaUuid' => $activeAssignments->first()?->dormitory_id ?? '']) }}">Wali Kamar</a> @endslot
        @slot('title') Profil Wali Kamar @endslot
    @endcomponent

    {{-- Profil Pegawai --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="profile-avatar">{{ strtoupper(substr($supervisorUser->name ?? '?', 0, 1)) }}</span>
                <div class="flex-grow-1">
                    <h4 class="mb-1">{{ $supervisorUser->name }}</h4>
                    <p class="text-muted mb-0">
                        <i class="ri-mail-line me-1"></i> {{ $supervisorUser->email ?? '-' }}
                        @if($supervisorUser->employment)
                            <span class="mx-2">·</span>
                            <i class="ri-briefcase-line me-1"></i> {{ $supervisorUser->employment->position ?? 'GTK' }}
                        @endif
                    </p>
                </div>
                <div class="d-flex gap-3 text-center">
                    <div>
                        <div class="text-muted small">Kamar</div>
                        <h4 class="mb-0 text-primary">{{ $stats['rooms_count'] }}</h4>
                    </div>
                    <div>
                        <div class="text-muted small">Santri</div>
                        <h4 class="mb-0 text-success">{{ $stats['students_count'] }}</h4>
                    </div>
                    <div>
                        <div class="text-muted small">Asrama</div>
                        <h4 class="mb-0 text-warning">{{ $stats['dormitories_count'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kamar yang dibina --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="ri-door-open-line me-1"></i> Kamar yang Menjadi Tanggung Jawab</h5>
        </div>
        <div class="card-body">
            @if($activeAssignments->isEmpty())
                <div class="text-center py-5">
                    <i class="ri-shield-user-line" style="font-size:3rem" class="text-muted"></i>
                    <p class="mt-2 text-muted">Pegawai ini belum memiliki penugasan Wali Kamar aktif.</p>
                </div>
            @else
                @foreach($activeAssignments as $assignment)
                    @php
                        $room = $assignment->room;
                        $residents = $residentsByRoom[$room->id] ?? collect();
                    @endphp
                    <div class="room-block">
                        <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
                            <div>
                                <h5 class="mb-1">
                                    <span class="badge bg-dark me-1">{{ $room->code }}</span>
                                    {{ $room->name ?? '' }}
                                </h5>
                                <small class="text-muted">
                                    <i class="ri-building-line me-1"></i> {{ $assignment->dormitory->name ?? '-' }}
                                    @if($room->wing) · {{ $room->wing->name }} @endif
                                    @if($room->floor !== null) · Lantai {{ $room->floor }} @endif
                                </small>
                            </div>
                            <a href="{{ route('user.asrama.room-supervisors.show', ['userId' => $userId, 'asramaUuid' => $assignment->dormitory_id, 'supervisorUuid' => $assignment->id]) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="ri-eye-line me-1"></i> Detail Penugasan
                            </a>
                        </div>

                        @if($residents->isEmpty())
                            <p class="text-muted small mb-0">Belum ada penghuni.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Santri</th>
                                            <th>NIS</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($residents as $r)
                                            <tr>
                                                <td>{{ $r->student->name ?? '-' }}</td>
                                                <td>{{ $r->student->nis ?? '-' }}</td>
                                                <td>
                                                    @if($r->is_active)
                                                        <span class="badge bg-success">Aktif</span>
                                                    @else
                                                        <span class="badge bg-secondary">Non-aktif</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endsection