@extends('layouts.master')
@section('title')
    Job Applications
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Recruitment
        @endslot
        @slot('title')
            Job Applications
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="applicationList">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Daftar Pelamar</h5>
                        <div class="flex-shrink-0">
                            <div class="d-flex gap-2">
                                <button class="btn btn-success" onclick="exportExcel()">
                                    <i class="ri-file-excel-line"></i> Export
                                </button>
                                <button class="btn btn-primary" onclick="filterData()">
                                    <i class="ri-filter-line"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form>
                        <div class="row g-3">
                            <div class="col-xxl-4 col-sm-6">
                                <div class="search-box">
                                    <input type="text" class="form-control search" id="searchInput"
                                        placeholder="Cari nama, posisi, atau perusahaan...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-xxl-2 col-sm-6">
                                <select class="form-control" data-choices id="filterJob">
                                    <option value="">Semua Posisi</option>
                                    @foreach ($jobs as $job)
                                        <option value="{{ $job->id }}"
                                            {{ request('job_id') == $job->id ? 'selected' : '' }}>
                                            {{ $job->judul }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <select class="form-control" data-choices id="filterStatus">
                                    <option value="">Semua Status</option>
                                    <option value="menunggu_seleksi">Menunggu Seleksi</option>
                                    <option value="seleksi_administrasi">Seleksi Administrasi</option>
                                    <option value="lolos_administrasi">Lolos Administrasi</option>
                                    <option value="tidak_lolos_administrasi">Tidak Lolos</option>
                                    <option value="tes_tertulis">Tes Tertulis</option>
                                    <option value="wawancara">Wawancara</option>
                                    <option value="diterima">Diterima</option>
                                    <option value="ditolak">Ditolak</option>
                                </select>
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <input type="text" class="form-control" id="dateRange" data-provider="flatpickr"
                                    data-range-date="true" placeholder="Rentang Tanggal">
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <button type="button" class="btn btn-primary w-100" onclick="applyFilters()">
                                    <i class="ri-equalizer-fill me-1"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    <ul class="nav nav-tabs nav-tabs-custom nav-success mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ !request('status') ? 'active' : '' }}" data-status="all"
                                href="javascript:void(0);" onclick="filterByStatus('all')">
                                Semua <span class="badge bg-secondary ms-1">{{ $stats['all'] ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'menunggu_seleksi' ? 'active' : '' }}"
                                data-status="menunggu_seleksi" href="javascript:void(0);"
                                onclick="filterByStatus('menunggu_seleksi')">
                                Menunggu <span class="badge bg-warning ms-1">{{ $stats['menunggu'] ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'seleksi_administrasi' ? 'active' : '' }}"
                                data-status="seleksi_administrasi" href="javascript:void(0);"
                                onclick="filterByStatus('seleksi_administrasi')">
                                Seleksi Adm <span class="badge bg-info ms-1">{{ $stats['seleksi_adm'] ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'tes_tertulis' ? 'active' : '' }}"
                                data-status="tes_tertulis" href="javascript:void(0);"
                                onclick="filterByStatus('tes_tertulis')">
                                Tes <span class="badge bg-primary ms-1">{{ $stats['tes'] ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'wawancara' ? 'active' : '' }}"
                                data-status="wawancara" href="javascript:void(0);" onclick="filterByStatus('wawancara')">
                                Wawancara <span class="badge bg-purple ms-1">{{ $stats['wawancara'] ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'diterima' ? 'active' : '' }}"
                                data-status="diterima" href="javascript:void(0);" onclick="filterByStatus('diterima')">
                                Diterima <span class="badge bg-success ms-1">{{ $stats['diterima'] ?? 0 }}</span>
                            </a>
                        </li>
                    </ul>

                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle" id="applicationTable">
                            <thead class="text-muted table-light">
                                <tr>
                                    <th style="width: 50px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="checkAll">
                                        </div>
                                    </th>
                                    <th>No. Lamaran</th>
                                    <th>Nama Pelamar</th>
                                    <th>Posisi</th>
                                    <th>Tanggal Melamar</th>
                                    <th>Pendidikan</th>
                                    <th>Pengalaman</th>
                                    <th>Status</th>
                                    <th>Nilai</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($applications as $app)
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    value="{{ $app->id }}">
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ route('user.ats.applications.show', ['userId' => $userId, 'application' => $app->id]) }}"
                                                class="fw-medium link-primary">
                                                #{{ $app->no_lamaran }}
                                            </a>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <img src="{{ $app->recruitmentProfile->user->avatar ? URL::asset('images/' . $app->recruitmentProfile->user->avatar) : URL::asset('build/images/users/avatar-1.jpg') }}"
                                                        alt="" class="avatar-xs rounded-circle">
                                                </div>
                                                <div class="flex-grow-1 ms-2">
                                                    {{ $app->recruitmentProfile->user->name }}
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $app->recruitmentJob->judul }}</td>
                                        <td>{{ $app->tanggal_melamar->format('d M Y') }}</td>
                                        <td>
                                            @php
                                                $pendidikan =
                                                    $app->recruitmentProfile->educations
                                                        ->where('jenjang', 's1')
                                                        ->first() ?? $app->recruitmentProfile->educations->last();
                                            @endphp
                                            {{ $pendidikan->jenjang ?? '-' }} - {{ $pendidikan->jurusan ?? '-' }}
                                        </td>
                                        <td>{{ $app->recruitmentProfile->workExperiences->sum('lama_bekerja_bulan') / 12 }}
                                            th</td>
                                        <td>
                                            @php
                                                $statusClass =
                                                    [
                                                        'menunggu_seleksi' => 'secondary',
                                                        'seleksi_administrasi' => 'info',
                                                        'lolos_administrasi' => 'primary',
                                                        'tidak_lolos_administrasi' => 'danger',
                                                        'tes_tertulis' => 'warning',
                                                        'lolos_tes' => 'success',
                                                        'tidak_lolos_tes' => 'danger',
                                                        'wawancara' => 'purple',
                                                        'lolos_wawancara' => 'success',
                                                        'diterima' => 'success',
                                                        'ditolak' => 'danger',
                                                    ][$app->status] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }}">
                                                {{ str_replace('_', ' ', $app->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($app->nilai_akhir)
                                                <span class="badge bg-success">{{ $app->nilai_akhir }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown">
                                                    <i class="ri-more-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ route('user.ats.applications.show', ['userId' => $userId, 'application' => $app->id]) }}">
                                                            <i class="ri-eye-line me-2"></i>Lihat Detail
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ route('user.ats.applications.stages', ['userId' => $userId, 'application' => $app->id]) }}">
                                                            <i class="ri-timeline-line me-2"></i>Proses Seleksi
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="">
                                                            <i class="ri-calendar-line me-2"></i>Atur Interview
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="javascript:void(0);"
                                                            onclick="deleteApplication('{{ $app->id }}')">
                                                            <i class="ri-delete-bin-line me-2"></i>Hapus
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop"
                                                style="width:72px;height:72px"></lord-icon>
                                            <h5 class="mt-3">Belum Ada Data</h5>
                                            <p class="text-muted">Belum ada pelamar yang mendaftar.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @include('shared._pagination', ['paginator' => $applications])
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
    <script>
        function filterByStatus(status) {
            let url = new URL(window.location.href);
            if (status == 'all') {
                url.searchParams.delete('status');
            } else {
                url.searchParams.set('status', status);
            }
            window.location.href = url.toString();
        }

        function applyFilters() {
            let url = new URL(window.location.href);
            let job = document.getElementById('filterJob').value;
            let status = document.getElementById('filterStatus').value;
            let dateRange = document.getElementById('dateRange').value;

            if (job) url.searchParams.set('job_id', job);
            else url.searchParams.delete('job_id');

            if (status) url.searchParams.set('status', status);
            else url.searchParams.delete('status');

            if (dateRange) url.searchParams.set('date_range', dateRange);
            else url.searchParams.delete('date_range');

            window.location.href = url.toString();
        }

        function deleteApplication(id) {
            Swal.fire({
                title: 'Yakin ingin menghapus?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit form delete via AJAX
                    $.ajax({
                        url: '/{{ $userId }}/ats/applications/' + id,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function() {
                            Swal.fire(
                                'Terhapus!',
                                'Data berhasil dihapus.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        }
                    });
                }
            });
        }

        // Checkbox all functionality
        document.getElementById('checkAll').addEventListener('change', function() {
            let checkboxes = document.querySelectorAll('tbody .form-check-input');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let searchValue = this.value.toLowerCase();
            let rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });
    </script>
@endsection
