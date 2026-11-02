@extends('layouts.master')
@section('title') QR Kelompok Aset @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.aset.index') }}">Aset</a> @endslot
    @slot('title') QR Kelompok @endslot
@endcomponent

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-qr-code-line me-1"></i> Label QR Seragam</h5>
            </div>
            <div class="card-body text-center">
                <img src="{{ route('sarpras.qr.image', ['id' => $asset->id, 'size' => 260]) }}"
                     alt="QR {{ $groupKey }}" class="img-fluid border rounded p-2 mb-3" style="max-width:240px">

                <div class="fw-semibold">{{ $asset->asset_name }}</div>
                <div class="text-muted small mb-2">
                    {{ $asset->room?->room_name ?? 'Tanpa ruang' }}
                    &middot; {{ $asset->category?->name ?? 'Tanpa kategori' }}
                </div>
                <span class="badge bg-primary-subtle text-primary">{{ $assets->count() }} unit dalam kelompok</span>

                <div class="d-grid gap-2 mt-3">
                    <a href="{{ route('sarpras.qr.pdf', ['asset_id' => $asset->id]) }}" class="btn btn-sm btn-outline-primary">
                        <i class="ri-printer-line me-1"></i> Cetak Label Kelompok
                    </a>
                    <a href="{{ route('sarpras.aset.show', ['id' => $asset->id]) }}" class="btn btn-sm btn-light">
                        <i class="ri-file-list-3-line me-1"></i> Detail Data Aset
                    </a>
                </div>

                <p class="text-muted small mt-3 mb-0">
                    Label ini mewakili seluruh unit sejenis di ruang tersebut. Untuk aset elektronik atau aset besar,
                    gunakan mode <strong>QR per unit</strong> agar riwayat tiap unit tercatat terpisah.
                </p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Unit dalam Kelompok</h5>
                <span class="text-muted small">Kode kelompok: <code>{{ $groupKey }}</code></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nama Aset</th>
                                <th>Kode</th>
                                <th>Ruang</th>
                                <th>Kondisi</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assets as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="fw-medium">{{ $item->asset_name }}</div>
                                        <div class="text-muted small">{{ $item->brand }} {{ $item->model }}</div>
                                    </td>
                                    <td><code>{{ $item->asset_code ?? '-' }}</code></td>
                                    <td>{{ $item->room?->room_name ?? '-' }}</td>
                                    <td>
                                        @php $tone = ['baik' => 'success', 'rusak_ringan' => 'warning', 'rusak_sedang' => 'warning', 'rusak_berat' => 'danger'][$item->condition] ?? 'secondary'; @endphp
                                        <span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">
                                            {{ ucfirst(str_replace('_', ' ', $item->condition)) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('sarpras.aset.show', ['id' => $item->id]) }}" class="btn btn-sm btn-light">Detail</a>
                                        <a href="{{ route('sarpras.aset.timeline', ['id' => $item->id]) }}" class="btn btn-sm btn-outline-primary">Timeline</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
