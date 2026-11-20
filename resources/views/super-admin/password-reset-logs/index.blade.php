@extends('layouts.master')

@section('title', 'Password Reset Logs')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('title') Password Reset Logs @endslot
    @endcomponent

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.66rem;letter-spacing:.5px">OTP Diminta</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($stats['requested'] ?? 0) }}</h4>
                    <small class="text-muted">{{ number_format($stats['requested_today'] ?? 0) }} hari ini</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.66rem;letter-spacing:.5px">OTP Terverifikasi</p>
                    <h4 class="mb-0 fw-bold text-success">{{ number_format($stats['verified'] ?? 0) }}</h4>
                    <small class="text-muted">{{ number_format($stats['verified_today'] ?? 0) }} hari ini</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.66rem;letter-spacing:.5px">OTP Gagal</p>
                    <h4 class="mb-0 fw-bold text-danger">{{ number_format($stats['failed'] ?? 0) }}</h4>
                    <small class="text-muted">{{ number_format($stats['failed_today'] ?? 0) }} hari ini</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.66rem;letter-spacing:.5px">Password Direset</p>
                    <h4 class="mb-0 fw-bold text-primary">{{ number_format($stats['completed'] ?? 0) }}</h4>
                    <small class="text-muted">{{ number_format($stats['otp_total'] ?? 0) }} OTP aktif/tersimpan</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-0">Riwayat OTP &amp; Reset Password</h5>
                <p class="text-muted mb-0">Riwayat permanen permintaan OTP, percobaan verifikasi, dan reset password.</p>
            </div>
            <a href="{{ route('user.sa.login-activities.index', ['userId' => $userId]) }}" class="btn btn-soft-primary btn-sm">
                <i class="ri-shield-keyhole-line me-1"></i>Aktivitas Login
            </a>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama, email, IP..." value="{{ request('search') }}">
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
                        <option value="">Semua User</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
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
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="ri-search-line"></i></button>
                </div>
                <div class="col-12">
                    <a href="{{ route('user.sa.password-reset-logs.index', ['userId' => $userId]) }}" class="small text-muted">Reset filter</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Waktu</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Peristiwa</th>
                            <th>Alasan</th>
                            <th>IP</th>
                            <th>Perangkat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($otpLogs as $log)
                            <tr>
                                <td class="small text-muted">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                                <td>
                                    @if($log->user)
                                        <strong>{{ $log->user->name }}</strong>
                                    @else
                                        <span class="text-muted">Tidak dikenal</span>
                                    @endif
                                </td>
                                <td class="small">{{ $log->email ?: ($log->user?->email ?? '-') }}</td>
                                <td><span class="badge bg-{{ $log->event_color }}-subtle text-{{ $log->event_color }}">{{ $log->event_label }}</span></td>
                                <td class="small text-muted">{{ $log->reason ?: '-' }}</td>
                                <td><code class="small">{{ $log->ip_address ?: '-' }}</code></td>
                                <td class="small text-muted">{{ $log->device }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Belum ada riwayat OTP. Riwayat akan tercatat saat pengguna meminta OTP reset password.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($otpLogs->hasPages())
                @include('shared._pagination', ['paginator' => $otpLogs])
            @endif
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header border-bottom-dashed">
            <h5 class="card-title mb-0">OTP Tersimpan (Aktif / Expired)</h5>
            <p class="text-muted mb-0">Record OTP saat ini. Riwayat lengkap tetap tersimpan di tabel di atas.</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Status</th>
                            <th>Expires At</th>
                            <th>Dibuat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($otpRecords as $otp)
                            <tr>
                                <td>
                                    @if($otp->user)
                                        <strong>{{ $otp->user->name }}</strong>
                                        <br><small class="text-muted">{{ $otp->user->email }}</small>
                                    @else
                                        <span class="text-muted">Unknown</span>
                                    @endif
                                </td>
                                <td>
                                    @if($otp->isExpired())
                                        <span class="badge bg-danger-subtle text-danger"><i class="ri-time-line me-1"></i>Expired</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning"><i class="ri-hourglass-line me-1"></i>Unused</span>
                                    @endif
                                </td>
                                <td><small>{{ $otp->expires_at->format('d/m/Y H:i') }}</small></td>
                                <td><small>{{ $otp->created_at->format('d/m/Y H:i') }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Tidak ada record OTP.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($otpRecords->hasPages())
                @include('shared._pagination', ['paginator' => $otpRecords])
            @endif
        </div>
    </div>
@endsection
