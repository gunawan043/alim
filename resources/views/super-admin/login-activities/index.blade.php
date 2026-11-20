@extends('layouts.master')

@section('title', 'Aktivitas Login')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('li_2') Log & Monitoring @endslot
        @slot('title') Aktivitas Login @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="ri-alert-line me-1"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.66rem;letter-spacing:.5px">Aktivitas Hari Ini</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($stats['today']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.66rem;letter-spacing:.5px">Login Berhasil</p>
                    <h4 class="mb-0 fw-bold text-success">{{ number_format($stats['success_today']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.66rem;letter-spacing:.5px">Login Gagal</p>
                    <h4 class="mb-0 fw-bold text-danger">{{ number_format($stats['failed_today']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.66rem;letter-spacing:.5px">Mencurigakan 7 Hari</p>
                    <h4 class="mb-0 fw-bold text-warning">{{ number_format($stats['suspicious_week']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.66rem;letter-spacing:.5px">Akun Terkunci</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($stats['locked_users']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.66rem;letter-spacing:.5px">IP Diblokir</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($stats['blocked_ips']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0"><i class="ri-lock-user-line text-warning me-1"></i>Akun Terkunci</h6>
                        <small class="text-muted">Buka akun yang terkunci agar pengguna bisa login kembali.</small>
                    </div>
                    <span class="badge bg-warning-subtle text-warning">{{ $lockedUsers->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Pengguna</th>
                                    <th>Percobaan</th>
                                    <th>Terkunci Sampai</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lockedUsers as $lockedUser)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $lockedUser->name }}</div>
                                            <small class="text-muted">{{ $lockedUser->email }}</small>
                                        </td>
                                        <td><span class="badge bg-danger-subtle text-danger">{{ $lockedUser->failed_login_attempts }}x</span></td>
                                        <td class="small text-muted">{{ $lockedUser->locked_until?->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('user.sa.login-activities.users.unlock', ['userId' => $userId, 'targetId' => $lockedUser->id]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-soft-success" onclick="return confirm('Buka akun {{ $lockedUser->name }}?')">
                                                    <i class="ri-lock-unlock-line me-1"></i>Buka
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted small py-3">Tidak ada akun terkunci.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0"><i class="ri-shut-down-line text-danger me-1"></i>IP Diblokir</h6>
                        <small class="text-muted">Blokir manual atau buka blokir IP tertentu.</small>
                    </div>
                    <span class="badge bg-danger-subtle text-danger">{{ $blockedIps->count() }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>IP</th><th>Sampai</th><th class="text-end">Aksi</th></tr>
                            </thead>
                            <tbody>
                                @forelse($blockedIps as $blockedIp)
                                    <tr>
                                        <td><code>{{ $blockedIp->ip_address }}</code></td>
                                        <td class="small text-muted">{{ $blockedIp->locked_until?->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('user.sa.login-activities.ips.unblock', ['userId' => $userId]) }}">
                                                @csrf
                                                <input type="hidden" name="ip_address" value="{{ $blockedIp->ip_address }}">
                                                <button class="btn btn-sm btn-soft-success" onclick="return confirm('Buka blokir IP {{ $blockedIp->ip_address }}?')">Buka</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted small py-3">Tidak ada IP diblokir.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <form method="POST" action="{{ route('user.sa.login-activities.ips.block', ['userId' => $userId]) }}" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <label class="form-label small mb-1">Blokir IP Baru</label>
                            <input type="text" name="ip_address" class="form-control form-control-sm" placeholder="contoh: 103.10.20.30" required>
                        </div>
                        <div class="col-6">
                            <input type="number" name="minutes" class="form-control form-control-sm" value="60" min="1" max="1440" required>
                        </div>
                        <div class="col-6">
                            <input type="text" name="reason" class="form-control form-control-sm" placeholder="Alasan (opsional)">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-sm btn-soft-danger w-100"><i class="ri-forbid-line me-1"></i>Blokir IP</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-3">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h6 class="mb-0"><i class="ri-spy-line text-warning me-1"></i>Perlu Perhatian</h6>
                    <small class="text-muted">IP dengan kegagalan login terbanyak (24 jam).</small>
                </div>
                <div class="card-body">
                    @forelse($topFailedIps as $failedIp)
                        <div class="d-flex align-items-center justify-content-between py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <code>{{ $failedIp->ip_address ?? '-' }}</code>
                                <div class="small text-muted">Percobaan gagal</div>
                            </div>
                            <span class="badge bg-warning-subtle text-warning">{{ $failedIp->attempts }}x</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Belum ada kegagalan login dalam 24 jam terakhir.</p>
                    @endforelse

                    @if($suspiciousRecent->isNotEmpty())
                        <hr>
                        <p class="small fw-semibold mb-2">Aktivitas mencurigakan terbaru</p>
                        @foreach($suspiciousRecent->take(4) as $suspicious)
                            <div class="small mb-2">
                                <span class="badge bg-warning-subtle text-warning me-1">{{ $suspicious->event_label }}</span>
                                <span class="text-muted">{{ $suspicious->created_at?->format('d/m H:i') }}</span>
                                <div class="text-muted">{{ $suspicious->ip_address }} · {{ $suspicious->identity ?: $suspicious->user?->email }}</div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-0">Riwayat Aktivitas Login</h5>
                <p class="text-muted mb-0">Setiap percobaan login, pemblokiran, verifikasi 2FA, dan logout.</p>
            </div>
            <a href="{{ route('user.sa.login-activities.export', ['userId' => $userId] + request()->query()) }}" class="btn btn-soft-primary">
                <i class="ri-download-cloud-2-line me-1"></i>Export CSV
            </a>
        </div>

        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama, email, IP, alasan..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="event" class="form-select form-select-sm">
                        <option value="">Semua Peristiwa</option>
                        @foreach($events as $eventKey => $eventLabel)
                            <option value="{{ $eventKey }}" {{ request('event') === $eventKey ? 'selected' : '' }}>{{ $eventLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">Semua Pengguna</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') === $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary btn-sm w-100"><i class="ri-search-line"></i></button>
                </div>
                <div class="col-12 d-flex align-items-center gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="suspicious" value="1" id="suspicious-filter" {{ request()->boolean('suspicious') ? 'checked' : '' }}>
                        <label class="form-check-label small" for="suspicious-filter">Hanya yang mencurigakan</label>
                    </div>
                    <a href="{{ route('user.sa.login-activities.index', ['userId' => $userId]) }}" class="small text-muted">Reset filter</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Waktu</th>
                            <th>Pengguna / Identity</th>
                            <th>Peristiwa</th>
                            <th>Alasan</th>
                            <th>IP</th>
                            <th>Perangkat</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                            <tr class="{{ $activity->is_suspicious ? 'table-warning' : '' }}">
                                <td class="small text-muted">{{ $activity->created_at?->format('d/m/Y H:i:s') }}</td>
                                <td>
                                    @if($activity->user)
                                        <div class="fw-semibold">{{ $activity->user->name }}</div>
                                        <small class="text-muted">{{ $activity->user->email }}</small>
                                    @else
                                        <div class="fw-semibold">{{ $activity->identity ?: 'Tidak dikenal' }}</div>
                                        <small class="text-muted">{{ $activity->email ?: 'User tidak ditemukan' }}</small>
                                    @endif
                                </td>
                                <td><span class="badge bg-{{ $activity->event_color }}-subtle text-{{ $activity->event_color }}">{{ $activity->event_label }}</span></td>
                                <td class="small text-muted">{{ $activity->reason ?: '-' }}</td>
                                <td><code class="small">{{ $activity->ip_address ?: '-' }}</code></td>
                                <td class="small text-muted">{{ $activity->device }}</td>
                                <td class="text-center">
                                    @if($activity->is_suspicious)
                                        <span class="badge bg-warning-subtle text-warning"><i class="ri-alert-line me-1"></i>Perlu dicek</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Normal</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('user.sa.login-activities.show', ['userId' => $userId, 'id' => $activity->id]) }}" class="btn btn-sm btn-soft-primary">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada aktivitas login yang cocok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($activities->hasPages())
                @include('shared._pagination', ['paginator' => $activities])
            @endif
        </div>
    </div>
@endsection
