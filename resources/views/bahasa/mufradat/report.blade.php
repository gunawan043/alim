@extends('layouts.master')

@section('title', 'Laporan Evaluasi Mufradat')

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') <a href="{{ route('user.bahasa.mufradat.show', ['userId' => $userId, 'period' => $record->id]) }}">{{ $record->title }}</a> @endslot
        @slot('title') Laporan Evaluasi @endslot
    @endcomponent

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">Laporan Evaluasi Hafalan Mufradat</h4>
            <div class="lang-muted">{{ $record->title }} · {{ $record->academicYear?->name ?? '-' }}</div>
        </div>
        <div class="d-flex gap-2">
            @if($canRecord ?? false)
                <a href="{{ route('user.bahasa.mufradat.attendance', ['userId' => $userId, 'period' => $record->id]) }}" class="btn btn-success"><i class="ri-check-double-line me-1"></i>Input Setoran</a>
            @endif
            <a href="{{ route('user.bahasa.mufradat.print-report', ['userId' => $userId, 'period' => $record->id]) }}" target="_blank" class="btn btn-light"><i class="ri-printer-line me-1"></i>Cetak</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="lang-stat"><div class="label">Santri Dievaluasi</div><div class="value">{{ $rows->count() }}</div></div></div>
        <div class="col-md-3"><div class="lang-stat"><div class="label">Total Setoran</div><div class="value">{{ $setorans->count() }}</div></div></div>
        <div class="col-md-3"><div class="lang-stat"><div class="label">Rata-rata Nilai</div><div class="value">{{ $rows->count() ? number_format($rows->avg('average'), 1, ',', '.') : 0 }}%</div></div></div>
        <div class="col-md-3"><div class="lang-stat"><div class="label">Perlu Pengulangan</div><div class="value">{{ $rows->where('follow_up', 'Pengulangan Hafalan')->count() }}</div></div></div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <x-bahasa-filter-fields
                :schools="$schools"
                :grade-levels="$gradeLevels"
                :study-groups="$studyGroups"
                :show-school="$showSchool"
                :school-id="$schoolId"
                :grade-level-id="$gradeLevelId"
                :study-group-id="$studyGroupId"
            />
            <div class="col-md-2"><button class="btn btn-soft-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button></div>
        </form>
    </div></div>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Rekap per Santri</h5></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>No</th><th>Nama Santri</th><th class="text-center">Setoran</th><th class="text-center">Kata</th><th class="text-center">Benar</th><th class="text-center">Rata-rata</th><th class="text-center">Tindak Lanjut</th></tr>
                </thead>
                <tbody>
                    @forelse($rows as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-semibold">{{ $row['student']?->name ?? '-' }}</td>
                            <td class="text-center">{{ $row['attempts'] }}</td>
                            <td class="text-center">{{ $row['total_words'] }}</td>
                            <td class="text-center">{{ $row['correct_words'] }}</td>
                            <td class="text-center fw-semibold">{{ number_format($row['average'], 1, ',', '.') }}%</td>
                            <td><span class="badge lang-badge {{ $row['follow_up'] === 'Tidak Ada' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ $row['follow_up'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">Belum ada data setoran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
