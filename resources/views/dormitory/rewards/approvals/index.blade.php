@extends('layouts.master')
@section('title') Persetujuan Penghargaan @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2')
            <a href="{{ route('user.asrama.rewards.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}">
                Penghargaan
            </a>
        @endslot
        @slot('title') Persetujuan @endslot
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

    @php
        $statusCards = [
            'pending' => ['label' => 'Menunggu', 'color' => 'warning', 'icon' => 'ri-time-line', 'note' => 'Perlu ditinjau'],
            'approved' => ['label' => 'Disetujui', 'color' => 'success', 'icon' => 'ri-checkbox-circle-line', 'note' => 'Sudah menjadi penghargaan'],
            'rejected' => ['label' => 'Ditolak', 'color' => 'danger', 'icon' => 'ri-close-circle-line', 'note' => 'Tidak disetujui'],
            'cancelled' => ['label' => 'Dibatalkan', 'color' => 'secondary', 'icon' => 'ri-forbid-line', 'note' => 'Dibatalkan pengaju'],
        ];
    @endphp

    {{-- ============================================================
         STATS CARDS
    ============================================================ --}}
    <div class="row">
        @foreach($statusCards as $key => $card)
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate stat-card h-90">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-{{ $card['color'] }}-subtle rounded">
                                    <i class="{{ $card['icon'] }} text-{{ $card['color'] }}"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <p class="stat-label mb-0">{{ $card['label'] }}</p>
                                <h3 class="stat-value">{{ number_format($counts[$key] ?? 0) }}</h3>
                            </div>
                        </div>
                        <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>{{ $card['note'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Alert info --}}
    <div class="alert alert-info d-flex align-items-start gap-2 mb-3" role="alert">
        <i class="ri-information-line fs-5"></i>
        <div>
            Prestasi santri dari satuan pendidikan otomatis masuk ke daftar ini jika santri tinggal di
            <strong>{{ $dormitory->name ?? 'asrama' }}</strong>.
            Penghargaan baru masuk ke data dan poin asrama setelah disetujui.
        </div>
    </div>

    {{-- ============================================================
         CARD UTAMA
    ============================================================ --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                {{-- HEADER: JUDUL + TAB ──────────────────────────────── --}}
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center mb-2">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Pengajuan Penghargaan</h5>
                            <p class="text-muted mb-0 small">Tinjau prestasi yang diajukan sekolah sebelum menjadi penghargaan asrama.</p>
                        </div>
                    </div>

                    {{-- Tab nav-pills di baris kedua --}}
                    <ul class="nav nav-pills gap-1">
                        @foreach([
                            'pending' => 'Menunggu',
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                            'cancelled' => 'Dibatalkan',
                            'all' => 'Semua',
                        ] as $key => $label)
                            <li class="nav-item">
                                <a class="nav-link {{ $status === $key ? 'active' : '' }}"
                                   href="{{ route('user.asrama.rewards.approvals.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid, 'status' => $key]) }}">
                                    {{ $label }}
                                    @if($key !== 'all' && isset($counts[$key]))
                                        <span class="badge bg-light text-dark ms-1">{{ $counts[$key] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="card-body">
                    {{-- Counter --}}
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $requests->count() }}</strong> dari
                            <strong>{{ $requests->total() }}</strong> pengajuan
                        </small>
                    </div>

                    {{-- ============================================================
                         TABEL (empty state di dalam tbody, dengan Lordicon)
                    ============================================================ --}}
                    <div class="table-container">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Prestasi</th>
                                    <th>Santri</th>
                                    <th>Tingkat / Peringkat</th>
                                    <th>Usulan Katalog</th>
                                    <th class="text-center">Poin</th>
                                    <th class="text-center">Status</th>
                                    <th>Diajukan</th>
                                    <th class="text-center" style="width:80px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $i => $rewardRequest)
                                    <tr>
                                        <td class="text-center text-muted">{{ $requests->firstItem() + $i }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $rewardRequest->title }}</div>
                                            <small class="text-muted">
                                                {{ $rewardRequest->achievement?->event_date?->format('d/m/Y') ?? '—' }}
                                            </small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $rewardRequest->student?->name ?? '—' }}</div>
                                            <small class="text-muted">{{ $rewardRequest->student?->nisn ?? '' }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $rewardRequest->achievement?->level_label ?? '—' }}</div>
                                            <small class="text-muted">{{ $rewardRequest->achievement?->position_label ?? '—' }}</small>
                                        </td>
                                        <td>
                                            {{ $rewardRequest->pointCatalog?->name ?? 'Belum ada usulan' }}
                                            @if($rewardRequest->pointCatalog)
                                                <div><small class="text-muted">{{ $rewardRequest->pointCatalog->code }}</small></div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success">+{{ $rewardRequest->points }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $rewardRequest->status_badge }}-subtle text-{{ $rewardRequest->status_badge }}">
                                                {{ $rewardRequest->status_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <div>{{ $rewardRequest->created_at?->format('d/m/Y H:i') }}</div>
                                            <small class="text-muted">{{ $rewardRequest->requestedBy?->name ?? '—' }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('user.asrama.rewards.approvals.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid, 'requestUuid' => $rewardRequest->id]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Tinjau">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5">
                                            <lord-icon
                                                src="https://cdn.lordicon.com/msoeawqm.json"
                                                trigger="loop"
                                                colors="primary:#121331,secondary:#08a88a"
                                                style="width:75px;height:75px">
                                            </lord-icon>
                                            <h6 class="text-muted mb-1 mt-3">Tidak ada pengajuan</h6>
                                            <p class="text-muted mb-0 small">Tidak ada pengajuan pada status ini.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($requests->hasPages())
                        @include('shared._pagination', ['paginator' => $requests->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection