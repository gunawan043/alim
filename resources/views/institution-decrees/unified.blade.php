@extends('layouts.master')
@section('title', 'SK Pembagian Tugas')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('personalia._gtk-ui')
    @component('components.breadcrumb') @slot('li_1') Administrasi @endslot @slot('li_2') <a href="{{ route('user.institution-decrees.index',['userId'=>$userId]) }}">Surat Keputusan</a> @endslot @slot('title') SK Pembagian Tugas @endslot @endcomponent
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card mb-3"><div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0"><i class="ri-file-text-line text-primary me-1"></i>SK Pembagian Tugas — {{ $workUnit?->name ?? 'Satuan kerja belum terdeteksi' }}</h5><p class="text-muted mb-0 small">Data GTK, jabatan, mapel/tugas, dan tugas tambahan saat ini.</p></div><div class="d-flex gap-2"><a href="{{ route('user.institution-decrees.create',['userId'=>$userId]) }}" class="btn btn-primary btn-sm"><i class="ri-add-line me-1"></i>Buat SK</a><button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#positionHoursModal"><i class="ri-time-line me-1"></i>JP Jabatan</button></div></div></div>

    <div class="row g-2 mb-3">
        @foreach([['GTK', $rows->count(), 'ri-team-line', 'primary'], ['Penandatangan', $ksp?->name ?? 'Belum ditetapkan', 'ri-user-star-line', 'success'], ['Total JP', number_format($rows->sum('total_hours'), 1), 'ri-time-line', 'info']] as [$label, $value, $icon, $color])
            <div class="col-md-4"><div class="card card-animate"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><span class="avatar-sm"><span class="avatar-title bg-{{ $color }}-subtle rounded"><i class="{{ $icon }} text-{{ $color }}"></i></span></span><div><div class="text-muted small">{{ $label }}</div><div class="fs-5 fw-bold">{{ $value }}</div></div></div></div></div></div>
        @endforeach
    </div>

    <div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-table-line text-primary me-1"></i>Daftar GTK Berdasarkan Jabatan</h5></div><div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>No</th><th>Nama</th><th>Jabatan</th><th>Mapel/Tugas</th><th>Tugas Tambahan</th><th>Rombel</th><th>JP</th><th>Total Jam</th></tr></thead><tbody>
    @forelse($rows as $i => $row)
        <tr><td>{{ $i+1 }}</td><td class="fw-medium">{{ $row['name'] }}</td><td><span class="badge bg-secondary-subtle text-secondary">{{ $row['position'] }}</span></td><td>{{ $row['teaching'] ?: '—' }}</td><td>{{ $row['additional'] ?: '—' }}</td><td>{{ $row['rombel'] ?: '—' }}</td><td>{{ number_format($row['jp'], 1) }}</td><td class="fw-semibold">{{ number_format($row['total_hours'], 1) }}</td></tr>
    @empty<tr><td colspan="8" class="text-center text-muted py-5">Belum ada GTK pada satuan kerja ini.</td></tr>@endforelse
    </tbody></table></div></div>

    <div class="modal fade" id="positionHoursModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('user.institution-decrees.position-hours',['userId'=>$userId,'workUnitId'=>$workUnit?->id]) }}">@csrf<div class="modal-header"><h5 class="modal-title">JP Jabatan per Satuan Kerja</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-info small">JP ini hanya berlaku untuk {{ $workUnit?->name }} dan wajib diisi sebelum SK diajukan.</div>@foreach($positions as $position)<div class="row g-2 align-items-center mb-2"><div class="col-8">{{ $position->name }}</div><div class="col-4"><input type="number" name="hours[{{ $position->id }}]" value="{{ $positionHours[$position->id] ?? 0 }}" min="0" step="0.5" class="form-control form-control-sm"></div></div>@endforeach</div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan JP</button></div></form></div></div></div>
@endsection
