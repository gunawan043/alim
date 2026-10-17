@extends('layouts.master')
@section('title') Hasil LJK — {{ $exam->judul }} @endsection

@section('css')
    @include('ljk._styles')
@endsection

@section('content')
    @php
        $badge = ['draft' => 'secondary', 'aktif' => 'primary', 'pengumpulan' => 'warning', 'review' => 'warning', 'final' => 'success', 'tersinkron' => 'success', 'dibatalkan' => 'danger'][$exam->status] ?? 'secondary';
        $stat = $statistics ?? [];
        $peserta = $stat['peserta'] ?? 0;
        $dipindai = $stat['dipindai'] ?? 0;
        $perluReview = $stat['perlu_review'] ?? 0;
        $bersih = $stat['bersih'] ?? 0;
        $pctScan = $peserta > 0 ? round($dipindai / $peserta * 100) : 0;
        $pctBersih = $dipindai > 0 ? round($bersih / $dipindai * 100) : 0;
        $canFinal = ($exam->status === 'final' || $exam->status === 'tersinkron') ? false : true;
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Bank Soal &amp; Asesmen @endslot
        @slot('li_2')
            <a href="{{ route('user.ljk.index', ['userId' => $userId]) }}">LJK Smart Scanner</a>
        @endslot
        @slot('li_3')
            <a href="{{ route('user.ljk.show', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}">{{ $exam->code }}</a>
        @endslot
        @slot('title') Hasil &amp; Verifikasi @endslot
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

    {{-- KEPALA + AKSI --}}
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-lg">
                    <h5 class="card-title mb-1">
                        {{ $exam->judul }}
                        <span class="badge bg-info-subtle text-info ms-1"><code>{{ $exam->code }}</code></span>
                        <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $exam->statusLabel() }}</span>
                    </h5>
                    <p class="text-muted mb-0 stat-label">
                        {{ $exam->studyGroup?->name ?? '-' }} · {{ $exam->subject?->name ?? '-' }} ·
                        target nilai: <strong>{{ $targetLabel }}</strong>
                    </p>
                </div>
                <div class="col-lg-auto">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('user.ljk.scan', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}" class="btn btn-soft-success">
                            <i class="ri-camera-line align-bottom me-1"></i> Pindai
                        </a>
                        @if ($canFinal)
                            <form method="POST" action="{{ route('user.ljk.finalize', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}">
                                @csrf
                                <button class="btn btn-primary" {{ $summary['scanned'] === 0 ? 'disabled' : '' }}>
                                    <i class="ri-check-double-line align-bottom me-1"></i> Finalkan Ujian
                                </button>
                            </form>
                        @endif
                        @if ($exam->adminBook)
                            <form method="POST" action="{{ route('user.ljk.sync', ['userId' => $userId, 'ljkUuid' => $exam->id]) }}"
                                  class="d-flex gap-2"
                                  onsubmit="return confirm('Sinkronkan nilai final LJK ke buku nilai ({{ $targetLabel }})?')">
                                @csrf
                                @if (count($columns) > 1)
                                    <select name="column_id" class="form-select" style="width:auto">
                                        @foreach ($columns as $column)
                                            <option value="{{ $column['id'] }}">{{ $column['label'] ?? $column['id'] }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                <button class="btn btn-dark"
                                        {{ in_array($exam->status, ['final', 'tersinkron'], true) ? '' : 'disabled' }}>
                                    <i class="ri-upload-cloud-2-line align-bottom me-1"></i> Sinkron ke Nilai
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info d-flex align-items-start gap-2">
            <i class="ri-magic-line fs-5"></i>
            <div style="font-size:0.875rem;">
                <strong>Nilai masuk otomatis.</strong>
                Setiap lembar yang terbaca <em>bersih</em> (tanpa nomor ⚠) langsung disimpan ke kolom resmi
                <strong>{{ $targetLabel }}</strong> pada buku nilai — tanpa menunggu tombol sinkron.
                Tombol <em>Sinkron ke Nilai</em> di atas dipakai untuk menyusul peserta yang tadinya tertunda,
                atau setelah koreksi massal.
            </div>
        </div>
    </div>

    {{-- STATISTIK --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2">
                                <i class="ri-camera-3-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Progres Pemindaian</p>
                            <h3 class="fw-bold ff-secondary mb-0">
                                {{ number_format($dipindai) }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">/ {{ number_format($peserta) }}</small>
                            </h3>
                        </div>
                    </div>
                    <div class="progress" style="height:5px;">
                        <div class="progress-bar bg-primary" style="width:{{ $pctScan }}%"></div>
                    </div>
                    <p class="text-muted mb-0 mt-1 stat-label">{{ $pctScan }}% lembar sudah dipindai</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2">
                                <i class="ri-error-warning-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Perlu Diperiksa</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($perluReview) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-2 stat-label">
                        <i class="ri-information-line me-1"></i>Ganda / meragukan / tidak terbaca
                    </p>
                    <a href="{{ request()->fullUrlWithQuery(['filter' => 'review', 'q' => null, 'page' => null]) }}"
                       class="btn btn-sm btn-warning w-100 {{ $filter === 'review' ? 'active' : '' }}">
                        <i class="ri-search-eye-line me-1"></i> Periksa Sekarang
                    </a>
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
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Bersih</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($bersih) }}</h3>
                        </div>
                    </div>
                    <div class="progress" style="height:5px;">
                        <div class="progress-bar bg-success" style="width:{{ $pctBersih }}%"></div>
                    </div>
                    <p class="text-muted mb-0 mt-1 stat-label">{{ $pctBersih }}% lembar tanpa temuan</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2">
                                <i class="ri-bar-chart-line text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Sebaran Nilai</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format((float) ($stat['rata_nilai'] ?? 0), 2) }}</h3>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-success-subtle text-success" style="font-size:10px;">
                            Tertinggi {{ $stat['tertinggi'] !== null ? number_format((float) $stat['tertinggi'], 2) : '-' }}
                        </span>
                        <span class="badge bg-danger-subtle text-danger" style="font-size:10px;">
                            Terendah {{ $stat['terendah'] !== null ? number_format((float) $stat['terendah'], 2) : '-' }}
                        </span>
                        @if (($stat['manual'] ?? 0) > 0)
                            <span class="badge bg-secondary-subtle text-secondary" style="font-size:10px;">
                                {{ $stat['manual'] }} jawaban manual
                            </span>
                        @endif
                    </div>
                    <p class="text-muted mb-0 mt-1 stat-label">Rata-rata nilai sementara peserta terpindai</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="ljkResults">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Nilai Peserta</h5>
                            <p class="text-muted mb-0 stat-label">
                                Hasil baca awal tersimpan; koreksi guru dicatat terpisah (ber-audit).
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <form method="GET" action="{{ request()->url() }}" class="d-flex gap-2">
                                @if ($filter)
                                    <input type="hidden" name="filter" value="{{ $filter }}">
                                @endif
                                <input type="text" class="form-control" name="q"
                                       placeholder="Cari nama / nomor peserta..." value="{{ $search ?? '' }}"
                                       style="width: 240px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-search-line"></i> Cari
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- FILTER CEPAT --}}
                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['filter' => null, 'page' => null]) }}"
                           class="filter-badge {{ $filter === 'semua' ? 'active' : '' }}">Semua</a>
                        <a href="{{ request()->fullUrlWithQuery(['filter' => 'review', 'page' => null]) }}"
                           class="filter-badge {{ $filter === 'review' ? 'active' : '' }}">
                            <i class="ri-error-warning-line"></i>Perlu Diperiksa
                            <span class="badge bg-warning text-dark ms-1">{{ number_format($perluReview) }}</span>
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['filter' => 'bersih', 'page' => null]) }}"
                           class="filter-badge {{ $filter === 'bersih' ? 'active' : '' }}">
                            <i class="ri-check-double-line"></i>Bersih
                            <span class="badge bg-success text-white ms-1">{{ number_format($bersih) }}</span>
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['filter' => 'belum', 'page' => null]) }}"
                           class="filter-badge {{ $filter === 'belum' ? 'active' : '' }}">
                            <i class="ri-camera-off-line"></i>Belum Dipindai
                            <span class="badge bg-secondary text-white ms-1">{{ number_format(max(0, $peserta - $dipindai)) }}</span>
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>#</th>
                                <th>Peserta</th>
                                <th>Lembar</th>
                                <th>Terbaca/Kosong</th>
                                <th>Ganda</th>
                                <th>Meragukan</th>
                                <th>Manual</th>
                                <th>Nilai</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($participants as $row)
                                @php $p = $row['participant']; $scan = $row['scan']; @endphp
                                <tr class="{{ $row['needs_review'] > 0 ? 'ljk-row-review' : '' }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="fw-medium">{{ $p->student?->name ?? '-' }}</span>
                                        <div class="text-muted stat-label">
                                            No. <code>{{ $p->nomor_peserta }}</code> · NIS {{ $p->student?->nis ?? '-' }}
                                        </div>
                                    </td>
                                    <td><code>{{ $p->sheet?->sheet_code ?? '-' }}</code></td>
                                    @if ($scan)
                                        <td><span class="badge bg-success-subtle text-success">{{ $row['terbaca'] }}</span></td>
                                        <td>
                                            <span class="badge bg-{{ $row['ganda'] > 0 ? 'danger' : 'light' }}-subtle text-{{ $row['ganda'] > 0 ? 'danger' : 'dark' }}">
                                                {{ $row['ganda'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $row['meragukan'] > 0 ? 'warning' : 'light' }}-subtle text-{{ $row['meragukan'] > 0 ? 'warning' : 'dark' }}">
                                                {{ $row['meragukan'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $row['manual'] > 0 ? 'info' : 'light' }}-subtle text-{{ $row['manual'] > 0 ? 'info' : 'dark' }}">
                                                {{ $row['manual'] }}
                                            </span>
                                        </td>
                                    @else
                                        <td colspan="4" class="text-muted stat-label">Belum dipindai</td>
                                    @endif
                                    <td>
                                        <span class="fw-bold">
                                            {{ $p->nilai_akhir !== null ? number_format((float) $p->nilai_akhir, 2) : '-' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('user.ljk.hasil.peserta', ['userId' => $userId, 'ljkUuid' => $exam->id, 'participantUuid' => $p->id]) }}"
                                           class="btn btn-sm {{ $row['needs_review'] > 0 ? 'btn-warning' : 'btn-soft-primary' }}">
                                            @if ($row['needs_review'] > 0)
                                                <i class="ri-search-eye-line me-1"></i> Periksa ({{ $row['needs_review'] }})
                                            @else
                                                <i class="ri-eye-line me-1"></i> Detail
                                            @endif
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <div class="avatar-lg mx-auto mb-3">
                                            <span class="avatar-title bg-light rounded-circle fs-1">
                                                <i class="ri-list-check-2 text-muted"></i>
                                            </span>
                                        </div>
                                        <h5 class="mb-1">Tidak ada data sesuai filter</h5>
                                        <p class="text-muted mb-0">Ubah filter cepat atau mulai memindai lembar peserta.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if ($syncLogs->isNotEmpty())
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="mb-0"><i class="ri-history-line me-1 text-primary"></i>Riwayat Sinkronisasi</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            @foreach ($syncLogs as $log)
                                <li class="d-flex align-items-center gap-2 py-1" style="font-size:0.875rem;">
                                    <i class="ri-upload-cloud-2-line text-success"></i>
                                    <span class="text-muted">{{ $log->created_at?->format('d/m/Y H:i') }}</span>
                                    <span class="fw-semibold">{{ $log->success_count }} peserta</span>
                                    <span class="text-muted">· {{ $log->skipped_count }} tertunda</span>
                                    <span class="text-muted">· oleh {{ $log->actor?->name ?? 'sistem' }}</span>
                                    @if ($log->action === 'resync')
                                        <span class="badge bg-info-subtle text-info">ulang</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
