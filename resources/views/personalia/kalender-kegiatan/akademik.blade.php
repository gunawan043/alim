{{-- Agenda Kegiatan — sumber data tunggal: Kaldik (kategori agenda). --}}
@extends('layouts.master')
@section('title') Agenda Kegiatan @endsection

@section('css')
    @include('shared._styles')
@endsection

@section('content')
    @php
        $today = now()->toDateString();
        $canCreateAgenda = function_exists('canPermission') && canPermission('kaldik-admin-tu');
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kalender Akademik @endslot
        @slot('li_2') <a href="{{ route('user.kaldik.index', ['userId' => $userId]) }}">Kalender Pendidikan</a> @endslot
        @slot('title') Agenda Kegiatan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Ringkasan --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-calendar-event-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Agenda</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $stats['total'] }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Agenda satuan &amp; pribadi Anda</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-loader-4-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Berlangsung</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $stats['berlangsung'] }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Sedang berjalan hari ini</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-calendar-check-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Mendatang</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $stats['mendatang'] }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Belum dimulai</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Selesai</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $stats['selesai'] }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Sudah terlewati</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="card-title mb-0">Daftar Agenda Kegiatan</h5>
                    <p class="text-muted mb-0 small">
                        Terhubung dengan <strong>Kalender Pendidikan</strong> — sumber data yang sama.
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('user.kaldik.index', ['userId' => $userId]) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ri-calendar-2-line me-1"></i>Kalender Pendidikan
                    </a>
                    <a href="{{ route('user.todos.index', ['userId' => $userId]) }}" class="btn btn-outline-primary btn-sm">
                        <i class="ri-task-line me-1"></i>To-do List
                    </a>
                    @if($canCreateAgenda)
                        <a href="{{ route('user.kaldik.create', ['userId' => $userId]) }}" class="btn btn-success btn-sm">
                            <i class="ri-add-line me-1"></i>Tambah Agenda
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('user.kalender-kegiatan.akademik', $userId) }}" class="row g-2 align-items-end mb-3">
                <div class="col-lg-4 col-md-6">
                    <label class="form-label small text-muted mb-1">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Tahun Ajaran</option>
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ request('academic_year_id') === $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-3">
                    <a href="{{ route('user.kalender-kegiatan.akademik', $userId) }}" class="btn btn-light btn-sm w-100">
                        <i class="ri-refresh-line me-1"></i>Reset
                    </a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px" class="text-center">No</th>
                            <th>Nama Kegiatan</th>
                            <th style="width:130px">Privasi</th>
                            <th style="width:210px">Periode</th>
                            <th style="width:130px" class="text-center">Status</th>
                            <th style="width:160px">Tahun Ajaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agendaList as $i => $agenda)
                            @php
                                $start = $agenda->start_date?->toDateString();
                                $end = $agenda->end_date?->toDateString();
                                $status = $start > $today ? ['Mendatang', 'info'] : ($end < $today ? ['Selesai', 'success'] : ['Berlangsung', 'warning']);
                                $isPribadi = $agenda->visibility === \App\Models\Kaldik::VISIBILITY_PRIBADI;
                            @endphp
                            <tr>
                                <td class="text-center text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $agenda->name }}</span>
                                    @if($agenda->description)
                                        <small class="text-muted d-block">{{ \Illuminate\Support\Str::limit($agenda->description, 90) }}</small>
                                    @endif
                                    <small class="text-muted d-block">
                                        {{ $agenda->type ? (\App\Models\Kaldik::TYPE_OPTIONS[$agenda->type] ?? ucfirst($agenda->type)) : ucfirst((string) $agenda->category) }}
                                    </small>
                                </td>
                                <td>
                                    @if($isPribadi)
                                        <span class="badge bg-danger-subtle text-danger"><i class="ri-lock-line me-1"></i>Pribadi</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary"><i class="ri-community-line me-1"></i>Satuan</span>
                                    @endif
                                </td>
                                <td class="small">
                                    {{ $agenda->start_date?->format('d M Y') ?? '-' }}
                                    @if($agenda->end_date && $agenda->end_date->ne($agenda->start_date))
                                        &ndash; {{ $agenda->end_date->format('d M Y') }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $status[1] }}-subtle text-{{ $status[1] }}">{{ $status[0] }}</span>
                                </td>
                                <td class="small">{{ $agenda->academicYear?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <x-empty-state colspan="6"
                                title="Belum ada agenda kegiatan"
                                message="Agenda satuan dan agenda pribadi Anda akan tampil di sini — sumbernya sama dengan Kalender Pendidikan." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
