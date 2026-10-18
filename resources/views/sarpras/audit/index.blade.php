@extends('layouts.master')
@section('title') Audit Data Sarpras @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Audit Data
    @endcomponent

    <div class="alert alert-info d-flex align-items-center gap-2"><i class="ri-shield-check-line fs-5"></i><span>Halaman ini membantu memeriksa kualitas data sebelum dan sesudah import aset. Perbaiki temuan di bawah agar Buku Induk, KIR, QR, dan laporan akurat.</span></div>

    <div class="row g-3 mb-3">
        @foreach([
            ['Total Aset', $stats['assets'], 'primary', 'ri-archive-line'],
            ['Menunggu Verifikasi', $stats['pending'], 'warning', 'ri-time-line'],
            ['Tanpa Ruang', $stats['no_room'], 'danger', 'ri-door-closed-line'],
            ['Tanpa Harga', $stats['no_price'], 'secondary', 'ri-money-dollar-circle-line'],
            ['Ruang Tanpa PIC', $stats['no_pic'], 'warning', 'ri-user-unfollow-line'],
            ['Ruang Tanpa Gedung', $stats['no_building'], 'danger', 'ri-building-line'],
            ['Gedung Tanpa Ruang', $stats['empty_buildings'], 'info', 'ri-hotel-building-line'],
            ['Asrama Belum Sinkron', $stats['unsynced_dorm'], 'danger', 'ri-hotel-bed-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-6 col-xl-3"><div class="card h-100 border-0 shadow-sm"><div class="card-body py-3 d-flex align-items-center gap-3"><span class="avatar-sm"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span><div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ number_format($value) }}</div></div></div></div></div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-time-line me-1 text-warning"></i>Menunggu Verifikasi</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead class="table-light"><tr><th>Aset</th><th>Ruang</th><th>Unit</th><th class="text-end">Aksi</th></tr></thead><tbody>
                @forelse($pendingAssets as $asset)<tr><td class="small">{{ $asset->asset_name }}<br><code>{{ $asset->asset_code }}</code></td><td class="small">{{ $asset->room?->room_name ?? '-' }}</td><td class="small">{{ $asset->workUnit?->name ?? '-' }}</td><td class="text-end"><a href="{{ route('sarpras.aset.show', ['id' => $asset->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td></tr>@empty<tr><td colspan="4" class="text-center py-3 text-muted">Tidak ada aset pending.</td></tr>@endforelse
            </tbody></table></div></div></div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-door-closed-line me-1 text-danger"></i>Aset Tanpa Ruang</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead class="table-light"><tr><th>Aset</th><th>Kode</th><th>Unit</th><th class="text-end">Aksi</th></tr></thead><tbody>
                @forelse($noRoom as $asset)<tr><td class="small">{{ $asset->asset_name }}</td><td><code class="small">{{ $asset->asset_code }}</code></td><td class="small">{{ $asset->workUnit?->name ?? '-' }}</td><td class="text-end"><a href="{{ route('sarpras.aset.edit', ['id' => $asset->id]) }}" class="btn btn-sm btn-outline-warning"><i class="ri-edit-line"></i></a></td></tr>@empty<tr><td colspan="4" class="text-center py-3 text-muted">Semua aset memiliki ruang.</td></tr>@endforelse
            </tbody></table></div></div></div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-money-dollar-circle-line me-1 text-secondary"></i>Aset Tanpa Harga Perolehan</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead class="table-light"><tr><th>Aset</th><th>Ruang</th><th>Kategori</th><th class="text-end">Aksi</th></tr></thead><tbody>
                @forelse($noPrice as $asset)<tr><td class="small">{{ $asset->asset_name }}</td><td class="small">{{ $asset->room?->room_name ?? '-' }}</td><td class="small">{{ $asset->category?->name ?? '-' }}</td><td class="text-end"><a href="{{ route('sarpras.aset.edit', ['id' => $asset->id]) }}" class="btn btn-sm btn-outline-warning"><i class="ri-edit-line"></i></a></td></tr>@empty<tr><td colspan="4" class="text-center py-3 text-muted">Semua aset memiliki harga.</td></tr>@endforelse
            </tbody></table></div></div></div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-user-unfollow-line me-1 text-warning"></i>Ruang Tanpa PIC</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead class="table-light"><tr><th>Ruang</th><th>Gedung</th><th>Unit</th><th class="text-end">Aksi</th></tr></thead><tbody>
                @forelse($noPic as $room)<tr><td class="small">{{ $room->room_name }}</td><td class="small">{{ $room->building?->building_name ?? '-' }}</td><td class="small">{{ $room->workUnit?->name ?? '-' }}</td><td class="text-end"><a href="{{ route('sarpras.ruang.edit', ['id' => $room->id]) }}" class="btn btn-sm btn-outline-warning"><i class="ri-edit-line"></i></a></td></tr>@empty<tr><td colspan="4" class="text-center py-3 text-muted">Semua ruang memiliki PIC.</td></tr>@endforelse
            </tbody></table></div></div></div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-building-line me-1 text-danger"></i>Ruang Tanpa Gedung / Gedung Tanpa Ruang</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead class="table-light"><tr><th>Data</th><th>Unit</th><th class="text-end">Aksi</th></tr></thead><tbody>
                @foreach($noBuildingRooms as $room)<tr><td class="small">Ruang: {{ $room->room_name }}</td><td class="small">{{ $room->workUnit?->name ?? '-' }}</td><td class="text-end"><a href="{{ route('sarpras.ruang.edit', ['id' => $room->id]) }}" class="btn btn-sm btn-outline-warning"><i class="ri-edit-line"></i></a></td></tr>@endforeach
                @foreach($emptyBuildings as $building)<tr><td class="small">Gedung: {{ $building->building_name }}</td><td class="small">{{ $building->workUnit?->name ?? '-' }}</td><td class="text-end"><a href="{{ route('sarpras.gedung.show', ['id' => $building->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td></tr>@endforeach
                @if($emptyBuildings->isEmpty() && $noBuildingRooms->isEmpty())<tr><td colspan="3" class="text-center py-3 text-muted">Tidak ada temuan.</td></tr>@endif
            </tbody></table></div></div></div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-file-warning-line me-1 text-danger"></i>Duplikat Nama Aset per Ruang</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead class="table-light"><tr><th>Nama Aset</th><th>Ruang</th><th class="text-end">Jumlah</th></tr></thead><tbody>
                @forelse($duplicateNames as $duplicate)<tr><td class="small">{{ $duplicate->asset_name }}</td><td class="small">{{ $duplicate->room?->room_name ?? '-' }}</td><td class="text-end"><span class="badge bg-danger-subtle text-danger">{{ $duplicate->total }}</span></td></tr>@empty<tr><td colspan="3" class="text-center py-3 text-muted">Tidak ada duplikat nama per ruang.</td></tr>@endforelse
            </tbody></table></div></div></div>
        </div>

        <div class="col-12">
            <div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-hotel-bed-line me-1 text-danger"></i>Inventaris Asrama Belum Tersinkron ke Master Aset</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead class="table-light"><tr><th>Asrama</th><th>Kamar</th><th>Barang</th><th class="text-end">Jumlah</th><th>Kondisi</th></tr></thead><tbody>
                @forelse($unsyncedDorm as $item)<tr><td class="small">{{ $item->dormitory?->name ?? '-' }}</td><td class="small">{{ $item->room?->name ?? '-' }}</td><td class="small">{{ $item->item_name }}</td><td class="text-end">{{ $item->quantity ?? 0 }}</td><td class="small">{{ ucfirst(str_replace('_',' ', $item->condition ?? '-')) }}</td></tr>@empty<tr><td colspan="5" class="text-center py-3 text-muted">Semua inventaris asrama sudah tersinkron.</td></tr>@endforelse
            </tbody></table></div></div></div>
        </div>
    </div>
@endsection
