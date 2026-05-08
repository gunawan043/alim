@extends('layouts.master')
@section('title') Failed Jobs @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('title') Failed Jobs @endslot
    @endcomponent

    @if(isset($error))
        <div class="alert alert-warning">{{ $error }}</div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Failed Jobs</h5>
                            <p class="text-muted mb-0">Job yang gagal dieksekusi.</p>
                        </div>
                        @if($hasTable && $jobs->total() > 0)
                            <div class="col-sm-auto">
                                <a href="{{ route('user.sa.failed-jobs.retry-all', ['userId' => $userId]) }}" class="btn btn-success btn-sm me-1" id="retryAllBtn">
                                    <i class="ri-restart-line align-bottom me-1"></i> Retry Semua
                                </a>
                                <button class="btn btn-danger btn-sm" id="flushAllBtn">
                                    <i class="ri-delete-bin-line align-bottom me-1"></i> Flush Semua
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    @if($hasTable)
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Cari job..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Job</th>
                                        <th>Connection</th>
                                        <th>Queue</th>
                                        <th>Failed At</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($jobs as $job)
                                        <tr>
                                            <td><code>{{ $job->id }}</code></td>
                                            <td><small>{{ $job->displayName }}</small></td>
                                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $job->connection }}</span></td>
                                            <td><span class="badge bg-light text-dark">{{ $job->queue }}</span></td>
                                            <td><small>{{ \Carbon\Carbon::parse($job->failed_at)->format('d/m/Y H:i:s') }}</small></td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-soft-secondary" data-bs-toggle="dropdown">
                                                        <i class="ri-more-2-fill"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('user.sa.failed-jobs.retry', ['userId' => $userId, 'id' => $job->id]) }}">
                                                                <i class="ri-restart-line text-success me-2"></i>Retry
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <button class="dropdown-item text-danger delete-failed-job"
                                                                data-id="{{ $job->id }}">
                                                                <i class="ri-delete-bin-line text-danger me-2"></i>Hapus
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-success">Tidak ada failed job.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($jobs->hasPages())
    @include('shared._pagination', ['paginator' => $jobs])
@endif
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="ri-database-line fs-1 text-secondary"></i>
                            <p class="mt-2">Tabel failed_jobs tidak ditemukan.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Flush All Modal --}}
    <div class="modal fade zoomIn" id="flushModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f06548,secondary:#f7b84b" style="width:80px;height:80px"></lord-icon>
                    <h4 class="mt-3">Flush Semua Failed Jobs?</h4>
                    <p class="text-muted">Semua failed job akan dihapus permanen. Tindakan ini tidak bisa diundo.</p>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <form id="flushForm" method="POST" action="{{ route('user.sa.failed-jobs.flush', ['userId' => $userId]) }}">
                        @csrf
                        <button type="submit" class="btn btn-danger">Ya, Flush!</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('flushAllBtn')?.addEventListener('click', function () {
            new bootstrap.Modal(document.getElementById('flushModal')).show();
        });

        document.querySelectorAll('.delete-failed-job').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                fetch(`/{{ $userId }}/sa/failed-jobs/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'json' }
                }).then(r => r.json()).then(data => {
                    if (data.success || data.message) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Failed job dihapus.', timer: 1500, showConfirmButton: false })
                            .then(() => location.reload());
                    }
                });
            });
        });

        document.getElementById('retryAllBtn')?.addEventListener('click', function (e) {
            if (!confirm('Retry semua failed job?')) e.preventDefault();
        });
    });
    </script>
@endsection
