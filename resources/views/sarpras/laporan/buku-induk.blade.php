@extends('layouts.master')
@section('title') Buku Induk Inventaris Aset @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.laporan.index') }}">Laporan</a> @endslot
        @slot('title') Buku Induk Inventaris
    @endcomponent

    <div class="row g-3 mb-3">
        @foreach([
            ['Baris Inventaris', $summary['items'] ?? 0, 'primary', 'ri-list-check-2'],
            ['Total Kuantitas', $summary['quantity'] ?? 0, 'info', 'ri-stack-line'],
            ['Harga Perolehan', 'Rp '.number_format($summary['value'] ?? 0, 0, ',', '.'), 'success', 'ri-money-dollar-circle-line'],
            ['Penyusutan / Tahun', 'Rp '.number_format($summary['depreciation'] ?? 0, 0, ',', '.'), 'warning', 'ri-line-chart-down-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-6 col-xl-3"><div class="card h-100 border-0 shadow-sm"><div class="card-body py-3 d-flex align-items-center gap-3"><span class="avatar-sm"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span><div><div class="text-muted small">{{ $label }}</div><div class="fs-5 fw-bold">{{ is_numeric($value) ? number_format($value) : $value }}</div></div></div></div></div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sarpras.laporan.buku-induk') }}" class="row g-2 align-items-end">
                <div class="col-md-3"><label class="form-label small fw-semibold">Cari</label><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama / kode / merk…"></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Satuan Kerja</label><select name="work_unit_id" class="form-select"><option value="">Semua</option>@foreach($workUnits as $unit)<option value="{{ $unit->id }}" {{ request('work_unit_id') === $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Ruang</label><select name="room_id" class="form-select"><option value="">Semua</option>@foreach($rooms as $room)<option value="{{ $room->id }}" {{ request('room_id') === $room->id ? 'selected' : '' }}>{{ $room->building?->building_name ? $room->building->building_name.' — ' : '' }}{{ $room->room_name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Kategori</label><select name="category_id" class="form-select"><option value="">Semua</option>@foreach($categories as $category)<option value="{{ $category->id }}" {{ request('category_id') === $category->id ? 'selected' : '' }}>{{ $category->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Kondisi</label><select name="condition" class="form-select"><option value="">Semua</option>@foreach(\App\Models\Asset::CONDITION_OPTIONS as $condition)<option value="{{ $condition }}" {{ request('condition') === $condition ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $condition)) }}</option>@endforeach</select></div>
                <div class="col-md-1"><button class="btn btn-primary w-100"><i class="ri-filter-3-line"></i></button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-book-2-line me-2 text-primary"></i>Buku Induk Inventaris Aset</h5><p class="text-muted small mb-0">Satu baris = satu unit aset ber-QR. Kuantitas dipakai untuk barang bulk.</p></div>
            <div class="d-flex gap-2"><a href="{{ route('sarpras.laporan.kir') }}" class="btn btn-outline-info btn-sm"><i class="ri-file-list-3-line me-1"></i>KIR</a><a href="{{ route('sarpras.laporan.buku-induk.pdf', request()->query()) }}" class="btn btn-danger btn-sm"><i class="ri-file-pdf-line me-1"></i>Export PDF</a></div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>No</th><th>Satuan Kerja</th><th>Ruangan</th><th>Jenis Barang</th><th>Merk</th><th>Tgl Perolehan</th><th>No Urut</th><th>Kode Aset</th><th class="text-end">Qty</th><th>Satuan</th><th>Sumber</th><th>Keadaan</th><th class="text-end">Harga Perolehan</th><th>Masa Pakai</th><th class="text-end">Penyusutan</th><th>Penanggung Jawab</th><th>Keterangan</th></tr></thead>
                    <tbody>
                    @forelse($assets as $i => $asset)
                        <tr>
                            <td>{{ $assets->firstItem() + $i }}</td>
                            <td class="small">{{ $asset->workUnit?->name ?? '-' }}</td>
                            <td class="small">{{ $asset->room?->building?->building_name ? $asset->room->building->building_name.' — ' : '' }}{{ $asset->room?->room_name ?? '-' }}</td>
                            <td><div class="fw-medium">{{ $asset->asset_name }}</div><small class="text-muted">{{ $asset->category?->inventory_group ? $asset->category->inventory_group.' · ' : '' }}{{ $asset->category?->name }}</small></td>
                            <td class="small">{{ $asset->brand ?? '-' }}</td>
                            <td class="small">{{ $asset->acquisition_date?->format('d/m/Y') ?? $asset->acquisition_year ?? '-' }}</td>
                            <td class="text-center">{{ $asset->work_unit_sequence ?? '-' }}</td>
                            <td><code class="small">{{ $asset->asset_code }}</code></td>
                            <td class="text-end">{{ number_format($asset->quantity) }}</td>
                            <td class="small">{{ $asset->unit }}</td>
                            <td class="small">{{ ucfirst(str_replace('_',' ', $asset->acquisition_source ?? '-')) }}</td>
                            <td><span class="badge bg-{{ $asset->kir_condition === 'B' ? 'success' : ($asset->kir_condition === 'KB' ? 'warning' : 'danger') }}-subtle text-{{ $asset->kir_condition === 'B' ? 'success' : ($asset->kir_condition === 'KB' ? 'warning' : 'danger') }}">{{ $asset->kir_condition }}</span></td>
                            <td class="text-end small">{{ $asset->acquisition_price ? number_format($asset->acquisition_price,0,',','.') : '-' }}</td>
                            <td class="small">{{ $asset->masa_pakai ?: '-' }} th</td>
                            <td class="text-end small">{{ $asset->harga_penyusutan ? number_format($asset->harga_penyusutan,0,',','.') : '-' }}</td>
                            <td class="small">{{ $asset->room?->responsibleUser?->name ?? $asset->workUnit?->primarySarprasOfficer?->user?->name ?? '-' }}</td>
                            <td class="small text-muted">{{ \Illuminate\Support\Str::limit($asset->notes, 40) ?: '-' }}</td>
                        </tr>
                    @empty
                        <x-empty-state colspan="17" title="Belum ada data inventaris" message="Tambahkan aset atau ubah filter laporan." />
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($assets->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $assets])</div>@endif
    </div>
@endsection
