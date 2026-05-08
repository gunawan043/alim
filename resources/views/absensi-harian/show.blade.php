@extends('layouts.master')
@section('title') Detail Absensi: {{ $student->name ?? '' }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}">Absensi Harian</a> @endslot
        @slot('title') {{ $student->name }}</span>
    @endcomponent

    <div class="row">
        {{-- Student Info & Stats --}}
        <div class="col-12 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="mb-1">{{ $student->name }}</h5>
                            <p class="text-muted mb-0">
                                NIS: <strong>{{ $student->nis ?? '-' }}</strong> &nbsp;|&nbsp;
                                NISN: <strong>{{ $student->nisn ?? '-' }}</strong> &nbsp;|&nbsp;
                                Jenis Kelamin: <strong>{{ $student->gender_text }}</strong>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="{{ route('user.absensi.harian.recap', ['userId' => $userId]) }}"
                                class="btn btn-light btn-sm">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-h-100 bg-success-subtle border-success">
                <div class="card-body text-center py-3">
                    <div class="fs-1 fw-bold text-success">{{ $stats->hadir ?? 0 }}</div>
                    <div class="text-muted small">Hadir</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-h-100 bg-warning-subtle border-warning">
                <div class="card-body text-center py-3">
                    <div class="fs-1 fw-bold text-warning">{{ $stats->terlambat ?? 0 }}</div>
                    <div class="text-muted small">Terlambat</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-h-100 bg-info-subtle border-info">
                <div class="card-body text-center py-3">
                    <div class="fs-1 fw-bold text-info">{{ $stats->izin ?? 0 }}</div>
                    <div class="text-muted small">Izin</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-h-100 bg-secondary-subtle border-secondary">
                <div class="card-body text-center py-3">
                    <div class="fs-1 fw-bold text-secondary">{{ $stats->sakit ?? 0 }}</div>
                    <div class="text-muted small">Sakit</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-h-100 bg-danger-subtle border-danger">
                <div class="card-body text-center py-3">
                    <div class="fs-1 fw-bold text-danger">{{ $stats->alpa ?? 0 }}</div>
                    <div class="text-muted small">Alpa</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card card-h-100 bg-dark-subtle border-dark">
                <div class="card-body text-center py-3">
                    @php
                        $total = ($stats->total ?? 0);
                        $persen = $total > 0
                            ? round((($stats->hadir + $stats->terlambat) / $total) * 100, 1)
                            : 0;
                    @endphp
                    <div class="fs-1 fw-bold">{{ $persen }}%</div>
                    <div class="text-muted small">% Kehadiran</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h6 class="mb-0"><i class="ri-calendar-line me-1"></i> Riwayat Absensi</h6>
                </div>
                <div class="card-body">
                    {{-- Filter --}}
                    <form method="GET" class="row g-3 mb-4">
                        <input type="hidden" name="studentUuid" value="{{ $student->id }}">
                        <div class="col-md-3">
                            <label class="form-label">Tahun Ajaran</label>
                            <select name="academic_year_id" class="form-control">
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}"
                                        {{ $selectedAyId == $ay->id ? 'selected' : '' }}>
                                        {{ $ay->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-control">
                                <option value="ganjil" {{ $selectedSemester == 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                <option value="genap" {{ $selectedSemester == 'genap' ? 'selected' : '' }}>Genap</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control"
                                value="{{ $startDate->toDateString() }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control"
                                value="{{ $endDate->toDateString() }}">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="ri-search-line me-1"></i> Filter
                            </button>
                            <a href="{{ route('user.absensi.harian.show', [
                                'userId' => $userId,
                                'studentUuid' => $student->id,
                            ]) }}" class="btn btn-light">Reset</a>
                        </div>
                    </form>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px">No</th>
                                    <th>Tanggal</th>
                                    <th>Hari</th>
                                    <th>Rombel</th>
                                    <th class="text-center">Status</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $idx => $rec)
                                    @php
                                        $statusConfig = [
                                            'hadir'     => ['badge' => 'bg-success',  'text' => 'Hadir'],
                                            'terlambat' => ['badge' => 'bg-warning',  'text' => 'Terlambat'],
                                            'izin'      => ['badge' => 'bg-info',     'text' => 'Izin'],
                                            'sakit'     => ['badge' => 'bg-secondary','text' => 'Sakit'],
                                            'alpa'      => ['badge' => 'bg-danger',   'text' => 'Alpa'],
                                        ];
                                        $cfg = $statusConfig[$rec->status] ?? ['badge' => 'bg-dark', 'text' => $rec->status];
                                        $hari = \Carbon\Carbon::parse($rec->attendance_date)->locale('id')->dayName;
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $records->firstItem() + $idx }}</td>
                                        <td>{{ \Carbon\Carbon::parse($rec->attendance_date)->format('d/m/Y') }}</td>
                                        <td>{{ $hari }}</td>
                                        <td>{{ $rec->studyGroup?->full_name ?? '-' }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $cfg['badge'] }}">{{ $cfg['text'] }}</span>
                                        </td>
                                        <td>{{ $rec->notes ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <em>Tidak ada data absensi untuk periode ini.</em>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $records->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
