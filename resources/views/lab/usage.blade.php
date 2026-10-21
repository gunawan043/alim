@extends('layouts.master')
@section('title', 'Izin Penggunaan Laboratorium')
@section('css') @include('shared._styles') @include('lab._styles') @endsection
@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp
@component('components.breadcrumb') @slot('li_1') Laboratorium @endslot @slot('title') Izin Penggunaan @endslot @endcomponent
@include('lab._nav')
@if(! $lab)<div class="alert alert-warning">Buat profil laboratorium terlebih dahulu.</div>@endif
<div class="row g-3">
    <div class="col-xl-4"><div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-file-shield-2-line me-1 text-primary"></i>Ajukan Izin</h5></div><div class="card-body"><form method="POST" action="{{ route('lab.usage.store', ['userId' => $userId]) }}">@csrf<input type="hidden" name="lab_id" value="{{ $lab?->id }}">
        <div class="mb-2"><label class="form-label">Nama Pemohon</label><input class="form-control" value="{{ auth()->user()->name }}" disabled></div>
        <div class="mb-2"><label class="form-label">Jabatan/Kelas</label><input name="applicant_position_class" class="form-control" placeholder="Guru / Kelas"></div>
        <div class="mb-2"><label class="form-label">Mata Pelajaran</label><select name="subject_id" class="form-select"><option value="">— pilih —</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select></div>
        <div class="mb-2"><label class="form-label">Hari/Tanggal Penggunaan</label><input type="date" name="usage_date" class="form-control" value="{{ today()->toDateString() }}" required></div>
        <div class="mb-2"><label class="form-label">Jenis Kegiatan</label><select name="activity_type" class="form-select"><option value="praktikum">Praktikum</option><option value="penelitian">Penelitian</option><option value="lainnya">Kegiatan Lain</option></select></div>
        <div class="mb-2"><label class="form-label">Nama Kegiatan Lain</label><input name="other_activity" class="form-control"></div>
        <div class="mb-2"><label class="form-label">Jumlah Peserta</label><input type="number" name="participants_count" class="form-control" min="0" value="0"></div>
        <div class="mb-2"><label class="form-label">Catatan Khusus / Risiko</label><textarea name="risk_notes" class="form-control" rows="3"></textarea></div>
        <button class="btn btn-primary w-100" @disabled(!$lab)><i class="ri-send-plane-line me-1"></i>Ajukan Izin</button>
    </form></div></div></div>
    <div class="col-xl-8"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Permohonan</h5></div><div class="card-body p-0"><div class="table-container"><table class="table table-hover table-freeze lab-table mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Pemohon</th><th>Mapel</th><th>Jenis</th><th>Peserta</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    @forelse($requests as $row)<tr><td>{{ $row->usage_date?->format('d/m/Y') }}</td><td>{{ $row->applicant?->name ?? '-' }}</td><td>{{ $row->subject?->name ?? '-' }}</td><td>{{ $row->activity_type }}@if($row->other_activity) — {{ $row->other_activity }}@endif</td><td>{{ $row->participants_count }}</td><td><span class="badge bg-info-subtle text-info">{{ $row->status }}</span></td><td>@if($row->status === 'submitted')<form method="POST" action="{{ route('lab.usage.approve', ['userId' => $userId, 'usage' => $row->id]) }}" class="d-inline">@csrf<button class="btn btn-sm btn-soft-success"><i class="ri-check-line"></i></button></form><form method="POST" action="{{ route('lab.usage.reject', ['userId' => $userId, 'usage' => $row->id]) }}" class="d-inline">@csrf<input type="hidden" name="rejection_reason" value="Ditolak koordinator"><button class="btn btn-sm btn-soft-danger"><i class="ri-close-line"></i></button></form>@endif</td></tr>
    @empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada permohonan.</td></tr>@endforelse
    </tbody></table></div></div>@if($requests instanceof \Illuminate\Pagination\LengthAwarePaginator && $requests->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $requests])</div>@endif</div></div>
</div>
@endsection
