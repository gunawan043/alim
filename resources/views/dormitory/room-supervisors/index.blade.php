@extends('layouts.master')
@section('title') Wali Kamar — {{ $dormitory->name }} @endsection
@section('css')
<style>
.card-animate { transition: all 0.3s ease; }
.card-animate:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.supervisor-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, #405189, #5b6cb8);
    color: #fff; display: inline-flex; align-items: center; justify-content: center;
    font-weight: 600; font-size: 14px;
}
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Wali Kamar @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-user-star-line text-primary"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Wali Kamar Aktif</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total_active']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-door-open-line text-success"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Kamar</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total_rooms']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-shield-user-line text-warning"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Kamar Terjaga</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['rooms_with_supervisor']) }} <small class="text-muted fs-6">/ {{ $stats['total_rooms'] }}</small></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="card-title mb-0"><i class="ri-shield-user-line me-1"></i> Daftar Wali Kamar</h5>
            <a href="{{ route('user.asrama.room-supervisors.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
               class="btn btn-primary btn-sm">
                <i class="ri-user-add-line me-1"></i> Tetapkan Wali Kamar
            </a>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama/kode kamar..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">-- Semua Status --</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Non-aktif</option>
                        <option value="ended" {{ request('status') === 'ended' ? 'selected' : '' }}>Berakhir</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary btn-sm w-100" type="submit">
                        <i class="ri-search-line me-1"></i> Filter
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Wali Kamar</th>
                            <th>Kamar</th>
                            <th>Blok</th>
                            <th>Periode</th>
                            <th>SK</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supervisors as $s)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="supervisor-avatar">{{ strtoupper(substr($s->user->name ?? '?', 0, 1)) }}</span>
                                        <div>
                                            <a href="{{ route('user.asrama.room-supervisors.profile', ['userId' => $userId, 'supervisorUserUuid' => $s->user_id]) }}"
                                               class="fw-medium text-body">{{ $s->user->name ?? '-' }}</a>
                                            <small class="d-block text-muted">{{ $s->user->email ?? '' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-dark">{{ $s->room->code ?? '-' }}</span>
                                    <small class="d-block text-muted">{{ $s->room->name ?? '' }}</small>
                                </td>
                                <td>{{ $s->room?->wing?->name ?? '-' }}</td>
                                <td>
                                    <small class="d-block">{{ $s->start_date?->format('d M Y') ?? '-' }}</small>
                                    <small class="text-muted">s/d {{ $s->end_date?->format('d M Y') ?? 'Sekarang' }}</small>
                                </td>
                                <td>
                                    @if($s->decree)
                                        <small>{{ $s->decree->number ?? $s->decree->id }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusMap = [
                                            'active' => ['success', 'Aktif'],
                                            'inactive' => ['warning', 'Non-aktif'],
                                            'ended' => ['secondary', 'Berakhir'],
                                        ];
                                        [$bg, $label] = $statusMap[$s->status] ?? ['secondary', ucfirst($s->status)];
                                    @endphp
                                    <span class="badge bg-{{ $bg }}">{{ $label }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('user.asrama.room-supervisors.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'supervisorUuid' => $s->id]) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="ri-shield-user-line" style="font-size:3rem" class="text-muted"></i>
                                    <p class="mt-2 text-muted">Belum ada Wali Kamar yang ditetapkan untuk asrama ini.</p>
                                    <a href="{{ route('user.asrama.room-supervisors.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                       class="btn btn-primary btn-sm mt-2">
                                        <i class="ri-user-add-line me-1"></i> Tetapkan Wali Kamar Pertama
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $supervisors->links() }}
            </div>
        </div>
    </div>
@endsection