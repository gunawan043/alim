@extends('layouts.master')
@section('title', 'Distribusi Paket Soal')

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $wLabel = \App\Models\PaketSoal::WORKFLOW_OPTIONS[$paket->workflow_status] ?? $paket->workflow_status;
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.paket-soal.index', ['userId' => $userId]) }}">Paket Soal</a> @endslot
        @slot('title') {{ $paket->kode_paket }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ $paket->judul }}</h4>
            <p class="text-muted mb-0 small">
                {{ $paket->kisiKisi?->subject?->name ?? '-' }}
                · {{ $paket->kisiKisi?->gradeLevel?->name ?? 'Semua Kelas' }}
                · {{ $paket->kisiKisi?->academicYear?->name ?? '-' }}
                · {{ ucfirst($paket->kisiKisi?->semester ?? '-') }}
                · {{ $paket->jumlah_soal_aktual }} soal · {{ $paket->total_bobot_aktual }} bobot
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge {{ $paket->isFinal() ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} p-2">{{ $wLabel }}</span>
            <form method="POST" action="{{ route('user.paket-soal.quality-gate', ['userId' => $userId, 'paketUuid' => $paket->id]) }}">
                @csrf
                <button class="btn btn-soft-warning btn-sm"><i class="ri-scan-2-line me-1"></i> Quality Gate</button>
            </form>
            @if($paket->workflow_status === \App\Models\PaketSoal::WORKFLOW_DRAFT || $paket->workflow_status === \App\Models\PaketSoal::WORKFLOW_REVISI)
                <form method="POST" action="{{ route('user.paket-soal.submit-approval', ['userId' => $userId, 'paketUuid' => $paket->id]) }}">
                    @csrf
                    <button class="btn btn-primary btn-sm"><i class="ri-send-plane-line me-1"></i> Ajukan Approval</button>
                </form>
            @endif
            @if($paket->isFinal())
                <form method="POST" action="{{ route('user.paket-soal.distribute', ['userId' => $userId, 'paketUuid' => $paket->id]) }}"
                      onsubmit="return confirm('Distribusikan paket final ke TU, Waka, Kurikulum, Koordinator, dan KSP?');">
                    @csrf
                    <button class="btn btn-success btn-sm"><i class="ri-share-forward-line me-1"></i> Distribusikan</button>
                </form>
            @endif
        </div>
    </div>

    @php
        $summary = $summary ?? ($paket->similarity_summary ?? []);
        $unapprovedCount = $summary['unapproved'] ?? $notApproved;
        $missingKey = $summary['missing_key'] ?? 0;
        $missingMetadata = $summary['missing_metadata'] ?? 0;
        $hasCompletenessIssue = $unapprovedCount > 0 || $missingKey > 0 || $missingMetadata > 0;
    @endphp

    @if($hasCompletenessIssue)
        <div class="alert alert-warning small" role="alert">
            <i class="ri-error-warning-line me-1"></i><strong>Kelengkapan paket</strong> (hasil quality gate menjadi bahan keputusan reviewer, bukan pengganti approval):
            <span class="badge bg-danger-subtle text-danger ms-1">{{ $unapprovedCount }} soal belum approved</span>
            @if($missingKey > 0)<span class="badge bg-danger-subtle text-danger">{{ $missingKey }} tanpa kunci jawaban</span>@endif
            @if($missingMetadata > 0)<span class="badge bg-warning-subtle text-warning">{{ $missingMetadata }} metadata belum lengkap</span>@endif
            @if($missingKey > 0 || $missingMetadata > 0)
                <div class="mt-1">Soal PG/B-S/Menjodohkan wajib memiliki kunci; materi, pertanyaan, dan TP wajib terisi.</div>
            @endif
        </div>
    @endif

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Duplikasi Internal</p>
                    <h3 class="fw-bold ff-secondary mb-0 {{ ($summary['internal_duplicates'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">{{ $summary['internal_duplicates'] ?? 0 }}</h3>
                    <p class="text-muted mb-0 stat-label">Jalankan quality gate untuk memeriksa</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Kemiripan Historis</p>
                    <h3 class="fw-bold ff-secondary mb-0 {{ ($summary['historical_warnings'] ?? 0) > 0 ? 'text-warning' : 'text-success' }}">{{ $summary['historical_warnings'] ?? 0 }}</h3>
                    <p class="text-muted mb-0 stat-label">Tertinggi {{ $summary['highest_historical'] ?? 0 }}%</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Approval Reviewer</p>
                    <h3 class="fw-bold ff-secondary mb-0">{{ $progress['approved'] }}/{{ $progress['total'] }}</h3>
                    <p class="text-muted mb-0 stat-label">{{ $progress['pending'] }} menunggu · {{ $progress['revision'] }} perbaikan</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Distribusi</p>
                    <h4 class="fw-bold ff-secondary mb-0">{{ $paket->distributed_at ? 'Terkirim' : 'Belum' }}</h4>
                    <p class="text-muted mb-0 stat-label">{{ $paket->distributed_at?->format('d/m/Y H:i') ?? 'Menunggu paket final' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-user-shared-line text-primary me-1"></i>Penerima Distribusi Internal</h5></div>
                <div class="card-body">
                    @forelse($paket->distributions as $distribution)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <div class="fw-semibold">{{ $distribution->recipient_role }}</div>
                                <div class="small text-muted">{{ $distribution->recipient_name ?? '—' }}</div>
                            </div>
                            <span class="badge bg-success-subtle text-success"><i class="ri-check-line me-1"></i>Diterima sistem</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Belum didistribusikan. Paket final akan dikirim ke: Tata Usaha, Waka, Kurikulum, Koordinator, dan KSP.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-printer-line text-primary me-1"></i>Produksi Tata Usaha</h5></div>
                <div class="card-body">
                    @forelse($paket->printJobs as $job)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <div class="fw-semibold">{{ number_format($job->jumlah_cetak) }} lembar</div>
                                <div class="small text-muted">{{ $job->petugas ?? '-' }} · {{ $job->tanggal_produksi?->format('d/m/Y') ?? 'Belum dijadwalkan' }}</div>
                            </div>
                            @php $sColor = ['antri' => 'secondary', 'proses' => 'warning', 'selesai' => 'success'][$job->status] ?? 'secondary'; @endphp
                            <span class="badge bg-{{ $sColor }}-subtle text-{{ $sColor }}">{{ \App\Models\PaketSoalPrintJob::STATUS_OPTIONS[$job->status] ?? $job->status }}</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Belum ada perintah cetak. Tata Usaha mengelola produksi melalui menu TU Paket Soal.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
