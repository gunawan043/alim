@extends('layouts.master')
@section('title') Imunisasi Santri @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('title') Imunisasi @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <?php
    $total   = $immunizations->total();
    $recent  = $immunizations->sortByDesc('date_given')->first();
    $byType  = $immunizations->groupBy('immunization_type')->count();
    $typeMap = [
        'BCG'=>'BCG','Polio_1'=>'Polio 1','Polio_2'=>'Polio 2','Polio_3'=>'Polio 3','Polio_4'=>'Polio 4',
        'DPT_HB_Hib_1'=>'DPT-HB-Hib 1','DPT_HB_Hib_2'=>'DPT-HB-Hib 2','DPT_HB_Hib_3'=>'DPT-HB-Hib 3',
        'Campak_MR'=>'Campak/MR','MR_2'=>'MR 2','Hepatitis_B'=>'Hepatitis B',
        'TT_1'=>'TT 1','TT_2'=>'TT 2','TT_3'=>'TT 3','TT_4'=>'TT 4','TT_5'=>'TT 5',
        'Covid19'=>'Covid-19','Influenza'=>'Influenza','Japanese_Encephalitis'=>'JE','lainnya'=>'Lainnya',
    ];
    $typeColor = [
        'BCG'=>'primary','Polio_1'=>'info','Polio_2'=>'info','Polio_3'=>'info','Polio_4'=>'info',
        'DPT_HB_Hib_1'=>'warning','DPT_HB_Hib_2'=>'warning','DPT_HB_Hib_3'=>'warning',
        'Campak_MR'=>'success','MR_2'=>'success','Hepatitis_B'=>'secondary',
        'TT_1'=>'primary','TT_2'=>'primary','TT_3'=>'primary','TT_4'=>'primary','TT_5'=>'primary',
        'Covid19'=>'danger','Influenza'=>'info','Japanese_Encephalitis'=>'warning','lainnya'=>'dark',
    ];
    ?>

    {{-- Stats Row --}}
    
    {{-- Stats Cards --}}
    <div class="row g-3 mb-2">
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-syringe-line text-primary"></i></span>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Total Data</p>
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
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-calendar-check-line text-success"></i></span>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Terakhir</p>
                            <h3 class="mb-0">{{ $recent ? $recent->date_given?->format('d/m/Y') : '-' }}</h3>
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
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-list-check-2 text-warning"></i></span>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Jenis Imunisasi</p>
                            <h3 class="mb-0">{{ $byType }} <span class="fs-6 text-muted">jenis</span></h3>
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
                            <h5 class="card-title mb-0">Riwayat Imunisasi Santri</h5>
                            <p class="text-muted mb-0 small">Dokumentasi imunisasi peserta didik</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.uks.immunizations.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Imunisasi
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Nama Santri..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="immunization_type" class="form-control">
                                <option value="">Semua Jenis</option>
                                @foreach($typeMap as $k => $v)
                                    <option value="{{ $k }}" {{ request('immunization_type')==$k?'selected':'' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="study_group_id" class="form-control">
                                <option value="">Semua Kelas</option>
                                @foreach($studyGroups as $sg)
                                    <option value="{{ $sg->id }}" {{ request('study_group_id')==$sg->id?'selected':'' }}>{{ $sg->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Nama Santri</th>
                                    <th>Jenis Imunisasi</th>
                                    <th>Tanggal</th>
                                    <th>Umur</th>
                                    <th>Tempat</th>
                                    <th>Efek Samping</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($immunizations as $i => $row)
                                <tr>
                                    <td>{{ $immunizations->firstItem() + $i }}</td>
                                    <td>
                                        <span class="fw-semibold">{{ $row->student?->name ?? '-' }}</span>
                                        @if($row->student?->nisn)
                                            <br><small class="text-muted">NISN: {{ $row->student->nisn }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $typeColor[$row->immunization_type] ?? 'secondary' }}">
                                            {{ $typeMap[$row->immunization_type] ?? $row->immunization_type }}
                                        </span>
                                    </td>
                                    <td>{{ $row->date_given?->format('d/m/Y') }}</td>
                                    <td>
                                        @if($row->age_at_vaccination_days)
                                            <?php $y = floor($row->age_at_vaccination_days / 365); $m = floor(($row->age_at_vaccination_days % 365) / 30); ?>
                                            {{ $y > 0 ? $y . ' th ' : '' }}{{ $m > 0 ? $m . ' bln' : '' }}
                                        @else - @endif
                                    </td>
                                    <td>{{ $row->place ?: '-' }}</td>
                                    <td>
                                        @if($row->side_effects)
                                            <span class="text-warning small"><i class="ri-error-warning-line"></i> {{ Str::limit($row->side_effects, 25) }}</span>
                                        @else <span class="text-muted">-</span> @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('user.uks.immunizations.show', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                           class="btn btn-sm btn-outline-primary me-1"><i class="ri-eye-line"></i></a>
                                        <a href="{{ route('user.uks.immunizations.edit', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                           class="btn btn-sm btn-outline-secondary me-1"><i class="ri-edit-line"></i></a>
                                        <form method="POST" action="{{ route('user.uks.immunizations.destroy', ['userId' => $userId, 'uuid' => $row->id]) }}" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-btn"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="ri-syringe-line fs-1 d-block mb-2"></i>
                                        Belum ada data imunisasi.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $immunizations->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection