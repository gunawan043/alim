@extends('layouts.master')
@section('title') Kandidat @endsection
@section('content')
@component('components.breadcrumb')
    @slot('li_1') Rekrutmen @endslot
    @slot('title') Kandidat @endslot
@endcomponent

{{-- Stat Cards --}}
<div class="row mb-3">
    <div class="col-md-3 col-sm-6">
        <div class="card border-start border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle text-primary rounded-2 fs-5">
                            <i class="ri-user-line"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <p class="text-muted mb-0" style="font-size:0.75rem">Total Kandidat</p>
                        <h5 class="mb-0">{{ $stats['total'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-start border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0">
                        <span class="avatar-title bg-success-subtle text-success rounded-2 fs-5">
                            <i class="ri-checkbox-circle-line"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <p class="text-muted mb-0" style="font-size:0.75rem">Terdaftar</p>
                        <h5 class="mb-0">{{ $stats['terverifikasi'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-start border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle text-warning rounded-2 fs-5">
                            <i class="ri-time-line"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <p class="text-muted mb-0" style="font-size:0.75rem">Pending</p>
                        <h5 class="mb-0">{{ $stats['pending'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-start border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle text-danger rounded-2 fs-5">
                            <i class="ri-close-circle-line"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <p class="text-muted mb-0" style="font-size:0.75rem">Ditolak</p>
                        <h5 class="mb-0">{{ $stats['ditolak'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Table Card --}}
<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center">
            <h6 class="card-title mb-0 flex-grow-1">Daftar Kandidat</h6>
            <a href="{{ route('user.ats.candidates.create', ['userId' => $userId]) }}" class="btn btn-primary btn-sm">
                <i class="ri-add-line me-1"></i> Tambah
            </a>
        </div>
    </div>

    <div class="card-body border-bottom py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <div class="search-box">
                    <input type="text" class="form-control" name="search"
                        placeholder="Nama, email, atau NIK..." value="{{ request('search') }}">
                    <i class="ri-search-line search-icon"></i>
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-control form-select" data-choices name="pendidikan">
                    <option value="">Semua Pendidikan</option>
                    @foreach($pendidikanOptions as $p)
                        <option value="{{ $p }}" {{ request('pendidikan') == $p ? 'selected' : '' }}>{{ strtoupper($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-control form-select" data-choices name="skill">
                    <option value="">Semua Skill</option>
                    @foreach($skillOptions as $s)
                        <option value="{{ $s }}" {{ request('skill') == $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-line me-1"></i>Filter</button>
                <a href="{{ route('user.ats.candidates.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm"><i class="ri-refresh-line"></i></a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-nowrap table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kandidat</th>
                        <th>Kontak</th>
                        <th>Pendidikan</th>
                        <th>Pengalaman</th>
                        <th>Skill</th>
                        <th>Lamaran</th>
                        <th class="text-center" style="width:90px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($candidates as $i => $c)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ $c->user->avatar ? asset('images/'.$c->user->avatar) : asset('build/images/users/avatar-1.jpg') }}"
                                    class="avatar-xs rounded-circle">
                                <div>
                                    <span class="fw-semibold text-body">{{ $c->user->name }}</span>
                                    <div class="small text-muted">{{ $c->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small"><i class="ri-phone-line text-muted me-1"></i>{{ $c->no_hp ?? '-' }}</div>
                            @if($c->no_whatsapp)
                                <div class="small"><i class="ri-whatsapp-line text-success me-1"></i>{{ $c->no_whatsapp }}</div>
                            @endif
                        </td>
                        <td>
                            @php $edu = $c->educations->sortByDesc('tahun_lulus')->first(); @endphp
                            @if($edu)
                                <span class="badge bg-primary-subtle text-primary">{{ strtoupper($edu->jenjang) }}</span>
                                <div class="small text-muted">{{ $edu->jurusan }}</div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @php $total = $c->workExperiences->sum('lama_bekerja_bulan'); @endphp
                            @if($total > 0)
                                {{ floor($total/12) }} th {{ $total%12 }} bln
                            @else
                                <span class="text-muted small">Fresh Graduate</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($c->skills->take(2) as $sk)
                                    <span class="badge bg-info-subtle text-info">{{ $sk->nama_skill }}</span>
                                @endforeach
                                @if($c->skills->count() > 2)
                                    <span class="badge bg-secondary-subtle">+{{ $c->skills->count()-2 }}</span>
                                @endif
                            </div>
                        </td>
                        <td><span class="badge bg-primary-subtle text-primary">{{ $c->applications_count }}</span></td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm" data-bs-toggle="dropdown"><i class="ri-more-2-fill"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('user.ats.candidates.show', ['userId' => $userId, 'candidate' => $c->id]) }}">
                                            <i class="ri-eye-line me-2 text-muted"></i>Lihat Detail
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('user.ats.applications.index', ['userId' => $userId, 'candidate' => $c->id]) }}">
                                            <i class="ri-file-list-line me-2 text-muted"></i>Lihat Lamaran
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('user.ats.candidates.download-cv', ['userId' => $userId, 'candidate' => $c->id]) }}">
                                            <i class="ri-file-pdf-line me-2 text-muted"></i>Download CV
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="py-4">
                                <i class="ri-user-line display-5 text-muted"></i>
                                <h6 class="mt-2 mb-1">Belum Ada Kandidat</h6>
                                <p class="text-muted mb-3">Data kandidat belum tersedia.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($candidates->hasPages())
        <div class="border-top px-3 py-2">
            @include('shared._pagination', ['paginator' => $candidates])
        </div>
        @endif
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection