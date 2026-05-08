@extends('layouts.master')
@section('title') Mata Pelajaran @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .kelompok-header { background: #f1f5f9 !important; }
        .badge-kelompok {
            font-size: 13px;
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: 600;
        }
    </style>
@endsection

@section('content')
@php
    $currentUser = auth()->user();
    $canViewAllSchools = $currentUser && (
        $currentUser->hasRole('Super Admin') ||
        $currentUser->hasRole('Administrator') ||
        $currentUser->hasRole('Wadir 1') ||
        $currentUser->hasRole('Mudir')
    );
@endphp

@component('components.breadcrumb')
    @slot('li_1') Akademik @endslot
    @slot('title') Mata Pelajaran @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-check-line me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <div class="row g-3 align-items-center">
                    <div class="col-sm">
                        <h5 class="card-title mb-0">
                            <i class="ri-book-open-line text-primary me-1"></i>
                            Mata Pelajaran
                        </h5>
                        <p class="text-muted mb-0" style="font-size:13px;">
                            Kelompok: Agama &bull; Bahasa Arab &bull; Hadits &bull; Umum
                        </p>
                    </div>
                    <div class="col-sm-auto">
                        <a href="{{ route('user.subjects.create', ['userId' => $userId]) }}" class="btn btn-primary btn-sm">
                            <i class="ri-add-line align-bottom me-1"></i> Tambah Mapel
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                {{-- Stats --}}
                <div class="d-flex gap-2 flex-wrap mb-3">
                    @foreach($kelompokLabels as $key => $meta)
                        <span class="badge badge-kelompok bg-{{ $meta['color'] }}-subtle text-{{ $meta['color'] }}">
                            <i class="{{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}: {{ ($grouped[$key] ?? collect([]))->count() }}
                        </span>
                    @endforeach
                    <span class="badge badge-kelompok bg-dark-subtle text-dark">
                        <i class="ri-file-list-line me-1"></i>Total: {{ $subjects->count() }}
                    </span>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted">
                            <tr>
                                <th style="width:40px;">#</th>
                                @if($canViewAllSchools)<th>Sekolah</th>@endif
                                <th>Kode</th>
                                <th>Nama Mata Pelajaran</th>
                                <th>Kategori</th>
                                <th>JP</th>
                                <th>Status</th>
                                <th style="width:90px;text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $rowNum = 0; @endphp
                            @foreach(['agama','arab','hadits','umum'] as $key)
                                @php $items = $grouped[$key] ?? collect([]); @endphp
                                @if($items->isEmpty())
                                    @continue
                                @endif
                                @php $meta = $kelompokLabels[$key]; @endphp
                                {{-- Kelompok header --}}
                                <tr class="kelompok-header">
                                    <td colspan="{{ $canViewAllSchools ? '8' : '7' }}">
                                        <i class="{{ $meta['icon'] }} me-1"></i>
                                        {{ $meta['label'] }}
                                        <span class="text-muted ms-2" style="font-weight:400;text-transform:none;font-size:11px;">
                                            ({{ $items->count() }} mapel)
                                        </span>
                                    </td>
                                </tr>
                                @foreach($items as $sub)
                                    @php $rowNum++ @endphp
                                    <tr class="{{ !$sub->is_active ? 'table-secondary opacity-75' : '' }}">
                                        <td class="text-muted text-center" style="font-size:12px;">{{ $rowNum }}</td>
                                        @if($canViewAllSchools)
                                        <td><span style="font-size:12px;" class="text-muted">{{ $sub->school?->name ?? '—' }}</span></td>
                                        @endif
                                        <td><span class="badge bg-dark-subtle text-dark" style="font-size:11px;">{{ $sub->code ?? '—' }}</span></td>
                                        <td>
                                            <a href="{{ route('user.subjects.show', ['userId' => $userId, 'id' => $sub->id]) }}"
                                               class="fw-medium" style="font-size:14px;">{{ $sub->name }}</a>
                                            @if($sub->description)
                                                <br><small class="text-muted">{{ $sub->description }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($sub->category === 'nasional')
                                                <span class="badge bg-info-subtle text-info" style="font-size:11px;">Nasional</span>
                                            @elseif($sub->category === 'muatan_lokal')
                                                <span class="badge bg-success-subtle text-success" style="font-size:11px;">Muatan Lokal</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning" style="font-size:11px;">{{ $sub->category }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $sub->credit_hours ?? '—' }} JP</td>
                                        <td>
                                            <span class="badge {{ $sub->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}"
                                                  style="font-size:11px;">{{ $sub->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.subjects.edit', ['userId' => $userId, 'id' => $sub->id]) }}"
                                               class="btn btn-soft-primary btn-sm" title="Edit"><i class="ri-pencil-line"></i></a>
                                            <button class="btn btn-soft-danger btn-sm delete-sub"
                                                    data-id="{{ $sub->id }}" data-name="{{ $sub->name }}" title="Hapus">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach

                            @if($subjects->isEmpty())
                                <tr>
                                    <td colspan="{{ $canViewAllSchools ? '8' : '7' }}" class="text-center py-5">
                                        <div class="avatar-lg mx-auto mb-3">
                                            <div class="avatar-title bg-light rounded-circle">
                                                <i class="ri-book-open-line fs-1 text-muted"></i>
                                            </div>
                                        </div>
                                        <h6 class="text-muted">Belum ada mata pelajaran</h6>
                                        <a href="{{ route('user.subjects.create', ['userId' => $userId]) }}" class="btn btn-primary btn-sm mt-1">
                                            <i class="ri-add-line me-1"></i>Tambah Mapel
                                        </a>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.querySelectorAll('.delete-sub').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.dataset.id;
                var name = this.dataset.name;
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: 'Mata pelajaran "' + name + '" akan dihapus permanen.',
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
                        form.action = '/{{ $userId }}/subjects/' + id;
                        var token = document.createElement('input'); token.type = 'hidden'; token.name = '_token'; token.value = '{{ csrf_token() }}';
                        var method = document.createElement('input'); method.type = 'hidden'; method.name = '_method'; method.value = 'DELETE';
                        form.appendChild(token); form.appendChild(method);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection