@extends('layouts.master')
@section('title', 'Ajukan Izin Laboratorium')
@section('css') @include('shared._styles') @include('lab._styles') @endsection
@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp
@component('components.breadcrumb') @slot('li_1') Laboratorium @endslot @slot('title') Ajukan Izin Penggunaan @endslot @endcomponent
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(! $lab)<div class="alert alert-warning">Laboratorium belum tersedia. Hubungi koordinator laboratorium.</div>@endif
<div class="row g-3">
    <div class="col-xl-5"><div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-file-shield-2-line me-1 text-primary"></i>Form Izin</h5></div><div class="card-body"><form method="POST" action="{{ route('lab.request.store', ['userId' => $userId]) }}">@csrf
        <div class="mb-2"><label class="form-label">Laboratorium</label><input class="form-control" value="{{ $lab?->name ?? '-' }}" disabled></div>
        <div class="mb-2"><label class="form-label">Nama Pemohon</label><input class="form-control" value="{{ auth()->user()->name }}" disabled></div>
        <div class="mb-2"><label class="form-label">Jabatan/Kelas</label><input name="applicant_position_class" class="form-control" placeholder="Guru / Kelas"></div>
        <div class="mb-2"><label class="form-label">Mata Pelajaran</label><select name="subject_id" class="form-select"><option value="">— pilih —</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select></div>
        <div class="mb-2"><label class="form-label">Hari/Tanggal Penggunaan</label><input type="date" name="usage_date" class="form-control" value="{{ today()->toDateString() }}" required></div>
        <div class="mb-2"><label class="form-label">Jenis Kegiatan</label><select name="activity_type" class="form-select"><option value="praktikum">Praktikum</option><option value="penelitian">Penelitian</option><option value="lainnya">Kegiatan Lain</option></select></div>
        <div class="mb-2"><label class="form-label">Nama Kegiatan Lain</label><input name="other_activity" class="form-control"></div>
        <div class="mb-2"><label class="form-label">Jumlah Peserta</label><input type="number" name="participants_count" class="form-control" min="0" value="0"></div>
        <div class="mb-2"><label class="form-label">Catatan Khusus / Risiko</label><textarea name="risk_notes" class="form-control" rows="3"></textarea></div>
        <button class="btn btn-primary w-100" @disabled(!$lab)><i class="ri-send-plane-line me-1"></i>Kirim Pengajuan</button>
    </form></div></div></div>
    <div class="col-xl-7"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Pengajuan Saya</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover lab-table mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Laboratorium</th><th>Jenis</th><th>Status</th></tr></thead><tbody>
    @forelse($myRequests as $row)<tr><td>{{ $row->usage_date?->format('d/m/Y') }}</td><td>{{ $row->lab?->name ?? '-' }}</td><td>{{ $row->activity_type }}</td><td><span class="badge bg-info-subtle text-info">{{ $row->status }}</span></td></tr>
    @empty<tr><td colspan="4" class="text-center text-muted py-4">Belum ada pengajuan.</td></tr>@endforelse
    </tbody></table></div></div></div></div>
</div>
@endsection
