@extends('layouts.master')
@section('title') Detail Poin Siswa @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK & Peserta Didik @endslot
        @slot('li_2') <a href="{{ route('user.violation-points.index', ['userId' => $userId]) }}">Poin Pelanggaran</a> @endslot
        @slot('li_3') <a href="{{ route('user.violation-points.recap', ['userId' => $userId]) }}">Rekap Per Siswa</a> @endslot
        @slot('title') {{ $student->name }} @endslot
    @endcomponent

    {{-- Student Info & Total Poin --}}
    <div class="row mb-4">
        <div class="col-sm-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h2 class="mb-0">{{ $totalPoints }}</h2>
                    <p class="mb-0 small opacity-75">Total Poin</p>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h2 class="mb-0">{{ $violations->total() }}</h2>
                    <p class="mb-0 small opacity-75">Jumlah Pelanggaran</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="text-muted small">Nama</label>
                            <div class="fw-semibold">{{ $student->name }}</div>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small">NISN</label>
                            <div class="fw-semibold">{{ $student->nisn ?? '-' }}</div>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small">Rombel</label>
                            <div class="fw-semibold">{{ $student->studyGroups->first()?->studyGroup?->full_name ?? '-' }}</div>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small">Tingkat Pelanggaran</label>
                            <div>
                                @php
                                    if ($totalPoints >= 25) {
                                        $level = ['bg-dark', 'Sangat Berat'];
                                    } elseif ($totalPoints >= 16) {
                                        $level = ['bg-danger', 'Berat'];
                                    } elseif ($totalPoints >= 6) {
                                        $level = ['bg-warning text-dark', 'Sedang'];
                                    } elseif ($totalPoints >= 1) {
                                        $level = ['bg-secondary', 'Ringan'];
                                    } else {
                                        $level = ['bg-success', 'Tidak Ada'];
                                    }
                                @endphp
                                <span class="badge {{ $level[0] }}">{{ $level[1] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Riwayat Pelanggaran</h5>
                        <a href="{{ route('user.violation-points.recap', ['userId' => $userId]) }}" class="btn btn-sm btn-secondary">
                            <i class="ri-arrow-left-line me-1"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Jenis Pelanggaran</th>
                                    <th class="text-center">Poin</th>
                                    <th>Tindakan</th>
                                    <th>Dicatat Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($violations as $i => $v)
                                    <tr>
                                        <td>{{ $violations->firstItem() + $i }}</td>
                                        <td>{{ $v->violation_date->format('d/m/Y') }}</td>
                                        <td>{{ $v->violation_type }}</td>
                                        <td class="text-center"><span class="badge bg-danger">{{ $v->points }}</span></td>
                                        <td><span class="small text-muted">{{ Str::limit($v->action_taken, 50) }}</span></td>
                                        <td>{{ $v->recordedBy?->name ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="ri-checkbox-circle-line fs-1 d-block mb-2 text-success"></i>
                                            Siswa ini tidak memiliki pelanggaran.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-3 pb-3">
                        {{ $violations->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
