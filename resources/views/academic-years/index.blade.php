@extends('layouts.master')
@section('title') Tahun Ajaran @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('title') Tahun Ajaran @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Tahun Ajaran</h5>
                            <p class="text-muted mb-0">Pengaturan tahun ajaran dan semester secara terpusat. Semua sekolah mengikuti data ini.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.academic-years.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Tahun Ajaran
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control"
                                placeholder="Cari nama tahun ajaran..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="semester" class="form-control">
                                <option value="">Semua Semester</option>
                                <option value="ganjil" {{ request('semester') === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                <option value="genap" {{ request('semester') === 'genap' ? 'selected' : '' }}>Genap</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="is_active" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Semester</th>
                                    <th>Periode</th>
                                    <th>Masa Pendaftaran</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($academicYears as $ay)
                                    <tr class="{{ $ay->is_active ? 'table-success' : '' }}">
                                        <td>{{ $loop->iteration + ($academicYears->currentPage() - 1) * $academicYears->perPage() }}</td>
                                        <td>
                                            <a href="{{ route('user.academic-years.show', ['userId' => $userId, 'id' => $ay->id]) }}" class="fw-medium link-primary">
                                                {{ $ay->name }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $ay->semester === 'ganjil' ? 'primary' : 'info' }}-subtle text-{{ $ay->semester === 'ganjil' ? 'primary' : 'info' }}">
                                                {{ $ay->semester_text }}
                                            </span>
                                        </td>
                                        <td>
                                            <small>
                                                @if($ay->start_date)
                                                    {{ $ay->start_date->format('d M Y') }} –
                                                    {{ $ay->end_date?->format('d M Y') ?? '-' }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            <small>
                                                @if($ay->registration_start)
                                                    {{ $ay->registration_start->format('d M Y') }} –
                                                    {{ $ay->registration_end?->format('d M Y') ?? '-' }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            @if($ay->is_active)
                                                <span class="badge bg-success-subtle text-success">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown">
                                                    <i class="ri-more-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.academic-years.show', ['userId' => $userId, 'id' => $ay->id]) }}">
                                                            <i class="ri-eye-line me-2"></i>Lihat Detail
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.academic-years.edit', ['userId' => $userId, 'id' => $ay->id]) }}">
                                                            <i class="ri-pencil-line me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.academic-years.toggle-active', ['userId' => $userId, 'id' => $ay->id]) }}"
                                                            onclick="return confirm('Yakin ingin {{ $ay->is_active ? 'menonaktifkan' : 'mengaktifkan' }} tahun ajaran ini?')">
                                                            <i class="ri-{{ $ay->is_active ? 'close' : 'check' }}-line me-2"></i>
                                                            {{ $ay->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger delete-ay" href="javascript:void(0)"
                                                            data-id="{{ $ay->id }}" data-name="{{ $ay->name }}">
                                                            <i class="ri-delete-bin-line me-2"></i>Hapus
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="avatar-lg mx-auto mb-3">
                                                <div class="avatar-title bg-light rounded-circle">
                                                    <i class="ri-calendar-event-line fs-1 text-muted"></i>
                                                </div>
                                            </div>
                                            <h5 class="text-muted">Belum ada data tahun ajaran</h5>
                                            <p class="text-muted">Tambah tahun ajaran baru untuk mulai.</p>
                                            <a href="{{ route('user.academic-years.create', ['userId' => $userId]) }}" class="btn btn-success">
                                                <i class="ri-add-line me-1"></i>Tambah Tahun Ajaran
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @include('shared._pagination', ['paginator' => $academicYears])
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.querySelectorAll('.delete-ay').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.dataset.id;
                var name = this.dataset.name;
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: "Tahun ajaran \"" + name + "\" akan dihapus permanen.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/{{ $userId }}/academic-years/' + id;
                        var token = document.createElement('input');
                        token.type = 'hidden';
                        token.name = '_token';
                        token.value = '{{ csrf_token() }}';
                        var method = document.createElement('input');
                        method.type = 'hidden';
                        method.name = '_method';
                        method.value = 'DELETE';
                        form.appendChild(token);
                        form.appendChild(method);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection
