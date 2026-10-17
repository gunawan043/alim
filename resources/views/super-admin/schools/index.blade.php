@extends('layouts.master')
@section('title') Manajemen Sekolah @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('title') Manajemen Sekolah @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Manajemen Sekolah</h5>
                            <p class="text-muted mb-0">Kelola semua sekolah di sistem.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.sa.schools.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Sekolah
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filter --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama sekolah/NPSN..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="level" class="form-control">
                                <option value="">Semua Jenjang</option>
                                <option value="sd" {{ request('level') == 'sd' ? 'selected' : '' }}>SD</option>
                                <option value="smp" {{ request('level') == 'smp' ? 'selected' : '' }}>SMP</option>
                                <option value="sma" {{ request('level') == 'sma' ? 'selected' : '' }}>SMA</option>
                                <option value="smk" {{ request('level') == 'smk' ? 'selected' : '' }}>SMK</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.sa.schools.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nama Sekolah</th>
                                    <th>Jenjang</th>
                                    <th>NPSN</th>
                                    <th>Unit Kerja</th>
                                    <th>Kepala Sekolah</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($schools as $school)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ $school->logo_url }}" alt="logo" class="rounded" width="40" height="40" style="object-fit:cover;">
                                                <div>
                                                    <div class="fw-bold">{{ $school->name }}</div>
                                                    <small class="text-muted">{{ $school->school_code ?? '-' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-primary-subtle text-primary">{{ $school->level_text }}</span></td>
                                        <td><small>{{ $school->npsn ?? '-' }}</small></td>
                                        <td><small>{{ $school->workUnit?->name ?? '-' }}</small></td>
                                        <td><small>{{ $school->principalUser?->name ?? '-' }}</small></td>
                                        <td>
                                            <span class="badge {{ $school->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                                {{ $school->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td><small class="text-muted">{{ $school->created_at?->format('Y-m-d') }}</small></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary" data-bs-toggle="dropdown">
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.sa.schools.edit', ['userId' => $userId, 'id' => $school->id]) }}">
                                                            <i class="ri-pencil-line text-primary me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item toggle-status-btn" data-id="{{ $school->id }}" data-active="{{ $school->is_active }}">
                                                            <i class="ri-{{ $school->is_active ? 'pause-circle' : 'play-circle' }} me-2"></i>
                                                            {{ $school->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger delete-school" data-id="{{ $school->id }}" data-name="{{ $school->name }}">
                                                            <i class="ri-delete-bin-line me-2"></i>Hapus
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">Belum ada data sekolah.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($schools->hasPages())
                        @include('shared._pagination', ['paginator' => $schools])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div class="modal fade zoomIn" id="deleteSchoolModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f06548,secondary:#f7b84b" style="width:80px;height:80px"></lord-icon>
                    <h4 class="mt-3">Hapus Sekolah?</h4>
                    <p class="text-muted">Sekolah <strong id="deleteSchoolName"></strong> akan dihapus secara permanen.</p>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <form id="deleteSchoolForm" method="POST" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger">Ya, Hapus</button>
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
        // Toggle status
        document.querySelectorAll('.toggle-status-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const isActive = this.dataset.active === 'true';
                fetch(`/{{ $userId }}/sa/schools/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                }).then(r => r.json()).then(data => {
                    if (data.success) location.reload();
                });
            });
        });

        // Delete school
        document.querySelectorAll('.delete-school').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('deleteSchoolName').textContent = this.dataset.name;
                document.getElementById('deleteSchoolForm').action = `/{{ $userId }}/sa/schools/${this.dataset.id}`;
                new bootstrap.Modal(document.getElementById('deleteSchoolModal')).show();
            });
        });
    });
    </script>
@endsection
