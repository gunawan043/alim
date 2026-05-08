@extends('layouts.master')
@section('title') Pemberian Obat @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('title') Pemberian Obat @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <?php
    $total  = $logs->total();
    $recent = $logs->sortByDesc('log_date')->first();
    $byMed  = $logs->groupBy(fn($r) => $r->inventory?->medicine_name ?? 'Lainnya')->count();
    ?>

    {{-- Stats --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card border-start border-1 border-primary">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-file-list-3-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Total Catatan</p>
                        <h5 class="mb-0">{{ $total }} <span class="fs-6 text-muted">record</span></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-start border-1 border-success">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-calendar-check-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Terakhir</p>
                        <h5 class="mb-0">{{ $recent ? $recent->log_date?->format('d/m/Y') : '-' }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-start border-1 border-info">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-info bg-opacity-10 text-info rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-medicine-bottle-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Jenis Obat Dipakai</p>
                        <h5 class="mb-0">{{ $byMed }} <span class="fs-6 text-muted">jenis</span></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Pemberian Obat ke Santi</h5>
                            <p class="text-muted mb-0 small">Dokumentasi pemberian obat di UKS pondok</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.uks.medicine-logs.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Catat Pemberian
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Nama Santi..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="study_group_id" class="form-control">
                                <option value="">Semua Kelas</option>
                                @foreach($studyGroups as $sg)
                                    <option value="{{ $sg->id }}" {{ request('study_group_id')==$sg->id?'selected':'' }}>{{ $sg->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="month" class="form-control">
                                <option value="">Semua Bulan</option>
                                @foreach([1,2,3,4,5,6,7,8,9,10,11,12] as $m)
                                    <option value="{{ date('Y').'-'.sprintf('%02d', $m) }}" {{ request('month') == date('Y').'-'.sprintf('%02d', $m) ? 'selected' : '' }}>
                                        {{ ucfirst(\Carbon\Carbon::createFromDate(date('Y'), $m, 1)->locale('id')->monthName) }} {{ date('Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.uks.medicine-logs.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Tanggal</th>
                                    <th>Nama Santi</th>
                                    <th>Obat</th>
                                    <th class="text-center">Jumlah</th>
                                    <th>Dosis</th>
                                    <th>Petugas</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $i => $row)
                                    <tr>
                                        <td class="text-center text-muted">{{ $logs->firstItem() + $i }}</td>
                                        <td>
                                            <span class="fw-medium">{{ $row->log_date?->format('d/m/Y') ?? '-' }}</span>
                                            @if($row->time_given)
                                                <br><small class="text-muted">{{ \Carbon\Carbon::parse($row->time_given)->format('H:i') }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $row->student?->name ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-primary">{{ $row->inventory?->medicine_name ?? '-' }}</span>
                                            @if($row->inventory?->unit)
                                                <br><small class="text-muted">{{ $row->inventory->unit }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center fw-bold">{{ $row->quantity_given }}</td>
                                        <td>
                                            @if($row->dosage)
                                                <span class="badge bg-light text-dark">{{ $row->dosage }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-muted">{{ $row->administeredBy?->name ?? '-' }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('user.uks.medicine-logs.show', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                               class="btn btn-sm btn-outline-primary me-1"><i class="ri-eye-line"></i></a>
                                            <form method="POST" action="{{ route('user.uks.medicine-logs.destroy', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                                  class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn"><i class="ri-delete-bin-line"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="ri-medicine-bottle-line fs-1 d-block mb-2"></i>
                                            Belum ada catatan pemberian obat.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $logs->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection