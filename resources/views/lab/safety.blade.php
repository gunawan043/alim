@extends('layouts.master')
@section('title', 'Inspeksi Keselamatan Laboratorium')
@section('css') @include('shared._styles') @include('lab._styles') @endsection
@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); $aspects = ['Kondisi umum laboratorium','Kebersihan, ventilasi, dan pencahayaan','Ketersediaan dan kondisi APD','Keamanan penyimpanan bahan kimia','Keadaan alat pemadam kebakaran','Kesiapan kotak P3K','Kondisi peralatan laboratorium','Kepatuhan terhadap prosedur keselamatan']; @endphp
@component('components.breadcrumb') @slot('li_1') Laboratorium @endslot @slot('title') Inspeksi Keselamatan @endslot @endcomponent
@include('lab._nav')
@if(! $lab)<div class="alert alert-warning">Buat profil laboratorium terlebih dahulu.</div>@endif
<div class="row g-3">
    <div class="col-xl-6"><div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-shield-check-line me-1 text-primary"></i>Catatan Inspeksi</h5></div><div class="card-body"><form method="POST" action="{{ route('lab.safety.store', ['userId' => $userId]) }}">@csrf<input type="hidden" name="lab_id" value="{{ $lab?->id }}">
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Tanggal Inspeksi</label><input type="date" name="inspection_date" class="form-control" value="{{ today()->toDateString() }}" required></div><div class="col-6"><label class="form-label">Jabatan Inspektor</label><input name="inspector_position" class="form-control" value="Koordinator Laboratorium"></div></div>
        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>No</th><th>Aspek</th><th style="width:100px">Status</th><th>Keterangan</th></tr></thead><tbody>@foreach($aspects as $i=>$aspect)<tr><td>{{ $i+1 }}<input type="hidden" name="items[{{ $i }}][source_no]" value="{{ $i+1 }}"><input type="hidden" name="items[{{ $i }}][aspect]" value="{{ $aspect }}"></td><td>{{ $aspect }}</td><td><select name="items[{{ $i }}][status]" class="form-select form-select-sm"><option value="Baik">Baik</option><option value="Tidak Baik">Tidak Baik</option></select></td><td><input name="items[{{ $i }}][notes]" class="form-control form-control-sm"></td></tr>@endforeach</tbody></table></div>
        <div class="mb-2"><label class="form-label">Temuan</label><textarea name="findings" class="form-control" rows="2"></textarea></div><div class="mb-2"><label class="form-label">Rekomendasi</label><textarea name="recommendations" class="form-control" rows="2"></textarea></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Tanggal Tindak Lanjut</label><input type="date" name="follow_up_date" class="form-control"></div><div class="col-6"><label class="form-label">Penanggung Jawab</label><input name="follow_up_person" class="form-control"></div></div>
        <div class="mb-2"><label class="form-label">Tindakan Perbaikan</label><textarea name="follow_up_action" class="form-control" rows="2"></textarea></div>
        <button class="btn btn-primary w-100" @disabled(!$lab)><i class="ri-save-line me-1"></i>Simpan Inspeksi</button>
    </form></div></div></div>
    <div class="col-xl-6"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Riwayat Inspeksi</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover table-freeze lab-table mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Inspektor</th><th>Status</th><th>Temuan</th><th>Aksi</th></tr></thead><tbody>
    @forelse($inspections as $row)<tr><td>{{ $row->inspection_date?->format('d/m/Y') }}</td><td>{{ $row->inspector?->name ?? '-' }}</td><td><span class="badge bg-{{ $row->status === 'closed' ? 'success' : 'warning' }}-subtle text-{{ $row->status === 'closed' ? 'success' : 'warning' }}">{{ $row->status }}</span></td><td>{{ \Illuminate\Support\Str::limit($row->findings, 60) ?: '-' }}</td><td><a href="{{ route('lab.safety.print', ['userId' => $userId, 'inspection' => $row->id]) }}" target="_blank" class="btn btn-sm btn-soft-secondary"><i class="ri-printer-line"></i></a></td></tr>
    @empty<tr><td colspan="5" class="text-center text-muted py-4">Belum ada inspeksi.</td></tr>@endforelse
    </tbody></table></div></div>@if($inspections instanceof \Illuminate\Pagination\LengthAwarePaginator && $inspections->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $inspections])</div>@endif</div></div>
</div>
@endsection
