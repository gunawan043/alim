@extends('layouts.master')

@section('title', 'Pantauan Santri Keluar-Masuk')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Satuan Keamanan @endslot
        @slot('li_2') Verifikasi Izin Santri @endslot
        @slot('title') {{ \App\Http\Controllers\Security\SecurityGateController::TABS[$tab] }} @endslot
    @endcomponent

    @component('components.hrd-page-header', [
        'stats' => [
            ['label' => 'Santri di Luar', 'value' => number_format($stats['outside']), 'icon' => 'ri-walk-line', 'color' => 'warning'],
            ['label' => 'Terlambat', 'value' => number_format($stats['late']), 'icon' => 'ri-timer-flash-line', 'color' => 'danger'],
            ['label' => 'Keluar Hari Ini', 'value' => number_format($stats['out_today']), 'icon' => 'ri-logout-box-r-line', 'color' => 'primary'],
            ['label' => 'Kembali Hari Ini', 'value' => number_format($stats['in_today']), 'icon' => 'ri-login-box-line', 'color' => 'success'],
        ],
    ])
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" role="tablist">
                        @foreach(\App\Http\Controllers\Security\SecurityGateController::TABS as $key => $label)
                            <li class="nav-item">
                                <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
                                   href="{{ route('user.keamanan.gate.index', ['userId' => $userId, 'tab' => $key]) }}">
                                    @if($key === 'log') <i class="ri-file-list-2-line me-1"></i>
                                    @elseif($key === 'late') <i class="ri-timer-line me-1"></i>
                                    @else <i class="ri-user-location-line me-1"></i>
                                    @endif
                                    {{ $label }}
                                    @if($key === 'late' && $stats['late'] > 0)
                                        <span class="badge bg-danger ms-1">{{ $stats['late'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <form method="GET" class="row g-2 align-items-end"
                          action="{{ route('user.keamanan.gate.index', ['userId' => $userId]) }}">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Cari Santri</label>
                            <input type="text" name="search" class="form-control form-control-sm"
                                   value="{{ request('search') }}" placeholder="Nama atau NISN">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Asrama</label>
                            <select name="dormitory_id" class="form-select form-select-sm">
                                <option value="">Semua asrama</option>
                                @foreach($dormitories as $dormitory)
                                    <option value="{{ $dormitory->id }}" {{ request('dormitory_id') === $dormitory->id ? 'selected' : '' }}>
                                        {{ $dormitory->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Dari</label>
                            <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Sampai</label>
                            <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="ri-search-line"></i></button>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze">
                            <thead class="table-light">
                                <tr>
                                    <th>Santri</th>
                                    <th>Asrama</th>
                                    <th>Jenis Izin</th>
                                    <th>Tujuan</th>
                                    <th>Rencana Kembali</th>
                                    <th>Keluar</th>
                                    <th>Kembali</th>
                                    <th>Durasi</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permits as $permit)
                                    @php
                                        $statusMap = [
                                            'pending' => ['secondary', 'Menunggu'],
                                            'approved' => ['info', 'Disetujui'],
                                            'picked_up' => ['warning', 'Di Luar'],
                                            'overdue' => ['danger', 'Terlambat'],
                                            'returned' => ['success', 'Kembali'],
                                            'rejected' => ['dark', 'Ditolak'],
                                        ];
                                        [$color, $label] = $statusMap[$permit->status] ?? ['secondary', ucfirst($permit->status)];
                                        $isLate = $permit->status === 'overdue'
                                            || ($permit->status === 'picked_up' && $permit->expected_return_datetime && $permit->expected_return_datetime->isPast());
                                    @endphp
                                    <tr class="{{ $isLate ? 'table-danger' : '' }}">
                                        <td>
                                            <div class="fw-semibold">{{ $permit->student->name ?? '—' }}</div>
                                            <div class="small text-muted">{{ $permit->student->nisn ?? '' }}</div>
                                        </td>
                                        <td>{{ $permit->dormitory->name ?? '—' }}</td>
                                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $permit->permit_type }}</span></td>
                                        <td>{{ $permit->destination ?: '—' }}</td>
                                        <td>
                                            {{ optional($permit->expected_return_datetime)->format('d/m/Y H:i') ?? '—' }}
                                            @if($isLate && $permit->expected_return_datetime)
                                                <div class="small text-danger fw-semibold">
                                                    <i class="ri-alarm-warning-line me-1"></i>{{ $permit->expected_return_datetime->diffForHumans() }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ optional($permit->pickup_scanned_at)->format('d/m H:i') ?? '—' }}</td>
                                        <td>{{ optional($permit->return_scanned_at)->format('d/m H:i') ?? '—' }}</td>
                                        <td>
                                            @if($permit->pickup_scanned_at)
                                                {{ $permit->pickup_scanned_at->diffForHumans($permit->return_scanned_at ?? now(), ['parts' => 2, 'short' => true]) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">{{ $label }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="9"
                                        title="{{ $tab === 'late' ? 'Tidak ada santri terlambat' : ($tab === 'outside' ? 'Semua santri berada di asrama' : 'Belum ada aktivitas keluar-masuk') }}"
                                        message="Data diambil dari modul perizinan asrama." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($permits->hasPages())
                        @include('shared._pagination', ['paginator' => $permits])
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
