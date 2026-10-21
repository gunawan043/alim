@extends('layouts.master')
@section('title', 'Evaluasi Laboratorium')
@section('css') @include('shared._styles') @include('lab._styles') @endsection
@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp
@component('components.breadcrumb') @slot('li_1') Laboratorium @endslot @slot('title') Evaluasi Akhir Semester @endslot @endcomponent
@include('lab._nav')
<div class="row g-3">
    <div class="col-xl-4"><div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-file-chart-line me-1 text-primary"></i>Evaluasi</h5></div><div class="card-body"><form method="POST" action="{{ route('lab.evaluation.store', ['userId' => $userId]) }}">@csrf<input type="hidden" name="lab_id" value="{{ $lab?->id }}">
        <div class="mb-2"><label class="form-label">Tanggal Evaluasi</label><input type="date" name="evaluation_date" class="form-control" value="{{ today()->toDateString() }}" required></div>
        <div class="mb-2"><label class="form-label">Temuan</label><textarea name="findings" class="form-control" rows="3" required></textarea></div>
        <div class="mb-2"><label class="form-label">Rekomendasi</label><textarea name="recommendations" class="form-control" rows="3"></textarea></div>
        <div class="mb-2"><label class="form-label">Tindak Lanjut</label><textarea name="follow_up" class="form-control" rows="2"></textarea></div>
        <button class="btn btn-primary w-100" @disabled(!$lab)><i class="ri-save-line me-1"></i>Simpan Evaluasi</button>
    </form></div></div></div>
    <div class="col-xl-8"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Riwayat Evaluasi</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover table-freeze lab-table mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Temuan</th><th>Rekomendasi</th><th>Status</th></tr></thead><tbody>
    @forelse($evaluations as $row)<tr><td>{{ $row->evaluation_date?->format('d/m/Y') }}</td><td>{{ \Illuminate\Support\Str::limit($row->findings,80) }}</td><td>{{ \Illuminate\Support\Str::limit($row->recommendations,60) ?: '-' }}</td><td><span class="badge bg-success-subtle text-success">{{ $row->status }}</span></td></tr>
    @empty<tr><td colspan="4" class="text-center text-muted py-4">Belum ada evaluasi.</td></tr>@endforelse
    </tbody></table></div></div>@if($evaluations instanceof \Illuminate\Pagination\LengthAwarePaginator && $evaluations->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $evaluations])</div>@endif</div></div>
</div>
@endsection
