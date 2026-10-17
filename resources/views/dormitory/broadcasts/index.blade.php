@extends('layouts.master')
@section('title') Broadcast Darurat Asrama @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('css')
<style>
    .severity-urgent { border-left: 4px solid #dc3545 !important; }
    .severity-emergency { border-left: 4px solid #dc3545 !important; animation: pulse-red 2s infinite; }
    @keyframes pulse-red {
        0%, 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
        50% { box-shadow: 0 0 0 6px rgba(220, 53, 69, 0); }
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.my-profile', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('title') Broadcast Darurat @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Broadcast Darurat</h5>
                            <p class="text-muted mb-0">Kirim pengumuman darurat ke semua penghuni asrama.</p>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Inline Broadcast Creation Form --}}
                    <div class="card bg-light mb-4 border">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0"><i class="ri-send-plane-line me-2 text-primary"></i>Kirim Broadcast Baru</h5>
                        </div>
                        <div class="card-body">
                            @if($errors->broadcast && $errors->broadcast->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <ul class="mb-0">
                                        @foreach($errors->broadcast->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                                </div>
                            @endif

                            <form method="POST"
                                  action="{{ route('user.asrama.broadcasts.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                  id="broadcastForm">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Judul Pesan <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control"
                                               value="{{ old('title') }}" placeholder="Contoh: Pengumuman Kepulangan"
                                               maxlength="100" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tingkat Severity <span class="text-danger">*</span></label>
                                        <select name="severity" class="form-control" required>
                                            <option value="">— Pilih Severity —</option>
                                            <option value="info" {{ old('severity') === 'info' ? 'selected' : '' }}>Info</option>
                                            <option value="warning" {{ old('severity', 'warning') === 'warning' ? 'selected' : '' }}>Warning</option>
                                            <option value="urgent" {{ old('severity') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                                            <option value="emergency" {{ old('severity') === 'emergency' ? 'selected' : '' }}>Emergency</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Kanal Broadcast <span class="text-danger">*</span></label>
                                        <select name="broadcast_via" class="form-control" required>
                                            <option value="">— Pilih Kanal —</option>
                                            <option value="app" {{ old('broadcast_via', 'app') === 'app' ? 'selected' : '' }}>In-App Notification</option>
                                            <option value="sms" {{ old('broadcast_via') === 'sms' ? 'selected' : '' }}>SMS</option>
                                            <option value="whatsapp" {{ old('broadcast_via') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                            <option value="all" {{ old('broadcast_via') === 'all' ? 'selected' : '' }}>Semua Kanal</option>
                                            <option value="email" {{ old('broadcast_via') === 'email' ? 'selected' : '' }}>Email</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Isi Pesan <span class="text-danger">*</span></label>
                                        <textarea name="content" class="form-control" rows="3"
                                                  placeholder="Tulis isi pesan broadcast..." required>{{ old('content') }}</textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Batas Waktu Kadaluarsa</label>
                                        <input type="datetime-local" name="expires_at" class="form-control"
                                               value="{{ old('expires_at') }}">
                                        <div class="form-text">Kosongkan jika tidak ada batasan.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label d-block">ACK Required?</label>
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="ack_required"
                                                   id="ackSwitch" value="1" {{ old('ack_required') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="ackSwitch">
                                                Wajib konfirmasi terima oleh penerima
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end gap-2 pb-md-4">
                                        <button type="submit" class="btn btn-primary flex-grow-1">
                                            <i class="ri-send-plane-line me-1"></i> Kirim Broadcast
                                        </button>
                                        <button type="reset" class="btn btn-light">
                                            <i class="ri-reset-left-line me-1"></i> Reset
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Broadcast List Table --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Judul</th>
                                    <th class="text-center">Severity</th>
                                    <th class="text-center">Via</th>
                                    <th class="text-center">ACK</th>
                                    <th>Berakhir</th>
                                    <th>Penulis</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($broadcasts ?? [] as $i => $bc)
                                    <tr class="{{ $bc->severity === 'emergency' ? 'severity-emergency' : ($bc->severity === 'urgent' ? 'severity-urgent' : '') }}">
                                        <td class="text-center">{{ ($broadcasts->currentPage() - 1) * $broadcasts->perPage() + $i + 1 }}</td>
                                        <td>
                                            <a href="{{ route('user.asrama.broadcasts.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'broadcastUuid' => $bc->id]) }}"
                                               class="fw-semibold text-decoration-none text-dark">
                                                {{ Str::limit($bc->title, 50) }}
                                            </a>
                                            <div class="text-muted small">{!! Str::limit(strip_tags($bc->content), 80) !!}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $bc->severity === 'emergency' ? 'danger' : ($bc->severity === 'urgent' ? 'warning' : ($bc->severity === 'warning' ? 'info' : 'secondary')) }}">
                                                <i class="ri-{{ $bc->severity === 'emergency' ? 'error-warning' : ($bc->severity === 'urgent' ? 'alert-line' : ($bc->severity === 'warning' ? 'information' : 'info')) }}-line me-1"></i>
                                                {{ ucfirst($bc->severity) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $viaLabels = [
                                                    'app' => 'In-App',
                                                    'sms' => 'SMS',
                                                    'whatsapp' => 'WA',
                                                    'all' => 'Semua',
                                                    'email' => 'Email',
                                                ];
                                            @endphp
                                            <span class="badge bg-dark-subtle text-dark">{{ $viaLabels[$bc->broadcast_via] ?? $bc->broadcast_via }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($bc->ack_required)
                                                <span class="badge bg-warning text-dark">
                                                    <i class="ri-checkbox-circle-line me-1"></i>Ya
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Tidak</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($bc->expires_at)
                                                <div class="small {{ $bc->expires_at->isPast() ? 'text-danger' : 'text-muted' }}">
                                                    <i class="ri-time-line me-1"></i>
                                                    {{ $bc->expires_at->format('d/m/Y H:i') }}
                                                    @if($bc->expires_at->isPast())
                                                        <span class="badge bg-danger ms-1">Expired</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted small">Tanpa batas</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="small fw-semibold">{{ $bc->creator?->name ?? '—' }}</div>
                                            <div class="text-muted small">{{ $bc->created_at->format('d/m/Y H:i') }}</div>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.asrama.broadcasts.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'broadcastUuid' => $bc->id]) }}"
                                               class="btn btn-sm btn-outline-primary me-1" title="Detail">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            @if($bc->created_by === (function_exists('auth') && auth()->check() ? auth()->id() : null))
                                                <form method="POST"
                                                      action="{{ route('user.asrama.broadcasts.destroy', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'broadcastUuid' => $bc->id]) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Yakin hapus broadcast ini?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                            <h6 class="text-muted mb-1 mt-3">Belum Ada Pesan Broadcast</h6>
                                            <p class="text-muted mb-3">Gunakan formulir di atas untuk mengirim pesan kepada pengguna.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <x-pagination :paginator="$broadcasts" />
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('broadcastForm');
    form.addEventListener('submit', function (e) {
        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="ri-loader-4-line me-1 spinner-border spinner-border-sm"></i> Mengirim...';
        }
    });
});
</script>
@endsection