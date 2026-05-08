@extends('layouts.master')
@section('title') Rekap Absensi Bulanan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}">Absensi Harian</a> @endslot
        @slot('title') Rekap Bulanan</span>
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Header --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row align-items-center g-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Rekap Absensi Bulanan</h5>
                            <p class="text-muted mb-0" style="font-size:0.8rem">
                                {{ $studyGroups->firstWhere('id', $selectedStudyGroupId)?->full_name ?? '—' }}
                                &nbsp;|&nbsp;
                                @php
                                    $monthName = \Carbon\Carbon::create($selectedYear, $selectedMonth, 1)->locale('id')->monthName;
                                @endphp
                                {{ $monthName }} {{ $selectedYear }}
                                &nbsp;|&nbsp; Semester {{ $selectedSemester == 'ganjil' ? 'Ganjil' : 'Genap' }}
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}"
                                class="btn btn-light btn-sm">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                            @if($selectedStudyGroupId)
                                <a href="{{ route('user.absensi.harian.create', ['userId' => $userId, 'study_group_id' => $selectedStudyGroupId]) }}"
                                    class="btn btn-outline-primary btn-sm">
                                    <i class="ri-edit-2-line me-1"></i> Input Absensi
                                </a>
                                <form method="GET" class="d-inline">
                                    <input type="hidden" name="study_group_id" value="{{ $selectedStudyGroupId }}">
                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                                    <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                                    <button type="submit" name="export" value="1" class="btn btn-success btn-sm">
                                        <i class="ri-file-excel-2-line me-1"></i> Export Excel
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Filter --}}
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Rombel</label>
                            <select name="study_group_id" class="form-control" onchange="this.form.submit()">
                                <option value="">— Semua Rombel —</option>
                                @foreach($studyGroups as $sg)
                                    <option value="{{ $sg->id }}"
                                        {{ $selectedStudyGroupId == $sg->id ? 'selected' : '' }}>
                                        {{ $sg->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Bulan</label>
                            <select name="month" class="form-control" onchange="this.form.submit()">
                                @foreach($months as $m)
                                    <option value="{{ $m['value'] }}"
                                        {{ $selectedMonth == $m['value'] ? 'selected' : '' }}>
                                        {{ $m['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tahun</label>
                            <input type="number" name="year" class="form-control"
                                value="{{ $selectedYear }}" min="2020" max="2030" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-control" onchange="this.form.submit()">
                                <option value="ganjil" {{ $selectedSemester == 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                <option value="genap" {{ $selectedSemester == 'genap' ? 'selected' : '' }}>Genap</option>
                            </select>
                        </div>
                    </form>
                </div>

                {{-- Tabel --}}
                @if($selectedStudyGroupId)
                    @php
                        $totalCols = 4 + $daysInMonth + 3;
                    @endphp
                    <div class="table-responsive" style="max-height:65vh">
                        <table class="table table-bordered table-hover align-middle mb-0" style="font-size:0.8rem">
                            <thead class="table-light text-center">
                                <tr>
                                    <th class="text-center" style="position:sticky;left:0;z-index:3;min-width:40px;background:#f3f4f6">No</th>
                                    <th style="position:sticky;left:40px;z-index:3;min-width:80px;background:#f3f4f6">NIS</th>
                                    <th style="position:sticky;left:120px;z-index:3;min-width:160px;background:#f3f4f6">Nama Lengkap</th>
                                    <th class="text-center" style="position:sticky;left:280px;z-index:3;min-width:40px;background:#f3f4f6">JK</th>
                                    @for($d = 1; $d <= $daysInMonth; $d++)
                                        <th class="text-center"
                                            style="min-width:30px;width:30px"
                                            title="{{ $startDate->copy()->day($d)->locale('id')->dayName }}">
                                            {{ $d }}
                                        </th>
                                    @endfor
                                    <th class="text-center bg-info-subtle" style="position:sticky;right:0;z-index:3;min-width:40px;background:#d0e8ff">S</th>
                                    <th class="text-center bg-primary-subtle" style="position:sticky;right:40px;z-index:3;min-width:40px;background:#bfdbfe">I</th>
                                    <th class="text-center bg-danger-subtle" style="position:sticky;right:80px;z-index:3;min-width:40px;background:#fecaca">A</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($studentRows as $idx => $student)
                                    @php
                                        $totalS = 0; $totalI = 0; $totalA = 0;
                                        $studentUuid = $student?->id;
                                    @endphp
                                    <tr>
                                        <td class="text-center" style="position:sticky;left:0;background:#fff;font-weight:600;color:#64748b">{{ $idx + 1 }}</td>
                                        <td style="position:sticky;left:40px;background:#fff">
                                            <code class="text-muted" style="font-size:0.75rem">{{ $student->nis ?? '-' }}</code>
                                        </td>
                                        <td style="position:sticky;left:120px;background:#fff">
                                            @if($studentUuid)
                                                <a href="{{ route('user.absensi.harian.show', ['userId' => $userId, 'studentUuid' => $studentUuid]) }}"
                                                    class="text-decoration-none fw-semibold">
                                                    {{ $student->name }}
                                                </a>
                                            @else
                                                <span class="fw-semibold">{{ $student->name }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center" style="position:sticky;left:280px;background:#fff">
                                            <span class="badge bg-{{ $student->gender === 'L' ? 'info' : 'danger' }}"
                                                style="font-size:0.7rem;padding:3px 7px">
                                                {{ $student->gender }}
                                            </span>
                                        </td>
                                        @for($d = 1; $d <= $daysInMonth; $d++)
                                            @php
                                                $dateStr = $startDate->copy()->day($d)->toDateString();
                                                $record = ($dateMap[$student->id] ?? [])[$dateStr] ?? null;
                                                $status = $record?->status;
                                                [$bgClass, $symbol] = match($status) {
                                                    'hadir'     => ['bg-success-subtle text-success', 'H'],
                                                    'terlambat' => ['bg-warning-subtle text-dark',   'T'],
                                                    'izin'      => ['bg-primary-subtle text-primary', 'I'],
                                                    'sakit'     => ['bg-info-subtle text-info',       'S'],
                                                    'alpa'      => ['bg-danger-subtle text-danger',   'A'],
                                                    default     => ['text-muted',                    '·'],
                                                };
                                                if ($status === 'sakit') $totalS++;
                                                elseif ($status === 'izin') $totalI++;
                                                elseif ($status === 'alpa') $totalA++;
                                            @endphp
                                            <td class="text-center {{ $bgClass }}" style="font-size:0.75rem"
                                                title="{{ $record?->notes ? $record->status_text . ': ' . $record->notes : ($record?->status_text ?? '') }}">
                                                {{ $symbol }}
                                            </td>
                                        @endfor
                                        <td class="text-center bg-info-subtle fw-bold text-info"
                                            style="position:sticky;right:0;background:#d0e8ff">{{ $totalS }}</td>
                                        <td class="text-center bg-primary-subtle fw-bold text-primary"
                                            style="position:sticky;right:40px;background:#bfdbfe">{{ $totalI }}</td>
                                        <td class="text-center bg-danger-subtle fw-bold text-danger"
                                            style="position:sticky;right:80px;background:#fecaca">{{ $totalA }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $totalCols }}" class="text-center text-muted py-5">
                                            <i class="ri-user-search-line d-block mb-2" style="font-size:2rem;color:#cbd5e1"></i>
                                            <strong>Belum ada siswa di rombel ini</strong>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer border-top bg-white">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <span class="badge bg-success-subtle text-success">H = Hadir</span>
                            <span class="badge bg-warning-subtle text-dark">T = Terlambat</span>
                            <span class="badge bg-primary-subtle text-primary">I = Izin</span>
                            <span class="badge bg-info-subtle text-info">S = Sakit</span>
                            <span class="badge bg-danger-subtle text-danger">A = Alpa</span>
                            <span class="badge bg-light text-muted">· = Belum diinput</span>
                            <small class="text-muted ms-auto">
                                <em>Scroll horizontal untuk melihat seluruh tanggal</em>
                            </small>
                        </div>
                    </div>
                @else
                    <div class="card-body text-center text-muted py-5">
                        <i class="ri-file-chart-line d-block mb-2" style="font-size:2.5rem;color:#e2e8f0"></i>
                        <strong>Pilih rombel untuk melihat rekap</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
