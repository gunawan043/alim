@extends('layouts.master')
@section('title') Absensi Harian Peserta Didik @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('title') Absensi Peserta Didik</span>
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row align-items-center g-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Absensi Harian Peserta Didik</h5>
                            <p class="text-muted mb-0" style="font-size:0.8rem">
                                {{ $activeYear ? $activeYear->name . ' — Semester ' . ucfirst($activeYear->semester) : 'Tahun ajaran belum aktif' }}
                                &nbsp;|&nbsp; {{ $selectedDate->locale('id')->translatedFormat('j F Y') }}
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.absensi.harian.recap.semester', ['userId' => $userId]) }}"
                                class="btn btn-outline-secondary btn-sm">
                                <i class="ri-file-chart-2-line me-1"></i> Rekap Semester
                            </a>
                            <a href="{{ route('user.absensi.harian.create', ['userId' => $userId]) }}"
                                class="btn btn-primary btn-sm">
                                <i class="ri-edit-2-line me-1"></i> Input Absensi
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="date" class="form-control"
                                value="{{ $selectedDate->toDateString() }}" max="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-control">
                                <option value="ganjil" {{ $selectedSemester == 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                <option value="genap" {{ $selectedSemester == 'genap' ? 'selected' : '' }}>Genap</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-search-line me-1"></i> Tampilkan
                            </button>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <a href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}"
                                class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:40px">#</th>
                                    <th>Rombel</th>
                                    <th class="text-center">Wali Kelas</th>
                                    <th class="text-center">Siswa</th>
                                    <th class="text-center bg-success-subtle">Hadir</th>
                                    <th class="text-center bg-warning-subtle">Terlambat</th>
                                    <th class="text-center bg-info-subtle">Izin</th>
                                    <th class="text-center bg-secondary-subtle">Sakit</th>
                                    <th class="text-center bg-danger-subtle">Alpa</th>
                                    <th class="text-center">Tercatat</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($studyGroups as $idx => $sg)
                                    @php
                                        $stats = $rombelStats[$sg->id] ?? null;
                                        $total = $stats['total'] ?? 0;
                                        $recorded = $stats['recorded'] ?? 0;
                                        $pct = $total > 0 ? round($recorded / $total * 100) : 0;
                                    @endphp
                                    <tr>
                                        <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                        <td>
                                            <a href="{{ route('user.absensi.harian.recap.detail', [
                                                'userId' => $userId,
                                                'study_group_id' => $sg->id,
                                                'month' => $selectedDate->month,
                                                'year' => $selectedDate->year,
                                                'semester' => $selectedSemester,
                                            ]) }}" class="text-decoration-none fw-semibold">
                                                {{ $sg->full_name }}
                                            </a>
                                            @if($sg->homeroomTeacher)
                                                <span class="text-muted ms-1" style="font-size:0.75rem">({{ $sg->homeroomTeacher->name }})</span>
                                            @endif
                                        </td>
                                        <td class="text-center text-muted" style="font-size:0.82rem">
                                            {{ $sg->homeroomTeacher?->name ?? '—' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-dark rounded-circle" style="font-size:0.72rem">{{ $total ?: '—' }}</span>
                                        </td>
                                        <td class="text-center bg-success-subtle fw-bold">{{ $stats['hadir'] ?? 0 }}</td>
                                        <td class="text-center bg-warning-subtle">{{ $stats['terlambat'] ?? 0 }}</td>
                                        <td class="text-center bg-info-subtle">{{ $stats['izin'] ?? 0 }}</td>
                                        <td class="text-center bg-secondary-subtle">{{ $stats['sakit'] ?? 0 }}</td>
                                        <td class="text-center bg-danger-subtle">{{ $stats['alpa'] ?? 0 }}</td>
                                        <td class="text-center">
                                            @if($total == 0)
                                                <span class="badge bg-light text-muted">Tanpa siswa</span>
                                            @elseif($pct == 100)
                                                <span class="badge bg-success">Lengkap</span>
                                            @elseif($pct > 0)
                                                <span class="badge bg-warning text-dark">{{ $recorded }}/{{ $total }}</span>
                                            @else
                                                <span class="badge bg-secondary">Belum</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.absensi.harian.create', [
                                                'userId' => $userId,
                                                'study_group_id' => $sg->id,
                                                'date' => $selectedDate->toDateString(),
                                                'semester' => $selectedSemester,
                                            ]) }}"
                                                class="btn btn-sm {{ $pct == 100 ? 'btn-outline-success' : 'btn-primary' }}"
                                                title="{{ $pct == 100 ? 'Lihat / Edit' : 'Input Absensi' }}">
                                                <i class="ri-{{ $pct == 100 ? 'eye' : 'edit' }}-2-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-4">
                                            <em>Tidak ada rombel ditemukan.</em>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
