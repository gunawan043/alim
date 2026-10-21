@extends('layouts.master')
@section('title', 'Insiden Laboratorium')
@section('css') @include('shared._styles') @include('lab._styles') @endsection
@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp
@component('components.breadcrumb') @slot('li_1') Laboratorium @endslot @slot('title') Laporan Insiden @endslot @endcomponent
@include('lab._nav')
@if(! $lab)<div class="alert alert-warning">Buat profil laboratorium terlebih dahulu.</div>@endif
<div class="row g-3">
    <div class="col-xl-5"><div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-alert-line me-1 text-danger"></i>Laporan Insiden</h5></div><div class="card-body"><form method="POST" action="{{ route('lab.incident.store', ['userId' => $userId]) }}">@csrf<input type="hidden" name="lab_id" value="{{ $lab?->id }}">
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Tanggal</label><input type="date" name="incident_date" class="form-control" value="{{ today()->toDateString() }}" required></div><div class="col-6"><label class="form-label">Waktu</label><input type="time" name="incident_time" class="form-control"></div></div>
        <div class="mb-2"><label class="form-label">Jenis Insiden</label><select name="incident_type" class="form-select"><option value="cedera">Cedera</option><option value="kerusakan_alat">Kerusakan Alat</option><option value="tumpahan_bahan_kimia">Tumpahan Bahan Kimia</option><option value="lainnya">Lainnya</option></select></div>
        <div class="mb-2"><label class="form-label">Jenis Lainnya</label><input name="other_type" class="form-control"></div>
        <div class="mb-2"><label class="form-label">Kronologi</label><textarea name="chronology" class="form-control" rows="3" required></textarea></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Lokasi</label><input name="location" class="form-control"></div><div class="col-6"><label class="form-label">Jabatan Pelapor</label><input name="reporter_position" class="form-control"></div></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Nama Korban</label><input name="victim_name" class="form-control"></div><div class="col-6"><label class="form-label">Jenis Cedera</label><input name="injury_type" class="form-control"></div></div>
        <div class="mb-2"><label class="form-label">Pertolongan Pertama</label><textarea name="first_aid" class="form-control" rows="2"></textarea></div><div class="mb-2"><label class="form-label">Alat Terlibat</label><textarea name="equipment_involved" class="form-control" rows="2"></textarea></div>
        <div class="mb-2"><label class="form-label">Tindakan Segera</label><textarea name="immediate_action" class="form-control" rows="2"></textarea></div><div class="mb-2"><label class="form-label">Pencegahan</label><textarea name="prevention" class="form-control" rows="2"></textarea></div><div class="mb-2"><label class="form-label">Personel Penanganan</label><textarea name="personnel" class="form-control" rows="2"></textarea></div><div class="mb-2"><label class="form-label">Rujukan UKS (opsional)</label><select name="uks_referral_id" class="form-select"><option value="">— tidak dirujuk —</option>@foreach($referrals as $referral)<option value="{{ $referral->id }}">{{ $referral->referral_no ?: 'Rujukan' }} · {{ $referral->referred_at?->format('d/m/Y') }} · {{ $referral->facility_name }}</option>@endforeach</select><small class="text-muted">Hubungkan insiden dengan surat rujukan UKS yang sudah ada.</small></div>
        <button class="btn btn-danger w-100" @disabled(!$lab)><i class="ri-save-line me-1"></i>Simpan Laporan</button>
    </form></div></div></div>
    <div class="col-xl-7"><div class="card"><div class="card-header"><h5 class="card-title mb-0">Riwayat Insiden</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover table-freeze lab-table mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Jenis</th><th>Lokasi</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    @forelse($incidents as $row)<tr><td>{{ $row->incident_date?->format('d/m/Y') }}</td><td>{{ ucfirst(str_replace('_',' ',$row->incident_type)) }}</td><td>{{ $row->location ?: '-' }}</td><td><span class="badge bg-warning-subtle text-warning">{{ $row->status }}</span></td><td><a href="{{ route('lab.incident.show', ['userId' => $userId, 'incident' => $row->id]) }}" class="btn btn-sm btn-soft-primary"><i class="ri-eye-line"></i></a></td></tr>
    @empty<tr><td colspan="5" class="text-center text-muted py-4">Belum ada insiden.</td></tr>@endforelse
    </tbody></table></div></div>@if($incidents instanceof \Illuminate\Pagination\LengthAwarePaginator && $incidents->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $incidents])</div>@endif</div></div>
</div>
@endsection
