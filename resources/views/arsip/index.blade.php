@extends('layouts.master')
@section('title', 'Inventaris Arsip')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $statusColor = match ($status) {
            'aktif' => 'success',
            'terkunci' => 'dark',
            default => 'info',
        };
        $typeLabels = [
            'snapshot_nilai' => 'Snapshot Nilai',
            'rapor' => 'Rapor',
            'leger' => 'Leger',
            'sk' => 'Surat Keputusan',
        ];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Administrasi @endslot
        @slot('title') Inventaris Arsip @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Panel Periode --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-lg">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div>
                            <h5 class="mb-1">
                                <i class="ri-archive-2-line text-primary me-1"></i>Arsip Tahun Ajaran
                            </h5>
                            <p class="text-muted mb-0 small">
                                Satu gerbang penutupan periode: status arsip, kelengkapan data per modul, dan dokumen final per tahun ajaran.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-auto">
                    <form method="GET" action="{{ route('user.arsip.index', ['userId' => $userId]) }}" class="d-flex gap-2">
                        <select name="academic_year_id" class="form-select" style="min-width:260px" onchange="this.form.submit()">
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ (string) $selectedAcademicYearId === (string) $ay->id ? 'selected' : '' }}>
                                    {{ $ay->name }} ({{ $ay->semester_text }})
                                    @if($ay->is_active) — Aktif @endif
                                    @if($ay->locked_at) — Terkunci @endif
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            @if($selectedAy)
                <hr class="my-3">
                <div class="row g-3 align-items-center">
                    <div class="col-lg">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} fs-6">
                                <i class="ri-shield-check-line me-1"></i>{{ $statusLabel ?? '-' }}
                            </span>
                            <span class="text-muted small">
                                @if($selectedAy->start_date || $selectedAy->end_date)
                                    {{ $selectedAy->start_date?->format('d M Y') }} – {{ $selectedAy->end_date?->format('d M Y') }}
                                @endif
                            </span>
                            @if($selectedAy->isLocked())
                                <span class="text-muted small">
                                    <i class="ri-lock-line me-1"></i>Dikunci {{ $selectedAy->locked_at?->format('d M Y H:i') }}
                                    @if($selectedAy->locker) oleh {{ $selectedAy->locker->name }} @endif
                                </span>
                                @if($selectedAy->lock_notes)
                                    <span class="text-muted small fst-italic">“{{ $selectedAy->lock_notes }}”</span>
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-auto">
                        <div class="d-flex flex-wrap gap-2">
                            @if($canManage && ! $selectedAy->isLocked())
                                <button type="button" class="btn btn-outline-danger"
                                        data-bs-toggle="modal" data-bs-target="#lockPeriodModal"
                                        @if($lockBlockReason) disabled title="{{ $lockBlockReason }}" @endif>
                                    <i class="ri-lock-line me-1"></i>Tutup &amp; Kunci Periode
                                </button>
                            @endif
                            @if($canManage && $selectedAy->isLocked())
                                <button type="button" class="btn btn-outline-warning"
                                        data-bs-toggle="modal" data-bs-target="#unlockPeriodModal">
                                    <i class="ri-lock-unlock-line me-1"></i>Buka Kunci
                                </button>
                            @endif
                            <a href="{{ route('user.schools.nilai.index', ['userId' => $userId, 'academic_year_id' => $selectedAcademicYearId]) }}"
                               class="btn btn-outline-info"><i class="ri-file-edit-line me-1"></i>Data Nilai</a>
                            <a href="{{ route('user.students.index', ['userId' => $userId, 'academic_year_id' => $selectedAcademicYearId]) }}"
                               class="btn btn-outline-primary"><i class="ri-community-line me-1"></i>Data Santri</a>
                            <a href="{{ route('user.study-groups.index', ['userId' => $userId, 'academic_year_id' => $selectedAcademicYearId]) }}"
                               class="btn btn-outline-secondary"><i class="ri-layout-grid-line me-1"></i>Data Rombel</a>
                        </div>
                    </div>
                </div>

                @if($lockBlockReason && ! $selectedAy->isLocked())
                    <div class="alert alert-info py-2 px-3 small mt-3 mb-0" role="alert">
                        <i class="ri-information-line me-1"></i>{{ $lockBlockReason }}
                        <em>Arsip berjalan tetap baca-saja; kunci resmi menambah segel permanen + jejak audit.</em>
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="row">
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-stack-line text-primary"></i></span></div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Modul Terisi</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $overview['filled'] }} <small class="fw-normal text-muted fs-6">/ {{ $overview['modules'] }}</small></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Modul dengan data pada periode ini</p>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-folder-shield-2-line text-success"></i></span></div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Dokumen Arsip</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($overview['documents']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-shield-check-line me-1"></i>Snapshot/dokumen final ber-hash</p>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-lock-2-line text-warning"></i></span></div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Status Periode</p>
                            <h5 class="fw-bold ff-secondary mb-0" style="font-size:16px;">{{ $selectedAy?->name ?? '—' }} ({{ ucfirst($status ?? '-') }})</h5>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Periode terkunci = data tidak dapat diubah</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Kesiapan Penutupan Periode --}}
    @if($selectedAy)
        @php
            $closureItems = collect($closure['items'] ?? []);
            $closureBadge = ($closure['errors'] ?? 0) > 0
                ? 'bg-danger-subtle text-danger'
                : ((($closure['warnings'] ?? 0) > 0) ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success');
            $closureLabel = ($closure['errors'] ?? 0) > 0
                ? 'Belum siap — ' . $closure['errors'] . ' item wajib'
                : ((($closure['warnings'] ?? 0) > 0) ? ($closure['warnings'] . ' peringatan untuk ditinjau') : 'Siap ditutup');
            $closureUrl = function (?string $name) use ($userId, $selectedAcademicYearId) {
                if (! $name) {
                    return null;
                }

                try {
                    return route($name, ['userId' => $userId, 'academic_year_id' => $selectedAcademicYearId]);
                } catch (\Throwable) {
                    return null;
                }
            };
        @endphp
        <div class="card">
            <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-checklist-2 text-success me-1"></i>Kesiapan Penutupan Periode</h5>
                    <p class="text-muted mb-0 small">Checklist sebelum periode dikunci. <strong>Item wajib</strong> memblokir penutupan; <strong>peringatan</strong> boleh ditutup setelah dikonfirmasi.</p>
                </div>
                <span class="badge {{ $closureBadge }}">{{ $closureLabel }}</span>
            </div>
            <div class="card-body">
                @forelse($closureItems as $item)
                    <div class="border rounded p-2 mb-2 d-flex align-items-center gap-2">
                        @if($item['status'] === 'ok')
                            <i class="ri-checkbox-circle-fill text-success fs-5"></i>
                        @elseif($item['status'] === 'error')
                            <i class="ri-close-circle-fill text-danger fs-5"></i>
                        @else
                            <i class="ri-error-warning-fill text-warning fs-5"></i>
                        @endif
                        <div class="flex-grow-1">
                            <div class="small fw-semibold">
                                @php $itemUrl = $closureUrl($item['route'] ?? null); @endphp
                                @if($itemUrl)
                                    <a href="{{ $itemUrl }}" class="link-primary">{{ $item['label'] }}</a>
                                @else
                                    {{ $item['label'] }}
                                @endif
                            </div>
                            @if(! empty($item['hint']))
                                <div class="text-muted" style="font-size:11px;">{{ $item['hint'] }}</div>
                            @endif
                        </div>
                        <span class="badge {{ $item['status'] === 'ok' ? 'bg-success-subtle text-success' : ($item['status'] === 'error' ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning') }}">
                            {{ $item['value'] }}
                        </span>
                    </div>
                @empty
                    <p class="text-muted mb-0 small">Belum ada data untuk dievaluasi.</p>
                @endforelse
            </div>
        </div>
    @endif

    {{-- Inventaris Modul --}}
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <h5 class="card-title mb-0"><i class="ri-list-check-2 text-primary me-1"></i>Inventaris Data per Modul</h5>
            <p class="text-muted mb-0 small">Angka diambil langsung dari tabel periode masing-masing (bukan perkiraan).</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach($modules as $module)
                    <div class="col-xl-3 col-md-4 col-sm-6">
                        <div class="border rounded p-3 h-100 d-flex flex-column">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="{{ $module['icon'] ?? 'ri-database-2-line' }} fs-4 text-primary"></i>
                                <span class="fw-semibold small flex-grow-1">{{ $module['label'] }}</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-auto">
                                <span class="badge {{ ($module['count'] ?? 0) > 0 ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} fs-6">
                                    {{ number_format($module['count'] ?? 0) }}
                                </span>
                                @if(! empty($module['link_route']) && $selectedAcademicYearId)
                                    <a class="small link-primary"
                                       href="{{ route($module['link_route'], ['userId' => $userId, 'academic_year_id' => $selectedAcademicYearId]) }}">
                                        Buka <i class="ri-arrow-right-line"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Arsipkan Rapor (PDF) --}}
    @if($selectedAy)
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-file-pdf-2-line text-danger me-1"></i>Arsipkan Rapor (PDF)</h5>
                <p class="text-muted mb-0 small">
                    Membuat salinan PDF rapor per santri — 4 varian: <strong>STS/SAS × Indonesia (LTR)/Arab (RTL)</strong> —
                    termasuk anggota rombel yang sudah pindah/naik kelas, lalu menyimpannya sebagai dokumen arsip ber-hash.
                    Aman dijalankan ulang — varian yang sudah ada dilewati.
                </p>
            </div>
            <div class="card-body">
                @if($studyGroups->isEmpty())
                    <p class="text-muted mb-0 small"><i class="ri-information-line me-1"></i>Belum ada rombel pada tahun ajaran ini.</p>
                @else
                    <form method="POST" action="{{ route('user.arsip.rapor', ['userId' => $userId, 'id' => $selectedAy->id]) }}"
                          class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Rombel</label>
                            <select name="study_group_id" class="form-select" required>
                                @foreach($studyGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Semester</label>
                            <select name="semester" class="form-select">
                                <option value="ganjil" {{ $selectedAy->semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                <option value="genap" {{ $selectedAy->semester === 'genap' ? 'selected' : '' }}>Genap</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Jenis Dokumen</label>
                            <select name="jenis" class="form-select">
                                <option value="all">Semua (STS &amp; SAS)</option>
                                <option value="sas" selected>SAS — Nilai Akhir</option>
                                <option value="sts">STS — Tengah Semester</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Bahasa</label>
                            <select name="bahasa" class="form-select">
                                <option value="all" selected>Indonesia &amp; Arab</option>
                                <option value="id">Indonesia (LTR)</option>
                                <option value="ar">Arab (RTL)</option>
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-success">
                                <i class="ri-archive-drawer-line me-1"></i>Arsipkan Rapor Rombel
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif

    {{-- Arsipkan Leger (PDF) --}}
    @if($selectedAy && $studyGroups->isNotEmpty())
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-table-line text-primary me-1"></i>Arsipkan Leger (PDF)</h5>
                <p class="text-muted mb-0 small">
                    Menyimpan Leger nilai satu rombel (A4 landscape, ber-hash) sebagai dokumen arsip periode.
                    Aman dijalankan ulang — leger yang sudah ada dilewati.
                </p>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('user.arsip.leger', ['userId' => $userId, 'id' => $selectedAy->id]) }}"
                      class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Rombel</label>
                        <select name="study_group_id" class="form-select" required>
                            @foreach($studyGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Semester</label>
                        <select name="semester" class="form-select">
                            <option value="ganjil" {{ $selectedAy->semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                            <option value="genap" {{ $selectedAy->semester === 'genap' ? 'selected' : '' }}>Genap</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Jenis Dokumen</label>
                        <select name="jenis" class="form-select">
                            <option value="sas">SAS — Nilai Akhir</option>
                            <option value="sts">STS — Tengah Semester</option>
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="ri-archive-drawer-line me-1"></i>Arsipkan Leger
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Dokumen Arsip --}}
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-folder-shield-2-line text-success me-1"></i>Dokumen Arsip Periode Ini</h5>
                    <p class="text-muted mb-0 small">Snapshot dibuat otomatis saat buku nilai dikunci final; berisi hash SHA-256 untuk verifikasi integritas.</p>
                </div>
                <form method="GET" class="d-flex flex-wrap gap-2">
                    <input type="hidden" name="academic_year_id" value="{{ $selectedAcademicYearId }}">
                    <select name="doc_type" class="form-select form-select-sm" onchange="this.form.submit()" title="Jenis dokumen">
                        <option value="">Semua Dokumen</option>
                        <option value="rapor" {{ ($documentFilters['type'] ?? null) === 'rapor' ? 'selected' : '' }}>Rapor</option>
                        <option value="leger" {{ ($documentFilters['type'] ?? null) === 'leger' ? 'selected' : '' }}>Leger</option>
                        <option value="snapshot_nilai" {{ ($documentFilters['type'] ?? null) === 'snapshot_nilai' ? 'selected' : '' }}>Snapshot Nilai</option>
                    </select>
                    <select name="doc_jenis" class="form-select form-select-sm" onchange="this.form.submit()" title="Jenis penilaian">
                        <option value="">Semua STS/SAS</option>
                        <option value="sts" {{ ($documentFilters['jenis'] ?? null) === 'sts' ? 'selected' : '' }}>STS</option>
                        <option value="sas" {{ ($documentFilters['jenis'] ?? null) === 'sas' ? 'selected' : '' }}>SAS</option>
                    </select>
                    <select name="doc_language" class="form-select form-select-sm" onchange="this.form.submit()" title="Bahasa dokumen">
                        <option value="">Semua Bahasa</option>
                        <option value="id" {{ ($documentFilters['language'] ?? null) === 'id' ? 'selected' : '' }}>Indonesia</option>
                        <option value="ar" {{ ($documentFilters['language'] ?? null) === 'ar' ? 'selected' : '' }}>Arab</option>
                    </select>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Dokumen</th>
                        <th>Jenis</th>
                        <th>Konteks</th>
                        <th>Dibuat</th>
                        <th class="text-center">Integritas</th>
                        <th class="text-end" style="width:110px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $document)
                        <tr>
                            <td>
                                <div class="fw-medium small">{{ $document->title }}</div>
                                <div class="text-muted" style="font-size:11px;">hash: {{ $document->short_hash }}… · {{ number_format($document->file_size / 1024, 1) }} KB</div>
                            </td>
                            <td><span class="badge bg-primary-subtle text-primary">{{ $typeLabels[$document->type] ?? $document->type }}</span></td>
                            <td class="small text-muted">
                                {{ $document->studyGroup?->name ?? '—' }}
                                @if($document->semester) · {{ ucfirst($document->semester) }} @endif
                                @if($document->jenis) · {{ strtoupper($document->jenis) }} @endif
                                @if($document->language)
                                    · {{ $document->language === 'ar' ? 'Arab (RTL)' : 'Indonesia (LTR)' }}
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $document->created_at?->format('d M Y H:i') }}
                                @if($document->generator) · {{ $document->generator->name }} @endif
                            </td>
                            <td class="text-center">
                                @if($document->file_verified ?? false)
                                    <span class="badge bg-success-subtle text-success"><i class="ri-shield-check-line me-1"></i>Terverifikasi</span>
                                @elseif($document->file_available ?? false)
                                    <span class="badge bg-warning-subtle text-warning"><i class="ri-error-warning-line me-1"></i>Hash berubah</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger"><i class="ri-file-warning-line me-1"></i>Berkas hilang</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($document->file_available ?? false)
                                    <a href="{{ route('user.arsip.dokumen.download', ['userId' => $userId, 'documentId' => $document->id]) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="ri-download-2-line"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="ri-folder-open-line fs-3 d-block mb-2"></i>
                                Belum ada dokumen arsip pada periode ini. Kunci buku nilai untuk membuat snapshot otomatis.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modul Belum Berperiode --}}
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <h5 class="card-title mb-0"><i class="ri-error-warning-line text-warning me-1"></i>Modul Belum Dukungan Arsip Periode</h5>
            <p class="text-muted mb-0 small">Inventaris jujur — item ini perlu perlakuan khusus pada tahap lanjut.</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach($unsupportedModules as $item)
                    <div class="col-lg-6">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-semibold small mb-1">{{ $item['label'] }}</div>
                            <div class="text-muted small">{{ $item['note'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Modal standar: Kunci / Buka Kunci Periode --}}
    @if($selectedAy && $canManage && ! $selectedAy->isLocked())
        <form id="lockPeriodForm" method="POST"
              action="{{ route('user.arsip.lock', ['userId' => $userId, 'id' => $selectedAy->id]) }}">
            @csrf
        </form>

        <x-modal-confirm id="lockPeriodModal"
            icon="https://cdn.lordicon.com/lupuorrc.json"
            primary-color="#f7b84b" secondary-color="#f06548"
            title="Tutup &amp; Kunci Periode?"
            message="Tahun ajaran <strong>{{ $selectedAy->name }} ({{ $selectedAy->semester_text }})</strong> akan dikunci sebagai arsip. Seluruh data periode ini tidak dapat diubah — termasuk bila tahun ajaran diaktifkan kembali — sampai kuncinya dibuka."
            submit-label="Ya, Kunci Periode"
            submit-icon="ri-lock-line"
            submit-class="btn-danger"
            form-id="lockPeriodForm">
            <label class="form-label small fw-semibold">Catatan penutupan (opsional)</label>
            <textarea name="lock_notes" form="lockPeriodForm" class="form-control" rows="3" maxlength="500"
                      placeholder="Mis. ditutup setelah rapor akhir semester dibagikan."></textarea>

            @if(($closure['warnings'] ?? 0) > 0)
                <div class="alert alert-warning small mt-3 mb-2">
                    <strong>{{ $closure['warnings'] }} peringatan kelengkapan:</strong>
                    <ul class="mb-0 ps-3">
                        @foreach(collect($closure['items'])->where('status', 'warning')->take(6) as $warning)
                            <li>{{ $warning['label'] }} ({{ $warning['value'] }})</li>
                        @endforeach
                    </ul>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" form="lockPeriodForm" name="confirm_warnings" value="1" id="confirmClosureWarnings" required>
                    <label class="form-check-label small" for="confirmClosureWarnings">
                        Saya sudah meninjau peringatan dan tetap menutup periode ini.
                    </label>
                </div>
            @endif
        </x-modal-confirm>
    @endif

    @if($selectedAy && $canManage && $selectedAy->isLocked())
        <form id="unlockPeriodForm" method="POST"
              action="{{ route('user.arsip.unlock', ['userId' => $userId, 'id' => $selectedAy->id]) }}">
            @csrf
        </form>

        <x-modal-confirm id="unlockPeriodModal"
            icon="https://cdn.lordicon.com/lupuorrc.json"
            primary-color="#f7b84b" secondary-color="#08a88a"
            title="Buka Kunci Arsip?"
            message="Kunci arsip <strong>{{ $selectedAy->name }} ({{ $selectedAy->semester_text }})</strong> akan dibuka dan tercatat pada audit log. Data baru dapat diubah setelah tahun ajaran diaktifkan kembali."
            submit-label="Ya, Buka Kunci"
            submit-icon="ri-lock-unlock-line"
            submit-class="btn-warning"
            form-id="unlockPeriodForm">
            <label class="form-label small fw-semibold">Alasan pembukaan (opsional)</label>
            <textarea name="lock_notes" form="unlockPeriodForm" class="form-control" rows="3" maxlength="500"
                      placeholder="Mis. koreksi nilai hasil rapat verifikasi."></textarea>
        </x-modal-confirm>
    @endif

@endsection
