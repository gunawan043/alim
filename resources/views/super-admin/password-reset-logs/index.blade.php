@extends('layouts.master')
@section('title') Password Reset Logs @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('title') Password Reset Logs @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            {{-- OTP Verification Logs --}}
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Riwayat OTP Terverifikasi</h5>
                            <p class="text-muted mb-0">Log user yang berhasil verifikasi OTP password reset.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Cari user..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="user_id" class="form-control">
                                <option value="">Semua User</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line"></i></button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.sa.password-reset-logs.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Record ID</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($otpVerifiedLogs as $log)
                                    <tr>
                                        <td><small>{{ $log->created_at->format('d/m/Y H:i:s') }}</small></td>
                                        <td>
                                            @if($log->user)
                                                <strong>{{ $log->user->name }}</strong>
                                            @else
                                                <span class="text-muted">System</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->user)
                                                <small class="text-muted">{{ $log->user->email }}</small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td><code>{{ $log->record_id ?? '-' }}</code></td>
                                        <td><small>{{ $log->ip_address ?? '-' }}</small></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada log OTP terverifikasi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($otpVerifiedLogs->hasPages())
    @include('shared._pagination', ['paginator' => $otpVerifiedLogs])
@endif
                </div>
            </div>

            {{-- OTP Records (unused/expired) --}}
            <div class="card mt-4">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">OTP Records (Unused / Expired)</h5>
                            <p class="text-muted mb-0">Token OTP yang belum digunakan atau sudah expired.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
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
        </div>
    </div>
@endsection
