@extends('layouts.master')
@section('title', 'Jadwal Laboratorium')
@section('css') @include('shared._styles') @include('lab._styles') @endsection
@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); $days = [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu']; @endphp
@component('components.breadcrumb') @slot('li_1') Laboratorium @endslot @slot('title') Jadwal Penggunaan @endslot @endcomponent
@include('lab._nav')
@if(! $lab)<div class="alert alert-warning">Buat profil laboratorium terlebih dahulu.</div>@endif
<div class="row g-3">
    <div class="col-xl-4"><div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-calendar-schedule-line me-1 text-primary"></i>Tambah Jadwal</h5></div><div class="card-body"><form method="POST" action="{{ route('lab.schedule.store', ['userId' => $userId]) }}">@csrf<input type="hidden" name="lab_id" value="{{ $lab?->id }}">
        <div class="mb-2"><label class="form-label">Hari</label><select name="day_of_week" class="form-select" required>@foreach($days as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
        <div class="mb-2"><label class="form-label">Jam Pelajaran ke-</label><input type="number" name="slot_index" class="form-control" min="1" max="20" value="1" required></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Mulai</label><input type="time" name="start_time" class="form-control"></div><div class="col-6"><label class="form-label">Selesai</label><input type="time" name="end_time" class="form-control"></div></div>
        <div class="mb-2"><label class="form-label">Kelas</label><select name="study_group_id" class="form-select"><option value="">— pilih —</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></div>
        <div class="mb-2"><label class="form-label">Mata Pelajaran</label><select name="subject_id" class="form-select"><option value="">— pilih —</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select></div>
        <div class="mb-2"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
        <button class="btn btn-primary w-100" @disabled(!$lab)><i class="ri-save-line me-1"></i>Simpan Jadwal</button>
    </form></div></div></div>
    <div class="col-xl-8"><div class="card"><div class="card-header d-flex justify-content-between"><h5 class="card-title mb-0">Jadwal {{ $lab?->name }}</h5><a href="{{ route('lab.schedule.print', ['userId' => $userId, 'lab_id' => $lab?->id]) }}" target="_blank" class="btn btn-sm btn-danger"><i class="ri-printer-line me-1"></i>Cetak</a></div><div class="card-body p-0"><div class="table-container"><table class="table table-bordered table-freeze lab-table mb-0"><thead class="table-light"><tr><th>No</th><th>Jam Pelajaran</th><th>Kelas</th>@foreach($days as $day)<th>{{ strtoupper($day) }}</th>@endforeach></tr></thead><tbody>
    @for($slot=1;$slot<=8;$slot++)<tr><td>{{ $slot }}</td><td>Jam ke-{{ $slot }}</td><td>@php $first = $slots->firstWhere('slot_index',$slot); @endphp{{ $first?->studyGroup?->name ?? '-' }}</td>@foreach($days as $dayNo=>$dayName)@php $cell = $slots->first(fn($s)=>$s->day_of_week===$dayNo && $s->slot_index===$slot); @endphp<td>{{ $cell?->studyGroup?->name ?? '' }}@if($cell?->subject)<br><small class="text-muted">{{ $cell->subject->name }}</small>@endif</td>@endforeach</tr>@endfor
    </tbody></table></div></div></div></div>
</div>
<div class="card mt-3"><div class="card-body"><strong>Guru Pembimbing:</strong> {{ $lab?->coordinator?->name ?? auth()->user()->name }} &nbsp; <span class="text-muted">Semester {{ $semester }} · {{ $year?->name }}</span></div></div>
@endsection
