@extends('layouts.master')
@section('title') Gatepass Asrama @endsection

@section('content')
    @php $userId = request()->route('userId') ?? auth()->id(); @endphp
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Gatepass @endslot
    @endcomponent

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
        <div><h5 class="mb-1"><i class="ri-qr-scan-2-line me-2 text-primary"></i>Gatepass Keluar/Masuk</h5>
            <p class="text-muted small mb-0">Pindai QR santri di gerbang asrama.</p></div>
        <div class="d-flex gap-2">
            <a href="{{ route('user.asrama.gatepass.cards', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}" class="btn btn-outline-primary btn-sm"><i class="ri-printer-line me-1"></i>Kartu QR</a>
            <a href="{{ route('user.asrama.gatepass.scan', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}" class="btn btn-primary btn-sm"><i class="ri-qr-scan-2-line me-1"></i>Scan Gatepass</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach(['keluar' => ['Keluar', 'warning', 'ri-logout-box-r-line'], 'masuk' => ['Masuk', 'success', 'ri-login-box-line'], 'di_luar' => ['Di Luar', 'info', 'ri-map-pin-user-line']] as $key => [$label, $tone, $icon])
            <div class="col-md-4"><div class="card h-100"><div class="card-body py-3 d-flex align-items-center gap-3">
                <span class="avatar-sm"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span>
                <div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ $stats[$key] }}</div></div>
            </div></div></div>
        @endforeach
    </div>

    <div class="card"><div class="card-header"><h5 class="card-title mb-0">Riwayat Hari Ini</h5></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Waktu</th><th>Santri</th><th>Jenis</th><th>Tujuan</th><th>Gerbang</th><th>Petugas</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td class="small">{{ $log->scanned_at?->format('H:i') }}</td>
                    <td>{{ $log->student?->name ?? '-' }}</td>
                    <td><span class="badge bg-{{ $log->type === 'keluar' ? 'warning' : 'success' }}-subtle text-{{ $log->type === 'keluar' ? 'warning' : 'success' }}">{{ ucfirst($log->type) }}</span></td>
                    <td class="small">{{ $log->purpose ?: '—' }}</td>
                    <td class="small">{{ $log->gate ?: '—' }}</td>
                    <td class="small">{{ $log->scanner?->name ?? '-' }}</td>
                </tr>
            @empty
                <x-empty-state colspan="6" title="Belum ada scan gatepass" message="Riwayat keluar/masuk hari ini akan tampil di sini." />
            @endforelse
            </tbody>
        </table></div></div>
        @if($logs->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $logs])</div>@endif
    </div>
@endsection
