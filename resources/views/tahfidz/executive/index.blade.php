@extends('layouts.master')
@section('title', 'Laporan Eksekutif Tahfidz')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Laporan @endslot @slot('title') Laporan Eksekutif Tahfidz @endslot @endcomponent

    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="text-muted small">Tahun ajaran: <strong>{{ $academicYear?->name ?? '—' }}</strong> · {{ $academicYear?->semester ? ucfirst($academicYear->semester) : '' }}</div>
        <a href="{{ route('user.tahfidz.executive.index', array_merge(request()->query(), ['userId'=>$userId,'export'=>1])) }}" class="btn btn-soft-success"><i class="ri-download-2-line me-1"></i>Export Excel</a>
    </div>

    <div class="row tahfidz-stat">
        @foreach([
            ['Sekolah', $stats['schools'], 'ri-building-line', 'primary', $stats['halaqah'].' halaqah'],
            ['Santri', $stats['members'], 'ri-user-heart-line', 'info', 'Aktif'],
            ['Setoran Terverifikasi', $stats['setoran'], 'ri-check-double-line', 'success', 'Rata nilai '.($stats['avg_nilai'] !== null ? number_format((float)$stats['avg_nilai'],1) : '—')],
            ['Juz Selesai', $stats['juz'], 'ri-book-read-line', 'warning', $stats['certificates'].' syahadah'],
        ] as [$label, $value, $icon, $color, $note])
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-{{ $color }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $color }}"></i></span></div>
                    <div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0 stat-label">{{ $label }}</p><h3 class="fw-bold ff-secondary mb-0">{{ number_format($value) }}</h3></div>
                </div>
                <p class="text-muted mb-0 stat-label">{{ $note }}</p>
            </div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-bar-chart-box-line text-primary me-1"></i>Ringkasan per Satuan Pendidikan</h5><p class="text-muted mb-0 small">Diurutkan berdasarkan skor kesehatan: capaian target dan keaktifan setoran 30 hari.</p></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tahfidz-table">
                <thead class="table-light"><tr><th>Sekolah</th><th class="text-center">Halaqah</th><th class="text-center">Santri</th><th class="text-center">Setoran</th><th class="text-center">Rata Nilai</th><th class="text-center">Juz</th><th class="text-center">Target</th><th class="text-center">Tanpa Setoran 30 Hari</th><th class="text-center">Syahadah</th><th class="text-center">Tindak Lanjut</th><th style="min-width:120px">Kesehatan</th></tr></thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td><div class="fw-medium">{{ $row['school']?->name }}</div></td>
                            <td class="text-center">{{ $row['halaqah'] }}</td>
                            <td class="text-center">{{ $row['members'] }}</td>
                            <td class="text-center">{{ $row['verified_setoran'] }}</td>
                            <td class="text-center">{{ $row['avg_nilai'] !== null ? number_format((float)$row['avg_nilai'], 2) : '—' }}</td>
                            <td class="text-center"><span class="badge bg-success-subtle text-success">{{ $row['juz_completed'] }}</span></td>
                            <td class="text-center">{{ $row['target_avg'] !== null ? number_format((float)$row['target_avg'], 1).'%' : '—' }}</td>
                            <td class="text-center"><span class="badge {{ $row['inactive_members'] > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}">{{ $row['inactive_members'] }}</span></td>
                            <td class="text-center">{{ $row['certificates'] }}</td>
                            <td class="text-center">{{ $row['open_interventions'] }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:6px;"><div class="progress-bar bg-{{ $row['health_score'] >= 70 ? 'success' : ($row['health_score'] >= 40 ? 'warning' : 'danger') }}" style="width: {{ $row['health_score'] }}%"></div></div><span class="small fw-semibold">{{ $row['health_score'] }}</span></div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-5">Belum ada data Tahfidz pada tahun ajaran ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body small text-muted"><i class="ri-information-line me-1"></i>Skor kesehatan = 50% rata-rata capaian target + 50% persentase santri yang memiliki setoran terverifikasi dalam 30 hari terakhir. Gunakan halaman Monitoring dan Tindak Lanjut untuk menindaklanjuti sekolah dengan skor rendah.</div>
    </div>
@endsection
