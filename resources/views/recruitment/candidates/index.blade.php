@extends('layouts.master')
@section('title') Candidates @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Recruitment @endslot
@slot('title') Candidates @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">Daftar Kandidat</h5>
                    <div class="flex-shrink-0">
                        <a href="{{ route('user.ats.candidates.create', ['userId' => $userId]) }}" class="btn btn-primary">
                            <i class="ri-add-line align-bottom me-1"></i> Tambah Kandidat
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body border border-dashed border-end-0 border-start-0">
                <form method="GET" action="{{ route('user.ats.candidates.index', ['userId' => $userId]) }}">
                    <div class="row g-3">
                        <div class="col-xxl-4 col-sm-6">
                            <div class="search-box">
                                <input type="text" class="form-control search" name="search" 
                                       placeholder="Cari nama, email, atau NIK..." value="{{ request('search') }}">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <div class="col-xxl-2 col-sm-4">
                            <select class="form-control" data-choices name="pendidikan">
                                <option value="">Semua Pendidikan</option>
                                @foreach($pendidikanOptions as $pendidikan)
                                    <option value="{{ $pendidikan }}" {{ request('pendidikan') == $pendidikan ? 'selected' : '' }}>
                                        {{ strtoupper($pendidikan) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xxl-2 col-sm-4">
                            <select class="form-control" data-choices name="skill">
                                <option value="">Semua Skill</option>
                                @foreach($skillOptions as $skill)
                                    <option value="{{ $skill }}" {{ request('skill') == $skill ? 'selected' : '' }}>
                                        {{ $skill }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xxl-2 col-sm-4">
                            <select class="form-control" data-choices name="pengalaman">
                                <option value="">Pengalaman</option>
                                <option value="1" {{ request('pengalaman') == '1' ? 'selected' : '' }}>Minimal 1 Tahun</option>
                                <option value="3" {{ request('pengalaman') == '3' ? 'selected' : '' }}>Minimal 3 Tahun</option>
                                <option value="5" {{ request('pengalaman') == '5' ? 'selected' : '' }}>Minimal 5 Tahun</option>
                            </select>
                        </div>
                        <div class="col-xxl-2 col-sm-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-equalizer-fill me-1"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Kontak</th>
                                <th>Pendidikan</th>
                                <th>Pengalaman</th>
                                <th>Skill</th>
                                <th>Lamaran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($candidates as $index => $candidate)
                            <tr>
                                <td>{{ $candidates->firstItem() + $index }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <img src="{{ $candidate->user->avatar ? URL::asset('images/'.$candidate->user->avatar) : URL::asset('build/images/users/avatar-1.jpg') }}" 
                                                 alt="" class="avatar-xs rounded-circle">
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-0">{{ $candidate->user->name }}</h6>
                                            <small class="text-muted">{{ $candidate->user->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="mb-0">{{ $candidate->no_hp ?? '-' }}</p>
                                    <small>{{ $candidate->no_whatsapp ? 'WA: '.$candidate->no_whatsapp : '' }}</small>
                                </td>
                                <td>
                                    @php
                                        $pendidikan = $candidate->educations->sortByDesc('tahun_lulus')->first();
                                    @endphp
                                    @if($pendidikan)
                                        <span class="badge bg-primary-subtle text-primary">{{ strtoupper($pendidikan->jenjang) }}</span>
                                        <small class="d-block text-muted">{{ $pendidikan->jurusan }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $totalPengalaman = $candidate->workExperiences->sum('lama_bekerja_bulan');
                                    @endphp
                                    @if($totalPengalaman > 0)
                                        {{ floor($totalPengalaman / 12) }} tahun {{ $totalPengalaman % 12 }} bulan
                                    @else
                                        Fresh Graduate
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($candidate->skills->take(3) as $skill)
                                            <span class="badge bg-info-subtle text-info">{{ $skill->nama_skill }}</span>
                                        @endforeach
                                        @if($candidate->skills->count() > 3)
                                            <span class="badge bg-secondary-subtle text-secondary">+{{ $candidate->skills->count() - 3 }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-success">{{ $candidate->applications_count }}</span>
                                </td>
                                <td>
                                    <div class="hstack gap-2">
                                        <a href="{{ route('user.ats.candidates.show', ['userId' => $userId, 'candidate' => $candidate->id]) }}" 
                                           class="btn btn-soft-primary btn-sm">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('user.ats.applications.index', ['userId' => $userId, 'candidate' => $candidate->id]) }}" 
                                           class="btn btn-soft-success btn-sm">
                                            <i class="ri-file-list-line"></i>
                                        </a>
                                        <a href="{{ route('user.ats.candidates.download-cv', ['userId' => $userId, 'candidate' => $candidate->id]) }}" 
                                           class="btn btn-soft-info btn-sm">
                                            <i class="ri-file-pdf-line"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" style="width:72px;height:72px"></lord-icon>
                                    <h5 class="mt-3">Belum Ada Kandidat</h5>
                                    <p class="text-muted">Belum ada data kandidat yang tersimpan.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @include('shared._pagination', ['paginator' => $candidates])
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/job-list.init.js') }}"></script>
    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection