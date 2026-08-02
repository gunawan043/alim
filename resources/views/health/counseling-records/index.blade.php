@extends('layouts.master')
@section('title') Konseling Santri @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('title') Konseling @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <?php
    $total        = $records->total();
    $referrals    = collect($records->items())->filter(fn($r) => $r->referral_needed)->count();
    $recent       = $records->sortByDesc('session_date')->first();
    $typeMap      = ['individu'=>'Individu','kelompok'=>'Kelompok','krisis'=>'Krisis'];
    $typeColor    = ['individu'=>'primary','kelompok'=>'info','krisis'=>'danger'];
    ?>

    {{-- Stats --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card border-start border-1 border-primary">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-chat-smile-3-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Total Sesi</p>
                        <h5 class="mb-0">{{ $total }} <span class="fs-6 text-muted">record</span></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-start border-1 border-danger">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <span class="bg-danger bg-opacity-10 text-danger rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="ri-arrow-right-circle-line fs-6"></i>
                    </span>
                    <div>
                        <p class="text-muted mb-0 small">Perlu Rujuk</p>
                        <h5 class="mb-0">{{ $referrals }} <span class="fs-6 text-muted">kali</span></h5>
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
                        <p class="text-muted mb-0 small">Sesi Terakhir</p>
                        <h5 class="mb-0">{{ $recent ? $recent->session_date?->format('d/m/Y') : '-' }}</h5>
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
                            <h5 class="card-title mb-0">Konseling Santri</h5>
                            <p class="text-muted mb-0 small">Dokumentasi sesi konseling &amp; kesehatan jiwa</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.uks.counseling-records.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Catat Sesi
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Nama / Topik..." value="{{ request('search') }}">
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
                            <a href="{{ route('user.uks.counseling-records.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Tanggal</th>
                                    <th>Nama Santri</th>
                                    <th>Tipe</th>
                                    <th>Topik</th>
                                    <th>Konselor</th>
                                    <th class="text-center">Perlu Rujuk</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $i => $row)
                                <tr class="{{ $row->referral_needed ? 'table-warning' : '' }}">
                                    <td class="text-center text-muted">{{ $records->firstItem() + $i }}</td>
                                    <td>
                                        <span class="fw-medium">{{ $row->session_date?->format('d/m/Y') ?? '-' }}</span>
                                        @if($row->academicYear)
                                            <br><small class="text-muted">{{ $row->academicYear->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ $row->student?->name ?? '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $typeColor[$row->session_type] ?? 'secondary' }}">
                                            {{ $typeMap[$row->session_type] ?? $row->session_type }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-dark">{{ Str::limit($row->topic, 35) }}</span>
                                        @if($row->follow_up_plan)
                                            <br><small class="text-info"><i class="ri-check-line me-1"></i>Ada rencana tindak lanjut</small>
                                        @endif
                                    </td>
                                    <td class="text-muted">{{ $row->counselor?->name ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($row->referral_needed)
                                            <span class="badge bg-danger">
                                                <i class="ri-arrow-right-s-line me-1"></i>Ya
                                            </span>
                                            @if($row->referred_to)
                                                <br><small class="text-muted">{{ Str::limit($row->referred_to, 20) }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-success">
                                                <i class="ri-check-line me-1"></i>Tidak
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('user.uks.counseling-records.show', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                           class="btn btn-sm btn-outline-primary me-1"><i class="ri-eye-line"></i></a>
                                        <a href="{{ route('user.uks.counseling-records.edit', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                           class="btn btn-sm btn-outline-secondary me-1"><i class="ri-edit-line"></i></a>
                                        <form method="POST" action="{{ route('user.uks.counseling-records.destroy', ['userId' => $userId, 'uuid' => $row->id]) }}"
                                              class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-btn"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="ri-chat-smile-3-line fs-1 d-block mb-2"></i>
                                        Belum ada catatan konseling.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $records->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection