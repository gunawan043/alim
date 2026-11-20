@extends('layouts.master')

@section('title', 'Log Sistem')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('li_2') Log & Monitoring @endslot
        @slot('title') Log Sistem @endslot
    @endcomponent

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.66rem;letter-spacing:.5px">Entri Terbaca</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($stats['total']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.66rem;letter-spacing:.5px">Critical / Alert</p>
                    <h4 class="mb-0 fw-bold text-danger">{{ number_format($stats['critical']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.66rem;letter-spacing:.5px">Error</p>
                    <h4 class="mb-0 fw-bold text-danger">{{ number_format($stats['error']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.66rem;letter-spacing:.5px">Warning</p>
                    <h4 class="mb-0 fw-bold text-warning">{{ number_format($stats['warning']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-0">Log Error &amp; Sistem</h5>
                <p class="text-muted mb-0">Cuplikan 3 MB terakhir dari <code>storage/logs/laravel.log</code>.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.sa.login-activities.index', ['userId' => $userId]) }}" class="btn btn-soft-primary btn-sm">
                    <i class="ri-shield-keyhole-line me-1"></i>Aktivitas Login
                </a>
                <a href="{{ route('user.sa.audit-logs.index', ['userId' => $userId]) }}" class="btn btn-soft-primary btn-sm">
                    <i class="ri-file-history-line me-1"></i>Audit Log
                </a>
                <a href="{{ route('user.sa.system-logs.download') }}" class="btn btn-soft-danger btn-sm">
                    <i class="ri-download-cloud-2-line me-1"></i>Download
                </a>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <select name="level" class="form-select form-select-sm">
                        <option value="">Semua Level</option>
                        @foreach($levels as $level)
                            <option value="{{ $level }}" {{ request('level') === $level ? 'selected' : '' }}>{{ $level }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari pesan error, exception, atau stack trace..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-sm w-100"><i class="ri-search-line me-1"></i>Filter</button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('user.sa.system-logs.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:150px">Waktu</th>
                            <th style="width:110px">Level</th>
                            <th>Pesan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $levelColors = [
                                'EMERGENCY' => 'danger', 'ALERT' => 'danger', 'CRITICAL' => 'danger',
                                'ERROR' => 'danger', 'WARNING' => 'warning', 'NOTICE' => 'info',
                                'INFO' => 'secondary', 'DEBUG' => 'secondary',
                            ];
                        @endphp
                        @forelse($entries as $entry)
                            <tr>
                                <td class="small text-muted">{{ $entry['time'] }}</td>
                                <td><span class="badge bg-{{ $levelColors[$entry['level']] ?? 'secondary' }}-subtle text-{{ $levelColors[$entry['level']] ?? 'secondary' }}">{{ $entry['level'] }}</span></td>
                                <td>
                                    <div class="small">{{ \Illuminate\Support\Str::limit($entry['message'], 260) }}</div>
                                    @if($entry['trace'])
                                        <details class="mt-1">
                                            <summary class="small text-primary" style="cursor:pointer">Lihat stack trace</summary>
                                            <pre class="bg-light border rounded p-2 mt-2 mb-0" style="max-height:260px;overflow:auto;font-size:11px">{{ $entry['trace'] }}</pre>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada entri log yang cocok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
