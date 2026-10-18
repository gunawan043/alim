@extends('layouts.master')
@section('title', 'Audit Akhir Tahfidz')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Audit @endslot @slot('title') Audit Akhir Tahfidz @endslot @endcomponent

    <div class="d-flex justify-content-end mb-2">
        <a href="{{ route('user.tahfidz.audit.index', array_merge(request()->query(), ['userId'=>$userId,'export'=>1])) }}" class="btn btn-soft-success"><i class="ri-download-2-line me-1"></i>Export Excel</a>
    </div>

    <div class="alert {{ $stats['total_issues'] > 0 ? 'alert-warning' : 'alert-success' }} d-flex align-items-center gap-2">
        <i class="ri-shield-check-line fs-4"></i>
        <div><strong>{{ $stats['total_issues'] }} temuan audit.</strong> {{ $academicYear?->name }} — periksa daftar di bawah dan tindak lanjuti.</div>
    </div>

    <div class="row tahfidz-stat">
        @foreach([
            ['Setoran Pending', $stats['pending'], 'ri-time-line', 'warning', $stats['pending_old'].' lebih dari 7 hari'],
            ['Progres Juz', $stats['verified_without_progress'] + $stats['recap_missing'], 'ri-line-chart-line', 'info', 'Belum progres/rekap'],
            ['Target & Mutaba’ah', $stats['target_missing'] + $stats['mutabaah_missing'], 'ri-focus-3-line', 'danger', 'Belum diatur/tertinggal'],
            ['Tasmi & Syahadah', $stats['tasmi_without_certificate'] + $stats['certificate_issues'], 'ri-award-line', 'primary', 'Belum lengkap'],
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

    <div class="card mt-1">
        <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0"><i class="ri-time-line text-warning me-1"></i>Setoran Pending &gt; 7 Hari</h5><a href="{{ route('user.tahfidz.setoran.index', ['userId'=>$userId,'verification'=>'pending']) }}" class="btn btn-sm btn-outline-warning">Buka Verifikasi</a></div>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Santri</th><th>Halaqah</th><th class="text-end">Nilai</th></tr></thead><tbody>
            @forelse($pendingOld as $setoran)
                <tr><td>{{ $setoran->setoran_date?->format('d/m/Y') }}</td><td>{{ $setoran->student?->name }}</td><td>{{ $setoran->group?->name }}</td><td class="text-end">{{ $setoran->nilai_setoran ?? '—' }}</td></tr>
            @empty<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada setoran pending lama.</td></tr>@endforelse
        </tbody></table></div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header border-bottom-dashed"><h6 class="card-title mb-0"><i class="ri-line-chart-line text-info me-1"></i>Setoran Terverifikasi Tanpa Progres Juz</h6></div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Santri</th><th>Halaqah</th><th class="text-end">Aksi</th></tr></thead><tbody>
                    @forelse($verifiedWithoutProgress as $setoran)
                        <tr><td>{{ $setoran->student?->name }}</td><td>{{ $setoran->group?->name }}</td><td class="text-end"><a href="{{ route('user.tahfidz.santri.show', ['userId'=>$userId,'studentId'=>$setoran->student_id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td></tr>
                    @empty<tr><td colspan="3" class="text-center text-muted py-3">Tidak ada temuan.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header border-bottom-dashed"><h6 class="card-title mb-0"><i class="ri-file-chart-line text-info me-1"></i>Progres Juz Tanpa Rekap</h6></div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Santri</th><th>Halaqah</th><th class="text-end">Aksi</th></tr></thead><tbody>
                    @forelse($recapMissing as $member)
                        <tr><td>{{ $member->student?->name }}</td><td>{{ $member->group?->name }}</td><td class="text-end"><a href="{{ route('user.tahfidz.santri.show', ['userId'=>$userId,'studentId'=>$member->student_id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td></tr>
                    @empty<tr><td colspan="3" class="text-center text-muted py-3">Tidak ada temuan.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header border-bottom-dashed"><h6 class="card-title mb-0"><i class="ri-focus-3-line text-danger me-1"></i>Target Belum Diatur</h6></div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Santri</th><th>Halaqah</th><th class="text-end">Aksi</th></tr></thead><tbody>
                    @forelse($targetMissing as $member)
                        <tr><td>{{ $member->student?->name }}</td><td>{{ $member->group?->name }}</td><td class="text-end"><a href="{{ route('user.tahfidz.halaqah.show', ['userId'=>$userId,'id'=>$member->tahfidz_group_id]) }}" class="btn btn-sm btn-outline-warning"><i class="ri-add-line"></i></a></td></tr>
                    @empty<tr><td colspan="3" class="text-center text-muted py-3">Semua santri memiliki target.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header border-bottom-dashed"><h6 class="card-title mb-0"><i class="ri-calendar-check-line text-danger me-1"></i>Mutaba’ah Tertinggal &gt; 7 Hari</h6></div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Santri</th><th>Halaqah</th><th class="text-end">Aksi</th></tr></thead><tbody>
                    @forelse($membersWithoutMutabaah as $member)
                        <tr><td>{{ $member->student?->name }}</td><td>{{ $member->group?->name }}</td><td class="text-end"><a href="{{ route('user.tahfidz.mutabaah.index', ['userId'=>$userId,'student_id'=>$member->student_id]) }}" class="btn btn-sm btn-outline-warning"><i class="ri-calendar-check-line"></i></a></td></tr>
                    @empty<tr><td colspan="3" class="text-center text-muted py-3">Semua mutaba’ah terpantau.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header border-bottom-dashed"><h6 class="card-title mb-0"><i class="ri-award-line text-primary me-1"></i>Nilai Tasmi’ Belum Bersyahadah</h6></div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Santri</th><th>Sesi</th><th>Nilai</th><th class="text-end">Aksi</th></tr></thead><tbody>
                    @forelse($tasmiWithoutCertificate as $participant)
                        <tr><td>{{ $participant->student?->name }}</td><td>{{ $participant->session?->session_name }}</td><td>{{ $participant->score?->nilai_akhir !== null ? number_format((float) $participant->score->nilai_akhir, 2) : '—' }}</td><td class="text-end"><a href="{{ route('user.tahfidz.tasmi.show', ['userId'=>$userId,'id'=>$participant->tasmian_session_id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td></tr>
                    @empty<tr><td colspan="4" class="text-center text-muted py-3">Semua nilai tasmi’ sudah bersyahadah.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center"><h6 class="card-title mb-0"><i class="ri-certificate-line text-primary me-1"></i>Syahadah Belum Lengkap</h6><a href="{{ route('user.tahfidz.tasmi.certificate.audit', ['userId'=>$userId]) }}" class="btn btn-sm btn-outline-primary">Audit Detail</a></div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Santri</th><th>Nomor</th><th>Nilai</th><th class="text-end">Aksi</th></tr></thead><tbody>
                    @forelse($certificateIssues as $certificate)
                        <tr><td>{{ $certificate->student?->name }}</td><td class="small">{{ $certificate->certificate_number ?: '—' }}</td><td>{{ $certificate->nilai_akhir !== null ? number_format((float) $certificate->nilai_akhir, 2) : '—' }}</td><td class="text-end"><a href="{{ route('user.tahfidz.tasmi.certificate.print', ['userId'=>$userId,'certificateId'=>$certificate->id]) }}" target="_blank" class="btn btn-sm btn-outline-dark"><i class="ri-printer-line"></i></a></td></tr>
                    @empty<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada syahadah bermasalah.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
    </div>
@endsection
