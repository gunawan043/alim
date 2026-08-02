@extends('layouts.master')
@section('title') Detail Broadcast @endsection

@section('css')
<style>
    .severity-emergency { border-top: 4px solid #dc3545; }
    .severity-urgent { border-top: 4px solid #ffc107; }
    .severity-warning { border-top: 4px solid #0dcaf0; }
    .severity-info { border-top: 4px solid #6c757d; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.my-profile', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.broadcasts.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Broadcast Darurat</a> @endslot
        @slot('title') {{ Str::limit($broadcast->title, 30) }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-animate severity-{{ $broadcast->severity }}">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-{{ $broadcast->severity === 'emergency' ? 'danger' : ($broadcast->severity === 'urgent' ? 'warning' : ($broadcast->severity === 'warning' ? 'info' : 'secondary')) }}-subtle rounded fs-3">
                                <i class="ri-{{ $broadcast->severity === 'emergency' ? 'error-warning' : ($broadcast->severity === 'urgent' ? 'alert-line' : ($broadcast->severity === 'warning' ? 'information' : 'info')) }}-line text-{{ $broadcast->severity === 'emergency' ? 'danger' : ($broadcast->severity === 'urgent' ? 'warning' : ($broadcast->severity === 'warning' ? 'info' : 'secondary')) }}"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h4 class="fw-bold mb-1">{{ $broadcast->title }}</h4>
                            <div class="d-flex gap-2 flex-wrap">
                                <span class="badge bg-{{ $broadcast->severity === 'emergency' ? 'danger' : ($broadcast->severity === 'urgent' ? 'warning' : ($broadcast->severity === 'warning' ? 'info' : 'secondary')) }}">
                                    {{ $broadcast->severity_text }}
                                </span>
                                @if($broadcast->expires_at)
                                    <span class="badge bg-light text-dark">
                                        <i class="ri-time-line me-1"></i>
                                        {{ $broadcast->expires_at->format('d/m/Y H:i') }}
                                    </span>
                                    @if($broadcast->is_expired)
                                        <span class="badge bg-danger">Expired</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <h6 class="text-muted text-uppercase mb-2" style="font-size: 11px;">Isi Pesan</h6>
                        <div class="bg-light p-3 rounded">{{ nl2br(e($broadcast->content)) }}</div>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted text-uppercase mb-2" style="font-size: 11px;">Kanal</h6>
                        @php
                            $viaLabels = [
                                'app' => 'In-App Notification',
                                'sms' => 'SMS',
                                'whatsapp' => 'WhatsApp',
                                'all' => 'Semua Kanal',
                                'email' => 'Email',
                            ];
                        @endphp
                        <span class="badge bg-dark-subtle text-dark">{{ $viaLabels[$broadcast->broadcast_via] ?? $broadcast->broadcast_via }}</span>
                        @if($broadcast->ack_required)
                            <span class="badge bg-warning text-dark ms-1"><i class="ri-checkbox-circle-line me-1"></i>ACK Required</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informasi</h5>
                </div>
                <div class="card-body">
                    <div class="table table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted" style="width: 40%;">Dibuat oleh</td>
                                    <td class="fw-semibold">{{ $broadcast->creator?->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Waktu dibuat</td>
                                    <td>{{ $broadcast->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Last updated</td>
                                    <td>{{ $broadcast->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-footer bg-transparent border-top">
                    <a href="{{ route('user.asrama.broadcasts.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="ri-arrow-left-line me-1"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
