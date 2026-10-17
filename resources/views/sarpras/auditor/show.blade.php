@extends('layouts.master')
@section('title') {{ $session->session_code }} @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Auditor @endslot
    @slot('li_2') <a href="{{ route('sarpras.auditor.dashboard') }}">Dashboard</a> @endslot
    @slot('title') {{ $session->session_code }} @endslot
@endcomponent

<div class="row">
    <div class="col-lg-4 mb-3">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Statistik</h5></div>
            <div class="card-body">
                <p class="mb-1"><strong>Total Aset:</strong> {{ $progress['total_assets'] ?? 0 }}</p>
                <p class="mb-1"><strong>Scan Selesai:</strong> {{ $progress['scanned'] ?? 0 }}</p>
                <p class="mb-1"><strong>Temukan:</strong> {{ $progress['found'] ?? 0 }}</p>
                <p class="mb-1 text-danger"><strong>Selisih:</strong> {{ $progress['discrepancies'] ?? 0 }}</p>
                <hr>
                <p class="mb-1"><strong>Jenis:</strong> {{ ucfirst($session->audit_type) }}</p>
                <p class="mb-1"><strong>Auditor:</strong> {{ $session->auditor?->name }}</p>
                <p class="mb-1"><strong>Lokasi:</strong> {{ $session->room?->room_name ?? 'Semua' }}</p>
                <p class="mb-1"><strong>Mulai:</strong> {{ $session->started_at?->format('d/m/Y H:i') }}</p>

                @if($session->status == 'in_progress')
                <hr>
                <form method="POST" action="{{ route('sarpras.auditor.session.close', $session->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-success w-100">
                        <i class="mdi mdi-check-bold me-1"></i> Tutup Session
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        {{-- Scan --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0"><i class="mdi mdi-qrcode-scan me-1"></i> Scan Aset</h5></div>
            <div class="card-body">
                <p class="mb-2">Scan QR / masukkan kode aset, atau gunakan pencarian asset via Passport</p>
                <form action="{{ route('sarpass.scan.start') }}" method="GET" class="input-group mb-2">
                    <input type="text" name="code" class="form-control" placeholder="Kode aset / QR ID..." required>
                    <button type="submit" class="btn btn-primary">Cari</button>
                </form>
                <small class="text-muted">Atau gunakan kamera: <a href="{{ route('sarpass.scan.camera') }}">Buka Kamera</a></small>
            </div>
        </div>

        {{-- Audit Items --}}
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="mdi mdi-format-list-bulleted me-1"></i> Hasil Audit</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Aset</th>
                                <th>Ditemukan</th>
                                <th>Lokasi Fisik</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($audits as $audit)
                            <tr>
                                <td>
                                    <code>{{ $audit->asset?->asset_code }}</code>
                                    <br><small>{{ $audit->asset?->asset_name ?? '-' }}</small>
                                </td>
                                <td>
                                    @if($audit->physical_found)
                                    <span class="badge bg-success">Ya</span>
                                    @else
                                    <span class="badge bg-danger">Tidak</span>
                                    @endif
                                </td>
                                <td>{{ $audit->physicalRoom?->room_name ?? $audit->asset?->room?->room_name ?? '-' }}</td>
                                <td><small>{{ $audit->condition_notes ?? '-' }}</small></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada hasil scan</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
