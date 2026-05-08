@extends('layouts.master')
@section('title') Detail Tahun Ajaran @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Tahun Ajaran @endslot
        @slot('title') {{ $academicYear->name }} — {{ $academicYear->semester_text }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        {{-- Main Info --}}
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i class="ri-calendar-event-line me-2"></i>
                            {{ $academicYear->name }} — {{ $academicYear->semester_text }}
                        </h5>
                        <div class="d-flex gap-2">
                            @if(!$academicYear->is_active)
                                <a href="{{ route('user.academic-years.toggle-active', $academicYear->id) }}"
                                    class="btn btn-sm btn-success"
                                    onclick="return confirm('Yakin ingin mengaktifkan tahun ajaran ini?')">
                                    <i class="ri-check-line me-1"></i> Aktifkan
                                </a>
                            @endif
                            <a href="{{ route('user.academic-years.edit', $academicYear->id) }}" class="btn btn-sm btn-warning">
                                <i class="ri-pencil-line me-1"></i> Edit
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        {{-- Status --}}
                        <div class="col-md-6">
                            <div class="p-3 rounded border">
                                <small class="text-muted text-uppercase ls-1">Status</small>
                                <div class="mt-1">
                                    @if($academicYear->is_active)
                                        <span class="badge bg-success-subtle text-success fs-6">
                                            <i class="ri-check-line me-1"></i> Aktif
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary fs-6">
                                            Nonaktif
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Semester --}}
                        <div class="col-md-6">
                            <div class="p-3 rounded border">
                                <small class="text-muted text-uppercase ls-1">Semester</small>
                                <h6 class="mt-1 mb-0">{{ $academicYear->semester_text }}</h6>
                            </div>
                        </div>

                        {{-- Periode --}}
                        <div class="col-md-6">
                            <div class="p-3 rounded border">
                                <small class="text-muted text-uppercase ls-1">Periode Tahun Ajaran</small>
                                <h6 class="mt-1 mb-0">
                                    @if($academicYear->start_date)
                                        {{ $academicYear->start_date->format('d M Y') }} — {{ $academicYear->end_date?->format('d M Y') ?? '-' }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </h6>
                            </div>
                        </div>

                        {{-- Masa Pendaftaran --}}
                        <div class="col-md-6">
                            <div class="p-3 rounded border">
                                <small class="text-muted text-uppercase ls-1">Masa Pendaftaran</small>
                                <h6 class="mt-1 mb-0">
                                    @if($academicYear->registration_start)
                                        {{ $academicYear->registration_start->format('d M Y') }} — {{ $academicYear->registration_end?->format('d M Y') ?? '-' }}
                                    @else
                                        <span class="text-muted">Belum diatur</span>
                                    @endif
                                </h6>
                            </div>
                        </div>

                        {{-- Durasi --}}
                        <div class="col-md-6">
                            <div class="p-3 rounded border">
                                <small class="text-muted text-uppercase ls-1">Durasi</small>
                                <h6 class="mt-1 mb-0">
                                    @if($academicYear->start_date && $academicYear->end_date)
                                        {{ $academicYear->start_date->diffInDays($academicYear->end_date) + 1 }} hari
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </h6>
                            </div>
                        </div>

                        {{-- Metadata --}}
                        <div class="col-12">
                            <hr>
                            <div class="row text-muted small">
                                <div class="col-md-4">
                                    <i class="ri-calendar-line me-1"></i> Dibuat:
                                    {{ $academicYear->created_at->format('d M Y H:i') }}
                                </div>
                                <div class="col-md-4">
                                    <i class="ri-update-line me-1"></i> Diperbarui:
                                    {{ $academicYear->updated_at->format('d M Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Danger Zone --}}
            <div class="card border-danger">
                <div class="card-header bg-danger-subtle">
                    <h5 class="card-title mb-0 text-danger"><i class="ri-alert-line me-2"></i> Zona Berbahaya</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">Menghapus tahun ajaran akan menghapus semua data yang terkait (rombongan belajar, mata pelajaran, dll).</p>
                    <button class="btn btn-sm btn-danger" onclick="deleteAY()">
                        <i class="ri-delete-bin-line me-1"></i> Hapus Tahun Ajaran
                    </button>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-links-line me-2"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('user.academic-years.index') }}" class="btn btn-light w-100 text-start">
                            <i class="ri-list-check me-2"></i> Daftar Tahun Ajaran
                        </a>
                        <a href="{{ route('user.academic-years.create') }}" class="btn btn-light w-100 text-start">
                            <i class="ri-add-line me-2"></i> Tambah Tahun Ajaran Lain
                        </a>
                    </div>
                </div>
            </div>

            {{-- Info Card --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-information-line me-2"></i> Informasi</h5>
                </div>
                <div class="card-body">
                    <div class="vstack gap-2">
                        <div>
                            <small class="text-muted text-uppercase ls-1">ID</small>
                            <div class="font-monospace small">{{ $academicYear->id }}</div>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase ls-1">Nama TA</small>
                            <div>{{ $academicYear->name }}</div>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase ls-1">Semester</small>
                            <div>{{ $academicYear->semester_text }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        function deleteAY() {
            Swal.fire({
                title: 'Yakin ingin menghapus?',
                text: "Tahun ajaran \"{{ $academicYear->name }} — {{ $academicYear->semester_text }}\" akan dihapus permanen. Data terkait akan ikut terhapus.",
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
                    form.action = '{{ route("academic-years.destroy", $academicYear->id) }}';
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
        }
    </script>
@endsection
