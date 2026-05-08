@extends('layouts.master')
@section('title') Medical Check-up @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('title') Medical Check-up @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <?php
    $total    = $checkups->total();
    $abnormal = collect($checkups->items())->filter(fn($r) => $r->bmi_category && in_array($r->bmi_category, ['sangat_kurang','kurang','lebih','gemuk']))->count();
    $typeMap  = ['rutin'=>'Rutin','akar'=>'Akar','masuk'=>'Masuk'];
    $catColor = ['sangat_kurang'=>'warning','kurang'=>'info','normal'=>'success','lebih'=>'primary','gemuk'=>'danger'];
    ?>

    {{-- Stats --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card border-start border-1 border-primary">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-clipboard-check-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Total Check-up</p>
                        <h5 class="mb-0">{{ $total }} <span class="fs-6 text-muted">record</span></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-start border-1 border-warning">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-warning bg-opacity-10 text-warning rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-alert-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Deviasi IMT</p>
                        <h5 class="mb-0">{{ $abnormal }} <span class="fs-6 text-muted">santi</span></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-start border-1 border-success">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-heart-pulse-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">IMT Normal</p>
                        <h5 class="mb-0">{{ $total - $abnormal }} <span class="fs-6 text-muted">santi</span></h5>
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
                            <h5 class="card-title mb-0">Data Medical Check-up</h5>
                            <p class="text-muted mb-0 small">Pemeriksaan kesehatan berkala &amp; skrining</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.uks.health-checkups.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Check-up
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
                            <select name="checkup_type" class="form-control">
                                <option value="">Semua Jenis</option>
                                @foreach($typeMap as $k => $v)
                                    <option value="{{ $k }}" {{ request('checkup_type')==$k?'selected':'' }}>{{ $v }}</option>
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
                        <div class="col-md-1">
                            <a href="{{ route('user.uks.health-checkups.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Nama Santi</th>
                                    <th>Tanggal</th>
                                    <th>Jenis</th>
                                    <th class="text-center">Tinggi(cm)</th>
                                    <th class="text-center">Berat(kg)</th>
                                    <th class="text-center">BMI</th>
                                    <th class="text-center">Status IMT</th>
                                    <th class="text-center">Gigi</th>
                                    <th class="text-center">TB</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($checkups as $i => $row)
                                <tr class="{{ $row->bmi_category && in_array($row->bmi_category, ['sangat_kurang','kurang','lebih','gemuk']) ? 'table-warning' : '' }}">
                                    <td>{{ $checkups->firstItem() + $i }}</td>
                                    <td>
                                        <span class="fw-semibold">{{ $row->student?->name ?? '-' }}</span>
                                        @if($row->academicYear)
                                            <br><small class="text-muted">{{ $row->academicYear->name }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $row->checkup_date?->format('d/m/Y') }}</td>
                                    <td><span class="badge bg-light text-dark">{{ $typeMap[$row->checkup_type] ?? $row->checkup_type }}</span></td>
                                    <td class="text-center">{{ $row->height_cm ?: '-' }}</td>
                                    <td class="text-center">{{ $row->weight_kg ?: '-' }}</td>
                                    <td class="text-center fw-semibold {{ $row->bmi ? 'text-primary' : 'text-muted' }}">{{ $row->bmi ? number_format($row->bmi, 1) : '-' }}</td>
                                    <td class="text-center">
                                        @if($row->bmi_category)
                                            <span class="badge bg-{{ $catColor[$row->bmi_category] ?? 'secondary' }}">{{ $row->bmi_category_text }}</span>
                                        @else - @endif
                                    </td>
                                    <td class="text-center">
                                        @php $gigi = ['normal'=>'success','karies'=>'danger','gangguan'=>'warning']; @endphp
                                        @if($row->dental_status)
                                            <span class="badge bg-{{ $gigi[$row->dental_status] ?? 'secondary' }}">{{ ucfirst($row->dental_status) }}</span>
                                        @else - @endif
                                    </td>
                                    <td class="text-center">
                                        @if($row->tb_screening_result)
                                            <span class="badge bg-{{ $row->tb_screening_result === 'negatif' ? 'success' : 'warning' }}">{{ ucfirst($row->tb_screening_result) }}</span>
                                        @else - @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('user.uks.health-checkups.show', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                           class="btn btn-sm btn-outline-primary me-1"><i class="ri-eye-line"></i></a>
                                        <a href="{{ route('user.uks.health-checkups.edit', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                           class="btn btn-sm btn-outline-secondary me-1"><i class="ri-edit-line"></i></a>
                                        <form method="POST" action="{{ route('user.uks.health-checkups.destroy', ['userId' => $userId, 'uuid' => $row->id]) }}" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-btn"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">
                                        <i class="ri-clipboard-check-line fs-1 d-block mb-2"></i>
                                        Belum ada data medical check-up.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $checkups->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection