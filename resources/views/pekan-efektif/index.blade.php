@extends('layouts.master')
@section('title') Pekan Efektif @endsection

@section('css')
    <style>
        .badge-soft-success { background: #d1fae5; color: #065f46; }
        .badge-soft-danger  { background: #fee2e2; color: #991b1b; }
        .badge-soft-warning { background: #fef3c7; color: #92400e; }
        .badge-soft-info    { background: #e0f2fe; color: #075985; }
        .badge-soft-secondary { background: #e2e8f0; color: #334155; }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('title') Pekan Efektif @endslot
    @endcomponent

    <div class="row mb-3">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Pekan Efektif</h4>
                <p class="text-muted small mb-0">
                    Turunan Kalender Pendidikan — dasar penghitungan alokasi JP dan perencanaan pembelajaran.
                </p>
            </div>
            <form method="GET" action="{{ route('user.pekan-efektif.index', ['userId' => $userId]) }}" class="d-flex gap-2 align-items-end">
                <div>
                    <label class="form-label small text-muted mb-1">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select form-select-sm">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>
                                {{ $ay->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label small text-muted mb-1">Semester</label>
                    <select name="semester" class="form-select form-select-sm">
                        <option value="1" {{ (int) $semester === 1 ? 'selected' : '' }}>Ganjil</option>
                        <option value="2" {{ (int) $semester === 2 ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>
                @if($selectedGroup)
                    <input type="hidden" name="study_group_id" value="{{ $selectedGroup->id }}">
                @endif
                <button type="submit" class="btn btn-sm btn-primary"><i class="ri-filter-3-line me-1"></i> Tampilkan</button>
            </form>
        </div>
    </div>

    @if(! $ringkasan)
        <div class="alert alert-warning py-2 small">
            <i class="ri-error-warning-line me-1"></i>
            Konteks satuan pendidikan belum tersedia. Hubungi administrator untuk pemetaan satuan pendidikan Anda.
        </div>
    @else
        @if($isPreview)
            <div class="alert alert-info py-2 small">
                <i class="ri-information-line me-1"></i>
                Pekan efektif tahun ajaran &amp; semester ini belum digenerate resmi oleh Kurikulum.
                Angka di bawah dihitung langsung dari Kalender Pendidikan.
            </div>
        @endif

        <div class="row mb-3">
            <div class="col-6 col-md-3">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="text-muted small">Minggu Efektif</div>
                        <h4 class="mb-0 text-success">{{ $ringkasan['minggu_efektif'] }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="text-muted small">Hari Efektif</div>
                        <h4 class="mb-0 text-primary">{{ $ringkasan['total_hari_efektif'] }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="text-muted small">Minggu Libur</div>
                        <h4 class="mb-0 text-danger">{{ $ringkasan['minggu_libur'] }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="text-muted small">Minggu Ujian</div>
                        <h4 class="mb-0 text-warning">{{ $ringkasan['minggu_ujian'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="ri-calendar-2-line me-1"></i> Rincian Pekan</h6>
                    <span class="badge badge-soft-secondary">{{ $rows->count() }} pekan</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Minggu</th>
                                    <th>Periode</th>
                                    <th>Jenis</th>
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
                                        <td><strong>{{ $row['minggu_ke'] }}</strong></td>
                                        <td>
                                            {{ $row['tanggal_mulai'] ? \Illuminate\Support\Carbon::parse($row['tanggal_mulai'])->format('d/m/Y') : '-' }}
                                            –
                                            {{ $row['tanggal_selesai'] ? \Illuminate\Support\Carbon::parse($row['tanggal_selesai'])->format('d/m/Y') : '-' }}
                                        </td>
                                        <td><span class="badge badge-soft-{{ $cls }}">{{ $jenisLabel }}</span></td>
                                        <td class="text-center">
                                            <span class="badge {{ $row['jumlah_hari'] > 0 ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                                {{ $row['jumlah_hari'] }}
                                            </span>
                                        </td>
                                        <td class="small text-muted">{{ \Illuminate\Support\Str::limit($row['keterangan'], 60) ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data pekan efektif.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="ri-stack-line me-1"></i> Alokasi JP Efektif</h6>
                    @if($studyGroups->count() > 1)
                        <form method="GET" action="{{ route('user.pekan-efektif.index', ['userId' => $userId]) }}">
                            <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">
                            <input type="hidden" name="semester" value="{{ $semester }}">
                            <select name="study_group_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach($studyGroups as $group)
                                    <option value="{{ $group->id }}" {{ $selectedGroup?->id === $group->id ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="px-3 pt-3 pb-0 small text-muted">
                        @if($selectedGroup)
                            Kelas <strong>{{ $selectedGroup->name }}</strong> •
                            {{ $mingguEfektif }} minggu efektif
                            @if($isGuru)<span class="badge bg-light text-dark ms-1">Kelas yang Anda ampu</span>@endif
                        @else
                            Belum ada kelas yang dapat ditampilkan.
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
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
                                    <tr><td colspan="3" class="text-center text-muted py-4">Belum ada plotting mengajar untuk kelas ini.</td></tr>
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
            </div>

            <div class="alert alert-light border small mb-0">
                <i class="ri-lightbulb-line me-1 text-warning"></i>
                JP efektif = JP per minggu × {{ $mingguEfektif }} minggu efektif. Angka ini menjadi dasar
                perencanaan alokasi pembelajaran (ATP/modul ajar) dan target penyelesaian materi.
            </div>
        </div>
    </div>
@endsection
