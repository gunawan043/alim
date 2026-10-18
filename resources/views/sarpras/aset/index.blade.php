@extends('layouts.master')
@section('title') Aset / Inventaris @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Aset / Inventaris @endslot
    @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row g-3 mb-3">
        @foreach([
            ['Total Aset', $stats['total'] ?? 0, 'primary', 'ri-archive-line'],
            ['Menunggu Verifikasi', $stats['pending'] ?? 0, 'warning', 'ri-time-line'],
            ['Kondisi Rusak', $stats['broken'] ?? 0, 'danger', 'ri-tools-line'],
            ['Dipinjam', $stats['borrowed'] ?? 0, 'info', 'ri-hand-coin-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body py-3 d-flex align-items-center gap-3">
                        <span class="avatar-sm"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span>
                        <div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ number_format($value) }}</div></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sarpras.aset.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3"><label class="form-label small fw-semibold">Cari</label><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama / kode / merk…"></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Ruang</label><select name="room_id" class="form-select"><option value="">Semua</option>@foreach($rooms as $r)<option value="{{ $r->id }}" {{ request('room_id') == $r->id ? 'selected' : '' }}>{{ $r->room_name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Kondisi</label><select name="condition" class="form-select"><option value="">Semua</option>@foreach(App\Models\Asset::CONDITION_OPTIONS as $c)<option value="{{ $c }}" {{ request('condition') == $c ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$c)) }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Semua</option>@foreach(App\Models\Asset::STATUS_OPTIONS as $s)<option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Verifikasi</label><select name="verification_status" class="form-select"><option value="">Semua</option><option value="pending" {{ request('verification_status') === 'pending' ? 'selected' : '' }}>Menunggu</option><option value="verified" {{ request('verification_status') === 'verified' ? 'selected' : '' }}>Terverifikasi</option><option value="rejected" {{ request('verification_status') === 'rejected' ? 'selected' : '' }}>Ditolak</option></select></div>
                <div class="col-md-1"><button class="btn btn-primary w-100"><i class="ri-filter-3-line"></i></button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-archive-line me-2 text-primary"></i>Daftar Aset / Inventaris</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('sarpras.aset.import') }}" class="btn btn-outline-primary btn-sm"><i class="ri-upload-cloud-line me-1"></i>Import</a>
                <a href="{{ route('sarpras.aset.create') }}" class="btn btn-success btn-sm"><i class="ri-add-line me-1"></i>Tambah</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>No</th><th>Nama Aset</th><th>Kode</th><th>Qty</th><th>Kategori</th><th>Ruang</th><th>Merk/Model</th><th>Kondisi</th><th>Status</th><th>Verifikasi</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                    @forelse($asets as $i => $a)
                        @php
                            $kc=['baik'=>'success','rusak_ringan'=>'warning','rusak_sedang'=>'warning','rusak_berat'=>'danger','hilang'=>'secondary','dihapus'=>'secondary'];
                            $sc=['tersedia'=>'success','dipinjam'=>'info','dalam_perbaikan'=>'warning','dihapus'=>'secondary'];
                            $vc=['pending'=>'warning','verified'=>'success','rejected'=>'danger'];
                        @endphp
                        <tr>
                            <td>{{ $asets->firstItem() + $i }}</td>
                            <td><a href="{{ route('sarpras.aset.show', ['id' => $a->id]) }}" class="fw-medium link-primary">{{ $a->asset_name }}</a></td>
                            <td><code>{{ $a->asset_code ?? '-' }}</code></td>
                            <td class="text-nowrap">{{ number_format($a->quantity ?? 1) }} <span class="text-muted small">{{ $a->unit ?? 'unit' }}</span></td>
                            <td>{{ $a->category?->name ?? '-' }}</td>
                            <td>{{ $a->room?->room_name ?? '-' }}</td>
                            <td>{{ $a->brand ? trim($a->brand.' '.$a->model) : '-' }}</td>
                            <td><span class="badge bg-{{ $kc[$a->condition] ?? 'secondary' }}-subtle text-{{ $kc[$a->condition] ?? 'secondary' }}">{{ ucfirst(str_replace('_',' ',$a->condition)) }}</span></td>
                            <td><span class="badge bg-{{ $sc[$a->status] ?? 'secondary' }}-subtle text-{{ $sc[$a->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_',' ',$a->status)) }}</span></td>
                            <td><span class="badge bg-{{ $vc[$a->verification_status ?? 'verified'] ?? 'secondary' }}-subtle text-{{ $vc[$a->verification_status ?? 'verified'] ?? 'secondary' }}">{{ ucfirst($a->verification_status ?? 'verified') }}</span></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    @if(($canVerify ?? false) && ($a->verification_status ?? 'verified') === 'pending')
                                        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#verifyAssetModal" data-action="{{ route('sarpras.aset.verify', ['id' => $a->id]) }}" data-name="{{ $a->asset_name }}" title="Verifikasi"><i class="ri-check-line"></i></button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectAssetModal" data-action="{{ route('sarpras.aset.verify', ['id' => $a->id]) }}" data-name="{{ $a->asset_name }}" title="Tolak"><i class="ri-close-line"></i></button>
                                    @endif
                                    <a href="{{ route('sarpras.aset.show', ['id' => $a->id]) }}" class="btn btn-sm btn-outline-info"><i class="ri-eye-line"></i></a>
                                    <a href="{{ route('sarpras.aset.edit', ['id' => $a->id]) }}" class="btn btn-sm btn-outline-warning"><i class="ri-edit-line"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="11" title="Belum ada data aset" message="Tambahkan aset pertama atau import data inventaris.">
                            <a href="{{ route('sarpras.aset.create') }}" class="btn btn-success mt-3"><i class="ri-add-line me-1"></i>Tambah Aset</a>
                        </x-empty-state>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($asets->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $asets])</div>@endif
    </div>

    <x-modal-confirm id="verifyAssetModal" class="zoomIn" icon="https://cdn.lordicon.com/lupuorrc.json" title="Verifikasi Aset?" submitLabel="Ya, Verifikasi" submitIcon="ri-check-line" submitClass="btn-success" formId="verifyAssetForm">
        <p class="text-center text-muted mb-0" id="verifyAssetMessage">Aset akan diverifikasi oleh Unit Rumah Tangga.</p>
    </x-modal-confirm>
    <form id="verifyAssetForm" method="POST" class="d-none">@csrf<input type="hidden" name="decision" value="verified"></form>

    <x-modal-confirm id="rejectAssetModal" class="zoomIn" icon="https://cdn.lordicon.com/gsqxdxog.json" title="Tolak Aset?" submitLabel="Ya, Tolak" submitIcon="ri-close-line" submitClass="btn-danger" formId="rejectAssetForm">
        <p class="text-center text-muted" id="rejectAssetMessage">Aset akan ditolak.</p>
        <textarea name="verification_note" form="rejectAssetForm" class="form-control" rows="3" placeholder="Catatan alasan penolakan…"></textarea>
    </x-modal-confirm>
    <form id="rejectAssetForm" method="POST" class="d-none">@csrf<input type="hidden" name="decision" value="rejected"></form>
@endsection

@section('script')
<script>
    document.getElementById('verifyAssetModal')?.addEventListener('show.bs.modal', function (e) {
        var b = e.relatedTarget; if (!b) return;
        document.getElementById('verifyAssetForm').action = b.getAttribute('data-action');
        document.getElementById('verifyAssetMessage').textContent = 'Verifikasi aset "' + b.getAttribute('data-name') + '"?';
    });
    document.getElementById('rejectAssetModal')?.addEventListener('show.bs.modal', function (e) {
        var b = e.relatedTarget; if (!b) return;
        document.getElementById('rejectAssetForm').action = b.getAttribute('data-action');
        document.getElementById('rejectAssetMessage').textContent = 'Tolak aset "' + b.getAttribute('data-name') + '"?';
    });
</script>
@endsection
