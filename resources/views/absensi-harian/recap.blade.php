@extends('layouts.master')
@section('title') Rekap Absensi Bulanan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}">Absensi Harian</a> @endslot
        @slot('title') Rekap Bulanan</span>
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Rekap Absensi Bulanan</h5>
                            <p class="text-muted mb-0">Rekapitulasi absensi per bulan per rombel (format Excel).</p>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filter --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Rombel</label>
                            <select name="study_group_id" class="form-control">
                                <option value="">— Pilih Rombel —</option>
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
                            <select name="month" class="form-control">
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
                                value="{{ $selectedYear }}" min="2020" max="2030">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-control">
                                <option value="ganjil" {{ $selectedSemester == 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                <option value="genap" {{ $selectedSemester == 'genap' ? 'selected' : '' }}>Genap</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-search-line me-1"></i> Tampilkan
                            </button>
                            @if($selectedStudyGroupId)
                                <button type="submit" name="export" value="1" class="btn btn-success">
                                    <i class="ri-file-excel-2-line me-1"></i> Export Excel
                                </button>
                            @endif
                        </div>
                    </form>

                    {{-- Table --}}
                    @if($selectedStudyGroupId)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:50px">No</th>
                                        <th style="width:100px">NIS</th>
                                        <th>Nama Lengkap</th>
                                        <th class="text-center" style="width:70px">JK</th>
                                        <th class="text-center bg-success-subtle">Hadir</th>
                                        <th class="text-center bg-warning-subtle">Terlambat</th>
                                        <th class="text-center bg-info-subtle">Izin</th>
                                        <th class="text-center bg-secondary-subtle">Sakit</th>
                                        <th class="text-center bg-danger-subtle">Alpa</th>
                                        <th class="text-center">Total<br>Hari</th>
                                        <th class="text-center">% Kehadiran</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rekapData as $idx => $row)
                                        @php
                                            $total = $row['hadir'] + $row['terlambat'] + $row['izin'] + $row['sakit'] + $row['alpa'];
                                            $persen = $total > 0
                                                ? round((($row['hadir'] + $row['terlambat']) / $total) * 100, 1)
                                                : 0;
                                            $persenClass = $persen >= 90 ? 'success' : ($persen >= 75 ? 'warning' : 'danger');
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $idx + 1 }}</td>
                                            <td><code>{{ $row['nis'] ?? '-' }}</code></td>
                                            <td>
                                                <a href="{{ route('user.absensi.harian.show', [
                                                    'userId' => $userId,
                                                    'studentUuid' => \App\Models\StudentClassHistory::where('student_id', $row['nis'] ?? '')->first()?->student_id ?? '',
                                                ]) }}">
                                                    {{ $row['name'] }}
                                                </a>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $row['gender'] === 'L' ? 'info' : 'pink' }}">
                                                    {{ $row['gender'] }}
                                                </span>
                                            </td>
                                            <td class="text-center fw-bold bg-success-subtle">{{ $row['hadir'] }}</td>
                                            <td class="text-center bg-warning-subtle">{{ $row['terlambat'] }}</td>
                                            <td class="text-center bg-info-subtle">{{ $row['izin'] }}</td>
                                            <td class="text-center bg-secondary-subtle">{{ $row['sakit'] }}</td>
                                            <td class="text-center bg-danger-subtle">{{ $row['alpa'] }}</td>
                                            <td class="text-center">{{ $total }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $persenClass }}">{{ $persen }}%</span>
                                            </td>
                                            <td></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="12" class="text-center text-muted py-4">
                                                <em>Tidak ada data absensi untuk periode ini. Pastikan absensi harian sudah diinput.</em>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="ri-file-chart-line fs-1 text-secondary mb-2 d-block"></i>
                            <strong>Silakan pilih rombel terlebih dahulu</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
