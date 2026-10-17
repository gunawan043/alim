@extends('layouts.master')
@section('title') Laporan Kebersihan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Laporan Kebersihan @endslot
    @endcomponent

    <div class="row g-3 mb-3">
        @foreach([
            ['Total Inspeksi', $summary['total'], 'primary', 'ri-file-list-3-line'],
            ['Rata-rata Skor', $summary['avg_score'], 'info', 'ri-bar-chart-line'],
            ['Lulus Standar', $summary['passed'], 'success', 'ri-checkbox-circle-line'],
            ['Tindak Lanjut', $summary['pending_follow_up'], 'warning', 'ri-time-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body py-3 d-flex align-items-center gap-3">
                        <span class="avatar-sm"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span>
                        <div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ $value }}</div></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-broom-line me-2 text-primary"></i>Evaluasi Kebersihan</h5>
            <span class="text-muted small">{{ $dormitory->name }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th><th>Lokasi</th><th class="text-center">Skor</th>
                            <th class="text-center">Status</th><th>Temuan</th><th>Tindak Lanjut</th><th>Pemeriksa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentInspections as $si)
                            <tr>
                                <td class="small">{{ $si->inspection_date?->format('d/m/Y') ?? '-' }}</td>
                                <td>{{ $rooms[$si->location_id] ?? 'Asrama' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $si->score >= 80 ? 'success' : ($si->score >= 60 ? 'warning' : 'danger') }}-subtle text-{{ $si->score >= 80 ? 'success' : ($si->score >= 60 ? 'warning' : 'danger') }}">{{ $si->score }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $si->is_passed ? 'success' : 'danger' }}-subtle text-{{ $si->is_passed ? 'success' : 'danger' }}">{{ $si->is_passed ? 'Lulus' : 'Perlu Perbaikan' }}</span>
                                </td>
                                <td class="small text-muted">{{ \Illuminate\Support\Str::limit((string) $si->findings, 60) ?: '—' }}</td>
                                <td class="small">
                                    @if($si->follow_up_completed_at)
                                        <span class="text-success">Selesai {{ $si->follow_up_completed_at->format('d/m/Y') }}</span>
                                    @elseif($si->follow_up_deadline)
                                        <span class="text-warning">Batas {{ $si->follow_up_deadline->format('d/m/Y') }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small">{{ $si->inspectedBy?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <x-empty-state colspan="7" title="Belum ada inspeksi kebersihan"
                                message="Data inspeksi sanitasi asrama akan tampil di sini." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
