@extends('waka.master')
@section('title') Ekstrakurikuler @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" />
    @include('kurikulum._styles')
    <style>
        .badge-soft-success { background: #d1fae5; color: #065f46; }
        .badge-soft-danger  { background: #fee2e2; color: #991b1b; }
    </style>
@endsection

@section('content')
    @php
        $stats = $statistics ?? ['total' => 0, 'aktif' => 0, 'pembina' => 0, 'peserta' => 0];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Ekstrakurikuler @endslot
        @slot('title') Daftar Ekstrakurikuler @endslot
    @endcomponent

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">Daftar Ekstrakurikuler</h4>
            <p class="text-muted small mb-0">Kelola kegiatan ekstrakurikuler, pembina, dan anggotanya.</p>
        </div>
        <a href="{{ route('waka.ekstrakurikuler.create') }}" class="btn btn-primary">
            <i class="ri-add-line align-middle me-1"></i> Tambah Ekstrakurikuler
        </a>
    </div>

    {{-- STATISTIK --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-award-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Ekskul</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Seluruh kegiatan terdaftar</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-play-circle-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Aktif</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['aktif']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-checkbox-circle-line me-1"></i>Sedang berjalan</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-user-star-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Pembina</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['pembina']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>GTK pembina terdata</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-group-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Peserta</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['peserta']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-user-follow-line me-1"></i>Anggota aktif seluruh ekskul</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="ekskulList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Ekstrakurikuler</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $ekskulList->total() }} ekskul</span>
                                <span class="text-muted small ms-2">Kelola pembina, jadwal, dan anggota.</span>
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <form method="GET" action="{{ route('waka.ekstrakurikuler.index') }}" class="d-flex flex-wrap gap-2">
                                @if(request()->filled('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                                <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                       style="width:220px" placeholder="Nama, pembimbing, lokasi">
                                <button type="submit" class="btn btn-primary"><i class="ri-search-line"></i></button>
                                <a href="{{ route('waka.ekstrakurikuler.index') }}" class="btn btn-light" title="Reset"><i class="ri-refresh-line"></i></a>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="filter-badge {{ ! request()->filled('status') ? 'active' : '' }}">Semua</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'aktif']) }}" class="filter-badge {{ request('status') === 'aktif' ? 'active' : '' }}"><i class="ri-play-circle-line"></i> Aktif</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'berhenti']) }}" class="filter-badge {{ request('status') === 'berhenti' ? 'active' : '' }}"><i class="ri-stop-circle-line"></i> Berhenti</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nama</th>
                                <th>Pembimbing</th>
                                <th class="text-center">Jadwal</th>
                                <th>Lokasi</th>
                                <th class="text-center">Kuota</th>
                                <th class="text-center">Anggota</th>
                                <th class="text-center">Status</th>
                                <th class="text-end" style="width:170px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ekskulList as $ekskul)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $ekskul->nama }}</div>
                                        @if($ekskul->deskripsi)
                                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit($ekskul->deskripsi, 70) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($ekskul->gtk)
                                            {{ $ekskul->gtk->name }}
                                        @elseif($ekskul->pembimbing)
                                            {{ $ekskul->pembimbing }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center small">
                                        {{ $ekskul->hari ?? '—' }}
                                        @if($ekskul->jam_mulai && $ekskul->jam_selesai)
                                            <div class="text-muted">{{ $ekskul->jam_mulai }} – {{ $ekskul->jam_selesai }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $ekskul->lokasi ?? '—' }}</td>
                                    <td class="text-center">{{ $ekskul->kuota ?? '—' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary">{{ $ekskul->jumlah_anggota }} anggota</span>
                                    </td>
                                    <td class="text-center">
                                        @if($ekskul->status === 'aktif')
                                            <span class="badge badge-soft-success">Aktif</span>
                                        @else
                                            <span class="badge badge-soft-danger">Berhenti</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('waka.ekstrakurikuler.show', $ekskul->id) }}" class="btn btn-sm btn-soft-info" title="Detail & Anggota">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('waka.ekstrakurikuler.edit', $ekskul->id) }}" class="btn btn-sm btn-soft-warning" title="Edit">
                                            <i class="ri-pencil-line"></i>
                                        </a>
                                        <form action="{{ route('waka.ekstrakurikuler.destroy', $ekskul->id) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Hapus ekstrakurikuler ini? Anggota yang terdaftar juga akan terhapus.')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-soft-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-award-line fs-1 d-block mb-2"></i>
                                            Belum ada ekstrakurikuler pada filter ini.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-body pt-0">
                    @include('shared._pagination', ['paginator' => $ekskulList])
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        @if(session('success'))
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: '{{ session('success') }}', timer: 2000 });
        @endif
    </script>
@endsection
