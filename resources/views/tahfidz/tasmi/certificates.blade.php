@extends('layouts.master')
@section('title', 'Syahadah Tasmi’')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') <a href="{{ route('user.tahfidz.tasmi.index', ['userId'=>$userId]) }}">Tasmi’</a> @endslot @slot('title') Syahadah Tasmi’ @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row tahfidz-stat">
        @foreach([
            ['Total Syahadah', $stats['total'], 'ri-certificate-line', 'primary', 'Sesuai filter'],
            ['Terbit Bulan Ini', $stats['this_month'], 'ri-calendar-check-line', 'success', 'Syahadah baru'],
            ['Sudah Dicetak', $stats['printed'], 'ri-printer-line', 'info', 'Pernah dibuka/cetak'],
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
            <div><h5 class="card-title mb-0"><i class="ri-certificate-line text-primary me-1"></i>Rekap Syahadah Tasmi’</h5><p class="text-muted mb-0 small">Syahadah diterbitkan dari peserta tasmi’ yang sudah memiliki nilai.</p></div>
            <div class="d-flex gap-2"><a href="{{ route('user.tahfidz.tasmi.certificate.audit', ['userId'=>$userId]) }}" class="btn btn-warning"><i class="ri-shield-check-line me-1"></i>Audit Syahadah</a><a href="{{ route('user.tahfidz.tasmi.index', ['userId'=>$userId]) }}" class="btn btn-soft-info"><i class="ri-award-line me-1"></i>Sesi Tasmi’</a></div>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small">Tahun Ajaran</label><select name="academic_year_id" class="form-select" onchange="this.form.submit()">@foreach($academicYears as $ay)<option value="{{ $ay->id }}" @selected($academicYearId===$ay->id)>{{ $ay->name }} — {{ ucfirst($ay->semester) }}</option>@endforeach</select></div>
                @if($schools->isNotEmpty())<div class="col-md-3"><label class="form-label small">Sekolah</label><select name="school_id" class="form-select" onchange="this.form.submit()"><option value="">Semua</option>@foreach($schools as $school)<option value="{{ $school->id }}" @selected(request('school_id')===$school->id)>{{ $school->name }}</option>@endforeach</select></div>@endif
                <div class="col-md-3"><label class="form-label small">Jenis</label><select name="certificate_type" class="form-select" onchange="this.form.submit()"><option value="">Semua</option>@foreach(['juz_tunggal'=>'Juz Tunggal','5_juz'=>'5 Juz','10_juz'=>'10 Juz','15_juz'=>'15 Juz','20_juz'=>'20 Juz','25_juz'=>'25 Juz','30_juz'=>'30 Juz','khatam'=>'Khatam'] as $key=>$label)<option value="{{ $key }}" @selected(request('certificate_type')===$key)>{{ $label }}</option>@endforeach</select></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tahfidz-table">
                <thead class="table-light"><tr><th>Santri</th><th>Kelas</th><th>Jenis</th><th>Nomor</th><th>Tanggal</th><th>Nilai</th><th>Predikat</th><th class="text-center">Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($certificates as $certificate)
                        <tr>
                            <td><div class="fw-medium">{{ $certificate->student?->name }}</div><div class="text-muted small">{{ $certificate->student?->nis }}</div><div class="text-muted" style="font-size:11px;direction:rtl;">{{ $certificate->student_name_arabic }}</div></td>
                            <td>{{ $certificate->class_name ?: '—' }}</td>
                            <td><span class="badge bg-primary-subtle text-primary">{{ $certificate->certificate_type_label }}</span><div class="text-muted small">{{ $certificate->golongan }}</div>@if($certificate->materi)<div class="text-muted small">{{ \Illuminate\Support\Str::limit($certificate->materi, 45) }}</div>@endif</td>
                            <td class="small">{{ $certificate->certificate_number }}</td>
                            <td>{{ $certificate->issued_date?->format('d M Y') }}</td>
                            <td>{{ $certificate->nilai_akhir !== null ? number_format((float) $certificate->nilai_akhir, 2) : '—' }}</td>
                            <td>{{ $certificate->predikat_label }}<div class="text-muted" style="direction:rtl;">{{ $certificate->predikat_arabic }}</div></td>
                            <td class="text-center"><span class="badge {{ $certificate->is_printed ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $certificate->is_printed ? 'Dicetak' : 'Belum' }}</span></td>
                            <td class="text-end"><a href="{{ route('user.tahfidz.tasmi.certificate.print', ['userId'=>$userId,'certificateId'=>$certificate->id]) }}" target="_blank" class="btn btn-sm btn-outline-dark" title="Cetak"><i class="ri-printer-line"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-5"><i class="ri-certificate-line fs-3 d-block mb-2"></i>Belum ada syahadah. Terbitkan dari peserta tasmi’ yang sudah dinilai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
