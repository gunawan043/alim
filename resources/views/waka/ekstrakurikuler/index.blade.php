@extends('waka.master')
@section('title') Ekstrakurikuler @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" />
    <style>
        .badge-soft-success { background: #d1fae5; color: #065f46; }
        .badge-soft-danger  { background: #fee2e2; color: #991b1b; }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ekstrakurikuler @endslot
        @slot('title') Daftar Ekstrakurikuler @endslot
    @endcomponent

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Daftar Ekstrakurikuler</h4>
            <a href="{{ route('waka.ekstrakurikuler.create') }}" class="btn btn-primary">
                <i class="ri-add-line align-middle me-1"></i> Tambah Ekstrakurikuler
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('waka.ekstrakurikuler.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Nama, pembimbing, lokasi">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">— Semua —</option>
                        @foreach(['aktif'=>'Aktif','berhenti'=>'Berhenti'] as $k => $v)
                            <option value="{{ $k }}" {{ request('status')===$k?'selected':'' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="ri-search-line"></i> Filter</button>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('waka.ekstrakurikuler.index') }}" class="btn btn-sm btn-secondary w-100"><i class="ri-refresh-line"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        @forelse($ekskulList as $ekskul)
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card card-animate">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0 fw-bold">{{ $ekskul->nama }}</h5>
                            @if($ekskul->status === 'aktif')
                                <span class="badge badge-soft-success">Aktif</span>
                            @else
                                <span class="badge badge-soft-danger">Berhenti</span>
                            @endif
                        </div>
                        <div class="mb-2">
                            @if($ekskul->gtk)
                                <small class="text-muted"><i class="ri-user-line align-middle"></i> Pembimbing: {{ $ekskul->gtk->name }}</small>
                            @elseif($ekskul->pembimbing)
                                <small class="text-muted"><i class="ri-user-line align-middle"></i> Pembimbing: {{ $ekskul->pembimbing }}</small>
                            @endif
                        </div>
                        <ul class="list-unstyled small text-muted mb-3">
                            @if($ekskul->hari)
                                <li><i class="ri-calendar-line align-middle"></i> Hari: {{ $ekskul->hari }}</li>
                            @endif
                            @if($ekskul->jam_mulai && $ekskul->jam_selesai)
                                <li><i class="ri-time-line align-middle"></i> {{ $ekskul->jam_mulai }} – {{ $ekskul->jam_selesai }}</li>
                            @endif
                            @if($ekskul->lokasi)
                                <li><i class="ri-map-pin-line align-middle"></i> {{ $ekskul->lokasi }}</li>
                            @endif
                            @if($ekskul->kuota)
                                <li><i class="ri-group-line align-middle"></i> Kuota: {{ $ekskul->kuota }}</li>
                            @endif
                        </ul>
                        <div class="text-muted small mb-2">
                            <strong>{{ $ekskul->jumlah_anggota }}</strong> anggota aktif
                        </div>
                        <a href="{{ route('waka.ekstrakurikuler.show', $ekskul->id) }}" class="btn btn-sm btn-outline-info w-100">
                            <i class="ri-eye-line"></i> Detail & Anggota
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <p class="text-center text-muted py-4">Belum ada ekstrakurikuler.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-3">{{ $ekskulList->links() }}</div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        @if(session('success'))
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: '{{ session('success') }}', timer: 2000 });
        @endif
    </script>
@endsection