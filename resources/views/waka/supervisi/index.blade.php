@extends('waka.master')
@section('title') Supervisi @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" />
    <style>
        .badge-soft-info    { background: #e0f2fe; color: #075985; }
        .badge-soft-warning { background: #fef3c7; color: #92400e; }
        .badge-soft-success { background: #d1fae5; color: #065f46; }
        .badge-soft-danger  { background: #fee2e2; color: #991b1b; }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Supervisi @endslot
        @slot('title') Daftar Supervisi @endslot
    @endcomponent

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Daftar Supervisi</h4>
            <a href="{{ route('waka.supervisi.create') }}" class="btn btn-primary">
                <i class="ri-add-line align-middle me-1"></i> Tambah Supervisi
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('waka.supervisi.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="GTK / observer / mapel">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">— Semua —</option>
                        @foreach(['terjadwal','berlangsung','selesai','dibatalkan'] as $s)
                            <option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Jenis</label>
                    <select name="jenis_supervisi" class="form-select form-select-sm">
                        <option value="">— Semua —</option>
                        @foreach(['perangkat_pembelajaran'=>'Perangkat','proses_pembelajaran'=>'Proses','penilaian'=>'Penilaian','lainnya'=>'Lainnya'] as $k=>$v)
                            <option value="{{ $k }}" {{ request('jenis_supervisi')===$k?'selected':'' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select form-select-sm">
                        <option value="">— Semua —</option>
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ request('academic_year_id')==$ay->id?'selected':'' }}>{{ $ay->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex">
                    <button type="submit" class="btn btn-sm btn-primary me-1 flex-grow-1"><i class="ri-search-line"></i> Filter</button>
                    <a href="{{ route('waka.supervisi.index') }}" class="btn btn-sm btn-secondary"><i class="ri-refresh-line"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p class="text-muted small">Total: {{ $supervisiList->total() }} supervisi</p>
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>GTK</th>
                        <th>Observer</th>
                        <th>Mapel</th>
                        <th>Jenis</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($supervisiList as $s)
                        <tr>
                            <td>{{ $s->tanggal_supervisi?->format('d/m/Y') }}</td>
                            <td>
                                <div class="fw-semibold">{{ $s->gtk?->name ?? $s->gtk_name }}</div>
                                @if($s->gtk?->latest_nupy)<small class="text-muted">NUPY. {{ $s->gtk->latest_nupy }}</small>@endif
                            </td>
                            <td>{{ $s->observer?->name ?? $s->observer_name }}</td>
                            <td>{{ $s->mata_pelajaran ?: '—' }}</td>
                            <td><span class="badge badge-soft-info">{{ \Illuminate\Support\Str::headline($s->jenis_supervisi) }}</span></td>
                            <td>
                                @php
                                    $badge = ['terjadwal'=>'warning','berlangsung'=>'info','selesai'=>'success','dibatalkan'=>'danger'][$s->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $badge }}">{{ ucfirst($s->status) }}</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('waka.supervisi.show', $s->id) }}" class="btn btn-outline-info"><i class="ri-eye-line"></i></a>
                                    <a href="{{ route('waka.supervisi.edit', $s->id) }}" class="btn btn-outline-warning"><i class="ri-edit-line"></i></a>
                                    <form action="{{ route('waka.supervisi.destroy', $s->id) }}" method="POST" class="d-inline form-delete">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-delete"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data supervisi.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-3">{{ $supervisiList->links() }}</div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const form = this.closest('form');
                Swal.fire({ title: 'Hapus data supervisi ini?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, hapus' })
                    .then((r) => { if (r.isConfirmed) form.submit(); });
            });
        });
    </script>
@endsection