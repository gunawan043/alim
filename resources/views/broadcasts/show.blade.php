@extends('layouts.master')
@section('title') Detail Broadcast @endsection

@section('content')
    @php
        $broadcastIndexRoute = $broadcastIndexRoute ?? 'user.broadcasts.index';
        $broadcastIndexParams = $broadcastIndexParams ?? ['userId' => $userId];
    @endphp
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route($broadcastIndexRoute, $broadcastIndexParams) }}">Broadcast</a> @endslot
        @slot('title') Detail Broadcast @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        @foreach(['recipients' => ['Penerima', 'primary', 'ri-group-line'], 'read' => ['Sudah Dibaca', 'success', 'ri-eye-line'], 'acknowledged' => ['Sudah Konfirmasi', 'info', 'ri-check-double-line']] as $key => $meta)
            <div class="col-md-4">
                <div class="card card-animate h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm"><span class="avatar-title bg-{{ $meta[1] }}-subtle text-{{ $meta[1] }} rounded"><i class="{{ $meta[2] }}"></i></span></div>
                            <div>
                                <p class="text-muted mb-0 small">{{ $meta[0] }}</p>
                                <h4 class="mb-0">{{ number_format($stats[$key]) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-4">
        <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $broadcast->title }}</h5>
            <span class="badge bg-{{ $broadcast->severity === 'emergency' ? 'danger' : ($broadcast->severity === 'urgent' ? 'warning' : 'info') }}-subtle text-{{ $broadcast->severity === 'emergency' ? 'danger' : ($broadcast->severity === 'urgent' ? 'warning' : 'info') }}">
                {{ ucfirst($broadcast->severity) }}
            </span>
        </div>
        <div class="card-body">
            @if($broadcast->banner_url)
                <img src="{{ $broadcast->banner_url }}" alt="Banner broadcast" class="img-fluid rounded mb-3" style="max-width:360px;">
            @endif
            @if($broadcast->activity_start_date)
                <div class="alert alert-info py-2 mb-3 small">
                    <i class="ri-calendar-event-line me-1"></i>
                    Tanggal kegiatan: <strong>{{ $broadcast->activity_start_date->format('d M Y') }}</strong>
                    @if($broadcast->activity_end_date)
                        – <strong>{{ $broadcast->activity_end_date->format('d M Y') }}</strong>
                    @endif
                </div>
            @endif
            <p class="mb-3" style="white-space: pre-wrap;">{{ $broadcast->content }}</p>
            <div class="row g-2 small text-muted">
                <div class="col-md-4"><strong>Target:</strong>
                    @if($broadcast->target_type === 'global') Semua Sekolah
                    @elseif($broadcast->target_type === 'school') {{ $broadcast->school?->name }}
                    @elseif($broadcast->target_type === 'dormitory') Asrama {{ $broadcast->dormitory?->name }}
                    @else Kelas {{ $broadcast->studyGroup?->name }}
                    @endif
                </div>
                <div class="col-md-4"><strong>Penerima:</strong> {{ ucfirst($broadcast->recipient_group) }}</div>
                <div class="col-md-4"><strong>Kanal:</strong> {{ implode(', ', $broadcast->channels ?? []) }}</div>
                <div class="col-md-4"><strong>Ack wajib:</strong> {{ $broadcast->ack_required ? 'Ya' : 'Tidak' }}</div>
                <div class="col-md-4"><strong>Dikirim:</strong> {{ $broadcast->published_at?->format('d/m/Y H:i') }}</div>
                <div class="col-md-4"><strong>Kedaluwarsa:</strong> {{ $broadcast->expires_at?->format('d/m/Y H:i') ?? '-' }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed"><h6 class="mb-0"><i class="ri-list-check me-1"></i>Status Penerima</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Penerima</th><th>Santri</th><th>Dibaca</th><th>Dikonfirmasi</th></tr></thead>
                    <tbody>
                        @forelse($recipients as $notification)
                            <tr>
                                <td>{{ $notification->user?->name ?? '-' }}</td>
                                <td>{{ data_get($notification->data, 'student_name', '-') }}</td>
                                <td>
                                    @if($notification->is_read)
                                        <span class="badge bg-success-subtle text-success">Ya</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Belum</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $acked = \App\Models\AnnouncementAcknowledgement::where('reference_type', \App\Models\HumasAnnouncement::class)
                                            ->where('reference_id', $broadcast->id)
                                            ->where('user_id', $notification->user_id)
                                            ->exists();
                                    @endphp
                                    <span class="badge bg-{{ $acked ? 'success' : 'secondary' }}-subtle text-{{ $acked ? 'success' : 'secondary' }}">
                                        {{ $acked ? 'Ya' : 'Belum' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada penerima.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($recipients->hasPages())<div class="card-footer">{{ $recipients->links() }}</div>@endif
    </div>
@endsection
