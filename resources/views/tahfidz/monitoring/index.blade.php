@extends('layouts.master')
@section('title', 'Monitoring Tahfidz')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Rekap & Monitoring @endslot @slot('title') Monitoring Santri @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row tahfidz-stat">
        @foreach([
            ['Halaqah', $stats['halaqah'], 'ri-team-line', 'primary', 'Halaqah pada filter'],
            ['Santri Aktif', $stats['members'], 'ri-user-heart-line', 'info', 'Anggota halaqah'],
            ['Setoran Terverifikasi', $stats['verified'], 'ri-check-double-line', 'success', 'Periode filter'],
            ['Perlu Perhatian', $stats['behind'], 'ri-error-warning-line', 'danger', 'Santri tertinggal'],
        ] as [$label, $value, $icon, $color, $note])
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body py-3">
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
            <div><h5 class="card-title mb-0"><i class="ri-line-chart-line text-primary me-1"></i>Rekap & Monitoring Santri</h5><p class="text-muted mb-0 small">Gabungan setoran, progres juz, target, mutaba’ah, dan tasmi’.</p></div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.tahfidz.setoran.index', ['userId'=>$userId,'verification'=>'pending']) }}" class="btn btn-soft-warning"><i class="ri-time-line me-1"></i>Verifikasi ({{ $stats['pending'] }})</a>
                <a href="{{ route('user.tahfidz.tasmi.certificates', ['userId'=>$userId]) }}" class="btn btn-soft-info"><i class="ri-certificate-line me-1"></i>Syahadah ({{ $stats['certificates'] }})</a>
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small">Tahun Ajaran</label><select name="academic_year_id" class="form-select" onchange="this.form.submit()">@foreach($academicYears as $ay)<option value="{{ $ay->id }}" @selected($academicYearId===$ay->id)>{{ $ay->name }} — {{ ucfirst($ay->semester) }}</option>@endforeach</select></div>
                @if($schools->isNotEmpty())<div class="col-md-3"><label class="form-label small">Sekolah</label><select name="school_id" class="form-select" onchange="this.form.submit()"><option value="">Semua</option>@foreach($schools as $school)<option value="{{ $school->id }}" @selected($schoolId===$school->id)>{{ $school->name }}</option>@endforeach</select></div>@endif
                <div class="col-md-2"><label class="form-label small">Dari</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control"></div>
                <div class="col-md-2"><label class="form-label small">Sampai</label><input type="date" name="date_to" value="{{ $dateTo }}" class="form-control"></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Terapkan</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tahfidz-table">
                <thead class="table-light"><tr><th>Santri</th><th>Halaqah</th><th>Setoran Terakhir</th><th class="text-center">Setoran</th><th class="text-center">Rata Nilai</th><th class="text-center">Juz</th><th class="text-center">Target</th><th>Mutaba’ah</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($memberPaginator as $row)
                        @php
                            $student = $row['student'];
                            $reasons = $row['reasons'];
                            $risk = count($reasons) > 0;
                        @endphp
                        <tr>
                            <td><div class="fw-medium">{{ $student?->name }}</div><div class="text-muted small">{{ $student?->nis }}</div></td>
                            <td>{{ $row['group']?->name ?? '—' }}</td>
                            <td>@if($row['last_setoran'])<div>{{ \Carbon\Carbon::parse($row['last_setoran'])->format('d/m/Y') }}</div><div class="text-muted small">{{ $row['setoran_days'] }} hari lalu</div>@else<span class="text-danger small">Belum ada</span>@endif</td>
                            <td class="text-center">{{ $row['setoran_count'] }}</td>
                            <td class="text-center">{{ $row['avg_nilai'] !== null ? number_format((float) $row['avg_nilai'], 1) : '—' }}</td>
                            <td class="text-center"><span class="badge bg-success-subtle text-success">{{ $row['juz_completed'] }}</span></td>
                            <td class="text-center">{{ $row['target_percent'] !== null ? number_format((float) $row['target_percent'], 1).'%' : '—' }}</td>
                            <td>@if($row['last_mutabaah'])<div>{{ \Carbon\Carbon::parse($row['last_mutabaah'])->format('d/m/Y') }}</div><div class="text-muted small">{{ $row['last_mutabaah_days'] }} hari lalu</div>@else<span class="text-danger small">Belum ada</span>@endif</td>
                            <td>
                                @if($risk)
                                    <span class="badge bg-danger-subtle text-danger">Perlu Perhatian</span>
                                    <div class="small text-muted mt-1">{{ implode(' · ', $reasons) }}</div>
                                @else
                                    <span class="badge bg-success-subtle text-success">Baik</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if($student?->id)<a href="{{ route('user.students.show', ['userId'=>$userId,'santriUuid'=>$student->id]) }}" class="btn btn-sm btn-outline-primary" title="Detail santri"><i class="ri-user-line"></i></a>@endif
                                @if($row['group']?->id)<a href="{{ route('user.tahfidz.halaqah.show', ['userId'=>$userId,'id'=>$row['group']->id]) }}" class="btn btn-sm btn-outline-info" title="Halaqah"><i class="ri-team-line"></i></a>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-5"><i class="ri-line-chart-line fs-3 d-block mb-2"></i>Belum ada santri pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($memberPaginator->hasPages())<div class="card-body border-top">{{ $memberPaginator->links() }}</div>@endif
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center"><h6 class="card-title mb-0"><i class="ri-time-line text-warning me-1"></i>Setoran Menunggu Verifikasi</h6><span class="badge bg-warning-subtle text-warning">{{ $stats['pending'] }}</span></div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Santri</th><th>Halaqah</th><th class="text-end">Nilai</th></tr></thead><tbody>
                    @forelse($pendingSetorans as $setoran)
                        <tr><td>{{ $setoran->setoran_date?->format('d/m') }}</td><td>{{ $setoran->student?->name }}</td><td>{{ $setoran->group?->name }}</td><td class="text-end">{{ $setoran->nilai_setoran ?? '—' }}</td></tr>
                    @empty<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada antrean verifikasi.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center"><h6 class="card-title mb-0"><i class="ri-award-line text-primary me-1"></i>Nilai Tasmi’ Belum Bersyahadah</h6><span class="badge bg-primary-subtle text-primary">{{ $tasmiPending->count() }}</span></div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Santri</th><th>Sesi</th><th>Nilai</th><th>Predikat</th><th class="text-end">Aksi</th></tr></thead><tbody>
                    @forelse($tasmiPending as $participant)
                        <tr><td>{{ $participant->student?->name }}</td><td>{{ $participant->session?->session_name }}</td><td>{{ $participant->score?->nilai_akhir !== null ? number_format((float) $participant->score->nilai_akhir, 2) : '—' }}</td><td>{{ $participant->score?->predikat_label ?? '—' }}</td><td class="text-end"><a href="{{ route('user.tahfidz.tasmi.show', ['userId'=>$userId,'id'=>$participant->tasmian_session_id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td></tr>
                    @empty<tr><td colspan="5" class="text-center text-muted py-3">Semua nilai tasmi’ sudah bersyahadah.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
    </div>
@endsection
