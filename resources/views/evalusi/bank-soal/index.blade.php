@extends('layouts.master')
@section('title') Bank Soal @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Bank Soal @endslot
        @slot('title') Daftar Bank Soal @endslot
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

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Bank Soal</h5>
                            <p class="text-muted mb-0">Kelola kumpulan soal untuk setiap mata pelajaran dan jenjang.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.bank-soal.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Bank Soal
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filter --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Cari nama bank soal..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="subject_id" class="form-control">
                                <option value="">Semua Mapel</option>
                                @foreach($subjects as $s)
                                    <option value="{{ $s->id }}" {{ request('subject_id') == $s->id ? 'selected' : '' }}>
                                        {{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="jenis_soal" class="form-control">
                                <option value="">Semua Jenis</option>
                                <option value="pilihan_ganda" {{ request('jenis_soal') === 'pilihan_ganda' ? 'selected' : '' }}>Pilihan Ganda</option>
                                <option value="benar_salah" {{ request('jenis_soal') === 'benar_salah' ? 'selected' : '' }}>Benar/Salah</option>
                                <option value="uraian" {{ request('jenis_soal') === 'uraian' ? 'selected' : '' }}>Uraian</option>
                                <option value="campuran" {{ request('jenis_soal') === 'campuran' ? 'selected' : '' }}>Campuran</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="shared_scope" class="form-control">
                                <option value="">Semua Jangkauan</option>
                                <option value="private" {{ request('shared_scope') === 'private' ? 'selected' : '' }}>Privat</option>
                                <option value="internal_school" {{ request('shared_scope') === 'internal_school' ? 'selected' : '' }}>Internal Sekolah</option>
                                <option value="public_pool" {{ request('shared_scope') === 'public_pool' ? 'selected' : '' }}>Publik</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-search-line me-1"></i> Filter
                            </button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Nama Bank Soal</th>
                                    <th>Mapel</th>
                                    <th>Jenis</th>
                                    <th>Tingkat</th>
                                    <th>Jumlah Soal</th>
                                    <th>Jangkauan</th>
                                    <th>Pemilik</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($banks as $bank)
                                    <tr>
                                        <td>{{ $loop->iteration + ($banks->currentPage() - 1) * $banks->perPage() }}</td>
                                        <td>
                                            <a href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}"
                                               class="fw-medium link-primary">
                                                {{ $bank->nama }}
                                            </a>
                                            @if($bank->deskripsi)
                                                <br><small class="text-muted">{{ Str::limit($bank->deskripsi, 80) }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $bank->subject?->name ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">
                                                {{ ucwords(str_replace('_', ' ', $bank->jenis_soal)) }}
                                            </span>
                                        </td>
                                        <td>{{ $bank->tingkat_kesulitan_target ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary">
                                                {{ $bank->soal_count ?? 0 }} soal
                                            </span>
                                        </td>
                                        <td>
                                            @if($bank->shared_scope === 'private')
                                                <span class="badge bg-danger-subtle text-danger">Privat</span>
                                            @elseif($bank->shared_scope === 'internal_school')
                                                <span class="badge bg-warning-subtle text-warning">Internal</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success">Publik</span>
                                            @endif
                                            @if($bank->is_public)
                                                &nbsp;<i class="ri-global-line text-success" title="Is Public"></i>
                                            @endif
                                        </td>
                                        <td>
                                            @if($bank->owner)
                                                <span class="text-muted small">{{ $bank->owner->name }}</span>
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
                                                           href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}">
                                                            <i class="ri-eye-line me-2"></i>Lihat
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('user.bank-soal.edit', ['userId' => $userId, 'id' => $bank->id]) }}">
                                                            <i class="ri-pencil-line me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('user.bank-soal.clone', ['userId' => $userId, 'id' => $bank->id]) }}">
                                                            <i class="ri-file-copy-line me-2"></i>Clone
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger delete-bank"
                                                           href="javascript:void(0)"
                                                           data-id="{{ $bank->id }}" data-name="{{ $bank->nama }}">
                                                            <i class="ri-delete-bin-line me-2"></i>Hapus
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="avatar-lg mx-auto mb-3">
                                                <div class="avatar-title bg-light rounded-circle">
                                                    <i class="ri-question-line fs-1 text-muted"></i>
                                                </div>
                                            </div>
                                            <h5 class="text-muted">Belum ada Bank Soal</h5>
                                            <a href="{{ route('user.bank-soal.create', ['userId' => $userId]) }}"
                                               class="btn btn-success mt-2">
                                                <i class="ri-add-line me-1"></i>Tambah Bank Soal
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @include('shared._pagination', ['paginator' => $banks])
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.querySelectorAll('.delete-bank').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.dataset.id;
                var name = this.dataset.name;
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: "Bank soal \"" + name + "\" akan dihapus permanen.",
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
                        form.action = '/{{ $userId }}/bank-soal/' + id;
                        ['_token', '_method'].forEach(function(n, i) {
                            var inp = document.createElement('input');
                            inp.type = 'hidden';
                            inp.name = n;
                            inp.value = i === 0 ? '{{ csrf_token() }}' : 'DELETE';
                            form.appendChild(inp);
                        });
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection
