@extends('layouts.master')
@section('title', 'Kegiatan Laboratorium')
@section('css') @include('shared._styles') @include('lab._styles') @endsection
@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp
@component('components.breadcrumb') @slot('li_1') Laboratorium @endslot @slot('title') Kegiatan & Praktikum @endslot @endcomponent
@include('lab._nav')
@if(! $lab)<div class="alert alert-warning">Buat profil laboratorium terlebih dahulu.</div>@endif
<div class="row g-3">
    <div class="col-xl-4"><div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-add-circle-line me-1 text-primary"></i>Rencana Kegiatan</h5></div><div class="card-body"><form method="POST" action="{{ route('lab.activity.store', ['userId' => $userId]) }}">@csrf<input type="hidden" name="lab_id" value="{{ $lab?->id }}">
        <div class="mb-2"><label class="form-label">Judul Praktikum</label><input name="title" class="form-control" required></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Tanggal</label><input type="date" name="activity_date" class="form-control" value="{{ today()->toDateString() }}" required></div><div class="col-3"><label class="form-label">Mulai</label><input type="time" name="start_time" class="form-control"></div><div class="col-3"><label class="form-label">Selesai</label><input type="time" name="end_time" class="form-control"></div></div>
        <div class="mb-2"><label class="form-label">Mata Pelajaran</label><select name="subject_id" class="form-select"><option value="">— pilih —</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select></div>
        <div class="mb-2"><label class="form-label">Kelas</label><select name="study_group_id" class="form-select"><option value="">— pilih —</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></div>
        <div class="mb-2"><label class="form-label">Jumlah Peserta</label><input type="number" name="participants_count" class="form-control" min="0" value="0"></div>
        <div class="mb-2"><label class="form-label">Risiko/Catatan</label><textarea name="risk_notes" class="form-control" rows="2"></textarea></div>
        <button class="btn btn-primary w-100" @disabled(!$lab)><i class="ri-save-line me-1"></i>Buat Kegiatan</button>
    </form></div></div></div>
    <div class="col-xl-8"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Daftar Kegiatan</h5></div><div class="card-body p-0"><div class="table-container"><table class="table table-hover table-freeze lab-table mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Judul</th><th>Mapel</th><th>Kelas</th><th>Peserta</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    @forelse($activities as $row)<tr><td>{{ $row->activity_date?->format('d/m/Y') }}</td><td class="fw-semibold">{{ $row->title }}</td><td>{{ $row->subject?->name ?? '-' }}</td><td>{{ $row->studyGroup?->name ?? '-' }}</td><td>{{ $row->participants_count }}</td><td><span class="badge bg-info-subtle text-info">{{ $row->status }}</span></td><td><a href="{{ route('lab.activity.show', ['userId' => $userId, 'activity' => $row->id]) }}" class="btn btn-sm btn-soft-primary"><i class="ri-eye-line"></i></a></td></tr>
    @empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada kegiatan.</td></tr>@endforelse
    </tbody></table></div></div>@if($activities instanceof \Illuminate\Pagination\LengthAwarePaginator && $activities->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $activities])</div>@endif</div></div>
</div>
@endsection
