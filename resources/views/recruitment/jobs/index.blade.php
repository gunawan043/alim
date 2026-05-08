@extends('layouts.master')
@section('title')
    Job Lists
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Jobs
        @endslot
        @slot('title')
            Job Lists
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <h6 class="card-title mb-0 flex-grow-1">Search Jobs</h6>
                        <div class="flex-shrink-0">
                            <a href="{{ route('user.ats.jobs.create', ['userId' => $userId]) }}" class="btn btn-primary">
                                <i class="ri-add-line align-bottom me-1"></i> Create New Job
                            </a>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('user.ats.jobs.index', ['userId' => $userId]) }}" id="searchForm">
                        <div class="row mt-3 gy-3">
                            <div class="col-xxl-10 col-md-6">
                                <div class="search-box">
                                    <input type="text" class="form-control search bg-light border-light" id="searchJob"
                                        name="search" autocomplete="off" placeholder="Search for jobs or companies..."
                                        value="{{ request('search') }}">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-xxl-2 col-md-6">
                                <div class="input-light">
                                    <select class="form-control" data-choices data-choices-search-false name="sort"
                                        id="idStatus" onchange="this.form.submit()">
                                        <option value="terbaru" {{ request('sort') == 'terbaru' ? 'selected' : '' }}>Terbaru
                                        </option>
                                        <option value="terlama" {{ request('sort') == 'terlama' ? 'selected' : '' }}>Terlama
                                        </option>
                                        <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>Populer
                                        </option>
                                        <option value="akan_tutup" {{ request('sort') == 'akan_tutup' ? 'selected' : '' }}>
                                            Akan Tutup</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xxl-12">
            <div id="job-list">
                @forelse($jobs as $job)
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm flex-shrink-0 me-3">
                                    <div class="avatar-title bg-light rounded">
                                        <img src="{{ $job->company_logo ?? URL::asset('build/images/companies/img-1.png') }}"
                                            alt="" class="avatar-xs">
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <h5 class="mb-1">
                                            <a href="{{ route('user.ats.jobs.show', ['userId' => $userId, 'job' => $job->id]) }}"
                                                class="text-body">{{ $job->judul }}</a>
                                        </h5>
                                        <span
                                            class="badge bg-{{ $job->status == 'aktif' ? 'success' : ($job->status == 'ditutup' ? 'danger' : 'warning') }}-subtle text-{{ $job->status == 'aktif' ? 'success' : ($job->status == 'ditutup' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($job->status) }}
                                        </span>
                                        @if ($job->jenis_pegawai)
                                            <span
                                                class="badge bg-primary-subtle text-primary">{{ $job->jenis_pegawai }}</span>
                                        @endif
                                    </div>
                                    <div class="hstack gap-3 flex-wrap">
                                        <div><i class="ri-building-line me-1 align-bottom"></i>
                                            {{ $job->workUnit->name ?? 'friday' }}</div>
                                        <div class="vr"></div>
                                        <div><i class="ri-map-pin-2-line me-1 align-bottom"></i>
                                            {{ $job->location ?? 'Mataram' }}</div>
                                        <div class="vr"></div>
                                        <div>Post Date : <span
                                                class="fw-medium">{{ $job->created_at->format('d M, Y') }}</span></div>
                                        <div class="vr"></div>
                                        <div class="text-warning">
                                            <i class="ri-bar-chart-line"></i> {{ $job->applications_count ?? 0 }} Pelamar
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-muted">{{ Str::limit($job->deskripsi_pekerjaan, 200) }}</p>
                                    </div>
                                    <div class="hstack gap-2 mt-2">
                                        @if ($job->kualifikasi_pendidikan)
                                            @foreach (json_decode($job->kualifikasi_pendidikan) as $pendidikan)
                                                <span class="badge bg-info-subtle text-info">{{ $pendidikan }}</span>
                                            @endforeach
                                        @endif
                                        @if ($job->kompetensi_dibutuhkan)
                                            @foreach (json_decode($job->kompetensi_dibutuhkan) as $skill)
                                                <span
                                                    class="badge bg-secondary-subtle text-secondary">{{ $skill }}</span>
                                            @endforeach
                                        @endif
                                    </div>
                                    <div class="mt-3 hstack gap-2">
                                        <a href="{{ route('user.ats.jobs.show', ['userId' => $userId, 'job' => $job->id]) }}"
                                            class="btn btn-soft-primary btn-sm">
                                            <i class="ri-eye-line"></i> Overview
                                        </a>
                                        <a href="{{ route('user.ats.applications.index', ['userId' => $userId, 'job' => $job->id]) }}"
                                            class="btn btn-soft-success btn-sm">
                                            <i class="ri-user-line"></i> Lihat Pelamar
                                        </a>
                                        <a href="{{ route('user.ats.jobs.edit', ['userId' => $userId, 'job' => $job->id]) }}"
                                            class="btn btn-soft-warning btn-sm">
                                            <i class="ri-edit-line"></i> Edit
                                        </a>
                                        @if ($job->status == 'aktif')
                                            <button type="button" class="btn btn-soft-danger btn-sm"
                                                onclick="toggleStatus('{{ $job->id }}')">
                                                <i class="ri-close-line"></i> Tutup
                                            </button>
                                        @elseif($job->status == 'ditutup')
                                            <button type="button" class="btn btn-soft-success btn-sm"
                                                onclick="toggleStatus('{{ $job->id }}')">
                                                <i class="ri-restart-line"></i> Buka Kembali
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop"
                                style="width:72px;height:72px"></lord-icon>
                            <h5 class="mt-3">Belum Ada Lowongan</h5>
                            <p class="text-muted">Silakan buat lowongan baru untuk mulai merekrut.</p>
                            <a href="{{ route('user.ats.jobs.create', ['userId' => $userId]) }}" class="btn btn-primary">Buat Lowongan</a>
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="row g-0 justify-content-end mb-4" id="pagination-element">
                <div class="col-sm-6">
                    @include('shared._pagination', ['paginator' => $jobs])
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <!-- apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/job-list.init.js') }}"></script>
    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.lordicon.com/lordicon.js"></script>
    <script>
        function toggleStatus(id) {
            Swal.fire({
                title: 'Konfirmasi',
                text: "Apakah Anda yakin ingin mengubah status lowongan ini?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Ubah!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('/{{ $userId }}/ats/jobs/' + id + '/toggle-status', {
                        _token: '{{ csrf_token() }}'
                    }).then(response => {
                        Swal.fire('Berhasil!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    });
                }
            });
        }

        // Auto-submit search after typing
        let timeout = null;
        document.getElementById('searchJob').addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                document.getElementById('searchForm').submit();
            }, 500);
        });
    </script>
@endsection
