@extends('layouts.master')
@section('title', 'Detail Kegiatan Laboratorium')
@section('css') @include('shared._styles') @include('lab._styles') @endsection
@section('content')
@php
    $userId = request()->route('userId') ?? auth()->id();
    $presensiHadir = $classAttendance->where('status', 'hadir')->count();
    $presensiTotal = $classAttendance->count();
    $presensiAbsen = max(0, $presensiTotal - $presensiHadir);
    $presensiAbsenNames = $classAttendance->where('status', '!=', 'hadir')->map(fn ($row) => $row->student?->name)->filter()->implode("\n");
    $totalPeserta = $presensiTotal ?: (int) $activity->participants_count;
    $hadirPeserta = $presensiTotal ? $presensiHadir : (int) $activity->participants_count;
    $absenPeserta = $presensiTotal ? $presensiAbsen : 0;
@endphp
@component('components.breadcrumb') @slot('li_1') Laboratorium @endslot @slot('li_2') <a href="{{ route('lab.activity.index', ['userId' => $userId, 'lab_id' => $lab?->id]) }}">Kegiatan</a> @endslot @slot('title') Detail Kegiatan @endslot @endcomponent
@include('lab._nav')
<div class="row g-3">
    <div class="col-xl-7"><div class="card h-100"><div class="card-header d-flex justify-content-between"><h5 class="card-title mb-0">{{ $activity->title }}</h5>@if($activity->report)<a href="{{ route('lab.activity.report.print', ['userId' => $userId, 'activity' => $activity->id]) }}" target="_blank" class="btn btn-sm btn-danger"><i class="ri-printer-line me-1"></i>Cetak Berita Acara</a>@endif</div><div class="card-body">
        <table class="table table-sm"><tr><th style="width:160px">Hari/Tanggal</th><td>{{ $activity->activity_date?->translatedFormat('l, d F Y') }}</td></tr><tr><th>Waktu</th><td>{{ $activity->start_time ? substr($activity->start_time,0,5) : '-' }} – {{ $activity->end_time ? substr($activity->end_time,0,5) : '-' }}</td></tr><tr><th>Mata Pelajaran</th><td>{{ $activity->subject?->name ?? '-' }}</td></tr><tr><th>Kelas</th><td>{{ $activity->studyGroup?->name ?? '-' }}</td></tr><tr><th>Guru</th><td>{{ $activity->teacher?->name ?? '-' }}</td></tr><tr><th>Jumlah Peserta</th><td>{{ $activity->participants_count }}</td></tr><tr><th>Status</th><td><span class="badge bg-info-subtle text-info">{{ $activity->status }}</span></td></tr><tr><th>Risiko/Catatan</th><td>{{ $activity->risk_notes ?: '-' }}</td></tr></table>
        <h6 class="mt-3">Kebutuhan Alat/Bahan</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Nama</th><th>Jumlah</th><th>Status</th></tr></thead><tbody>@forelse($activity->items as $item)<tr><td>{{ $item->item_name }}</td><td>{{ $item->quantity }} {{ $item->unit }}</td><td>{{ $item->readiness_status }}</td></tr>@empty<tr><td colspan="3" class="text-muted">Belum ada item.</td></tr>@endforelse</tbody></table></div>
    </div></div></div>
    <div class="col-xl-5"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Berita Acara Praktikum</h5></div><div class="card-body"><form method="POST" action="{{ route('lab.activity.report.store', ['userId' => $userId, 'activity' => $activity->id]) }}">@csrf
        <div class="mb-2"><label class="form-label">Hari/Tanggal</label><input type="date" name="report_date" class="form-control" value="{{ $activity->activity_date?->toDateString() }}" required></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Waktu Mulai</label><input type="time" name="time_start" class="form-control" value="{{ $activity->start_time }}"></div><div class="col-6"><label class="form-label">Waktu Selesai</label><input type="time" name="time_end" class="form-control" value="{{ $activity->end_time }}"></div></div>
        <div class="mb-2"><label class="form-label">Judul Praktikum</label><input name="title" class="form-control" value="{{ $activity->title }}" required></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Mata Pelajaran</label><input name="subject_name" class="form-control" value="{{ $activity->subject?->name }}"></div><div class="col-6"><label class="form-label">Kelas</label><input name="class_name" class="form-control" value="{{ $activity->studyGroup?->name }}"></div></div>
        <div class="row g-2 mb-2"><div class="col-4"><label class="form-label">Total</label><input type="number" name="participants_total" class="form-control" min="0" value="{{ $totalPeserta }}" required></div><div class="col-4"><label class="form-label">Hadir</label><input type="number" name="participants_present" class="form-control" min="0" value="{{ $hadirPeserta }}"></div><div class="col-4"><label class="form-label">Absen</label><input type="number" name="participants_absent" class="form-control" min="0" value="{{ $absenPeserta }}"></div></div><small class="text-muted d-block mb-2">Sumber presensi kelas: {{ $presensiTotal ? $presensiTotal.' peserta' : 'belum tersedia, isi manual' }}</small>
        <div class="mb-2"><label class="form-label">Nama Peserta Tidak Hadir</label><textarea name="absent_names" class="form-control" rows="2" placeholder="Satu nama per baris">{{ $presensiAbsenNames }}</textarea></div>
        <div class="mb-2"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
        <button class="btn btn-primary w-100"><i class="ri-save-line me-1"></i>Simpan Berita Acara</button>
    </form></div></div></div>
</div>
@endsection
