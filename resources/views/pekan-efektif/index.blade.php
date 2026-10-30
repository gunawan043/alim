@extends('layouts.master')
@section('title', 'Pekan Efektif')

@section('css')
    @include('kurikulum._styles')
@endsection

@php
    $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null);
@endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('title') Pekan Efektif @endslot
    @endcomponent

    {{-- FLASH --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- CARD PENGATURAN --}}
    @if($canManageSettings ?? false)
        <div class="card mb-3">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0">
                    <i class="ri-settings-3-line me-1 text-primary"></i>Pengaturan Pekan Efektif
                </h5>
                <p class="text-muted mb-0 small">
                    Satu pekan dianggap efektif jika jumlah hari belajar efektif minimal sama dengan nilai ini. Pengaturan ini berlaku untuk seluruh satuan pendidikan.
                </p>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('user.pekan-efektif.settings', ['userId' => $userId]) }}">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-4 col-lg-3">
                            <label class="form-label fs-12 text-muted mb-1">Minimal Hari/Pekan</label>
                            <input type="number" name="min_effective_days" class="form-control"
                                   min="1" max="6"
                                   value="{{ old('min_effective_days', $minEffectiveDays) }}" required>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-3-line me-1"></i> Simpan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- CARD UTAMA --}}
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0">Pekan Efektif</h5>
                    <p class="text-muted mb-0 small">
                        Turunan Kalender Pendidikan — pekan efektif minimal {{ $minEffectiveDays }} hari belajar per pekan.
                    </p>
                </div>
                <div class="col-sm-auto">
                    <div class="toolbar-actions">
                        @if($canManagePrintSettings ?? false)
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#pekanPrintSettingsModal">
                                <i class="ri-settings-3-line me-1"></i> Pengaturan Cetak
                            </button>
                        @endif
                        <a href="{{ route('user.pekan-efektif.pdf', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'school_id' => $schoolId]) }}"
                           target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="ri-printer-line me-1"></i> Cetak PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($ringkasan)
        {{-- ALERT PREVIEW --}}
        @if($isPreview)
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="ri-information-line me-1"></i>
                Pekan efektif tahun ajaran &amp; semester ini belum digenerate resmi oleh Kurikulum.
                Angka di bawah dihitung langsung dari Kalender Pendidikan.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        @endif

        {{-- STATS CARDS --}}
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-90">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-success-subtle rounded fs-2">
                                    <i class="ri-calendar-check-line text-success"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Minggu Efektif</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ $ringkasan['minggu_efektif'] }}</h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Dari {{ $ringkasan['total_minggu'] ?? '—' }} minggu semester ini</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-90">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-primary-subtle rounded fs-2">
                                    <i class="ri-calendar-2-line text-primary"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Hari Efektif</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ $ringkasan['total_hari_efektif'] }}</h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Senin–Sabtu dikurangi libur</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-90">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-danger-subtle rounded fs-2">
                                    <i class="ri-calendar-close-line text-danger"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Minggu Libur / Tidak Efektif</p>
                                <h3 class="fw-bold ff-secondary mb-0">
                                    {{ $ringkasan['minggu_libur'] }}
                                    <span class="fs-16 text-muted">/ {{ $ringkasan['minggu_tidak_efektif'] ?? 0 }}</span>
                                </h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 stat-label"><i class="ri-lock-line me-1"></i>{{ $ringkasan['total_hari_libur'] ?? '—' }} hari libur tercatat</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-90">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-warning-subtle rounded fs-2">
                                    <i class="ri-draft-line text-warning"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Minggu Ujian</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ $ringkasan['minggu_ujian'] }}</h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Pekan sumatif/ujian</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- RINCIAN PEKAN + PREVIEW PDF + ALOKASI JP --}}
        <div class="row">
            {{-- Rincian Pekan (kiri) --}}
            <div class="col-xl-7">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <div class="row g-3 align-items-center mb-3">
                            <div class="col-sm">
                                <h5 class="card-title mb-0"><i class="ri-calendar-2-line text-primary me-1"></i>Rincian Pekan</h5>
                                <p class="text-muted mb-0 small">Rincian pekan efektif per minggu.</p>
                            </div>
                            <div class="col-sm-auto">
                                <div class="toolbar-actions">
                                    <span class="badge {{ $isPreview ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }}">
                                        {{ $isPreview ? 'Hitung langsung dari Kaldik' : 'Tersimpan (hasil generate)' }}
                                    </span>
                                    <span class="badge bg-primary-subtle text-primary">{{ $rows->count() }} pekan</span>
                                </div>
                            </div>
                        </div>

                        {{-- Filter cepat --}}
                        <form method="GET" action="{{ route('user.pekan-efektif.index', ['userId' => $userId]) }}">
                            <div class="row g-2 align-items-end">
                                @if($canChooseSchool ?? false)
                                    <div class="col-12 col-md-3">
                                        <label class="form-label fs-12 text-muted mb-1">Satuan Pendidikan</label>
                                        <select name="school_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                            @foreach($schools as $school)
                                                <option value="{{ $school->id }}" {{ $schoolId === $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div class="col-12 col-md-3">
                                    <label class="form-label fs-12 text-muted mb-1">Tahun Ajaran</label>
                                    <select name="academic_year_id" class="form-select form-select-sm">
                                        @foreach($academicYears as $ay)
                                            <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-2">
                                    <label class="form-label fs-12 text-muted mb-1">Semester</label>
                                    <select name="semester" class="form-select form-select-sm">
                                        <option value="1" {{ (int) $semester === 1 ? 'selected' : '' }}>Ganjil</option>
                                        <option value="2" {{ (int) $semester === 2 ? 'selected' : '' }}>Genap</option>
                                    </select>
                                </div>

                                @if($selectedGroup)
                                    <input type="hidden" name="study_group_id" value="{{ $selectedGroup->id }}">
                                @endif

                                <div class="col-12 col-md-auto d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                                        <i class="ri-search-line me-1"></i> Tampilkan
                                    </button>
                                    <a href="{{ route('user.pekan-efektif.index', ['userId' => $userId]) }}"
                                       class="btn btn-light btn-sm flex-shrink-0" title="Reset filter">
                                        <i class="ri-refresh-line"></i>
                                    </a>
                                </div>
                            </div>
                        </form>

                        {{-- Alert konteks --}}
                        @if(! $ringkasan)
                            <div class="alert alert-warning alert-dismissible fade show mt-3 mb-0" role="alert">
                                <i class="ri-error-warning-line me-1"></i>
                                Konteks satuan pendidikan belum tersedia. Hubungi administrator untuk pemetaan satuan pendidikan Anda.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                            </div>
                        @endif
                    </div>

                    <div class="card-body">
                        <div class="table-container">
                            <table class="table table-hover align-middle table-freeze">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width:80px;">Minggu</th>
                                        <th>Periode</th>
                                        <th class="text-center">Jenis</th>
                                        <th class="text-center">Hari Efektif</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rows as $row)
                                        @php
                                            $map = ['efektif' => 'success', 'libur' => 'danger', 'ujian' => 'warning', 'kegiatan_sekolah' => 'info', 'lainnya' => 'secondary', 'tidak_efektif' => 'secondary'];
                                            $cls = $map[$row['jenis']] ?? 'secondary';
                                            $jenisLabel = \App\Models\PekanEfektif::JENIS_OPTIONS[$row['jenis']] ?? ucfirst(str_replace('_', ' ', $row['jenis']));
                                        @endphp
                                        <tr>
                                            <td class="text-center fw-semibold">{{ $row['minggu_ke'] }}</td>
                                            <td class="small">
                                                {{ $row['tanggal_mulai'] ? \Illuminate\Support\Carbon::parse($row['tanggal_mulai'])->locale('id')->translatedFormat('l, d F Y') : '—' }}
                                                –
                                                {{ $row['tanggal_selesai'] ? \Illuminate\Support\Carbon::parse($row['tanggal_selesai'])->locale('id')->translatedFormat('l, d F Y') : '—' }}
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $cls }}-subtle text-{{ $cls }}">{{ $jenisLabel }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ $row['jumlah_hari'] >= $minEffectiveDays && ! in_array($row['jenis'], ['libur', 'tidak_efektif'], true) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                                    {{ $row['jumlah_hari'] }}
                                                </span>
                                            </td>
                                            <td class="small text-muted">{{ \Illuminate\Support\Str::limit($row['keterangan'], 60) ?: '—' }}</td>
                                        </tr>
                                    @empty
                                        <x-empty-state colspan="5"
                                            title="Belum ada data pekan efektif"
                                            message="Pekan efektif dihitung dari Kalender Pendidikan." />
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kanan: Preview PDF + Alokasi JP --}}
            <div class="col-xl-5">
                {{-- Preview PDF --}}
                @if($pdfPreviewUrl)
                    <div class="card mb-3">
                        <div class="card-header border-bottom-dashed">
                            <div class="row g-3 align-items-center">
                                <div class="col-sm">
                                    <h5 class="card-title mb-0"><i class="ri-file-pdf-2-line text-danger me-1"></i>Preview PDF</h5>
                                    <p class="text-muted mb-0 small">Analisis pekan efektif.</p>
                                </div>
                                <div class="col-sm-auto">
                                    <div class="toolbar-actions">
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                onclick="document.getElementById('pekanPdfPreview').src='{{ $pdfPreviewUrl }}#toolbar=1&navpanes=0&view=FitH';">
                                            <i class="ri-refresh-line me-1"></i> Muat Ulang
                                        </button>
                                        @if($canManagePrintSettings ?? false)
                                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#pekanPrintSettingsModal">
                                                <i class="ri-settings-3-line me-1"></i> Pengaturan Cetak
                                            </button>
                                        @endif
                                        <a href="{{ route('user.pekan-efektif.pdf', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'school_id' => $schoolId]) }}"
                                           target="_blank" class="btn btn-outline-secondary btn-sm">
                                            <i class="ri-printer-line me-1"></i> Cetak PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <iframe id="pekanPdfPreview"
                                    src="{{ $pdfPreviewUrl }}#toolbar=1&navpanes=0&view=FitH"
                                    title="Preview PDF Analisis Pekan Efektif"
                                    style="width:100%;height:560px;border:0;border-radius:0 0 .375rem .375rem;background:#f8fafc;"></iframe>
                        </div>
                    </div>
                @endif

                {{-- Alokasi JP --}}
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <div class="row g-3 align-items-center">
                            <div class="col-sm">
                                <h5 class="card-title mb-0"><i class="ri-stack-line text-primary me-1"></i>Alokasi JP Efektif</h5>
                                <p class="text-muted mb-0 small">JP efektif per mata pelajaran.</p>
                            </div>
                            @if($studyGroups->count() > 1)
                                <div class="col-sm-auto">
                                    <form method="GET" action="{{ route('user.pekan-efektif.index', ['userId' => $userId]) }}">
                                        <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">
                                        <input type="hidden" name="semester" value="{{ $semester }}">
                                        <select name="study_group_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                            @foreach($studyGroups as $group)
                                                <option value="{{ $group->id }}" {{ $selectedGroup?->id === $group->id ? 'selected' : '' }}>{{ $group->full_name ?? $group->name }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card-body">
                        @if($selectedGroup)
                            <p class="text-muted small mb-3">
                                Kelas <strong>{{ $selectedGroup->name }}</strong> •
                                {{ $mingguEfektif }} minggu efektif
                                @if($isGuru)<span class="badge bg-light text-dark ms-1">Kelas yang Anda ampu</span>@endif
                            </p>
                        @else
                            <p class="text-muted small mb-0">Belum ada kelas yang dapat ditampilkan.</p>
                        @endif

                        <div class="table-container">
                            <table class="table table-hover align-middle table-freeze">
                                <thead>
                                    <tr>
                                        <th>Mata Pelajaran</th>
                                        <th class="text-center">JP/Minggu</th>
                                        <th class="text-center">JP Efektif</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $totalJp = 0; @endphp
                                    @forelse($jpRows as $jp)
                                        @php $totalJp += $jp['jp_efektif']; @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-medium">
                                                    {{ $jp['subject'] }}
                                                    @if(! empty($jp['subject_code']))
                                                        <span class="badge bg-light text-muted ms-1" style="font-size:.65rem;">{{ $jp['subject_code'] }}</span>
                                                    @endif
                                                </div>
                                                <div class="small text-muted">{{ $jp['teacher'] }}</div>
                                            </td>
                                            <td class="text-center">{{ $jp['weekly_hours'] }}</td>
                                            <td class="text-center fw-semibold">{{ $jp['jp_efektif'] }}</td>
                                        </tr>
                                    @empty
                                        <x-empty-state colspan="3"
                                            title="Belum ada plotting mengajar"
                                            message="Tambahkan plotting mapel untuk kelas ini." />
                                    @endforelse
                                </tbody>
                                @if(count($jpRows))
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="2" class="text-end">Total JP Efektif</th>
                                            <th class="text-center">{{ $totalJp }}</th>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>

                        <div class="alert alert-light border small mb-0 mt-3">
                            <i class="ri-lightbulb-line me-1 text-warning"></i>
                            JP efektif = JP per minggu × {{ $mingguEfektif }} minggu efektif. Angka ini menjadi dasar
                            perencanaan alokasi pembelajaran (ATP/modul ajar) dan target penyelesaian materi.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL: PRINT SETTINGS --}}
    @if($canManagePrintSettings ?? false)
        <div class="modal fade" id="pekanPrintSettingsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('user.pekan-efektif.print-settings', ['userId' => $userId]) }}">
                        @csrf
                        <input type="hidden" name="school_id" value="{{ $schoolId }}">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="ri-printer-settings-line me-2 text-primary"></i>Pengaturan Cetak PDF</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-light border small mb-3">
                                Pengaturan ini hanya berlaku untuk <strong>{{ $schoolId ? ($schools->firstWhere('id', $schoolId)?->name ?? 'satuan pendidikan ini') : 'satuan pendidikan ini' }}</strong>, tidak memengaruhi sekolah lain.
                            </div>

                            <h6 class="text-uppercase fw-semibold text-muted small mb-2">Margin (mm)</h6>
                            <div class="row g-2 mb-3">
                                @foreach(['margin_top'=>'Atas','margin_right'=>'Kanan','margin_bottom'=>'Bawah','margin_left'=>'Kiri'] as $field=>$label)
                                    <div class="col-6 col-md-3">
                                        <label class="form-label fs-12 text-muted mb-1">{{ $label }}</label>
                                        <input type="number" step="0.5" min="0" max="50" name="{{ $field }}" class="form-control form-control-sm" value="{{ old($field, $printLayout[$field]) }}" required>
                                    </div>
                                @endforeach
                            </div>

                            <h6 class="text-uppercase fw-semibold text-muted small mb-2">Font &amp; Kertas</h6>
                            <div class="row g-2 mb-3">
                                <div class="col-6 col-md-3">
                                    <label class="form-label fs-12 text-muted mb-1">Font Umum (pt)</label>
                                    <input type="number" step="0.5" min="6" max="24" name="font_size" class="form-control form-control-sm" value="{{ old('font_size', $printLayout['font_size']) }}" required>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fs-12 text-muted mb-1">Font Judul (pt)</label>
                                    <input type="number" step="0.5" min="8" max="30" name="title_font_size" class="form-control form-control-sm" value="{{ old('title_font_size', $printLayout['title_font_size']) }}" required>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fs-12 text-muted mb-1">Font Tabel (pt)</label>
                                    <input type="number" step="0.5" min="6" max="20" name="table_font_size" class="form-control form-control-sm" value="{{ old('table_font_size', $printLayout['table_font_size']) }}" required>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fs-12 text-muted mb-1">Line Height</label>
                                    <input type="number" step="0.05" min="1" max="2.5" name="line_height" class="form-control form-control-sm" value="{{ old('line_height', $printLayout['line_height']) }}" required>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fs-12 text-muted mb-1">Font Info (pt)</label>
                                    <input type="number" step="0.5" min="6" max="20" name="info_font_size" class="form-control form-control-sm" value="{{ old('info_font_size', $printLayout['info_font_size']) }}" required>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fs-12 text-muted mb-1">Font Footer (pt)</label>
                                    <input type="number" step="0.5" min="5" max="16" name="footer_font_size" class="form-control form-control-sm" value="{{ old('footer_font_size', $printLayout['footer_font_size']) }}" required>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fs-12 text-muted mb-1">Ukuran Kertas</label>
                                    <select name="paper_size" class="form-select form-select-sm">
                                        @foreach(['a4'=>'A4','folio'=>'F4 / Folio','letter'=>'Letter'] as $value=>$label)
                                            <option value="{{ $value }}" @selected(old('paper_size', $printLayout['paper_size']) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-3 d-flex align-items-end">
                                    <div class="form-check">
                                        <input type="checkbox" name="show_kop" value="1" class="form-check-input" id="pekanShowKop" @checked(old('show_kop', $printLayout['show_kop']))>
                                        <label class="form-check-label small" for="pekanShowKop">Tampilkan KOP</label>
                                    </div>
                                </div>
                            </div>

                            <h6 class="text-uppercase fw-semibold text-muted small mb-2">Tanda Tangan</h6>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label fs-12 text-muted mb-1">Kota</label>
                                    <input name="city" class="form-control form-control-sm" value="{{ old('city', $printLayout['city'] ?? 'Mataram') }}">
                                </div>
                                <div class="col-md-8 d-flex align-items-end">
                                    <div class="alert alert-light border py-2 mb-0 small w-100">
                                        <i class="ri-information-line me-1"></i>Nama guru otomatis mengikuti akun yang mencetak PDF.
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input type="checkbox" name="show_signature" value="1" class="form-check-input" id="pekanShowSignature" @checked(old('show_signature', $printLayout['show_signature']))>
                                        <label class="form-check-label small" for="pekanShowSignature">Tampilkan kolom tanda tangan</label>
                                    </div>
                                </div>
                            </div>

                            @if($canManageSettings ?? false)
                                <h6 class="text-uppercase fw-semibold text-muted small mb-2 mt-3">Footer Dokumen (Super Admin)</h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label fs-12 text-muted mb-1">Kode Form</label>
                                        <input name="footer_form_code" class="form-control form-control-sm" value="{{ old('footer_form_code', $footerFormCode) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fs-12 text-muted mb-1">Revisi</label>
                                        <input name="footer_revision" class="form-control form-control-sm" value="{{ old('footer_revision', $footerRevision) }}">
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-3-line me-1"></i> Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if($errors->any())
        if (document.getElementById('pekanPrintSettingsModal')) {
            new bootstrap.Modal(document.getElementById('pekanPrintSettingsModal')).show();
        }
    @endif
});
</script>
@endsection