@extends('layouts.master')
@section('title', 'Anggota Perpustakaan')
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $quickBase = ['userId' => $userId, 'unit_id' => $unit->id];
        $filterAction = route('user.perpustakaan.members.index');
        $filterHidden = ['unit_id' => $unit->id];
        $filterFields = [
            ['name' => 'search', 'label' => 'Pencarian', 'type' => 'text', 'placeholder' => 'Nama, NIS, atau nomor anggota'],
            ['name' => 'member_type', 'label' => 'Jenis Anggota', 'type' => 'select', 'options' => ['' => 'Semua jenis', 'student' => 'Santri', 'gtk' => 'GTK']],
        ];
        $filterControls = [
            ['type' => 'text', 'name' => 'search', 'label' => 'Pencarian', 'value' => request('search'), 'placeholder' => 'Nama, NIS, atau nomor anggota'],
            ['type' => 'select', 'name' => 'member_type', 'label' => 'Jenis Anggota', 'options' => ['' => 'Semua jenis', 'student' => 'Santri', 'gtk' => 'GTK']],
        ];
        $quickFilters = [
            ['label' => 'Semua Anggota', 'url' => route('user.perpustakaan.members.index', $quickBase), 'icon' => 'ri-team-line', 'active' => ! request('member_type')],
            ['label' => 'Santri', 'url' => route('user.perpustakaan.members.index', $quickBase + ['member_type' => 'student']), 'icon' => 'ri-user-star-line', 'active' => request('member_type') === 'student'],
            ['label' => 'GTK', 'url' => route('user.perpustakaan.members.index', $quickBase + ['member_type' => 'gtk']), 'icon' => 'ri-user-settings-line', 'active' => request('member_type') === 'gtk'],
        ];
    @endphp
    @include('perpustakaan._styles')
    @component('components.breadcrumb') @slot('li_1') Perpustakaan @endslot @slot('li_2') Keanggotaan @endslot @slot('title') Anggota & Kartu @endslot @endcomponent
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="row g-2 mb-3 library-stats">
        @foreach([['Total Anggota', $stats['total'], 'ri-team-line', 'primary', 'Santri dan GTK terdaftar'], ['Aktif', $stats['active'], 'ri-user-follow-line', 'success', 'Kartu masih berlaku'], ['Santri', $stats['student'], 'ri-user-star-line', 'info', 'Anggota santri'], ['GTK', $stats['gtk'], 'ri-user-settings-line', 'warning', 'Anggota guru/karyawan']] as [$label, $value, $icon, $color, $hint])
            @include('perpustakaan._stat-card', compact('label', 'value', 'icon', 'color', 'hint'))
        @endforeach
    </div>
    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-id-card-line text-primary me-1"></i>Anggota {{ $unit->name }}</h5><p class="text-muted mb-0 small">Kartu ID-1 · kartu hilang harus diganti · QR tidak menggantikan kartu fisik.</p></div>
            <div class="d-flex gap-2"><a href="{{ route('user.perpustakaan.imports.members.form', ['userId'=>$userId, 'unit_id'=>$unit->id]) }}" class="btn btn-success btn-sm"><i class="ri-upload-cloud-2-line me-1"></i>Import</a>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#memberModal" onclick="openMemberCreate()"><i class="ri-user-add-line me-1"></i>Daftar Anggota</button></div>
        </div>
        @include('perpustakaan._filter-panel')
        @include('perpustakaan._quick-filters')
        <form method="GET" action="{{ route('user.perpustakaan.members.cards',['userId'=>$userId]) }}" target="_blank"><input type="hidden" name="library_unit_id" value="{{ $unit->id }}">
        <div class="px-3 py-2 bg-light border-bottom d-flex gap-2 align-items-center"><span class="text-muted small"><i class="ri-printer-line me-1"></i>Cetak kartu:</span><button class="btn btn-sm btn-outline-primary">Cetak kartu terpilih</button><span class="text-muted small">Centang anggota.</span></div>
        <div class="table-container"><table class="table table-hover library-table align-middle mb-0 table-freeze"><thead class="table-light"><tr><th style="width:35px"></th><th>Nomor Anggota</th><th>Nama</th><th>Jenis</th><th>Kartu</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
        @forelse($members as $member)
            @php $card=$member->cards->first(); @endphp
            <tr><td><input type="checkbox" class="form-check-input" name="member_ids[]" value="{{ $member->id }}"></td><td><code>{{ $member->member_number }}</code></td><td><div class="fw-medium">{{ $member->display_name }}</div><div class="text-muted small">{{ $member->student?->nis ?? $member->user?->name }}</div></td><td><span class="badge bg-light text-dark">{{ strtoupper($member->member_type) }}</span></td><td class="small">{{ $card?->card_number ?? '—' }}</td><td><span class="badge {{ $member->status==='active'?'bg-success-subtle text-success':'bg-warning-subtle text-warning' }}">{{ ucfirst($member->status) }}</span></td><td class="text-end"><div class="btn-group btn-group-sm"><a href="{{ route('user.perpustakaan.members.card',['userId'=>$userId,'id'=>$member->id]) }}" target="_blank" class="btn btn-soft-primary" title="Cetak kartu"><i class="ri-printer-line"></i></a><form method="POST" action="{{ route('user.perpustakaan.members.replace-card',['userId'=>$userId,'id'=>$member->id]) }}" onsubmit="return confirm('Terbitkan kartu pengganti? Kartu lama akan dinonaktifkan.')">@csrf<button class="btn btn-soft-warning" title="Kartu hilang / pengganti"><i class="ri-refresh-line"></i></button></form></div></td></tr>
        @empty<tr><td colspan="7" class="text-center text-muted py-5">Belum ada anggota. Santri dan GTK harus mendaftar.</td></tr>@endforelse
        </tbody></table></div></form>
    </div>

    <div class="modal fade library-modal" id="memberModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><form method="POST" action="{{ route('user.perpustakaan.members.store',['userId'=>$userId]) }}">@csrf<input type="hidden" name="library_unit_id" value="{{ $unit->id }}"><div class="modal-header bg-primary-subtle"><h5 class="modal-title">Daftar Anggota</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-4"><label class="form-label">Jenis Anggota</label><select name="member_type" id="memberType" class="form-select" onchange="toggleMemberType()"><option value="student">Santri</option><option value="gtk">GTK</option></select></div><div class="col-md-8" id="studentWrap"><label class="form-label">Santri</label><select name="student_id" id="studentSelect" class="form-select"><option value="">— Pilih Santri —</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->nis ?? $student->nisn }} — {{ $student->name }} ({{ $student->currentClassHistory?->studyGroup?->name ?? '—' }})</option>@endforeach</select></div><div class="col-md-8 d-none" id="gtkWrap"><label class="form-label">GTK</label><select name="user_id" id="gtkSelect" class="form-select"><option value="">— Pilih GTK —</option>@foreach($gtkUsers as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div><div class="col-md-4"><label class="form-label">Nomor Anggota</label><input name="member_number" class="form-control" placeholder="Otomatis"></div><div class="col-md-4"><label class="form-label">Tanggal Daftar</label><input type="date" name="joined_at" class="form-control" value="{{ today()->toDateString() }}"></div></div><div class="alert alert-info py-2 mt-3 mb-0 small"><i class="ri-information-line me-1"></i>Kartu anggota aktif dibuat otomatis setelah pendaftaran.</div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan &amp; Terbitkan Kartu</button></div></form></div></div></div>
    @include('perpustakaan._filter-modal')
@endsection
@section('script')
<script>
function openMemberCreate(){document.getElementById('memberType').value='student';toggleMemberType();}
function toggleMemberType(){const t=document.getElementById('memberType').value;document.getElementById('studentWrap').classList.toggle('d-none',t!=='student');document.getElementById('gtkWrap').classList.toggle('d-none',t!=='gtk');document.getElementById('studentSelect').disabled=t!=='student';document.getElementById('gtkSelect').disabled=t!=='gtk';}
document.addEventListener('DOMContentLoaded',()=>{if(document.getElementById('memberType'))toggleMemberType();});
</script>
@endsection
