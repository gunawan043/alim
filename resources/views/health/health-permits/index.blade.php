@extends('layouts.master')
@section('title') Izin Sakit Santi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('title') Izin Sakit @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <?php
    $total     = $permits->total();
    $pending   = collect($permits->items())->filter(fn($r) => $r->status === 'pending')->count();
    $approved  = collect($permits->items())->filter(fn($r) => $r->status === 'approved')->count();
    $notified  = collect($permits->items())->filter(fn($r) => $r->parent_notified)->count();
    $typeMap   = ['sakit_ringan'=>'Sakit Ringan','sakit_sedang'=>'Sakit Sedang','sakit_berat'=>'Sakit Berat','kontrol_dokter'=>'Kontrol Dokter','isolasi'=>'Isolasi'];
    $typeColor = ['sakit_ringan'=>'info','sakit_sedang'=>'warning','sakit_berat'=>'danger','kontrol_dokter'=>'primary','isolasi'=>'dark'];
    $stsColor  = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','extended'=>'info','cancelled'=>'secondary'];
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
                        <p class="text-muted mb-0 small">Total Izin</p>
                        <h5 class="mb-0">{{ $total }} <span class="fs-6 text-muted">record</span></h5>
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
                        <p class="text-muted mb-0 small">Menunggu</p>
                        <h5 class="mb-0">{{ $pending }} <span class="fs-6 text-muted">izin</span></h5>
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
                        <p class="text-muted mb-0 small">Disetujui</p>
                        <h5 class="mb-0">{{ $approved }} <span class="fs-6 text-muted">izin</span></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-1 border-info">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-info bg-opacity-10 text-info rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-notification-3-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Wali Dinotifikasi</p>
                        <h5 class="mb-0">{{ $notified }} <span class="fs-6 text-muted">kali</span></h5>
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
                            <h5 class="card-title mb-0">Izin Sakit Santi</h5>
                            <p class="text-muted mb-0 small">Permohonan izin sakit &amp; istirahat</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.uks.health-permits.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Ajukan Izin Sakit
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
                            <select name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Menunggu</option>
                                <option value="approved" {{ request('status')=='approved'?'selected':'' }}>Disetujui</option>
                                <option value="rejected" {{ request('status')=='rejected'?'selected':'' }}>Ditolak</option>
                                <option value="extended" {{ request('status')=='extended'?'selected':'' }}>Diperpanjang</option>
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
                        <div class="col-md-1">
                            <a href="{{ route('user.uks.health-permits.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Nama Santi</th>
                                    <th>Jenis Izin</th>
                                    <th>Tanggal Mulai</th>
                                    <th>Tanggal Selesai</th>
                                    <th class="text-center">Hari</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Wali Diinformasikan</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permits as $i => $row)
                                <tr class="{{ $row->status === 'pending' ? 'table-warning' : '' }}">
                                    <td class="text-center text-muted">{{ $permits->firstItem() + $i }}</td>
                                    <td>
                                        <span class="fw-semibold">{{ $row->student?->name ?? '-' }}</span>
                                        @if($row->academicYear)
                                            <br><small class="text-muted">{{ $row->academicYear->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $typeColor[$row->permit_type] ?? 'secondary' }}">
                                            {{ $typeMap[$row->permit_type] ?? $row->permit_type }}
                                        </span>
                                    </td>
                                    <td>{{ $row->start_date?->format('d/m/Y') }}</td>
                                    <td>{{ $row->end_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="text-center fw-bold">{{ $row->rest_days }} <span class="fw-normal text-muted">hari</span></td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $stsColor[$row->status] ?? 'secondary' }}">
                                            {{ $row->status_text }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($row->parent_notified)
                                            <span class="badge bg-success"><i class="ri-check-line me-1"></i>Sudah</span>
                                            @if($row->parent_notified_at)
                                                <br><small class="text-muted">{{ $row->parent_notified_at->format('d/m H:i') }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-light text-dark"><i class="ri-close-line me-1"></i>Belum</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('user.uks.health-permits.show', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                           class="btn btn-sm btn-outline-primary me-1"><i class="ri-eye-line"></i></a>
                                        <a href="{{ route('user.uks.health-permits.edit', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                           class="btn btn-sm btn-outline-secondary me-1"><i class="ri-edit-line"></i></a>
                                        <form method="POST" action="{{ route('user.uks.health-permits.destroy', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                              class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-btn"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="ri-file-list-3-line fs-1 d-block mb-2"></i>
                                        Belum ada data izin sakit.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $permits->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
