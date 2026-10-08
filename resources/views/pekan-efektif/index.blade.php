@extends('layouts.master')
@section('title', 'Pekan Efektif')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('title') Pekan Efektif @endslot
    @endcomponent

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">Pekan Efektif</h4>
            <p class="text-muted mb-0 small">
                Turunan Kalender Pendidikan — dasar penghitungan alokasi JP dan perencanaan pembelajaran.
            </p>
        </div>
        <form method="GET" action="{{ route('user.pekan-efektif.index', ['userId' => $userId]) }}" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label">Tahun Ajaran</label>
                <select name="academic_year_id" class="form-select form-select-sm">
                    @foreach($academicYears as $ay)
                        <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Semester</label>
                <select name="semester" class="form-select form-select-sm">
                    <option value="1" {{ (int) $semester === 1 ? 'selected' : '' }}>Ganjil</option>
                    <option value="2" {{ (int) $semester === 2 ? 'selected' : '' }}>Genap</option>
                </select>
            </div>
            @if($selectedGroup)
                <input type="hidden" name="study_group_id" value="{{ $selectedGroup->id }}">
            @endif
            <button type="submit" class="btn btn-primary btn-sm"><i class="ri-search-line align-bottom me-1"></i> Tampilkan</button>
            <a href="{{ route('user.kurikulum.cetak.pekan-efektif', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}"
               target="_blank" class="btn btn-soft-secondary btn-sm">
                <i class="ri-printer-line align-bottom me-1"></i> Cetak PDF
            </a>
        </form>
    </div>

    @if(! $ringkasan)
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>
            Konteks satuan pendidikan belum tersedia. Hubungi administrator untuk pemetaan satuan pendidikan Anda.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @else
        @if($isPreview)
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="ri-information-line me-1"></i>
                Pekan efektif tahun ajaran &amp; semester ini belum digenerate resmi oleh Kurikulum.
                Angka di bawah dihitung langsung dari Kalender Pendidikan.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-calendar-check-line text-success"></i></span>
                            </div>
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Minggu Efektif</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ $ringkasan['minggu_efektif'] }}</h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Dari Kalender Pendidikan</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-calendar-2-line text-primary"></i></span>
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
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-calendar-close-line text-danger"></i></span>
                            </div>
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Minggu Libur</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ $ringkasan['minggu_libur'] }}</h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 stat-label"><i class="ri-lock-line me-1"></i>Tidak dihitung efektif</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-draft-line text-warning"></i></span>
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
    @endif

    <div class="row">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0"><i class="ri-calendar-2-line text-primary me-1"></i> Rincian Pekan</h5>
                    <span class="badge bg-primary-subtle text-primary">{{ $rows->count() }} pekan</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">Minggu</th>
                                <th>Periode</th>
                                <th class="text-center">Jenis</th>
                                <th class="text-center">Hari Efektif</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                @php
                                    $map = ['efektif' => 'success', 'libur' => 'danger', 'ujian' => 'warning', 'kegiatan_sekolah' => 'info', 'lainnya' => 'secondary'];
                                    $cls = $map[$row['jenis']] ?? 'secondary';
                                    $jenisLabel = \App\Models\PekanEfektif::JENIS_OPTIONS[$row['jenis']] ?? ucfirst(str_replace('_', ' ', $row['jenis']));
                                @endphp
                                <tr>
                                    <td class="text-center fw-semibold">{{ $row['minggu_ke'] }}</td>
                                    <td class="small">
                                        {{ $row['tanggal_mulai'] ? \Illuminate\Support\Carbon::parse($row['tanggal_mulai'])->format('d/m/Y') : '-' }}
                                        –
                                        {{ $row['tanggal_selesai'] ? \Illuminate\Support\Carbon::parse($row['tanggal_selesai'])->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="text-center"><span class="badge bg-{{ $cls }}-subtle text-{{ $cls }}">{{ $jenisLabel }}</span></td>
                                    <td class="text-center">
                                        <span class="badge {{ $row['jumlah_hari'] > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                            {{ $row['jumlah_hari'] }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">{{ \Illuminate\Support\Str::limit($row['keterangan'], 60) ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-calendar-2-line fs-1 d-block mb-2"></i>
                                            Belum ada data pekan efektif.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between gap-2">
                    <h5 class="card-title mb-0"><i class="ri-stack-line text-primary me-1"></i> Alokasi JP Efektif</h5>
                    @if($studyGroups->count() > 1)
                        <form method="GET" action="{{ route('user.pekan-efektif.index', ['userId' => $userId]) }}">
                            <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">
                            <input type="hidden" name="semester" value="{{ $semester }}">
                            <select name="study_group_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach($studyGroups as $group)
                                    <option value="{{ $group->id }}" {{ $selectedGroup?->id === $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    @endif
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
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
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
                                        <div class="fw-medium">{{ $jp['subject'] }}</div>
                                        <div class="small text-muted">{{ $jp['teacher'] }}</div>
                                    </td>
                                    <td class="text-center">{{ $jp['weekly_hours'] }}</td>
                                    <td class="text-center fw-semibold">{{ $jp['jp_efektif'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-stack-line fs-1 d-block mb-2"></i>
                                            Belum ada plotting mengajar untuk kelas ini.
                                        </div>
                                    </td>
                                </tr>
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
            </div>

            <div class="alert alert-light border small mb-0">
                <i class="ri-lightbulb-line me-1 text-warning"></i>
                JP efektif = JP per minggu × {{ $mingguEfektif }} minggu efektif. Angka ini menjadi dasar
                perencanaan alokasi pembelajaran (ATP/modul ajar) dan target penyelesaian materi.
            </div>
        </div>
    </div>
@endsection
