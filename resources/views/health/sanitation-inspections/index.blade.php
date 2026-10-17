@extends('layouts.master')
@section('title') Inspeksi Sanitasi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('title') Inspeksi Sanitasi @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <?php
    $total      = $inspections->total();
    $passed     = collect($inspections->items())->filter(fn($r) => $r->score >= 80)->count();
    $pending    = collect($inspections->items())->filter(fn($r) => !$r->follow_up_completed_at && $r->follow_up_deadline)->count();
    $overdue    = collect($inspections->items())->filter(fn($r) => $r->follow_up_deadline && $r->follow_up_deadline->isPast() && !$r->follow_up_completed_at)->count();
    $avgScore   = $inspections->count() ? round($inspections->avg('score'), 1) : 0;
    $locMap     = ['asrama'=>'Asrama','kantin'=>'Kantin','toilet'=>'Toilet','tempat_sampah'=>'Tempat Sampah','sumber_air'=>'Sumber Air','ruang_kelas'=>'Ruang Kelas','halaman'=>'Halaman','dapur'=>'Dapur'];
    ?>

    {{-- Stats --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card border-start border-1 border-primary">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-file-list-3-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Total Inspeksi</p>
                        <h5 class="mb-0">{{ $total }} <span class="fs-6 text-muted">record</span></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-1 border-success">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-checkbox-circle-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Lulus (&ge;80)</p>
                        <h5 class="mb-0">{{ $passed }} <span class="fs-6 text-muted">inspeksi</span></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-1 border-warning">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-warning bg-opacity-10 text-warning rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-time-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Menunggu Follow-up</p>
                        <h5 class="mb-0">{{ $pending }} <span class="fs-6 text-muted">inspeksi</span></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-1 border-danger">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-danger bg-opacity-10 text-danger rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-alert-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Terlambat</p>
                        <h5 class="mb-0">{{ $overdue }} <span class="fs-6 text-muted">inspeksi</span></h5>
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
                            <h5 class="card-title mb-0">Inspeksi Sanitasi Lingkungan</h5>
                            <p class="text-muted mb-0 small">Pemeriksaan kebersihan &amp; sanitasi lingkungan pondok</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.uks.sanitation-inspections.dashboard', ['userId' => $userId]) }}" class="btn btn-info me-2">
                                <i class="ri-bar-chart-line me-1"></i> Dashboard
                            </a>
                            <a href="{{ route('user.uks.sanitation-inspections.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Inspeksi
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Temuan..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="location_type" class="form-control">
                                <option value="">Semua Lokasi</option>
                                @foreach($locMap as $k => $v)
                                    <option value="{{ $k }}" {{ request('location_type')==$k?'selected':'' }}>{{ $v }}</option>
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
                            <a href="{{ route('user.uks.sanitation-inspections.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Tanggal</th>
                                    <th>Lokasi</th>
                                    <th>Skor</th>
                                    <th>Hasil</th>
                                    <th>Deadline Follow-up</th>
                                    <th>Status Follow-up</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inspections as $i => $row)
                                <?php
                                $scoreColor = $row->score >= 80 ? 'success' : ($row->score >= 60 ? 'warning' : 'danger');
                                ?>
                                <tr class="{{ $overdue && $row->follow_up_deadline && $row->follow_up_deadline->isPast() && !$row->follow_up_completed_at ? 'table-danger' : ($row->score < 60 ? 'table-warning' : '') }}">
                                    <td class="text-center text-muted">{{ $inspections->firstItem() + $i }}</td>
                                    <td>
                                        <span class="fw-medium">{{ $row->inspection_date?->format('d/m/Y') ?? '-' }}</span>
                                        @if($row->inspectedBy)
                                            <br><small class="text-muted">{{ $row->inspectedBy->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $locMap[$row->location_type] ?? $row->location_type }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress w-50" style="height:6px;">
                                                <div class="progress-bar bg-{{ $scoreColor }}"
                                                     style="width: {{ $row->score }}%"></div>
                                            </div>
                                            <span class="fw-bold small text-{{ $scoreColor }}">{{ $row->score }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $scoreColor }}">{{ $row->score_label }}</span>
                                    </td>
                                    <td>
                                        @if($row->follow_up_deadline)
                                            <span class="{{ $row->follow_up_completed_at ? 'text-muted' : ($row->follow_up_deadline->isPast() ? 'text-danger fw-semibold' : 'text-warning') }}">
                                                {{ $row->follow_up_deadline->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($row->follow_up_completed_at)
                                            <span class="badge bg-success"><i class="ri-check-line me-1"></i>Selesai</span>
                                            <br><small class="text-muted">{{ $row->follow_up_completed_at->format('d/m H:i') }}</small>
                                        @elseif($row->follow_up_deadline && $row->follow_up_deadline->isPast())
                                            <span class="badge bg-danger"><i class="ri-alert-line me-1"></i>Terlambat</span>
                                        @elseif($row->follow_up_deadline)
                                            <span class="badge bg-warning"><i class="ri-time-line me-1"></i>Menunggu</span>
                                        @else
                                            <span class="badge bg-light text-dark">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('user.uks.sanitation-inspections.show', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                           class="btn btn-sm btn-outline-primary me-1"><i class="ri-eye-line"></i></a>
                                        <a href="{{ route('user.uks.sanitation-inspections.edit', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                           class="btn btn-sm btn-outline-secondary me-1"><i class="ri-edit-line"></i></a>
                                        <form method="POST" action="{{ route('user.uks.sanitation-inspections.destroy', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                              class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-btn"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="ri-showers-line fs-1 d-block mb-2"></i>
                                        Belum ada data inspeksi sanitasi.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $inspections->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection