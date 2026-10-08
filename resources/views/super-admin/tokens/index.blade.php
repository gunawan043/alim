@extends('layouts.master')
@section('title') Token & Sesi @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('title') Token & Sesi @endslot
    @endcomponent

    {-- Statistik ringkas --}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4">
            <div class="card">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <span class="bg-success-subtle text-success" style="width:38px;height:38px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.05rem;flex-shrink:0">
                        <i class="ri-shield-check-line"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0" style="font-size:.66rem;text-transform:uppercase;letter-spacing:.5px;">Sesi Aktif (Sanctum)</p>
                        <h5 class="mb-0 fw-bold">{{ number_format($stats['sessions'] ?? 0) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4">
            <div class="card">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <span class="bg-primary-subtle text-primary" style="width:38px;height:38px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.05rem;flex-shrink:0">
                        <i class="ri-key-2-line"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0" style="font-size:.66rem;text-transform:uppercase;letter-spacing:.5px;">Secure Token Aktif</p>
                        <h5 class="mb-0 fw-bold">{{ number_format($stats['secure'] ?? 0) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4">
            <div class="card">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <span class="bg-info-subtle text-info" style="width:38px;height:38px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.05rem;flex-shrink:0">
                        <i class="ri-history-line"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0" style="font-size:.66rem;text-transform:uppercase;letter-spacing:.5px;">Dipakai 24 Jam</p>
                        <h5 class="mb-0 fw-bold">{{ number_format($stats['used_24h'] ?? 0) }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>



    @if(session('token'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Token berhasil dibuat!</strong> Salin token ini (hanya ditampilkan sekali):
            <div class="input-group mt-2" style="max-width:500px">
                <input type="text" class="form-control font-monospace" id="generatedToken" value="{{ session('token') }}" readonly>
                <button class="btn btn-success" onclick="copyToken()"><i class="ri-file-copy-line"></i></button>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ $tab === 'sessions' ? 'active' : '' }}"
                                href="{{ route('user.sa.tokens.index', ['userId' => $userId, 'tab' => 'sessions']) }}">
                                <i class="ri-shield-check-line me-1"></i> Sessions (Sanctum)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tab === 'secure-tokens' ? 'active' : '' }}"
                                href="{{ route('user.sa.tokens.index', ['userId' => $userId, 'tab' => 'secure-tokens']) }}">
                                <i class="ri-key-2-line me-1"></i> Secure Tokens
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tab === 'create' ? 'active' : '' }}"
                                href="{{ route('user.sa.tokens.index', ['userId' => $userId, 'tab' => 'create']) }}">
                                <i class="ri-add-circle-line me-1"></i> Buat Token
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    {{-- TAB: Sessions --}}
                    @if($tab === 'sessions' && $tokens)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Token Name</th>
                                        <th>Abilities</th>
                                        <th>Last Used</th>
                                        <th>Expires</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($tokens as $token)
                                        <tr>
                                            <td>
                                                <strong>{{ $token->user_name }}</strong>
                                                <br><small class="text-muted">{{ $token->user_email }}</small>
                                            </td>
                                            <td><code>{{ $token->name }}</code></td>
                                            <td><span class="badge bg-light text-dark">{{ $token->abilities }}</span></td>
                                            <td><small>{{ $token->last_used_at ? \Carbon\Carbon::parse($token->last_used_at)->format('d/m/Y H:i') : 'Belum pernah' }}</small></td>
                                            <td>
                                                @if($token->expires_at)
                                                    <small class="{{ \Carbon\Carbon::parse($token->expires_at)->isPast() ? 'text-danger' : 'text-success' }}">
                                                        {{ \Carbon\Carbon::parse($token->expires_at)->format('d/m/Y') }}
                                                    </small>
                                                @else
                                                    <span class="badge bg-success-subtle text-success">Permanent</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-soft-danger revoke-token"
                                                    data-id="{{ $token->id }}" data-name="{{ $token->name }}">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">Belum ada session token.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($tokens->hasPages())
    @include('shared._pagination', ['paginator' => $tokens])
@endif
                    @endif

                    {{-- TAB: Secure Tokens --}}
                    @if($tab === 'secure-tokens' && $secureTokens)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Nama Token</th>
                                        <th>Dibuat</th>
                                        <th>Expires</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($secureTokens as $st)
                                        <tr>
                                            <td>
                                                @if($st->user)
                                                    <strong>{{ $st->user->name }}</strong>
                                                    <br><small class="text-muted">{{ $st->user->email }}</small>
                                                @else
                                                    <span class="text-muted">System</span>
                                                @endif
                                            </td>
                                            <td>{{ $st->name }}</td>
                                            <td><small>{{ $st->created_at->format('d/m/Y H:i') }}</small></td>
                                            <td>
                                                @if($st->expires_at)
                                                    <small class="{{ \Carbon\Carbon::parse($st->expires_at)->isPast() ? 'text-danger' : 'text-success' }}">
                                                        {{ \Carbon\Carbon::parse($st->expires_at)->format('d/m/Y') }}
                                                    </small>
                                                @else
                                                    <span class="badge bg-success-subtle text-success">Permanent</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-soft-danger revoke-secure-token"
                                                    data-id="{{ $st->id }}" data-name="{{ $st->name }}">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Belum ada secure token.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($secureTokens->hasPages())
    @include('shared._pagination', ['paginator' => $secureTokens])
@endif
                    @endif

                    {{-- TAB: Create Token --}}
                    @if($tab === 'create')
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card border">
                                    <div class="card-header bg-light"><h6 class="mb-0">Buat Secure Token</h6></div>
                                    <div class="card-body">
                                        <form method="POST" action="{{ route('user.sa.tokens.create', ['userId' => $userId]) }}">
                                            @csrf
                                            <div class="mb-3">
                                                <label class="form-label">User <span class="text-danger">*</span></label>
                                                <select name="user_id" class="form-control" required>
                                                    <option value="">Pilih User</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Note / Nama Token</label>
                                                <input type="text" name="note" class="form-control" placeholder="Contoh: API Integration Token">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Expired At (opsional)</label>
                                                <input type="date" name="expires_at" class="form-control" min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                                                <small class="text-muted">Default: 7 hari dari sekarang</small>
                                            </div>
                                            <button type="submit" class="btn btn-success"><i class="ri-key-2-line me-1"></i> Generate Token</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
    function copyToken() {
        const input = document.getElementById('generatedToken');
        input.select();
        document.execCommand('copy');
        Swal.fire({ icon: 'success', title: 'Disalin!', text: 'Token berhasil disalin.', timer: 1500, showConfirmButton: false });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.revoke-token').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!confirm(`Revoke token "${this.dataset.name}"?`)) return;
                fetch(`/{{ $userId }}/sa/tokens/sessions/${this.dataset.id}/revoke`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'json' }
                }).then(r => r.json()).then(() => location.reload());
            });
        });

        document.querySelectorAll('.revoke-secure-token').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!confirm(`Revoke token "${this.dataset.name}"?`)) return;
                fetch(`/{{ $userId }}/sa/tokens/secure/${this.dataset.id}/revoke`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'json' }
                }).then(r => r.json()).then(() => location.reload());
            });
        });
    });
    </script>
@endsection
