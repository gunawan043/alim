@extends('layouts.master')
@section('title') Surat Keputusan @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Administrasi @endslot
        @slot('li_2') Jadwal KBM @endslot
        @slot('title') Daftar Surat Keputusan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $currentUser = auth()->user();
        $canViewAllSchools = $currentUser && ($currentUser->hasRole('Super Admin') || $currentUser->hasRole('Administrator') || $currentUser->hasRole('Wadir 1') || $currentUser->hasRole('Mudir'));
    @endphp

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Surat Keputusan</h5>
                            <p class="text-muted mb-0">Kelola SK Pembagian Tugas Mengajar.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.institution-decrees.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Buat SK
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        @if($canViewAllSchools)
                        <div class="col-md-3">
                            <select name="school_id" class="form-control">
                                <option value="">Semua Sekolah</option>
                                @foreach($schools as $s)
                                    <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-3">
                            <select name="academic_year_id" class="form-control">
                                <option value="">Semua Th. Ajaran</option>
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="search" class="form-control" placeholder="No. SK / Judul..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Arsip</option>
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
                                    <th>No. SK</th>
                                    <th>Judul</th>
                                    <th>Jenis</th>
                                    <th>Sekolah</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Tanggal SK</th>
                                    <th>Penandatangan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($decrees as $d)
                                    <tr>
                                        <td>{{ $loop->iteration + ($decrees->currentPage() - 1) * $decrees->perPage() }}</td>
                                        <td>
                                            <a href="{{ route('user.institution-decrees.show', ['userId' => $userId, 'id' => $d->id]) }}" class="fw-medium link-primary">
                                                <code>{{ $d->decree_number }}</code>
                                            </a>
                                        </td>
                                        <td>{{ $d->title }}</td>
                                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $d->decree_type }}</span></td>
                                        <td>
                                            @if($d->school)
                                                <span class="text-truncate" style="max-width:120px;">{{ $d->school->name }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $d->academicYear?->name }}@if($d->academicYear?->semester_text) ({{ $d->academicYear->semester_text }})@endif</td>
                                        <td>{{ $d->issued_date?->translatedFormat('d M Y') ?? '-' }}</td>
                                        <td>
                                            @if($d->signer)
                                                <span class="text-truncate" style="max-width:120px;">{{ $d->signer->name }}</span>
                                                <br><small class="text-muted">{{ $d->signed_position }}</small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($d->status === 'active')
                                                <span class="badge bg-success-subtle text-success">Aktif</span>
                                            @elseif($d->status === 'archived')
                                                <span class="badge bg-secondary-subtle text-secondary">Arsip</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning">Draft</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown">
                                                    <i class="ri-more-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.institution-decrees.show', ['userId' => $userId, 'id' => $d->id]) }}">
                                                            <i class="ri-eye-line me-2"></i>Lihat
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.institution-decrees.edit', ['userId' => $userId, 'id' => $d->id]) }}">
                                                            <i class="ri-pencil-line me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger delete-d" href="javascript:void(0)"
                                                            data-id="{{ $d->id }}" data-name="{{ $d->decree_number }}">
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
                                            <div class="avatar-lg mx-auto mb-3">
                                                <div class="avatar-title bg-light rounded-circle">
                                                    <i class="ri-file-text-line fs-1 text-muted"></i>
                                                </div>
                                            </div>
                                            <h5 class="text-muted">Belum ada data Surat Keputusan</h5>
                                            <a href="{{ route('user.institution-decrees.create', ['userId' => $userId]) }}" class="btn btn-success">
                                                <i class="ri-add-line me-1"></i>Buat SK Baru
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('shared._pagination', ['paginator' => $decrees])
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.querySelectorAll('.delete-d').forEach(function(btn) {
            btn.addEventListener('click', function() {
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: 'SK "' + this.dataset.name + '" beserta semua penugasan di dalamnya akan dihapus permanen.',
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
                        form.action = '/{{ $userId }}/institution-decrees/' + btn.dataset.id;
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