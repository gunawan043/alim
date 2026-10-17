@extends('layouts.master')
@section('title') Pusat Notifikasi @endsection

@section('content')
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-1"><i class="ri-notification-3-line me-1"></i> Pusat Notifikasi</h4>
            <p class="text-muted small mb-0">
                @if($unreadCount > 0)
                    Anda punya <strong>{{ $unreadCount }}</strong> notifikasi belum dibaca
                @else
                    Tidak ada notifikasi baru
                @endif
            </p>
        </div>
        @if($unreadCount > 0)
            <form method="POST" action="{{ route('portal.notifications.read-all', ['token' => $token]) }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="ri-check-double-line me-1"></i> Tandai Semua Dibaca
                </button>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <a href="{{ route('portal.dashboard', ['token' => $token]) }}" class="btn btn-sm btn-link mb-3">
        <i class="ri-arrow-left-line"></i> Kembali ke Dashboard
    </a>

    @forelse($notifications as $notif)
        @php
            $iconMap = [
                'permit_decision' => ['icon' => 'ri-suitcase-line', 'color' => 'primary'],
                'visit_decision'  => ['icon' => 'ri-user-heart-line', 'color' => 'info'],
                'health_decision' => ['icon' => 'ri-heart-pulse-line', 'color' => 'warning'],
            ];
            $style = $iconMap[$notif->type] ?? ['icon' => 'ri-information-line', 'color' => 'secondary'];
        @endphp

        <div class="card mb-2 {{ $notif->is_read ? '' : 'border-start border-primary border-3' }}">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-start">
                    <div class="me-3">
                        <span class="badge bg-{{ $style['color'] }} bg-opacity-10 text-{{ $style['color'] }} p-2 rounded">
                            <i class="{{ $style['icon'] }} fs-5"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1 {{ $notif->is_read ? 'text-muted' : 'fw-bold' }}">{{ $notif->title }}</h6>
                                <p class="mb-1 small">{{ $notif->message }}</p>
                            </div>
                            <small class="text-muted text-nowrap ms-2">
                                {{ $notif->created_at->diffForHumans() }}
                            </small>
                        </div>

                        <div class="d-flex align-items-center gap-2 mt-2">
                            @if($notif->action_url && $notif->action_text)
                                <a href="{{ $notif->action_url }}" class="btn btn-sm btn-link p-0">
                                    {{ $notif->action_text }}
                                </a>
                            @endif
                            @if(! $notif->is_read)
                                <form method="POST" action="{{ route('portal.notifications.read', ['token' => $token, 'id' => $notif->id]) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2">
                                        <i class="ri-check-line"></i> Tandai Dibaca
                                    </button>
                                </form>
                            @else
                                <small class="text-muted">
                                    <i class="ri-check-double-line"></i> Dibaca {{ $notif->read_at?->diffForHumans() }}
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="ri-notification-off-line display-1 text-muted"></i>
                <p class="text-muted mt-3">Belum ada notifikasi.</p>
            </div>
        </div>
    @endforelse

    <div class="mt-3">
        {{ $notifications->links() }}
    </div>
</div>
@endsection