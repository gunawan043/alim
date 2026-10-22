@extends('layouts.master')
@section('title') Keamanan Akun @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Profil @endslot
        @slot('title') Keamanan Akun @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="ri-shield-keyhole-line me-1"></i>Verifikasi Dua Langkah</h6>
                    @if($twoFactorEnabled)
                        <span class="badge bg-success-subtle text-success">Aktif</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                    @endif
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        @if($twoFactorEnabled)
                            Login web Anda dilindungi kode OTP email.
                            @if($twoFactorScope === 'super_admin')
                                Kebijakan ini berlaku untuk Super Admin / System Admin.
                            @else
                                Kebijakan ini berlaku untuk semua pengguna web.
                            @endif
                        @else
                            2FA sedang dinonaktifkan oleh administrator. Aktifkan melalui Pengaturan Sistem jika diperlukan.
                        @endif
                    </p>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Kode pemulihan tersisa</span>
                        <strong>{{ $recoveryCodeCount }}</strong>
                    </div>

                    <form method="POST" action="{{ route('user.profile.security.recovery-codes', ['userId' => $userId]) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-soft-primary w-100" {{ $twoFactorEnabled ? '' : 'disabled' }}>
                            <i class="ri-key-2-line me-1"></i>{{ $recoveryCodeCount ? 'Buat Ulang Kode Pemulihan' : 'Buat Kode Pemulihan' }}
                        </button>
                    </form>

                    <div class="alert alert-info small mt-3 mb-0">
                        <i class="ri-information-line me-1"></i>
                        Kode pemulihan hanya ditampilkan sekali. Simpan di tempat aman dan jangan bagikan.
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><i class="ri-smartphone-line me-1"></i>Authenticator App</h6>
                        @if($totpEnabled)
                            <span class="badge bg-success-subtle text-success">Aktif</span>
                        @endif
                    </div>

                    @if($totpEnabled)
                        <p class="text-muted small">Kode 6 digit dari Google Authenticator, Microsoft Authenticator, atau Authy dapat dipakai saat login.</p>
                        <form method="POST" action="{{ route('user.profile.security.totp.disable', ['userId' => $userId]) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Nonaktifkan authenticator? Kode pemulihan lama akan dihapus.')">
                                Nonaktifkan Authenticator
                            </button>
                        </form>
                    @elseif($twoFactorEnabled)
                        @if($totpPending)
                            <p class="text-muted small">Scan QR berikut, lalu masukkan kode 6 digit untuk mengonfirmasi.</p>
                            @if($totpQr)
                                <div class="text-center bg-white rounded p-2 mb-2" style="max-width:200px;margin:0 auto">
                                    {!! $totpQr !!}
                                </div>
                            @endif
                            <div class="mb-2">
                                <label class="form-label small mb-1">Secret manual</label>
                                <code class="d-block bg-body-tertiary rounded p-2 text-center">{{ $totpSecret }}</code>
                            </div>
                            <form method="POST" action="{{ route('user.profile.security.totp.confirm', ['userId' => $userId]) }}">
                                @csrf
                                <div class="input-group input-group-sm mb-2">
                                    <input type="text" name="totp_code" class="form-control" maxlength="6" inputmode="numeric"
                                           placeholder="Kode 6 digit" required>
                                    <button class="btn btn-primary" type="submit">Konfirmasi</button>
                                </div>
                                @error('totp_code')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </form>
                        @else
                            <p class="text-muted small">Gunakan aplikasi authenticator sebagai alternatif OTP email.</p>
                            <form method="POST" action="{{ route('user.profile.security.totp.setup', ['userId' => $userId]) }}">
                                @csrf
                                <button class="btn btn-sm btn-soft-primary w-100">
                                    <i class="ri-qr-code-line me-1"></i>Aktifkan Authenticator
                                </button>
                            </form>
                        @endif
                    @else
                        <p class="text-muted small mb-0">Aktifkan 2FA terlebih dahulu untuk memasang authenticator.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h6 class="mb-0"><i class="ri-computer-line me-1"></i>Sesi Web Aktif</h6>
                    <small class="text-muted">Sesi yang sedang login melalui browser.</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-3">
                            <thead class="table-light">
                                <tr>
                                    <th>Perangkat</th>
                                    <th>IP</th>
                                    <th>Terakhir Aktif</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($webSessions as $session)
                                    <tr>
                                        <td>
                                            {{ $session->device_label }}
                                            @if($session->is_current)
                                                <span class="badge bg-success-subtle text-success ms-1">Saat ini</span>
                                            @endif
                                        </td>
                                        <td class="text-muted small">{{ $session->ip_address ?: '-' }}</td>
                                        <td class="text-muted small">{{ $session->last_activity_at?->diffForHumans() ?? '-' }}</td>
                                        <td class="text-end">
                                            @if(! $session->is_current)
                                                <form method="POST" action="{{ route('user.profile.security.sessions.revoke', ['userId' => $userId, 'sessionId' => $session->id]) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-soft-danger" onclick="return confirm('Cabut sesi ini?')">Cabut</button>
                                                </form>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted small py-3">Belum ada sesi web.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <form method="POST" action="{{ route('user.profile.security.sessions.revoke-others', ['userId' => $userId]) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Cabut semua sesi web lain?')">
                            <i class="ri-logout-circle-r-line me-1"></i>Cabut Semua Sesi Web Lain
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h6 class="mb-0"><i class="ri-smartphone-line me-1"></i>Sesi Aplikasi Mobile</h6>
                    <small class="text-muted">Token aplikasi yang masih memiliki akses ke akun Anda.</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-3">
                            <thead class="table-light">
                                <tr>
                                    <th>Perangkat</th>
                                    <th>IP</th>
                                    <th>Terakhir Digunakan</th>
                                    <th>Kedaluwarsa</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mobileSessions as $token)
                                    <tr>
                                        <td>{{ $token->device_label ?: ($token->name ?: 'Aplikasi Mobile') }}</td>
                                        <td class="text-muted small">{{ $token->ip_last ?: '-' }}</td>
                                        <td class="text-muted small">{{ $token->last_used_at?->diffForHumans() ?? 'Belum digunakan' }}</td>
                                        <td class="text-muted small">{{ $token->expires_at?->format('d/m/Y H:i') ?? 'Tidak terbatas' }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('user.profile.security.mobile-sessions.revoke', ['userId' => $userId, 'tokenId' => $token->id]) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-soft-danger" onclick="return confirm('Cabut sesi mobile ini?')">Cabut</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted small py-3">Belum ada sesi mobile.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($mobileSessions->isNotEmpty())
                        <form method="POST" action="{{ route('user.profile.security.mobile-sessions.revoke-all', ['userId' => $userId]) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Cabut semua sesi mobile?')">
                                <i class="ri-logout-circle-r-line me-1"></i>Cabut Semua Sesi Mobile
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h6 class="mb-0"><i class="ri-history-line me-1"></i>Riwayat Login Terakhir</h6>
                    <small class="text-muted">20 aktivitas login terakhir pada akun ini.</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Waktu</th>
                                    <th>Metode</th>
                                    <th>IP</th>
                                    <th>Perangkat</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($loginHistory as $entry)
                                    <tr>
                                        <td class="text-muted small">{{ $entry->created_at?->format('d/m/Y H:i') }}</td>
                                        <td><span class="badge bg-info-subtle text-info">{{ $entry->formatted_action }}</span></td>
                                        <td class="text-muted small">{{ $entry->ip_address ?: '-' }}</td>
                                        <td class="text-muted small">{{ \Illuminate\Support\Str::limit((string) $entry->user_agent, 60) ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted small py-3">Belum ada riwayat login.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if(! empty($recoveryCodes))
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning-subtle border-bottom-dashed">
                        <h6 class="mb-0"><i class="ri-key-2-line me-1"></i>Kode Pemulihan Baru — Simpan Sekarang</h6>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">Kode ini hanya ditampilkan sekali. Gunakan salah satu kode jika tidak bisa menerima OTP email.</p>
                        <div class="row g-2">
                            @foreach($recoveryCodes as $code)
                                <div class="col-md-3">
                                    <code class="d-block bg-body-tertiary rounded p-2 text-center">{{ $code }}</code>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
