@extends('layouts.master')
@section('title', 'Laporan Kesiapan Perangkat')
@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.kurikulum.index', ['userId' => $userId]) }}">Kurikulum</a> @endslot
        @slot('li_2') <a href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId]) }}">Perangkat</a> @endslot
        @slot('title') Laporan Kesiapan Perangkat @endslot
    @endcomponent

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-1"><i class="ri-shield-check-line text-primary me-1"></i>Laporan Kesiapan Perangkat</h5>
                <p class="text-muted mb-0 small">Status penyusunan dan persetujuan KSP untuk setiap guru dan mapel.</p>
            </div>
            <a href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
                <i class="ri-arrow-left-line me-1"></i>Kembali ke Perangkat
            </a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach([
            ['Total Perangkat', $stats['total'], 'primary', 'ri-booklet-line'],
            ['Disetujui Rumpun', $stats['rumpun'], 'info', 'ri-team-line'],
            ['Disetujui KSP', $stats['ksp'], 'success', 'ri-shield-check-line'],
            ['Masih Draft', $stats['draft'], 'warning', 'ri-draft-line'],
        ] as [$label, $value, $color, $icon])
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-title bg-{{ $color }}-subtle text-{{ $color }} rounded fs-3" style="width:44px;height:44px;">
                            <i class="{{ $icon }}"></i>
                        </span>
                        <div>
                            <p class="text-muted mb-1">{{ $label }}</p>
                            <h4 class="mb-0">{{ $value }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select" onchange="this.form.submit()">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" @selected($academicYearId === $ay->id)>{{ $ay->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select" onchange="this.form.submit()">
                        <option value="ganjil" @selected($semester === 'ganjil')>Ganjil</option>
                        <option value="genap" @selected($semester === 'genap')>Genap</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Guru</th>
                            <th>Mapel / Kelas</th>
                            <th>Perangkat</th>
                            <th>Status</th>
                            <th>KSP</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $isKsp = $row->isKspApproved();
                                $isRumpun = $row->workflow_status === \App\Models\PerangkatPembelajaran::WORKFLOW_APPROVED;
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $row->teacher?->name ?? '—' }}</td>
                                <td>
                                    {{ $row->subject?->name ?? '—' }}
                                    <div class="small text-muted">{{ $row->studyGroup?->name ?? $row->gradeLevel?->name ?? 'Semua Kelas' }}</div>
                                </td>
                                <td>
                                    <a href="{{ route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $row->id]) }}" class="link-primary">
                                        {{ $row->judul }}
                                    </a>
                                    <div class="small text-muted">v{{ $row->version ?? 1 }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $isRumpun ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ $isRumpun ? 'Disetujui Rumpun' : \App\Models\PerangkatPembelajaran::WORKFLOW_OPTIONS[$row->workflow_status] ?? $row->workflow_status }}
                                    </span>
                                </td>
                                <td>
                                    @if($isKsp)
                                        <span class="badge bg-success-subtle text-success"><i class="ri-check-line me-1"></i>Disetujui KSP</span>
                                    @elseif($isRumpun)
                                        <span class="badge bg-warning-subtle text-warning">Menunggu KSP</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Belum final</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $row->id]) }}" class="btn btn-sm btn-soft-primary">
                                        Buka
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">Belum ada perangkat pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
