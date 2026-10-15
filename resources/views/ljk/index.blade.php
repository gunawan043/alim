@extends('layouts.master')
@section('title') LJK Smart Scanner @endsection

@section('css')
    @include('ljk._styles')
@endsection

@section('content')
    @php
        $total = $statistics['total'] ?? 0;
        $aktif = $statistics['aktif'] ?? 0;
        $final = $statistics['final'] ?? 0;
        $tersinkron = $statistics['tersinkron'] ?? 0;
        $dibatalkan = $statistics['dibatalkan'] ?? 0;
        $statusCounts = $statistics['status_counts'] ?? [];
        $ratioAktif = $total > 0 ? round($aktif / $total * 100) : 0;
        $ratioFinal = $total > 0 ? round(($final + $tersinkron) / $total * 100) : 0;

        $statusMeta = [
            'draft' => ['label' => 'Draft', 'color' => 'secondary'],
            'aktif' => ['label' => 'Siap Dipindai', 'color' => 'primary'],
            'pengumpulan' => ['label' => 'Pengumpulan', 'color' => 'warning'],
            'review' => ['label' => 'Perlu Pemeriksaan', 'color' => 'warning'],
            'final' => ['label' => 'Final', 'color' => 'success'],
            'tersinkron' => ['label' => 'Tersinkron', 'color' => 'success'],
            'dibatalkan' => ['label' => 'Dibatalkan', 'color' => 'danger'],
        ];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Bank Soal &amp; Asesmen @endslot
        @slot('title') LJK Smart Scanner @endslot
    @endcomponent

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- STATISTIK --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2">
                                <i class="ri-scan-2-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Ujian LJK</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($total) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-2 stat-label">
                        <i class="ri-information-line me-1"></i>Terbit dari Paket Soal final
                    </p>
                    <div class="progress" style="height:5px;">
                        <div class="progress-bar bg-success" style="width:{{ $ratioFinal }}%"></div>
                    </div>
                    <p class="text-muted mb-0 mt-1 stat-label">{{ $ratioFinal }}% sudah final/tersinkron</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2">
                                <i class="ri-camera-3-line text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Berjalan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($aktif) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-2 stat-label">
                        <i class="ri-time-line me-1"></i>Siap dipindai s.d. menunggu pemeriksaan
                    </p>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-primary-subtle text-primary" style="font-size:10px;">
                            Siap {{ number_format($statusCounts['aktif'] ?? 0) }}
                        </span>
                        <span class="badge bg-warning-subtle text-warning" style="font-size:10px;">
                            Dikumpulkan {{ number_format($statusCounts['pengumpulan'] ?? 0) }}
                        </span>
                        <span class="badge bg-warning-subtle text-warning" style="font-size:10px;">
                            Review {{ number_format($statusCounts['review'] ?? 0) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="ri-check-double-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Final & Tersinkron</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($final + $tersinkron) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-2 stat-label">
                        <i class="ri-upload-cloud-2-line me-1"></i>Nilai siap/tersalin ke buku nilai
                    </p>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-success-subtle text-success" style="font-size:10px;">
                            Final {{ number_format($final) }}
                        </span>
                        <span class="badge bg-success-subtle text-success" style="font-size:10px;">
                            Tersinkron {{ number_format($tersinkron) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-2">
                                <i class="ri-close-circle-line text-danger"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Dibatalkan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($dibatalkan) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-archive-line me-1"></i>Riwayat tetap tersimpan untuk audit
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="ljkList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">Daftar Ujian LJK</h5>
                                <p class="text-muted mb-0 stat-label">
                                    Lembar jawaban otomatis dari Paket Soal — pindai dengan kamera HP.
                                </p>
                            </div>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-start gap-2">
                                <form method="GET" action="{{ route('user.ljk.index', ['userId' => $userId]) }}" class="d-flex gap-2">
                                    @if ($status)
                                        <input type="hidden" name="status" value="{{ $status }}">
                                    @endif
                                    <input type="text" class="form-control" name="q"
                                        placeholder="Cari judul, kode, atau paket soal..."
                                        value="{{ $search ?? '' }}" style="width: 260px;">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-search-line"></i> Cari
                                    </button>
                                </form>
                                <a href="{{ route('user.ljk.create', ['userId' => $userId]) }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Terbitkan LJK
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- FILTER CEPAT --}}
                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}"
                           class="filter-badge {{ !$status ? 'active' : '' }}">Semua</a>
                        @foreach (\App\Models\LjkExam::STATUS_OPTIONS as $key => $label)
                            @if (($statusCounts[$key] ?? 0) > 0 || $status === $key)
                                <a href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => null]) }}"
                                   class="filter-badge {{ $status === $key ? 'active' : '' }}">
                                    <i class="ri-circle-fill" style="font-size:8px;"></i>{{ $label }}
                                    <span class="badge bg-light text-dark ms-1">{{ number_format($statusCounts[$key] ?? 0) }}</span>
                                </a>
                            @endif
                        @endforeach
                        <span class="text-muted small ms-2 me-2">·</span>
                        @foreach (['sts' => 'STS', 'sas' => 'SAS', 'ulangan_harian' => 'Ulangan', 'try_out' => 'Try Out'] as $key => $label)
                            <a href="{{ request()->fullUrlWithQuery(['jenis' => $jenis === $key ? null : $key, 'page' => null]) }}"
                               class="filter-badge {{ ($jenis ?? null) === $key ? 'active' : '' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>#</th>
                                <th>Ujian</th>
                                <th>Paket Soal</th>
                                <th>Rombel</th>
                                <th>Progress Lembar</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($exams as $exam)
                                @php
                                    $badge = ['draft' => 'secondary', 'aktif' => 'primary', 'pengumpulan' => 'warning', 'review' => 'warning', 'final' => 'success', 'tersinkron' => 'success', 'dibatalkan' => 'danger'][$exam->status] ?? 'secondary';
                                    $pctScan = $exam->sheets_count > 0 ? round($exam->scanned_sheets_count / $exam->sheets_count * 100) : 0;
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration + ($exams->currentPage() - 1) * $exams->perPage() }}</td>
                                    <td>
                                        <a href="{{ route('user.ljk.show', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}"
                                           class="fw-medium link-primary">{{ $exam->judul }}</a>
                                        <div class="text-muted stat-label">
                                            <code>{{ $exam->code }}</code> ·
                                            <span class="badge bg-info-subtle text-info" style="font-size:10px;">{{ strtoupper($exam->jenis_ujian) }}</span>
                                            @if ($exam->tanggal) · {{ $exam->tanggal->format('d/m/Y') }} @endif
                                        </div>
                                    </td>
                                    <td>
                                        {{ $exam->paketSoal?->judul ?? '-' }}
                                        <div class="text-muted stat-label">
                                            <code>{{ $exam->paketSoal?->kode_paket }}</code>
                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size:10px;">v{{ $exam->paket_version }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $exam->studyGroup?->name ?? '-' }}
                                        <div class="text-muted stat-label">{{ $exam->subject?->name ?? '-' }}</div>
                                    </td>
                                    <td style="min-width:150px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height:6px;">
                                                <div class="progress-bar bg-success" style="width:{{ $pctScan }}%"></div>
                                            </div>
                                            <span class="fw-semibold stat-label">{{ $exam->scanned_sheets_count }}/{{ $exam->sheets_count }}</span>
                                        </div>
                                        <div class="text-muted stat-label">{{ $exam->participants_count }} peserta · {{ $exam->jumlah_soal }} soal</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $exam->statusLabel() }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="{{ route('user.ljk.show', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}"
                                               class="btn btn-sm btn-soft-primary" title="Detail">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('user.ljk.scan', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}"
                                               class="btn btn-sm btn-soft-success" title="Pindai LJK">
                                                <i class="ri-camera-line"></i>
                                            </a>
                                            <a href="{{ route('user.ljk.soal', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}"
                                               class="btn btn-sm btn-soft-info" title="Lihat Soal">
                                                <i class="ri-file-text-line"></i>
                                            </a>
                                            <a href="{{ route('user.ljk.hasil', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}"
                                               class="btn btn-sm btn-soft-dark" title="Hasil & Verifikasi">
                                                <i class="ri-list-check-2"></i>
                                            </a>
                                            <a href="{{ route('user.ljk.pdf', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}"
                                               target="_blank" class="btn btn-sm btn-soft-secondary" title="Cetak PDF">
                                                <i class="ri-printer-line"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="avatar-lg mx-auto mb-3">
                                            <span class="avatar-title bg-light rounded-circle fs-1">
                                                <i class="ri-scan-2-line text-muted"></i>
                                            </span>
                                        </div>
                                        <h5 class="mb-1">Belum ada ujian LJK</h5>
                                        <p class="text-muted mb-3">
                                            Pilih Paket Soal final (approved &amp; published), lalu terbitkan LJK —
                                            sistem menyiapkan lembar A4 siap cetak.
                                        </p>
                                        <a href="{{ route('user.ljk.create', ['userId' => $userId]) }}" class="btn btn-success">
                                            <i class="ri-add-line me-1"></i> Terbitkan LJK
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-body border-top">
                    @include('shared._pagination', ['paginator' => $exams])
                </div>
            </div>
        </div>
    </div>
@endsection
