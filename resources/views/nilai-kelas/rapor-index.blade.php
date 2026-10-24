@extends('layouts.master')
@section('title') Cetak Rapor — {{ $studyGroup->name ?? '' }} @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? Auth::id();
        $previewBaseUrl = route('user.schools.nilai-kelas.rapor.cetak', [
            'userId' => $userId,
            'studyGroupId' => $studyGroup->id,
            'studentId' => '__STUDENT__',
        ]);
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.schools.nilai-kelas.sts', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}">Nilai Kelas</a> @endslot
        @slot('title') Cetak Rapor @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $stats = $stats ?? ['total_santri' => 0, 'catatan_terisi' => 0, 'nilai_final' => 0];
    @endphp

    {{-- STATISTIK --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-group-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Santri</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total_santri']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Santri aktif di {{ $studyGroup->name }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-chat-1-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Catatan Wali Terisi</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['catatan_terisi']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-check-double-line me-1"></i>Dari {{ number_format($stats['total_santri']) }} santri</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-save-3-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Nilai Final Tersimpan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['nilai_final']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Tersimpan saat cetak rapor SAS</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-calendar-check-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Periode</p>
                            <h3 class="fw-bold ff-secondary mb-0" style="font-size:18px;">{{ ucfirst($selectedSem) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-calendar-2-line me-1"></i>{{ $selectedAy?->name ?? 'Belum ada TA' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Publikasi Rapor ke Wali Santri --}}
    <div class="card mb-3">
        <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-0"><i class="ri-send-plane-line text-success me-1"></i>Publikasi Rapor</h5>
                <p class="text-muted mb-0 small">Hanya rapor yang dipublikasikan yang dapat dilihat oleh wali santri.</p>
            </div>
            @php
                $pipeline = [
                    'draft' => $registrations->where('status', 'draft')->where('is_published', false)->count(),
                    'in_progress' => $registrations->where('status', 'in_progress')->where('is_published', false)->count(),
                    'finalized' => $registrations->where('status', 'finalized')->where('is_published', false)->count(),
                    'published' => $stats['published'] ?? 0,
                ];
            @endphp
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-secondary-subtle text-secondary">Draft {{ $pipeline['draft'] }}</span>
                <span class="badge bg-warning-subtle text-warning">Proses {{ $pipeline['in_progress'] }}</span>
                <span class="badge bg-info-subtle text-info">Final {{ $pipeline['finalized'] }}</span>
                <span class="badge bg-success-subtle text-success">Published {{ $pipeline['published'] }}</span>
            </div>
        </div>
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="small text-muted">
                <i class="ri-information-line me-1"></i>
                Pipeline: <strong>Draft → In Progress → Finalized → Published</strong>.
                Publikasi mencakup seluruh santri aktif di {{ $studyGroup->name }} untuk
                {{ $selectedAy?->name ?? 'TA aktif' }} — {{ ucfirst($selectedSem) }}.
            </div>
            @if($canPublish ?? false)
                <div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('user.schools.nilai-kelas.rapor.finalize', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                          onsubmit="return confirm('Finalisasi rapor rombel ini? Status berubah menjadi Finalized.')">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSem }}">
                        <button type="submit" class="btn btn-outline-info btn-sm">
                            <i class="ri-check-double-line me-1"></i>Finalisasi Rapor
                        </button>
                    </form>
                    <form method="POST" action="{{ route('user.schools.nilai-kelas.rapor.unpublish', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                          onsubmit="return confirm('Batalkan publikasi rapor? Wali tidak akan dapat melihat rapor ini.')">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSem }}">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="ri-eye-off-line me-1"></i>Batalkan Publikasi
                        </button>
                    </form>
                    <form method="POST" action="{{ route('user.schools.nilai-kelas.rapor.publish', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                          onsubmit="return confirm('Publikasikan rapor ini ke wali santri?')">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSem }}">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="ri-send-plane-line me-1"></i>Publikasikan Rapor
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    {{-- Pengaturan Tanggal Pembagian Rapor --}}
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <h5 class="card-title mb-0"><i class="ri-calendar-event-line text-success me-1"></i>Tanggal Pembagian Rapor</h5>
            <p class="text-muted mb-0 small">Dipakai pada dokumen rapor (cetak &amp; arsip) untuk TA/semester ini — menggantikan tanggal cetak otomatis.</p>
        </div>
        <div class="card-body">
            <form method="POST"
                  action="{{ route('user.schools.nilai-kelas.rapor.pengaturan', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                  class="row g-2 align-items-end">
                @csrf
                <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                <input type="hidden" name="semester" value="{{ $selectedSem }}">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Tanggal Pembagian</label>
                    <input type="date" name="distribution_date" class="form-control"
                           value="{{ $distributionSetting?->distribution_date?->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Kota</label>
                    <input type="text" name="city" class="form-control" maxlength="100"
                           value="{{ $distributionSetting?->city ?? $studyGroup->school?->city }}"
                           placeholder="Mataram">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Catatan</label>
                    <input type="text" name="notes" class="form-control" maxlength="500"
                           value="{{ $distributionSetting?->notes }}" placeholder="Opsional">
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-success">
                        <i class="ri-save-line me-1"></i>Simpan
                    </button>
                </div>
                @if($distributionSetting?->distribution_date)
                    <div class="col-12">
                        <span class="badge bg-info-subtle text-info">
                            Aktif: {{ $distributionSetting->distribution_date->format('d M Y') }}
                            @if($distributionSetting->city) — {{ $distributionSetting->city }} @endif
                        </span>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="row g-3 mb-5">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">
                                <i class="ri-file-paper-2-line text-primary me-1"></i>
                                Cetak Rapor Santri
                            </h5>
                            <p class="text-muted mb-0" style="font-size:12px;">
                                <span class="badge bg-primary-subtle text-primary">{{ $stats['total_santri'] }} santri</span>
                                {{ $studyGroup->school?->name ?? '' }} — {{ $studyGroup->name }} — TA {{ $selectedAy?->name ?? '' }} Semester {{ ucfirst($selectedSem) }}
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            @if(($stats['total_santri'] ?? 0) > 0)
                                <div class="btn-group me-1">
                                    <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-printer-line me-1"></i> Cetak Rapor Massal (1 PDF)
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><h6 class="dropdown-header">SAS — Nilai Akhir</h6></li>
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAy?->id, 'semester' => $selectedSem, 'jenis' => 'sas', 'bahasa' => 'id']) }}">Indonesia (LTR)</a></li>
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAy?->id, 'semester' => $selectedSem, 'jenis' => 'sas', 'bahasa' => 'ar']) }}">Arab (RTL)</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><h6 class="dropdown-header">STS — Tengah Semester</h6></li>
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAy?->id, 'semester' => $selectedSem, 'jenis' => 'sts', 'bahasa' => 'id']) }}">Indonesia (LTR)</a></li>
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAy?->id, 'semester' => $selectedSem, 'jenis' => 'sts', 'bahasa' => 'ar']) }}">Arab (RTL)</a></li>
                                    </ul>
                                </div>
                            @else
                                <span class="badge bg-warning-subtle text-warning me-1">
                                    <i class="ri-information-line me-1"></i>Belum ada santri pada TA/semester ini
                                </span>
                            @endif
                            <a href="{{ route('user.schools.nilai-kelas.sts', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filter --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label" style="font-size:12px;">Tahun Ajaran</label>
                            <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ $selectedAyId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Semester</label>
                            <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="ganjil" {{ $selectedSem == 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                <option value="genap" {{ $selectedSem == 'genap' ? 'selected' : '' }}>Genap</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Cari Nama</label>
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="Nama Santri..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="ri-search-line me-1"></i> Filter
                            </button>
                        </div>
                    </form>

                    {{-- Student List --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-freeze mb-0" style="font-size:13px;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;">No</th>
                                    <th>NIS</th>
                                    <th>Nama Santri</th>
                                    <th style="width:75px;text-align:center;">Nilai</th>
                                    <th style="width:90px;text-align:center;">Predikat</th>
                                    <th style="width:70px;text-align:center;">Peringkat</th>
                                    <th style="width:120px;text-align:center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowNum = 0; @endphp
                                @forelse($students as $history)
                                    @php
                                        $student = $history->student;
                                        if (request('search') && stripos($student->name, request('search')) === false)
                                            continue;
                                    @endphp
                                    @php
                                        $rowNum++;
                                        $summary = $summaries[$student->id] ?? ['nilai' => null, 'predikat' => '—', 'rank' => null];
                                        $predicateClass = match ($summary['predikat']) {
                                            'Mumtaz Murtafi\'', 'Mumtaz' => 'success',
                                            'Jayyid Jiddan' => 'info',
                                            'Jayyid' => 'warning',
                                            'Maqbul' => 'secondary',
                                            'Roosib' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="text-center text-muted">{{ $rowNum }}</td>
                                        <td class="text-center">{{ $student->nis ?? '-' }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-primary text-white" style="font-size:10px;">
                                                        {{ strtoupper(mb_substr(($__w = preg_split('/\s+/', trim((string) $student->name)))[0] ?? '', 0, 1) . (count($__w) > 1 ? mb_substr($__w[count($__w) - 1], 0, 1) : mb_substr($__w[0] ?? '', 1, 1))) }}
                                                    </span>
                                                </div>
                                                <span class="fw-semibold">{{ $student->name }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center fw-semibold">
                                            {{ $summary['nilai'] !== null ? number_format((float) $summary['nilai'], 1) : '—' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $predicateClass }}-subtle text-{{ $predicateClass }}">
                                                {{ $summary['predikat'] ?: '—' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($summary['rank'])
                                                <span class="badge bg-primary-subtle text-primary">#{{ $summary['rank'] }}</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-printer-line me-1"></i> Rapor
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><h6 class="dropdown-header">STS — Tengah Semester</h6></li>
                                                    <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'studentId' => $student->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSem, 'jenis' => 'sts', 'bahasa' => 'id']) }}">Indonesia (LTR)</a></li>
                                                    <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'studentId' => $student->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSem, 'jenis' => 'sts', 'bahasa' => 'ar']) }}">Arab (RTL)</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><h6 class="dropdown-header">SAS — Nilai Akhir</h6></li>
                                                    <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'studentId' => $student->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSem, 'jenis' => 'sas', 'bahasa' => 'id']) }}">Indonesia (LTR)</a></li>
                                                    <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'studentId' => $student->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSem, 'jenis' => 'sas', 'bahasa' => 'ar']) }}">Arab (RTL)</a></li>
                                                </ul>
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm ms-1 js-note"
                                                    data-name="{{ $student->name }}"
                                                    data-note="{{ $registrations[$student->id]->homeroom_note ?? '' }}"
                                                    data-action="{{ route('user.schools.nilai-kelas.rapor.note', [
                                                        'userId' => $userId,
                                                        'studyGroupId' => $studyGroup->id,
                                                        'studentId' => $student->id,
                                                        'academic_year_id' => $selectedAyId,
                                                        'semester' => $selectedSem,
                                                    ]) }}">
                                                <i class="ri-chat-1-line me-1"></i> Catatan
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="ri-group-line me-1"></i>Tidak ada Santri.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            @php
                $previewStudent = $students->first()?->student;
                $previewUrl = $previewStudent
                    ? route('user.schools.nilai-kelas.rapor.cetak', [
                        'userId' => $userId,
                        'studyGroupId' => $studyGroup->id,
                        'studentId' => $previewStudent->id,
                        'academic_year_id' => $selectedAyId,
                        'semester' => $selectedSem,
                        'jenis' => 'sts',
                        'bahasa' => 'id',
                    ])
                    : null;
            @endphp
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-settings-3-line text-success me-1"></i>Pengaturan &amp; Preview PDF</h5>
                    <p class="text-muted mb-0 small">Berlaku untuk rapor <strong>STS</strong> dan <strong>SAS</strong> pada TA/semester ini.</p>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger py-2 small">
                            <i class="ri-error-warning-line me-1"></i> Periksa kembali pengaturan tata letak.
                        </div>
                    @endif

                    <form method="POST"
                          action="{{ route('user.schools.nilai-kelas.rapor.layout', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSem }}">

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0 small fw-semibold"><i class="ri-layout-line me-1"></i>Margin (mm)</h6>
                            <span class="text-muted" style="font-size:11px;">Atas / Kanan / Bawah / Kiri</span>
                        </div>
                        <div class="row g-2 mb-3">
                            @foreach(['margin_top' => 'Atas', 'margin_right' => 'Kanan', 'margin_bottom' => 'Bawah', 'margin_left' => 'Kiri'] as $field => $label)
                                <div class="col-6 col-md-3">
                                    <label class="form-label small mb-1">{{ $label }}</label>
                                    <input type="number" name="{{ $field }}" class="form-control form-control-sm"
                                           min="0" max="50" step="0.5" required
                                           value="{{ old($field, $layout[$field]) }}">
                                </div>
                            @endforeach
                        </div>

                        <h6 class="mb-2 small fw-semibold"><i class="ri-font-size-2 me-1"></i>Ukuran Font (pt) &amp; Baris</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">Isi</label>
                                <input type="number" name="font_size" class="form-control form-control-sm" min="6" max="24" step="0.5" required value="{{ old('font_size', $layout['font_size']) }}">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">Judul</label>
                                <input type="number" name="title_font_size" class="form-control form-control-sm" min="8" max="30" step="0.5" required value="{{ old('title_font_size', $layout['title_font_size']) }}">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">Tabel</label>
                                <input type="number" name="table_font_size" class="form-control form-control-sm" min="6" max="20" step="0.5" required value="{{ old('table_font_size', $layout['table_font_size']) }}">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">Info Santri</label>
                                <input type="number" name="info_font_size" class="form-control form-control-sm" min="6" max="20" step="0.5" required value="{{ old('info_font_size', $layout['info_font_size']) }}">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">Footer/TTD</label>
                                <input type="number" name="footer_font_size" class="form-control form-control-sm" min="5" max="16" step="0.5" required value="{{ old('footer_font_size', $layout['footer_font_size']) }}">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">Tinggi Baris</label>
                                <input type="number" name="line_height" class="form-control form-control-sm" min="1" max="2.5" step="0.05" required value="{{ old('line_height', $layout['line_height']) }}">
                            </div>
                        </div>

                        <div class="row g-2 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Ukuran Kertas</label>
                                <select name="paper_size" class="form-select form-select-sm" required>
                                    <option value="a4" {{ old('paper_size', $layout['paper_size']) === 'a4' ? 'selected' : '' }}>A4</option>
                                    <option value="folio" {{ old('paper_size', $layout['paper_size']) === 'folio' ? 'selected' : '' }}>F4 / Folio</option>
                                    <option value="letter" {{ old('paper_size', $layout['paper_size']) === 'letter' ? 'selected' : '' }}>Letter</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-success btn-sm w-100">
                                    <i class="ri-save-3-line me-1"></i>Simpan Tata Letak
                                </button>
                            </div>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <h6 class="mb-0 small fw-semibold"><i class="ri-eye-line me-1"></i>Preview PDF</h6>
                        <span class="badge bg-info-subtle text-info">STS / SAS</span>
                    </div>

                    @if($students->isNotEmpty() && $previewStudent)
                        <div class="row g-2 mb-2">
                            <div class="col-12">
                                <select id="previewStudent" class="form-select form-select-sm">
                                    @foreach($students as $history)
                                        @if($history->student)
                                            <option value="{{ $history->student->id }}">{{ $history->student->name }} — {{ $history->student->nis ?? $history->student->nisn ?? '-' }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-5">
                                <select id="previewJenis" class="form-select form-select-sm">
                                    <option value="sts">STS</option>
                                    <option value="sas">SAS</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <select id="previewBahasa" class="form-select form-select-sm">
                                    <option value="id">Indonesia</option>
                                    <option value="ar">Arab</option>
                                </select>
                            </div>
                            <div class="col-3">
                                <button type="button" id="previewRefresh" class="btn btn-outline-primary btn-sm w-100" title="Muat ulang preview">
                                    <i class="ri-refresh-line"></i>
                                </button>
                            </div>
                        </div>
                        <div class="border rounded overflow-hidden bg-light" style="height:720px;">
                            <iframe id="raporPreview"
                                    src="{{ $previewUrl }}"
                                    title="Preview PDF rapor"
                                    style="width:100%;height:100%;border:0;background:#fff;"
                                    loading="lazy"></iframe>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small class="text-muted">Preview memakai pengaturan yang sudah disimpan.</small>
                            <a href="{{ $previewUrl }}" target="_blank" id="previewOpen" class="btn btn-outline-success btn-sm">
                                <i class="ri-external-link-line me-1"></i>Buka PDF
                            </a>
                        </div>
                    @else
                        <div class="alert alert-warning py-2 mb-0 small">
                            <i class="ri-information-line me-1"></i>Belum ada santri pada TA/semester ini, preview belum tersedia.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Pengaturan Mapel Rapor & Urutan --}}
    <div class="card mt-3">
        <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-0"><i class="ri-book-open-line text-primary me-1"></i>Mapel yang Masuk Rapor &amp; Urutannya</h5>
                <p class="text-muted mb-0 small">Centang mapel yang dicetak, lalu atur nomor urut. Nomor lebih kecil tampil lebih dulu. Berlaku untuk STS dan SAS.</p>
            </div>
            <span class="badge bg-primary-subtle text-primary">{{ count($selectedSubjectIds) }} mapel dipilih</span>
        </div>
        <div class="card-body">
            @if($availableSubjects->isEmpty())
                <div class="alert alert-warning py-2 mb-0 small">
                    <i class="ri-information-line me-1"></i>Belum ada buku/mapel aktif untuk rombel dan semester ini.
                </div>
            @else
                <form method="POST"
                      action="{{ route('user.schools.nilai-kelas.rapor.subjects', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}">
                    @csrf
                    <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                    <input type="hidden" name="semester" value="{{ $selectedSem }}">

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-3">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:70px;" class="text-center">Masuk</th>
                                    <th>Mata Pelajaran</th>
                                    <th style="width:120px;" class="text-center">Urutan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $nextOrder = count($selectedSubjectIds) + 1; @endphp
                                @foreach($availableSubjects as $subject)
                                    @php
                                        $isSelected = in_array($subject->id, $selectedSubjectIds, true);
                                        $orderIndex = array_search($subject->id, $selectedSubjectIds, true);
                                    @endphp
                                    <tr>
                                        <td class="text-center">
                                            <input class="form-check-input" type="checkbox"
                                                   name="subject_ids[]" value="{{ $subject->id }}"
                                                   id="subject-{{ $subject->id }}" @checked($isSelected)>
                                        </td>
                                        <td>
                                            <label class="fw-semibold mb-0" for="subject-{{ $subject->id }}">{{ $subject->name }}</label>
                                            @if($subject->category)
                                                <small class="text-muted ms-1">({{ $subject->category }})</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <input type="number" class="form-control form-control-sm text-center mx-auto"
                                                   style="max-width:90px;" name="subject_order[{{ $subject->id }}]"
                                                   min="1" max="99" step="1"
                                                   value="{{ $isSelected && $orderIndex !== false ? $orderIndex + 1 : $nextOrder++ }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <small class="text-muted"><i class="ri-information-line me-1"></i>Minimal satu mapel harus dipilih. Urutan hanya dipakai untuk mapel yang dicentang.</small>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ri-save-3-line me-1"></i>Simpan Mapel &amp; Urutan
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- Modal: Catatan Wali Kelas --}}
    <div class="modal fade" id="noteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="noteForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-chat-1-line me-1"></i> Catatan Wali Kelas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-2">Santri: <strong id="noteStudent">—</strong></p>
                        <textarea name="homeroom_note" id="noteText" class="form-control" rows="4" maxlength="500"
                                  placeholder="Catatan untuk rapor (maks. 500 karakter)"></textarea>
                        <small class="text-muted">Catatan ini tampil pada bagian "Catatan Wali Kelas" di Rapor.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-note').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('noteForm').action = btn.dataset.action;
        document.getElementById('noteStudent').textContent = btn.dataset.name || '—';
        document.getElementById('noteText').value = btn.dataset.note || '';
        new bootstrap.Modal(document.getElementById('noteModal')).show();
    });
});

(function () {
    const iframe = document.getElementById('raporPreview');
    const student = document.getElementById('previewStudent');
    const jenis = document.getElementById('previewJenis');
    const bahasa = document.getElementById('previewBahasa');
    const open = document.getElementById('previewOpen');
    const refresh = document.getElementById('previewRefresh');

    if (!iframe || !student) return;

    const baseUrl = @json($previewBaseUrl);
    const academicYearId = @json($selectedAyId);
    const semester = @json($selectedSem);

    function buildPreviewUrl() {
        if (!student.value) return '';

        const url = new URL(baseUrl.replace('__STUDENT__', encodeURIComponent(student.value)));
        if (academicYearId) url.searchParams.set('academic_year_id', academicYearId);
        if (semester) url.searchParams.set('semester', semester);
        url.searchParams.set('jenis', jenis.value || 'sts');
        url.searchParams.set('bahasa', bahasa.value || 'id');

        return url.toString();
    }

    function refreshPreview() {
        const url = buildPreviewUrl();
        if (!url) return;
        iframe.src = url;
        if (open) open.href = url;
    }

    [student, jenis, bahasa].forEach(function (el) {
        if (el) el.addEventListener('change', refreshPreview);
    });
    if (refresh) refresh.addEventListener('click', refreshPreview);
})();
</script>
@endpush
