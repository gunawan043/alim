@extends('layouts.master')
@section('title') Detail Wali Kamar @endsection
@section('css')
<style>
.supervisor-avatar-lg {
    width: 64px; height: 64px; border-radius: 50%;
    background: linear-gradient(135deg, #405189, #5b6cb8);
    color: #fff; display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 24px;
}
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.room-supervisors.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Wali Kamar</a> @endslot
        @slot('title') Detail Penetapan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="row g-3">
        {{-- Profil Wali Kamar --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <span class="supervisor-avatar-lg mb-2">{{ strtoupper(substr($supervisor->user->name ?? '?', 0, 1)) }}</span>
                    <h5 class="mt-2 mb-1">{{ $supervisor->user->name ?? '-' }}</h5>
                    <p class="text-muted mb-2 small">{{ $supervisor->user->email ?? '' }}</p>

                    <hr>

                    <table class="table table-borderless table-sm text-start mb-0">
                        <tr>
                            <th class="text-muted" style="width:140px;">Kamar</th>
                            <td>
                                @if($room)
                                    <span class="badge bg-dark">{{ $room->code }}</span>
                                    {{ $room->name ?? '' }}
                                    <small class="d-block text-muted">{{ $room->wing?->name ?? '' }}</small>
                                @else — @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Tahun Ajaran</th>
                            <td>{{ $supervisor->academicYear->name ?? $supervisor->academic_year_id }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Mulai</th>
                            <td>{{ $supervisor->start_date?->format('d M Y') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Selesai</th>
                            <td>{{ $supervisor->end_date?->format('d M Y') ?? 'Sekarang' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Status</th>
                            <td>
                                @php
                                    $statusMap = [
                                        'active' => ['success', 'Aktif'],
                                        'inactive' => ['warning', 'Non-aktif'],
                                        'ended' => ['secondary', 'Berakhir'],
                                    ];
                                    [$bg, $label] = $statusMap[$supervisor->status] ?? ['secondary', ucfirst($supervisor->status)];
                                @endphp
                                <span class="badge bg-{{ $bg }}">{{ $label }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">SK</th>
                            <td>
                                @if($supervisor->decree)
                                    {{ $supervisor->decree->number ?? $supervisor->decree->id }}
                                @else <span class="text-muted">—</span> @endif
                            </td>
                        </tr>
                    </table>

                    @if($supervisor->notes)
                        <hr>
                        <p class="text-start small text-muted mb-0"><strong>Catatan:</strong><br>{{ $supervisor->notes }}</p>
                    @endif
                </div>
                <div class="card-footer d-flex gap-2 justify-content-between">
                    <a href="{{ route('user.asrama.room-supervisors.profile', ['userId' => $userId, 'supervisorUserUuid' => $supervisor->user_id]) }}"
                       class="btn btn-sm btn-outline-info">
                        <i class="ri-user-line me-1"></i> Profil Pegawai
                    </a>
                    <div class="d-flex gap-1">
                        <a href="{{ route('user.asrama.room-supervisors.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'supervisorUuid' => $supervisor->id]) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="ri-edit-line"></i>
                        </a>
                        @if($supervisor->status === 'active')
                            <form method="POST" action="{{ route('user.asrama.room-supervisors.end', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'supervisorUuid' => $supervisor->id]) }}"
                                  onsubmit="return confirm('Akhiri penugasan Wali Kamar ini?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Akhiri Penugasan">
                                    <i class="ri-stop-circle-line"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Statistik & daftar penghuni kamar --}}
        <div class="col-lg-8">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body py-3 text-center">
                            <small class="text-muted text-uppercase">Total Penghuni</small>
                            <h3 class="fw-bold mb-0">{{ $stats['total_residents'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body py-3 text-center">
                            <small class="text-muted text-uppercase">Di Asrama</small>
                            <h3 class="fw-bold mb-0 text-success">{{ $stats['in_dormitory'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body py-3 text-center">
                            <small class="text-muted text-uppercase">Izin Pulang</small>
                            <h3 class="fw-bold mb-0 text-warning">{{ $stats['on_permit'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-team-line me-1"></i> Daftar Penghuni Kamar</h5></div>
                <div class="card-body">
                    @if($residents->isEmpty())
                        <p class="text-muted text-center py-4 mb-0">Belum ada penghuni di kamar ini.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle table-hover">
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
                                            <td>
                                                @if($r->student)
                                                    <a href="{{ route('user.asrama.residents.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'residentUuid' => $r->id]) }}"
                                                       class="fw-medium text-body">
                                                        {{ $r->student->name }}
                                                    </a>
                                                @else — @endif
                                            </td>
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
            </div>

            <div class="mt-3 d-flex gap-2">
                @if($room)
                    <a href="{{ route('user.asrama.rooms.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => $room->id]) }}"
                       class="btn btn-outline-secondary">
                        <i class="ri-door-open-line me-1"></i> Lihat Detail Kamar
                    </a>
                @endif
                <a href="{{ route('user.asrama.room-supervisors.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                   class="btn btn-outline-secondary">
                    <i class="ri-arrow-left-line me-1"></i> Kembali ke Daftar
                </a>
            </div>
        </div>
    </div>
@endsection