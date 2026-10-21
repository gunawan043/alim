@extends('layouts.master')
@section('title', 'Stok & Batch Bahan Laboratorium')
@section('css') @include('shared._styles') @include('lab._styles') @endsection
@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp
@component('components.breadcrumb') @slot('li_1') Laboratorium @endslot @slot('title') Stok & Batch Bahan @endslot @endcomponent
@include('lab._nav')
@if(! $lab)<div class="alert alert-warning">Buat profil laboratorium terlebih dahulu.</div>@endif
<div class="row g-3">
    <div class="col-xl-4"><div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-add-circle-line me-1 text-primary"></i>Batch Masuk</h5></div><div class="card-body"><form method="POST" action="{{ route('lab.batches.store', ['userId' => $userId]) }}">@csrf<input type="hidden" name="lab_id" value="{{ $lab?->id }}">
        <div class="mb-2"><label class="form-label">Bahan/Aset</label><select name="asset_id" class="form-select" required><option value="">— pilih —</option>@foreach($assets as $asset)<option value="{{ $asset->id }}">{{ $asset->asset_name }}</option>@endforeach</select></div>
        <div class="mb-2"><label class="form-label">Kode Batch</label><input name="batch_code" class="form-control" placeholder="mis. B-2026-01"></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Jumlah</label><input type="number" name="quantity" class="form-control" min="1" value="1" required></div><div class="col-6"><label class="form-label">Satuan</label><input name="unit" class="form-control" value="unit"></div></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Tanggal Masuk</label><input type="date" name="received_date" class="form-control" value="{{ today()->toDateString() }}"></div><div class="col-6"><label class="form-label">Kedaluwarsa</label><input type="date" name="expiry_date" class="form-control"></div></div>
        <div class="mb-2"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
        <button class="btn btn-primary w-100" @disabled(!$lab)><i class="ri-save-line me-1"></i>Simpan Batch</button>
    </form></div></div></div>
    <div class="col-xl-8"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Stok Batch</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover table-freeze lab-table mb-0"><thead class="table-light"><tr><th>Batch</th><th>Bahan</th><th>Stok</th><th>Masuk</th><th>Kedaluwarsa</th><th>Aksi</th></tr></thead><tbody>
    @forelse($batches as $row)<tr><td>{{ $row->batch_code ?: '-' }}</td><td>{{ $row->asset?->asset_name ?? '-' }}</td><td>{{ $row->quantity }} {{ $row->unit }}</td><td>{{ $row->received_date?->format('d/m/Y') ?? '-' }}</td><td class="{{ $row->expiry_date && $row->expiry_date->isPast() ? 'text-danger fw-semibold' : '' }}">{{ $row->expiry_date?->format('d/m/Y') ?? '-' }}</td><td><form method="POST" action="{{ route('lab.batches.consume', ['userId' => $userId, 'batch' => $row->id]) }}" class="d-flex gap-1">@csrf<input type="number" name="quantity" class="form-control form-control-sm" style="width:75px" min="1" max="{{ $row->quantity }}" value="1"><button class="btn btn-sm btn-soft-warning" title="Catat pemakaian"><i class="ri-subtract-line"></i></button></form></td></tr>
    @empty<tr><td colspan="6" class="text-center text-muted py-4">Belum ada batch stok.</td></tr>@endforelse
    </tbody></table></div></div></div>
    <div class="card mt-3"><div class="card-header"><h5 class="card-title mb-0">Riwayat Pergerakan Stok</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Waktu</th><th>Bahan</th><th>Tipe</th><th>Jumlah</th><th>Batch</th></tr></thead><tbody>@forelse($movements as $row)<tr><td>{{ $row->occurred_at?->format('d/m/Y H:i') }}</td><td>{{ $row->asset?->asset_name ?? '-' }}</td><td>{{ $row->movement_type }}</td><td>{{ $row->quantity }}</td><td>{{ $row->batch?->batch_code ?? '-' }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-3">Belum ada pergerakan stok.</td></tr>@endforelse</tbody></table></div></div></div>
</div>
@endsection
