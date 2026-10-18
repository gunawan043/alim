@extends('layouts.master')
@section('title', 'Audit Syahadah Tasmi’')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') <a href="{{ route('user.tahfidz.tasmi.certificates', ['userId'=>$userId]) }}">Syahadah</a> @endslot @slot('title') Audit Syahadah @endslot @endcomponent

    <div class="row tahfidz-stat">
        @foreach([
            ['Total Syahadah', $stats['total'], 'ri-certificate-line', 'primary', 'Pada tahun ajaran ini'],
            ['Lengkap', $stats['complete'], 'ri-check-double-line', 'success', 'Siap dicetak'],
            ['Perlu Perbaikan', $stats['issues'], 'ri-error-warning-line', 'danger', 'Data belum lengkap'],
        ] as [$label, $value, $icon, $color, $note])
            <div class="col-xl-4 col-md-6"><div class="card card-animate"><div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-{{ $color }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $color }}"></i></span></div>
                    <div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0 stat-label">{{ $label }}</p><h3 class="fw-bold ff-secondary mb-0">{{ number_format($value) }}</h3></div>
                </div>
                <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>{{ $note }}</p>
            </div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-shield-check-line text-primary me-1"></i>Audit Akhir Syahadah</h5><p class="text-muted mb-0 small">Memeriksa nama Arab (A), nilai, predikat, nomor, jenis, dan daftar juz.</p></div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.tahfidz.tasmi.certificates', ['userId'=>$userId]) }}" class="btn btn-soft-info"><i class="ri-arrow-left-line me-1"></i>Rekap Syahadah</a>
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small">Tahun Ajaran</label><select name="academic_year_id" class="form-select" onchange="this.form.submit()">@foreach($academicYears as $ay)<option value="{{ $ay->id }}" @selected($academicYearId===$ay->id)>{{ $ay->name }} — {{ ucfirst($ay->semester) }}</option>@endforeach</select></div>
            </form>
        </div>
        @if($audit->isEmpty())
            <div class="card-body text-center py-5"><i class="ri-shield-check-line fs-1 text-success d-block mb-2"></i><h5>Semua syahadah lengkap</h5><p class="text-muted mb-0">Tidak ada data syahadah yang perlu diperbaiki.</p></div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tahfidz-table">
                    <thead class="table-light"><tr><th>Santri</th><th>Nomor</th><th>Jenis</th><th>Nilai</th><th>Predikat</th><th>Masalah</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        @foreach($audit as $row)
                            @php $certificate = $row['certificate']; @endphp
                            <tr>
                                <td><div class="fw-medium">{{ $certificate->student?->name ?? '—' }}</div><div class="text-muted small">{{ $certificate->student?->nis }}</div><div class="text-muted" style="font-size:11px;direction:rtl;">{{ $certificate->student_name_arabic }}</div></td>
                                <td class="small">{{ $certificate->certificate_number ?: '—' }}</td>
                                <td>{{ $certificate->certificate_type_label }}</td>
                                <td>{{ $certificate->nilai_akhir !== null ? number_format((float) $certificate->nilai_akhir, 2) : '—' }}</td>
                                <td>{{ $certificate->predikat_label }}<div class="text-muted" style="direction:rtl;">{{ $certificate->predikat_arabic }}</div></td>
                                <td><ul class="mb-0 ps-3 small text-danger">@foreach($row['problems'] as $problem)<li>{{ $problem }}</li>@endforeach</ul></td>
                                <td class="text-end"><a href="{{ route('user.tahfidz.tasmi.certificate.print', ['userId'=>$userId,'certificateId'=>$certificate->id]) }}" target="_blank" class="btn btn-sm btn-outline-dark"><i class="ri-printer-line"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
