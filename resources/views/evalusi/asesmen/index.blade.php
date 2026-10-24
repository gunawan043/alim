@extends('layouts.master')
@section('title') Pusat Asesmen @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Evaluasi @endslot
        @slot('title') Pusat Asesmen @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── Header pusat asesmen ───────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-4">
            <div class="row align-items-center g-4">
                <div class="col-xl-8">
                    <span class="badge bg-primary-subtle text-primary mb-2">Ruang Kerja Asesmen</span>
                    <h3 class="mb-2">Pusat Asesmen</h3>
                    <p class="text-muted mb-3">
                        Satu pintu untuk kisi-kisi, bank soal, paket, cetak naskah, LJK, sampai nilai.
                        Gunakan tombol aksi di tabel untuk membuka halaman yang dituju.
                    </p>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <a href="{{ route('user.kisi-kisi-soal.create', ['userId' => $userId]) }}" class="btn btn-success">
                            <i class="ri-add-line me-1"></i> Buat Asesmen
                        </a>
                        <a href="{{ route('user.bank-soal.index', ['userId' => $userId]) }}" class="btn btn-soft-primary">
                            <i class="ri-archive-2-line me-1"></i> Bank Soal
                        </a>
                        <a href="{{ route('user.paket-soal.index', ['userId' => $userId]) }}" class="btn btn-soft-primary">
                            <i class="ri-stack-line me-1"></i> Paket Soal
                        </a>
                        <a href="{{ route('user.ljk.index', ['userId' => $userId]) }}" class="btn btn-soft-primary">
                            <i class="ri-scan-2-line me-1"></i> LJK
                        </a>
                        <a href="{{ route('user.asesmen.cetak', ['userId' => $userId]) }}" class="btn btn-light">
                            <i class="ri-printer-line me-1"></i> Pusat Cetak
                        </a>
                        <a href="{{ route('user.review-soal.index', ['userId' => $userId]) }}" class="btn btn-soft-secondary">
                            <i class="ri-shield-check-line me-1"></i> Review Soal
                        </a>
                        <a href="{{ route('user.schools.guru-mapel.index', ['userId' => $userId]) }}" class="btn btn-soft-secondary">
                            <i class="ri-book-open-line me-1"></i> Buku Nilai
                        </a>
                        <a href="{{ route('user.schools.nilai-kelas.index', ['userId' => $userId]) }}" class="btn btn-soft-secondary">
                            <i class="ri-survey-line me-1"></i> Nilai Kelas
                        </a>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="rounded border bg-light-subtle p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-semibold"><i class="ri-flow-chart me-1 text-primary"></i>Alur Asesmen</span>
                            <span class="badge bg-success-subtle text-success">Terpadu</span>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-1 small">
                            <span class="badge bg-white text-dark border">Kisi</span>
                            <i class="ri-arrow-right-s-line text-muted"></i>
                            <span class="badge bg-white text-dark border">Soal</span>
                            <i class="ri-arrow-right-s-line text-muted"></i>
                            <span class="badge bg-white text-dark border">Paket</span>
                            <i class="ri-arrow-right-s-line text-muted"></i>
                            <span class="badge bg-white text-dark border">Cetak</span>
                            <i class="ri-arrow-right-s-line text-muted"></i>
                            <span class="badge bg-white text-dark border">LJK</span>
                            <i class="ri-arrow-right-s-line text-muted"></i>
                            <span class="badge bg-white text-dark border">Nilai</span>
                        </div>
                        <p class="small text-muted mb-0 mt-2">
                            Paket harus final dan published sebelum LJK diterbitkan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Statistik ringkas ────────────────────────────────────────────── --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('user.asesmen.index', ['userId' => $userId]) }}" class="card card-animate h-100 text-decoration-none">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">Asesmen / Kisi-kisi</p>
                            <h3 class="mb-0">{{ $stats['total'] }}</h3>
                            <small class="text-muted">{{ $stats['final'] }} sudah punya paket final</small>
                        </div>
                        <span class="avatar-title bg-primary-subtle text-primary rounded fs-4" style="width:44px;height:44px;">
                            <i class="ri-file-list-3-line"></i>
                        </span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('user.bank-soal.index', ['userId' => $userId]) }}" class="card card-animate h-100 text-decoration-none">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">Bank Soal</p>
                            <h3 class="mb-0">{{ $stats['banks'] }}</h3>
                            <small class="text-muted">Bank soal yang dapat diakses</small>
                        </div>
                        <span class="avatar-title bg-success-subtle text-success rounded fs-4" style="width:44px;height:44px;">
                            <i class="ri-archive-2-line"></i>
                        </span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('user.paket-soal.index', ['userId' => $userId]) }}" class="card card-animate h-100 text-decoration-none">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">Paket Soal</p>
                            <h3 class="mb-0">{{ $stats['pakets'] }}</h3>
                            <small class="text-muted">Draft, review, dan final</small>
                        </div>
                        <span class="avatar-title bg-warning-subtle text-warning rounded fs-4" style="width:44px;height:44px;">
                            <i class="ri-stack-line"></i>
                        </span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('user.ljk.index', ['userId' => $userId]) }}" class="card card-animate h-100 text-decoration-none">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">LJK</p>
                            <h3 class="mb-0">{{ $stats['ljk'] }}</h3>
                            <small class="text-muted">Sesi ujian & lembar jawaban</small>
                        </div>
                        <span class="avatar-title bg-info-subtle text-info rounded fs-4" style="width:44px;height:44px;">
                            <i class="ri-scan-2-line"></i>
                        </span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- ── Tabel dokumen ────────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-1">Dokumen Asesmen</h5>
                    <p class="text-muted mb-0">Klik tab untuk berpindah antar-dokumen. Semua tombol aksi tersedia di kolom kanan.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('user.kisi-kisi-soal.create', ['userId' => $userId]) }}" class="btn btn-sm btn-success">
                        <i class="ri-add-line me-1"></i> Asesmen Baru
                    </a>
                    <a href="{{ route('user.bank-soal.create', ['userId' => $userId]) }}" class="btn btn-sm btn-soft-primary">
                        <i class="ri-add-line me-1"></i> Bank Baru
                    </a>
                </div>
            </div>
        </div>
        <div class="card-header px-3 pt-3 pb-0">
            <ul class="nav nav-tabs-custom card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-assessments" type="button" role="tab">
                        <i class="ri-file-list-3-line me-1"></i>Asesmen
                        <span class="badge bg-primary-subtle text-primary ms-1">{{ $stats['total'] }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-banks" type="button" role="tab">
                        <i class="ri-archive-2-line me-1"></i>Bank Soal
                        <span class="badge bg-success-subtle text-success ms-1">{{ $stats['banks'] }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pakets" type="button" role="tab">
                        <i class="ri-stack-line me-1"></i>Paket Soal
                        <span class="badge bg-warning-subtle text-warning ms-1">{{ $stats['pakets'] }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ljk" type="button" role="tab">
                        <i class="ri-scan-2-line me-1"></i>LJK
                        <span class="badge bg-info-subtle text-info ms-1">{{ $stats['ljk'] }}</span>
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                {{-- ── Tab Asesmen ───────────────────────────────────────── --}}
                <div class="tab-pane fade show active" id="tab-assessments" role="tabpanel">
                    <form method="GET" class="row g-2 align-items-end mb-3">
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Cari asesmen</label>
                            <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Judul asesmen...">
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Tahun Ajaran</label>
                            <select name="academic_year_id" class="form-select">
                                <option value="">Semua tahun ajaran</option>
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ request('academic_year_id') === $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-select">
                                <option value="">Semua</option>
                                <option value="ganjil" {{ request('semester') === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                <option value="genap" {{ request('semester') === 'genap' ? 'selected' : '' }}>Genap</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <button class="btn btn-primary w-100" type="submit"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <a href="{{ route('user.asesmen.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Asesmen</th>
                                    <th>Periode</th>
                                    <th>Dokumen</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assessments as $assessment)
                                    @php
                                        $state = $states[$assessment->id] ?? ['label' => '-', 'color' => 'secondary'];
                                        $latestPaket = $latestPakets[$assessment->id] ?? null;
                                        $latestLjk = $latestPaket ? ($latestLjks[$latestPaket->id] ?? null) : null;
                                        $bankUrl = route('user.bank-soal.index', [
                                            'userId' => $userId,
                                            'subject_id' => $assessment->subject_id,
                                            'academic_year_id' => $assessment->academic_year_id,
                                            'semester' => $assessment->semester,
                                        ]);
                                        $paketUrl = $latestPaket
                                            ? route('user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $latestPaket->id])
                                            : route('user.paket-soal.create', ['userId' => $userId, 'kisiKisiId' => $assessment->id]);
                                        $ljkUrl = $latestLjk
                                            ? route('user.ljk.show', ['userId' => $userId, 'ljkUuid' => $latestLjk->id])
                                            : ($latestPaket && $latestPaket->isFinal()
                                                ? route('user.ljk.create', ['userId' => $userId, 'paket_soal_id' => $latestPaket->id])
                                                : route('user.ljk.index', ['userId' => $userId]));
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('user.asesmen.show', ['userId' => $userId, 'kisiUuid' => $assessment->id]) }}" class="fw-semibold link-primary">
                                                {{ $assessment->judul }}
                                            </a>
                                            <div class="small text-muted">
                                                {{ $assessment->subject?->name ?? '-' }} · {{ $assessment->gradeLevel?->name ?? 'Semua tingkat' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div>{{ $assessment->academicYear?->name ?? '-' }}</div>
                                            <small class="text-muted">{{ ucfirst($assessment->semester ?? '-') }}</small>
                                        </td>
                                        <td>
                                            <div class="small">{{ $assessment->items_count }} item kisi</div>
                                            <div class="small text-muted">{{ $assessment->pakets_count }} paket</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $state['color'] }}-subtle text-{{ $state['color'] }}">{{ $state['label'] }}</span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex flex-wrap justify-content-end gap-1">
                                                <a href="{{ route('user.kisi-kisi-soal.edit', ['userId' => $userId, 'kisiUuid' => $assessment->id]) }}"
                                                   class="btn btn-sm btn-soft-secondary" title="Edit kisi-kisi">
                                                    <i class="ri-pencil-line me-1"></i>Kisi
                                                </a>
                                                <a href="{{ $bankUrl }}" class="btn btn-sm btn-soft-success" title="Buka bank soal mapel ini">
                                                    <i class="ri-archive-2-line me-1"></i>Soal
                                                </a>
                                                <a href="{{ $paketUrl }}" class="btn btn-sm btn-soft-primary" title="Buka / buat paket soal">
                                                    <i class="ri-stack-line me-1"></i>Paket
                                                </a>
                                                @if($latestPaket)
                                                    <a href="{{ route('user.paket-soal.naskah.cetak', ['userId' => $userId, 'paketUuid' => $latestPaket->id]) }}"
                                                       target="_blank" class="btn btn-sm btn-soft-dark" title="Cetak naskah PDF">
                                                        <i class="ri-printer-line me-1"></i>Cetak
                                                    </a>
                                                @else
                                                    <span class="btn btn-sm btn-light disabled" title="Paket belum dibuat">
                                                        <i class="ri-printer-line me-1"></i>Cetak
                                                    </span>
                                                @endif
                                                <a href="{{ $ljkUrl }}" class="btn btn-sm btn-soft-info" title="Buka / terbitkan LJK">
                                                    <i class="ri-scan-2-line me-1"></i>LJK
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            Belum ada asesmen. Mulai dengan tombol <strong>Buat Asesmen</strong>.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $assessments->links() }}</div>
                </div>

                {{-- ── Tab Bank Soal ─────────────────────────────────────── --}}
                <div class="tab-pane fade" id="tab-banks" role="tabpanel">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h6 class="mb-1">Bank Soal</h6>
                            <p class="text-muted mb-0 small">Input soal, kelola wacana, dan pantau status soal approved.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('user.bank-soal.create', ['userId' => $userId]) }}" class="btn btn-sm btn-success"><i class="ri-add-line me-1"></i>Bank Baru</a>
                            <a href="{{ route('user.bank-soal.index', ['userId' => $userId]) }}" class="btn btn-sm btn-light">Lihat Semua <i class="ri-arrow-right-line ms-1"></i></a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bank Soal</th>
                                    <th>Mapel / Tingkat</th>
                                    <th>Periode</th>
                                    <th>Soal</th>
                                    <th>Diperbarui</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($banks as $bank)
                                    <tr>
                                        <td>
                                            <a href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}" class="fw-semibold link-primary">{{ $bank->nama }}</a>
                                            <div class="small text-muted">{{ \App\Models\Soal::BAHASA_OPTIONS[$bank->bahasa] ?? 'Latin' }} · {{ $bank->jenis_soal ? ucwords(str_replace('_', ' ', $bank->jenis_soal)) : '-' }}</div>
                                        </td>
                                        <td>{{ $bank->subject?->name ?? '-' }}<div class="small text-muted">{{ $bank->gradeLevel?->name ?? 'Semua tingkat' }}</div></td>
                                        <td>{{ $bank->academicYear?->name ?? '-' }}<div class="small text-muted">{{ ucfirst($bank->semester ?? '-') }}</div></td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success">{{ $bank->approved_soal_count }} approved</span>
                                            <div class="small text-muted">{{ $bank->soal_count }} total soal</div>
                                        </td>
                                        <td class="small text-muted">{{ $bank->updated_at?->diffForHumans() ?? '-' }}</td>
                                        <td class="text-end">
                                            <div class="d-flex flex-wrap justify-content-end gap-1">
                                                <a href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}" class="btn btn-sm btn-soft-primary">Buka</a>
                                                <a href="{{ route('user.soal.create', ['userId' => $userId, 'bankId' => $bank->id]) }}" class="btn btn-sm btn-soft-success">Input Soal</a>
                                                <a href="{{ route('user.bank-soal.soal-list', ['userId' => $userId, 'bankId' => $bank->id]) }}" class="btn btn-sm btn-soft-secondary">Daftar Soal</a>
                                                <a href="{{ route('user.bank-soal.edit', ['userId' => $userId, 'id' => $bank->id]) }}" class="btn btn-sm btn-light" title="Edit bank"><i class="ri-pencil-line"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-5">Belum ada bank soal yang dapat diakses.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3"><a href="{{ route('user.bank-soal.index', ['userId' => $userId]) }}" class="btn btn-sm btn-light">Lihat semua bank soal <i class="ri-arrow-right-line ms-1"></i></a></div>
                </div>

                {{-- ── Tab Paket Soal ────────────────────────────────────── --}}
                <div class="tab-pane fade" id="tab-pakets" role="tabpanel">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h6 class="mb-1">Paket Soal</h6>
                            <p class="text-muted mb-0 small">Susun paket, validasi, preview, dan cetak naskah.</p>
                        </div>
                        <a href="{{ route('user.paket-soal.index', ['userId' => $userId]) }}" class="btn btn-sm btn-light">Lihat Semua <i class="ri-arrow-right-line ms-1"></i></a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Paket</th>
                                    <th>Mapel / Tingkat</th>
                                    <th>Status</th>
                                    <th>Butir</th>
                                    <th>Diperbarui</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pakets as $paket)
                                    @php
                                        $paketBadge = match ($paket->workflow_status) {
                                            \App\Models\PaketSoal::WORKFLOW_REVIEW => 'info',
                                            \App\Models\PaketSoal::WORKFLOW_REVISI => 'warning',
                                            \App\Models\PaketSoal::WORKFLOW_APPROVED => 'primary',
                                            \App\Models\PaketSoal::WORKFLOW_PUBLISHED => 'success',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $paket->id]) }}" class="fw-semibold link-primary">{{ $paket->judul }}</a>
                                            <div class="small text-muted">{{ $paket->kode_paket }} · v{{ $paket->versi }}</div>
                                        </td>
                                        <td>{{ $paket->kisiKisi?->subject?->name ?? '-' }}<div class="small text-muted">{{ $paket->kisiKisi?->gradeLevel?->name ?? 'Semua tingkat' }}</div></td>
                                        <td><span class="badge bg-{{ $paketBadge }}-subtle text-{{ $paketBadge }}">{{ \App\Models\PaketSoal::WORKFLOW_OPTIONS[$paket->workflow_status] ?? $paket->workflow_status }}</span></td>
                                        <td>{{ $paket->items_count }} butir</td>
                                        <td class="small text-muted">{{ $paket->updated_at?->diffForHumans() ?? '-' }}</td>
                                        <td class="text-end">
                                            <div class="d-flex flex-wrap justify-content-end gap-1">
                                                <a href="{{ route('user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $paket->id]) }}" class="btn btn-sm btn-soft-primary">Buka</a>
                                                <a href="{{ route('user.paket-soal.validasi-soal', ['userId' => $userId, 'paketUuid' => $paket->id]) }}" class="btn btn-sm btn-soft-secondary">Validasi</a>
                                                <a href="{{ route('user.paket-soal.naskah.cetak', ['userId' => $userId, 'paketUuid' => $paket->id]) }}" target="_blank" class="btn btn-sm btn-soft-dark"><i class="ri-printer-line me-1"></i>Cetak</a>
                                                @if($paket->isFinal())
                                                    <a href="{{ route('user.ljk.create', ['userId' => $userId, 'paket_soal_id' => $paket->id]) }}" class="btn btn-sm btn-soft-info"><i class="ri-scan-2-line me-1"></i>LJK</a>
                                                @else
                                                    <span class="btn btn-sm btn-light disabled" title="Paket harus final dan published"><i class="ri-scan-2-line me-1"></i>LJK</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-5">Belum ada paket soal.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3"><a href="{{ route('user.paket-soal.index', ['userId' => $userId]) }}" class="btn btn-sm btn-light">Lihat semua paket <i class="ri-arrow-right-line ms-1"></i></a></div>
                </div>

                {{-- ── Tab LJK ───────────────────────────────────────────── --}}
                <div class="tab-pane fade" id="tab-ljk" role="tabpanel">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h6 class="mb-1">LJK &amp; Nilai</h6>
                            <p class="text-muted mb-0 small">Cetak lembar, pindai, verifikasi, lalu sinkronkan ke buku nilai.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('user.ljk.create', ['userId' => $userId]) }}" class="btn btn-sm btn-success"><i class="ri-add-line me-1"></i>Terbitkan LJK</a>
                            <a href="{{ route('user.ljk.index', ['userId' => $userId]) }}" class="btn btn-sm btn-light">Semua LJK <i class="ri-arrow-right-line ms-1"></i></a>
                            <a href="{{ route('user.schools.guru-mapel.index', ['userId' => $userId]) }}" class="btn btn-sm btn-soft-secondary"><i class="ri-book-open-line me-1"></i>Buku Nilai</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ujian</th>
                                    <th>Kelas / Paket</th>
                                    <th>Peserta</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ljkExams as $exam)
                                    @php
                                        $ljkBadge = match ($exam->status) {
                                            \App\Models\LjkExam::STATUS_AKTIF => 'success',
                                            \App\Models\LjkExam::STATUS_PENGUMPULAN => 'info',
                                            \App\Models\LjkExam::STATUS_REVIEW => 'warning',
                                            \App\Models\LjkExam::STATUS_FINAL => 'primary',
                                            \App\Models\LjkExam::STATUS_TERSINKRON => 'success',
                                            \App\Models\LjkExam::STATUS_DIBATALKAN => 'secondary',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('user.ljk.show', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}" class="fw-semibold link-primary">{{ $exam->judul }}</a>
                                            <div class="small text-muted">{{ $exam->code }}</div>
                                        </td>
                                        <td>{{ $exam->studyGroup?->name ?? '-' }}<div class="small text-muted">{{ $exam->paketSoal?->judul ?? '-' }}</div></td>
                                        <td>
                                            <div>{{ $exam->participants_count }} peserta</div>
                                            <div class="small text-muted">{{ $exam->sheets_count }} lembar</div>
                                        </td>
                                        <td><span class="badge bg-{{ $ljkBadge }}-subtle text-{{ $ljkBadge }}">{{ $exam->statusLabel() }}</span></td>
                                        <td class="small text-muted">{{ $exam->tanggal?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="text-end">
                                            <div class="d-flex flex-wrap justify-content-end gap-1">
                                                <a href="{{ route('user.ljk.show', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}" class="btn btn-sm btn-soft-primary">Buka</a>
                                                @if($exam->canScan())
                                                    <a href="{{ route('user.ljk.scan', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}" class="btn btn-sm btn-soft-success"><i class="ri-camera-line me-1"></i>Scan</a>
                                                @endif
                                                <a href="{{ route('user.ljk.hasil', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}" class="btn btn-sm btn-soft-secondary">Hasil</a>
                                                <a href="{{ route('user.ljk.pdf', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}" target="_blank" class="btn btn-sm btn-soft-dark"><i class="ri-printer-line me-1"></i>PDF</a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-5">Belum ada LJK. Terbitkan LJK dari paket yang sudah final.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3"><a href="{{ route('user.ljk.index', ['userId' => $userId]) }}" class="btn btn-sm btn-light">Lihat semua LJK <i class="ri-arrow-right-line ms-1"></i></a></div>
                </div>
            </div>
        </div>
    </div>
@endsection
