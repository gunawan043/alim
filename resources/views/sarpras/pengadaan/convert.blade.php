@extends('layouts.master')
@section('title') Konversi ke Aset @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.pengadaan.index') }}">Pengadaan</a> @endslot
    @slot('li_3') <a href="{{ route('sarpras.pengadaan.show', ['id' => $procurement->id]) }}">{{ $procurement->request_number }}</a> @endslot
    @slot('title') Konversi Aset @endslot
@endcomponent

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row">
    {{-- INFO PENGADAAN --}}
    <div class="col-12">
        <div class="alert alert-success d-flex align-items-center gap-2 py-2">
            <i class="ri-checkbox-circle-line fs-5"></i>
            <strong>Barang sudah diterima.</strong> Lengkapi form di bawah untuk mengkonversi item menjadi aset.
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-arrow-left-right-line me-1"></i> Konversi Item ke Aset</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('sarpras.pengadaan.convert', ['id' => $procurement->id]) }}">
                    @csrf
                    <p class="text-muted small mb-3">
                        Setiap item yang dipilih akan dibuatkan record aset baru. Jika jumlah lebih dari 1, setiap unit akan dibuat terpisah.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px"><input type="checkbox" id="checkAll"></th>
                                    <th>Item</th>
                                    <th style="width:100px">Jumlah</th>
                                    <th>Target Ruang</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($procurement->items as $i => $item)
                                @php
                                    $maxQty = $item->actual_quantity_received ?? $item->quantity;
                                @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox" name="items[{{ $i }}][selected]" class="item-check"
                                            value="1" checked data-default-qty="{{ $maxQty }}">
                                        <input type="hidden" name="items[{{ $i }}][item_id]" value="{{ $item->id }}">
                                    </td>
                                    <td>
                                        <strong>{{ $item->item_name }}</strong>
                                        @if($item->specification)
                                            <br><small class="text-muted">{{ $item->specification }}</small>
                                        @endif
                                        <br><small class="text-muted">
                                            Tersedia: {{ $maxQty }} {{ $item->unit ?? 'pcs' }} @
                                            {{ $item->actual_price_per_unit ? 'Rp ' . number_format($item->actual_price_per_unit, 0, ',', '.') : ($item->estimated_price_per_unit ? 'Rp ' . number_format($item->estimated_price_per_unit, 0, ',', '.') : '-') }}
                                        </small>
                                    </td>
                                    <td>
                                        <input type="number" name="items[{{ $i }}][quantity]" class="form-control form-control-sm qty-input"
                                            min="1" max="{{ $maxQty }}" value="{{ $maxQty }}">
                                    </td>
                                    <td>
                                        <select name="items[{{ $i }}][room_id]" class="form-select form-select-sm">
                                            <option value="">— Pilih Ruang —</option>
                                            @foreach(\App\Models\AssetRoom::where('is_active', true)->orderBy('room_name')->get() as $r)
                                                <option value="{{ $r->id }}"
                                                    {{ old("items.{$i}.room_id", $item->room_id) == $r->id ? 'selected' : '' }}>
                                                    {{ $r->room_name }}
                                                    @if($r->building) — {{ $r->building->building_name }} @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="items[{{ $i }}][asset_name]" class="form-control form-control-sm mt-1"
                                            placeholder="Nama aset override"
                                            value="{{ old("items.{$i}.asset_name", $item->item_name) }}">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="hstack gap-2 mt-4">
                        <button type="submit" class="btn btn-success" onclick="return confirm('Konversi item yang dicentang menjadi aset?')">
                            <i class="ri-arrow-left-right-line me-1"></i> Konversi ke Aset
                        </button>
                        <a href="{{ route('sarpras.pengadaan.show', ['id' => $procurement->id]) }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SIDEBAR --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Info Pengadaan</h5></div>
            <div class="card-body p-0">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted small">No. Request</td><td class="fw-medium"><code>{{ $procurement->request_number }}</code></td></tr>
                    <tr><td class="text-muted small">Vendor</td><td>{{ $procurement->vendor_name ?? '-' }}</td></tr>
                    <tr><td class="text-muted small">Total Biaya Aktual</td>
                        <td class="fw-medium text-danger">
                            {{ $procurement->total_actual_cost ? 'Rp ' . number_format($procurement->total_actual_cost, 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                    <tr><td class="text-muted small">Sumber Dana</td><td>{{ $procurement->budget_source ?? '-' }}</td></tr>
                    <tr><td class="text-muted small">Tanggal Terima</td><td>{{ $procurement->delivery_date?->format('d/m/Y') ?? '-' }}</td></tr>
                    <tr><td class="text-muted small">Penerima</td><td>{{ $procurement->receiver?->name ?? '-' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h5 class="card-title mb-0">Panduan</h5></div>
            <div class="card-body">
                <ol class="mb-0 ps-3 small text-muted">
                    <li class="mb-1">Centang item yang ingin dikonversi.</li>
                    <li class="mb-1">Atur jumlah unit per item.</li>
                    <li class="mb-1">Pilih ruang tujuan untuk setiap item.</li>
                    <li class="mb-0">Klik "Konversi" — setiap unit akan dibuatkan kode aset unik.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@section('script')
<script>
$('#checkAll').on('change', function() {
    $('.item-check').prop('checked', this.checked);
});

$('.item-check').on('change', function() {
    if (!this.checked) {
        $('#checkAll').prop('checked', false);
        $(this).closest('tr').find('.qty-input').val(0);
    } else {
        $(this).closest('tr').find('.qty-input').val($(this).data('default-qty'));
    }
});
</script>
@endsection