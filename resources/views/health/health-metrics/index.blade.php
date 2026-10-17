@extends('layouts.master')
@section('title') Antropometri Santri @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('title') Antropometri @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <?php
    $total    = $metrics->total();
    $abnormal = collect($metrics->items())->filter(fn($r) => $r->bmi_category && in_array($r->bmi_category, ['sangat_kurang','kurang','lebih','gemuk']))->count();
    $normal   = collect($metrics->items())->filter(fn($r) => $r->bmi_category === 'normal')->count();
    $catColor = ['sangat_kurang'=>'warning','kurang'=>'info','normal'=>'success','lebih'=>'primary','gemuk'=>'danger'];
    $catLabel = ['sangat_kurang'=>'Sangat Kurang','kurang'=>'Kurang','normal'=>'Normal','lebih'=>'Berlebih','gemuk'=>'Gemuk'];
    ?>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-2">
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-ruler-line text-primary"></i></span>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Total Pengukuran</p>
                            <h3 class="mb-0">{{ $total }} <span class="fs-6 text-muted">record</span></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-alert-line text-warning"></i></span>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Deviasi BMI</p>
                            <h3 class="mb-0">{{ $abnormal }} <span class="fs-6 text-muted">Santri</span></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-heart-pulse-line text-success"></i></span>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Normal</p>
                            <h3 class="mb-0">{{ $normal }} <span class="fs-6 text-muted">Santri</span></h3>
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
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Data Antropometri Santri</h5>
                            <p class="text-muted mb-0 small">Pengukuran tinggi &amp; berat badan berkala</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.uks.health-metrics.dashboard', ['userId' => $userId]) }}" class="btn btn-info me-2">
                                <i class="ri-bar-chart-line me-1"></i> Dashboard
                            </a>
                            <a href="{{ route('user.uks.health-metrics.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Data
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Nama Santri..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="study_group_id" class="form-control">
                                <option value="">Semua Kelas</option>
                                @foreach($studyGroups as $sg)
                                    <option value="{{ $sg->id }}" {{ request('study_group_id') == $sg->id ? 'selected' : '' }}>{{ $sg->full_name }}</option>
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
                            <a href="{{ route('user.uks.health-metrics.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Nama Santri</th>
                                    <th>Tanggal Ukur</th>
                                    <th>Sesi</th>
                                    <th class="text-center">Tinggi (cm)</th>
                                    <th class="text-center">Berat (kg)</th>
                                    <th class="text-center">BMI</th>
                                    <th class="text-center">Kategori BMI</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($metrics as $i => $row)
                                <tr class="{{ $row->bmi_category && in_array($row->bmi_category, ['sangat_kurang','kurang','lebih','gemuk']) ? 'table-warning' : '' }}">
                                    <td class="text-center text-muted">{{ $metrics->firstItem() + $i }}</td>
                                    <td>
                                        <span class="fw-semibold">{{ $row->student?->name ?? '-' }}</span>
                                        @if($row->academicYear)
                                            <br><small class="text-muted">{{ $row->academicYear->name }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $row->record_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td>
                                        @php $sessionLabel = ['awal_tahun'=>'Awal','tengah_tahun'=>'Tengah','akhir_tahun'=>'Akhir','lainnya'=>'Lain'][$row->measurement_session ?? ''] ?? '-'; @endphp
                                        <span class="badge bg-light text-dark">{{ $sessionLabel }}</span>
                                    </td>
                                    <td class="text-center">{{ $row->height_cm ?: '-' }}</td>
                                    <td class="text-center">{{ $row->weight_kg ?: '-' }}</td>
                                    <td class="text-center fw-bold {{ $row->bmi ? 'text-primary' : 'text-muted' }}">
                                        {{ $row->bmi ? number_format($row->bmi, 2) : '-' }}
                                    </td>
                                    <td class="text-center">
                                        @if($row->bmi_category)
                                            <span class="badge bg-{{ $catColor[$row->bmi_category] ?? 'secondary' }}">
                                                {{ $catLabel[$row->bmi_category] ?? $row->bmi_category }}
                                            </span>
                                        @else - @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('user.uks.health-metrics.show', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                           class="btn btn-sm btn-outline-primary me-1"><i class="ri-eye-line"></i></a>
                                        <a href="{{ route('user.uks.health-metrics.student', ['userId' => $userId, 'studentUuid' => $row->student_id]) }}"
                                           class="btn btn-sm btn-outline-info me-1" title="Grafik"><i class="ri-line-chart-line"></i></a>
                                        <a href="{{ route('user.uks.health-metrics.edit', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                           class="btn btn-sm btn-outline-secondary me-1"><i class="ri-edit-line"></i></a>
                                        <form method="POST" action="{{ route('user.uks.health-metrics.destroy', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                              class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-btn"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="ri-ruler-line fs-1 d-block mb-2"></i>
                                        Belum ada data antropometri.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $metrics->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection