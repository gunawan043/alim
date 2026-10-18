@extends('layouts.master')
@section('title', 'Konsistensi Rapor–Leger–Syahadah')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Audit @endslot @slot('title') Konsistensi Rapor–Leger–Syahadah @endslot @endcomponent

    <div class="alert {{ $stats['total'] > 0 ? 'alert-warning' : 'alert-success' }} d-flex align-items-center gap-2">
        <i class="ri-shield-check-line fs-4"></i>
        <div><strong>{{ $stats['total'] }} ketidaksesuaian ditemukan.</strong> {{ $academicYear?->name ?? '' }} — bandingkan nilai rapor THF, jumlah juz leger, dan syahadah.</div>
    </div>

    <div class="row tahfidz-stat">
        @foreach([
            ['Temuan Rapor', $stats['rapor'], 'ri-book-open-line', 'primary', 'Nilai THF tersimpan'],
            ['Temuan Leger', $stats['leger'], 'ri-table-line', 'info', 'Jumlah juz'],
            ['Temuan Syahadah', $stats['syahadah'], 'ri-certificate-line', 'warning', 'Nilai/jenis/nama Arab'],
            ['Total', $stats['total'], 'ri-error-warning-line', 'danger', 'Seluruh ketidaksesuaian'],
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
        <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center"><div><h5 class="card-title mb-0"><i class="ri-shield-check-line text-primary me-1"></i>Audit Konsistensi</h5><p class="text-muted mb-0 small">Nilai yang diharapkan vs nilai yang tersimpan.</p></div><a href="{{ route('user.tahfidz.audit.index', ['userId'=>$userId]) }}" class="btn btn-soft-info">Audit Umum</a></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tahfidz-table">
                <thead class="table-light"><tr><th>Kategori</th><th>Santri</th><th>Konteks</th><th>Seharusnya</th><th>Tersimpan</th><th>Keterangan</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($issues as $issue)
                        <tr>
                            <td><span class="badge bg-warning-subtle text-warning">{{ $issue['category'] }}</span></td>
                            <td><div class="fw-medium">{{ $issue['student']?->name ?? '—' }}</div><div class="text-muted small">{{ $issue['student']?->nis }}</div></td>
                            <td class="small">{{ $issue['context'] ?? '—' }}</td>
                            <td class="fw-semibold text-success">{{ $issue['expected'] }}</td>
                            <td class="fw-semibold text-danger">{{ $issue['stored'] }}</td>
                            <td class="small text-muted">{{ $issue['detail'] }}</td>
                            <td class="text-end">@if($issue['student_id'])<a href="{{ route('user.tahfidz.santri.show', ['userId'=>$userId,'studentId'=>$issue['student_id']]) }}" class="btn btn-sm btn-outline-primary" title="Detail santri"><i class="ri-eye-line"></i></a>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ri-shield-check-line fs-3 d-block mb-2"></i>Semua data konsisten.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
