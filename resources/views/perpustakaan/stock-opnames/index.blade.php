@extends('layouts.master')
@section('title', 'Stock Opname Perpustakaan')
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $quickBase = ['userId' => $userId, 'unit_id' => $unit->id];
        $filterAction = route('user.perpustakaan.stock-opnames.index');
        $filterHidden = ['unit_id' => $unit->id];
        $filterFields = [
            ['name' => 'status', 'label' => 'Status Sesi', 'type' => 'select', 'options' => ['' => 'Semua sesi', 'in_progress' => 'Berjalan', 'completed' => 'Selesai']],
        ];
        $quickFilters = [
            ['label' => 'Semua Sesi', 'url' => route('user.perpustakaan.stock-opnames.index', $quickBase), 'icon' => 'ri-list-check', 'active' => ! request('status')],
            ['label' => 'Berjalan', 'url' => route('user.perpustakaan.stock-opnames.index', $quickBase + ['status' => 'in_progress']), 'icon' => 'ri-time-line', 'active' => request('status') === 'in_progress'],
            ['label' => 'Selesai', 'url' => route('user.perpustakaan.stock-opnames.index', $quickBase + ['status' => 'completed']), 'icon' => 'ri-checkbox-circle-line', 'active' => request('status') === 'completed'],
        ];
    @endphp
    @include('perpustakaan._styles')
    @component('components.breadcrumb') @slot('li_1') Perpustakaan @endslot @slot('li_2') Inventaris @endslot @slot('title') Stock Opname @endslot @endcomponent
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card mb-3"><div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0"><i class="ri-file-list-2-line text-primary me-1"></i>Sesi Stock Opname {{ $unit->name }}</h5><p class="text-muted mb-0 small">Setiap eksemplar diperiksa. Temuan tidak menghapus inventaris.</p></div><div class="d-flex gap-2"><form method="GET" class="d-flex gap-2"><input type="hidden" name="unit_id" value="{{ $unit->id }}"><select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">— Pilih sesi —</option>@foreach($sessions as $s)<option value="{{ $s->id }}" @selected($session?->id===$s->id)>{{ $s->session_date?->format('d/m/Y') }} · {{ ucfirst($s->status) }}</option>@endforeach</select></form><button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#libraryFilterModal"><i class="bx bx-filter-alt me-1"></i>Filter Lanjutan</button><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#stockModal"><i class="ri-add-line me-1"></i>Sesi Baru</button></div></div></div>
    <div class="card mb-3">@include('perpustakaan._quick-filters')</div>

    @if($session)
        <div class="row g-2 mb-3 library-stats">@foreach([['Eksemplar',$stats['items'],'ri-stack-line','primary'],['Diperiksa',$stats['checked'],'ri-checkbox-circle-line','success'],['Hilang',$stats['missing'],'ri-error-warning-line','danger'],['Pindah Lokasi',$stats['moved'],'ri-map-pin-line','warning']] as [$l,$v,$i,$c])<div class="col-md-3"><div class="card"><div class="card-body py-3"><div class="d-flex gap-2 align-items-center"><span class="avatar-sm"><span class="avatar-title bg-{{ $c }}-subtle rounded"><i class="{{ $i }} text-{{ $c }}"></i></span></span><div><div class="text-muted small">{{ $l }}</div><div class="fs-4 fw-bold">{{ $v }}</div></div></div></div></div></div>@endforeach</div>
        <div class="card"><div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0">Pemeriksaan {{ $session->session_date?->format('d/m/Y') }}</h5><p class="text-muted mb-0 small">Status sesi: <strong>{{ ucfirst($session->status) }}</strong> · {{ $session->notes }}</p></div>@if($session->status !== 'completed')<form method="POST" action="{{ route('user.perpustakaan.stock-opnames.complete',['userId'=>$userId,'id'=>$session->id]) }}" onsubmit="return confirm('Selesaikan sesi stock opname?')">@csrf<button class="btn btn-success btn-sm"><i class="ri-check-double-line me-1"></i>Selesaikan Sesi</button></form>@endif</div>
            <div class="table-container"><table class="table table-hover library-table align-middle mb-0 table-freeze"><thead class="table-light"><tr><th>Kode</th><th>Judul</th><th>Status Sistem</th><th>Temuan</th><th>Lokasi Aktual</th><th>Kondisi</th><th>Catatan</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($items as $row)
                <tr><td><code>{{ $row->item?->inventory_code }}</code></td><td><div class="fw-medium">{{ $row->item?->title?->title }}</div><div class="text-muted small">{{ $row->item?->title?->author }}</div></td><td><span class="badge bg-light text-dark">{{ ucfirst(str_replace('_',' ',$row->system_status ?? '—')) }}</span></td><td colspan="5"><form method="POST" action="{{ route('user.perpustakaan.stock-opnames.items.update',['userId'=>$userId,'sessionId'=>$session->id,'itemId'=>$row->id]) }}" class="row g-1 align-items-center">@csrf<div class="col-md-2"><select name="finding" class="form-select form-select-sm" @disabled($session->status==='completed')>@foreach(['pending'=>'Belum','found'=>'Ada di rak','borrowed'=>'Dipinjam','moved'=>'Pindah lokasi','missing'=>'Hilang','damaged'=>'Rusak','label_issue'=>'Label salah','number_issue'=>'Nomor induk salah'] as $v=>$l)<option value="{{ $v }}" @selected($row->finding===$v)>{{ $l }}</option>@endforeach</select></div><div class="col-md-2"><input name="physical_location" value="{{ $row->physical_location }}" class="form-control form-control-sm" placeholder="Lokasi"></div><div class="col-md-2"><select name="condition" class="form-select form-select-sm" @disabled($session->status==='completed')>@foreach(['baik'=>'Baik','rusak_ringan'=>'Rusak ringan','rusak_sedang'=>'Rusak sedang','rusak_berat'=>'Rusak berat','lost'=>'Hilang'] as $v=>$l)<option value="{{ $v }}" @selected($row->condition===$v)>{{ $l }}</option>@endforeach</select></div><div class="col-md-3"><input name="notes" value="{{ $row->notes }}" class="form-control form-control-sm" placeholder="Catatan"></div><div class="col-md-2"><button class="btn btn-sm btn-primary" @disabled($session->status==='completed')>Simpan</button></div></form></td></tr>
            @empty<tr><td colspan="8" class="text-center text-muted py-5">Belum ada eksemplar dalam sesi ini.</td></tr>@endforelse
            </tbody></table></div>
        </div>
    @else
        <div class="alert alert-info">Belum ada sesi stock opname. Buat sesi baru untuk memulai pemeriksaan.</div>
    @endif

    <div class="modal fade" id="stockModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('user.perpustakaan.stock-opnames.store',['userId'=>$userId]) }}">@csrf<input type="hidden" name="library_unit_id" value="{{ $unit->id }}"><div class="modal-header bg-primary-subtle"><h5 class="modal-title">Sesi Stock Opname Baru</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Tanggal Sesi</label><input type="date" name="session_date" class="form-control" value="{{ today()->toDateString() }}" required></div><div><label class="form-label">Catatan</label><textarea name="notes" rows="2" class="form-control"></textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Mulai Sesi</button></div></form></div></div></div>
    @include('perpustakaan._filter-modal')
@endsection
