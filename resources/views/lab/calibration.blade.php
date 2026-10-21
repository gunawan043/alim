@extends('layouts.master')
@section('title', 'Kalibrasi Laboratorium')
@section('css') @include('shared._styles') @include('lab._styles') @endsection
@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp
@component('components.breadcrumb') @slot('li_1') Laboratorium @endslot @slot('title') Jadwal Kalibrasi @endslot @endcomponent
@include('lab._nav')
@if(! $lab)<div class="alert alert-warning">Buat profil laboratorium terlebih dahulu.</div>@endif
<div class="row g-3">
    <div class="col-xl-4"><div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-timer-line me-1 text-primary"></i>Catat Kalibrasi</h5></div><div class="card-body"><form method="POST" action="{{ route('lab.calibration.store', ['userId' => $userId]) }}">@csrf<input type="hidden" name="lab_id" value="{{ $lab?->id }}">
        <div class="mb-2"><label class="form-label">Alat</label><select name="asset_id" class="form-select" required><option value="">— pilih alat —</option>@foreach($assets as $asset)<option value="{{ $asset->id }}">{{ $asset->asset_name }}</option>@endforeach</select></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Kalibrasi Terakhir</label><input type="date" name="last_calibration_date" class="form-control"></div><div class="col-6"><label class="form-label">Berikutnya</label><input type="date" name="next_calibration_date" class="form-control" placeholder="Otomatis"></div></div>
        <div class="mb-2"><label class="form-label">Jenis Kalibrasi</label><select name="calibration_type" class="form-select"><option value="I">I — Internal</option><option value="E">E — Eksternal</option></select></div>
        <div class="mb-2"><label class="form-label">Lembaga/Petugas</label><input name="institution_name" class="form-control" placeholder="Nama lembaga kalibrasi"></div>
        <div class="mb-2"><label class="form-label">Hasil</label><textarea name="result" class="form-control" rows="2"></textarea></div>
        <div class="mb-2"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
        <button class="btn btn-primary w-100" @disabled(!$lab)><i class="ri-save-line me-1"></i>Simpan</button>
    </form></div></div></div>
    <div class="col-xl-8"><div class="card"><div class="card-header d-flex justify-content-between"><h5 class="card-title mb-0">Daftar Kalibrasi</h5><a href="{{ route('lab.calibration.print', ['userId' => $userId, 'lab_id' => $lab?->id]) }}" target="_blank" class="btn btn-sm btn-danger"><i class="ri-printer-line me-1"></i>Cetak</a></div><div class="card-body p-0"><div class="table-container"><table class="table table-hover table-freeze lab-table mb-0"><thead class="table-light"><tr><th>No</th><th>Nama Alat</th><th>Jml</th><th>Terakhir</th><th>Berikutnya</th><th>Jenis</th><th>Status</th></tr></thead><tbody>
    @forelse($calibrations as $i => $row)<tr><td>{{ $calibrations instanceof \Illuminate\Pagination\LengthAwarePaginator ? $calibrations->firstItem() + $i : $i + 1 }}</td><td>{{ $row->asset?->asset_name ?? '-' }}</td><td>{{ $row->asset?->quantity ?? 1 }}</td><td>{{ $row->last_calibration_date?->format('d/m/Y') ?? '-' }}</td><td>{{ $row->next_calibration_date?->format('d/m/Y') ?? '-' }}</td><td><span class="badge bg-info-subtle text-info">{{ $row->calibration_type }}</span></td><td><span class="badge bg-{{ $row->status === 'OD' ? 'danger' : 'success' }}-subtle text-{{ $row->status === 'OD' ? 'danger' : 'success' }}">{{ $row->status }}</span></td></tr>
    @empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada data kalibrasi.</td></tr>@endforelse
    </tbody></table></div></div>@if($calibrations instanceof \Illuminate\Pagination\LengthAwarePaginator && $calibrations->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $calibrations])</div>@endif</div></div>
</div>
@endsection
