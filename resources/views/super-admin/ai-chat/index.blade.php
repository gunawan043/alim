@extends('layouts.master')
@section('title', 'Monitoring Chat AI')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('li_2') Chat AI @endslot
        @slot('title') Monitoring Chat AI @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-chat-history-line text-primary me-1"></i>Riwayat Chat Pengguna</h5>
                    <p class="text-muted mb-0 small">Pantau percakapan AI per pengguna. Data ini hanya untuk Super Admin.</p>
                </div>
                <span class="badge bg-primary-subtle text-primary">{{ $conversations->total() }} percakapan</span>
            </div>
        </div>

        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">Cari pengguna / judul</label>
                    <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Nama, email, atau judul chat">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Sekolah</label>
                    <select name="school_id" class="form-select">
                        <option value="">Semua sekolah</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}" @selected($schoolId === $school->id)>{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Dari tanggal</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Sampai tanggal</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-primary" title="Filter"><i class="ri-filter-3-line"></i></button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Pengguna</th>
                        <th>Judul Percakapan</th>
                        <th>Sekolah</th>
                        <th class="text-center">Pesan</th>
                        <th>Aktivitas Terakhir</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($conversations as $conversation)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $conversation->user?->name ?? 'Pengguna dihapus' }}</div>
                                <div class="text-muted small">{{ $conversation->user?->email }}</div>
                            </td>
                            <td>
                                <div>{{ $conversation->title ?: 'Tanpa judul' }}</div>
                                <div class="text-muted" style="font-size:11px;">Model: {{ $conversation->model ?: '—' }}</div>
                            </td>
                            <td class="small">{{ $conversation->school?->name ?? 'Global' }}</td>
                            <td class="text-center"><span class="badge bg-secondary-subtle text-secondary">{{ $conversation->messages_count }}</span></td>
                            <td class="small text-muted">{{ $conversation->last_message_at?->format('d M Y H:i') ?? $conversation->created_at?->format('d M Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('user.sa.ai-chat.show', ['userId' => $userId, 'conversation' => $conversation->id]) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="ri-eye-line me-1"></i>Lihat
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada percakapan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($conversations->hasPages())
            <div class="card-body border-top">{{ $conversations->links() }}</div>
        @endif
    </div>
@endsection
