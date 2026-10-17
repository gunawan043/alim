@extends('layouts.master')

@section('title') Laporan Santri @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Laporan @endslot
        @slot('li_2') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Semua Laporan</a> @endslot
        @slot('title') Laporan Santri @endslot
    @endcomponent

    @php
        $statusLabels = [
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
            'graduate' => 'Lulus',
            'dropped' => 'Keluar',
            'transfer' => 'Pindah',
        ];
    @endphp

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Total</div>
                <div class="fs-3 fw-bold">{{ number_format($summary['total']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Aktif</div>
                <div class="fs-3 fw-bold text-success">{{ number_format($summary['aktif']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Laki-laki</div>
                <div class="fs-3 fw-bold text-primary">{{ number_format($summary['laki']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Perempuan</div>
                <div class="fs-3 fw-bold text-danger">{{ number_format($summary['perempuan']) }}</div>
            </div></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('user.laporan.santri', ['userId' => $userId]) }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Cari</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nama / NISN / NIS…">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Rombel</label>
                    <select name="rombel" class="form-select">
                        <option value="">Semua</option>
                        @foreach($studyGroups as $group)
                            <option value="{{ $group->id }}" {{ request('rombel') === $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach($statusLabels as $val => $label)
                            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Jenis Kelamin</label>
                    <select name="gender" class="form-select">
                        <option value="">Semua</option>
                        <option value="L" {{ request('gender') === 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ request('gender') === 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button>
                    <a href="{{ route('user.laporan.export', array_merge(['userId' => $userId, 'type' => 'santri'], request()->query())) }}"
                       class="btn btn-success w-100"><i class="ri-download-2-line me-1"></i>CSV</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-user-star-line me-2 text-primary"></i>Daftar Santri</h5>
            <a href="{{ route('user.laporan.pdf', array_merge(['userId' => $userId, 'type' => 'santri'], request()->query())) }}"
               target="_blank" class="btn btn-sm btn-danger">
                <i class="ri-file-pdf-2-line me-1"></i>Cetak PDF
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">No</th>
                            <th>NISN / NIS</th>
                            <th>Nama</th>
                            <th>JK</th>
                            <th>Rombel</th>
                            @if(! $schoolId)
                                <th>Sekolah</th>
                            @endif
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $i => $student)
                            @php
                                $tone = match ($student->status) {
                                    'active' => 'success',
                                    'inactive' => 'secondary',
                                    'graduate' => 'info',
                                    'dropped', 'transfer' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td>{{ $students->firstItem() + $i }}</td>
                                <td>
                                    <div class="fw-medium">{{ $student->nisn ?: '—' }}</div>
                                    <div class="small text-muted">{{ $student->nis ?: '' }}</div>
                                </td>
                                <td>{{ $student->name }}</td>
                                <td>{{ $student->gender === 'L' ? 'L' : ($student->gender === 'P' ? 'P' : '—') }}</td>
                                <td>{{ $student->currentClassHistory?->studyGroup?->name ?? '—' }}</td>
                                @if(! $schoolId)
                                    <td class="small">{{ $student->school?->name ?? '—' }}</td>
                                @endif
                                <td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ $statusLabels[$student->status] ?? ucfirst($student->status) }}</span></td>
                            </tr>
                        @empty
                            <x-empty-state colspan="{{ $schoolId ? 6 : 7 }}" title="Tidak ada santri"
                                message="Tidak ada data santri yang sesuai filter." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($students->hasPages())
            <div class="card-footer">
                @include('shared._pagination', ['paginator' => $students])
            </div>
        @endif
    </div>
@endsection
