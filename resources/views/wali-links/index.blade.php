@extends('layouts.master')
@section('title') Penghubungan Wali @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Wali Santri @endslot
        @slot('title') Penghubungan Wali @endslot
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
        $tabs = [
            'pending' => ['label' => 'Menunggu', 'color' => 'warning', 'icon' => 'ri-time-line'],
            'active' => ['label' => 'Aktif', 'color' => 'success', 'icon' => 'ri-checkbox-circle-line'],
            'suspended' => ['label' => 'Ditangguhkan', 'color' => 'secondary', 'icon' => 'ri-pause-circle-line'],
            'revoked' => ['label' => 'Dicabut', 'color' => 'danger', 'icon' => 'ri-close-circle-line'],
            'all' => ['label' => 'Semua', 'color' => 'primary', 'icon' => 'ri-list-check'],
        ];
        $relationshipLabels = [
            'ayah' => 'Ayah', 'ibu' => 'Ibu', 'kakek' => 'Kakek', 'nenek' => 'Nenek',
            'paman' => 'Paman', 'bibi' => 'Bibi', 'wali' => 'Wali', 'lainnya' => 'Lainnya',
        ];
        $statusLabels = [
            'pending' => 'Menunggu', 'active' => 'Aktif',
            'suspended' => 'Ditangguhkan', 'revoked' => 'Dicabut',
        ];
    @endphp

    {{-- ============================================================
         STATS CARDS
    ============================================================ --}}
    <div class="row">
        @foreach(['pending', 'active', 'suspended', 'revoked'] as $summaryStatus)
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate stat-card h-90">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-{{ $tabs[$summaryStatus]['color'] }}-subtle rounded">
                                    <i class="{{ $tabs[$summaryStatus]['icon'] }} text-{{ $tabs[$summaryStatus]['color'] }}"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <p class="stat-label mb-0">{{ $tabs[$summaryStatus]['label'] }}</p>
                                <h3 class="stat-value">{{ number_format($counts[$summaryStatus] ?? 0) }}</h3>
                            </div>
                        </div>
                        <p class="stat-note mb-0">
                            <i class="ri-parent-line me-1"></i>Penghubungan wali {{ strtolower($tabs[$summaryStatus]['label']) }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ============================================================
         CARD UTAMA
    ============================================================ --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                {{-- Tab nav-tabs di card-header --}}
                <div class="card-header border-bottom-dashed">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        @foreach($tabs as $key => $tab)
                            <li class="nav-item">
                                <a class="nav-link {{ $status === $key ? 'active' : '' }}"
                                   href="{{ route('user.wali-links.index', ['userId' => $userId, 'status' => $key, 'search' => $search, 'school_id' => $schoolId]) }}">
                                    <i class="{{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
                                    <span class="badge bg-{{ $tab['color'] }}-subtle text-{{ $tab['color'] }} ms-1">
                                        {{ $key === 'all' ? array_sum($counts) : ($counts[$key] ?? 0) }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="card-body">
                    {{-- ============================================================
                         FILTER CEPAT (inline, server-side)
                    ============================================================ --}}
                    <form method="GET" action="{{ route('user.wali-links.index', ['userId' => $userId]) }}" class="mb-3">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <div class="row g-2 align-items-end">
                            @if($isSuperAdmin)
                                <div class="col-12 col-md-6 col-lg-3">
                                    <label class="form-label fs-12 text-muted mb-1">Satuan Pendidikan</label>
                                    <select name="school_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="">Semua Satuan Pendidikan</option>
                                        @foreach($schools as $school)
                                            <option value="{{ $school->id }}" {{ $schoolId === $school->id ? 'selected' : '' }}>
                                                {{ $school->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-12 col-md-6 col-lg-{{ $isSuperAdmin ? '5' : '8' }}">
                                <label class="form-label fs-12 text-muted mb-1">Cari</label>
                                <input type="text" name="search" class="form-control form-control-sm"
                                       value="{{ $search }}"
                                       placeholder="Cari nama wali, email, No HP, nama santri, NISN/NIK...">
                            </div>
                            <div class="col-12 col-md-auto d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                                    <i class="ri-search-line me-1"></i> Cari
                                </button>
                                @if($search !== '' || ($isSuperAdmin && $schoolId !== null))
                                    <a href="{{ route('user.wali-links.index', ['userId' => $userId, 'status' => $status]) }}"
                                       class="btn btn-light btn-sm flex-shrink-0" title="Reset">
                                        <i class="ri-refresh-line"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>

                    @if($search !== '')
                        <div class="mb-3">
                            <span class="text-muted small">Filter aktif:</span>
                            <span class="filter-badge active"><i class="ri-search-line"></i> "{{ $search }}"</span>
                        </div>
                    @endif

                    {{-- Counter --}}
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $links->count() }}</strong> dari
                            <strong>{{ $links->total() }}</strong> penghubungan
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
                                    <th style="min-width:150px;">Tanggal</th>
                                    <th style="min-width:220px;">Wali</th>
                                    <th style="min-width:220px;">Santri</th>
                                    <th>Sekolah</th>
                                    <th>Hubungan</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="min-width:190px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($links as $i => $link)
                                    <tr>
                                        <td class="text-center text-muted">{{ $links->firstItem() + $i }}</td>
                                        <td class="small text-muted">
                                            {{ $link->created_at?->format('d/m/Y H:i') ?? '—' }}
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $link->user?->name ?? '—' }}</div>
                                            <div class="small text-muted">{{ $link->user?->email ?? '—' }}</div>
                                            @if($link->user?->no_hp)
                                                <div class="small text-muted"><i class="ri-phone-line me-1"></i>{{ $link->user->no_hp }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $link->student?->name ?? '—' }}</div>
                                            <div class="small text-muted">NISN: {{ $link->student?->nisn ?: '—' }}</div>
                                        </td>
                                        <td class="small">{{ $link->student?->school?->name ?? '—' }}</td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">
                                                {{ $relationshipLabels[$link->role] ?? ucfirst((string) $link->role) }}
                                            </span>
                                            @if($link->is_primary)
                                                <span class="badge bg-primary-subtle text-primary ms-1">Utama</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $tabs[$link->status]['color'] ?? 'secondary' }}-subtle text-{{ $tabs[$link->status]['color'] ?? 'secondary' }}">
                                                <i class="{{ $tabs[$link->status]['icon'] ?? 'ri-flag-line' }} me-1"></i>
                                                {{ $statusLabels[$link->status] ?? ucfirst((string) $link->status) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                @if($link->status === 'pending')
                                                    <form method="POST"
                                                          action="{{ route('user.wali-links.approve', ['userId' => $userId, 'id' => $link->id]) }}"
                                                          onsubmit="return confirm('Setujui penghubungan wali ini?');"
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Setujui">
                                                            <i class="ri-check-line me-1"></i>Setujui
                                                        </button>
                                                    </form>
                                                    <form method="POST"
                                                          action="{{ route('user.wali-links.reject', ['userId' => $userId, 'id' => $link->id]) }}"
                                                          onsubmit="return confirm('Tolak pengajuan penghubungan ini?');"
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Tolak">
                                                            <i class="ri-close-line me-1"></i>Tolak
                                                        </button>
                                                    </form>
                                                @elseif($link->status === 'active')
                                                    <form method="POST"
                                                          action="{{ route('user.wali-links.suspend', ['userId' => $userId, 'id' => $link->id]) }}"
                                                          onsubmit="return confirm('Tangguhkan hubungan wali ini sementara?');"
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Tangguhkan">
                                                            <i class="ri-pause-line me-1"></i>Tangguhkan
                                                        </button>
                                                    </form>
                                                    <form method="POST"
                                                          action="{{ route('user.wali-links.revoke', ['userId' => $userId, 'id' => $link->id]) }}"
                                                          onsubmit="return confirm('Cabut hubungan wali ini? Wali akan kehilangan akses ke data santri.');"
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Cabut">
                                                            <i class="ri-link-unlink me-1"></i>Cabut
                                                        </button>
                                                    </form>
                                                @elseif($link->status === 'suspended')
                                                    <form method="POST"
                                                          action="{{ route('user.wali-links.revoke', ['userId' => $userId, 'id' => $link->id]) }}"
                                                          onsubmit="return confirm('Cabut hubungan wali ini?');"
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Cabut">
                                                            <i class="ri-link-unlink me-1"></i>Cabut
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-muted small">Tidak ada aksi</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <lord-icon
                                                src="https://cdn.lordicon.com/msoeawqm.json"
                                                trigger="loop"
                                                colors="primary:#121331,secondary:#08a88a"
                                                style="width:75px;height:75px">
                                            </lord-icon>
                                            <h6 class="text-muted mb-1 mt-3">Belum ada data penghubungan</h6>
                                            <p class="text-muted mb-0 small">
                                                {{ $status === 'pending' ? 'Tidak ada pengajuan yang menunggu persetujuan.' : 'Belum ada data pada status ini.' }}
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($links->hasPages())
                        @include('shared._pagination', ['paginator' => $links->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection