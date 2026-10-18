@extends('layouts.master')
@section('title', 'Setoran Tahfizh')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Operasional @endslot @slot('title') Setoran Tahfizh @endslot @endcomponent
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-file-add-line text-primary me-1"></i>Setoran Tahfizh</h5><p class="text-muted mb-0 small">Input harian guru halaqah, verifikasi koordinator/departemen.</p></div>
            <div class="d-flex gap-2"><a href="{{ route('user.tahfidz.halaqah.index', ['userId'=>$userId]) }}" class="btn btn-soft-info"><i class="ri-team-line me-1"></i>Halaqah</a><a href="{{ route('user.tahfidz.setoran.create', ['userId'=>$userId,'group_id'=>request('group_id')]) }}" class="btn btn-success"><i class="ri-add-line me-1"></i>Input Setoran</a></div>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small">Halaqah</label><select name="group_id" class="form-select" onchange="this.form.submit()"><option value="">Semua</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected(request('group_id')===$group->id)>{{ $group->name }} — {{ $group->school?->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label small">Dari</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
                <div class="col-md-3"><label class="form-label small">Sampai</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
                <div class="col-md-2"><label class="form-label small">Verifikasi</label><select name="verification" class="form-select" onchange="this.form.submit()"><option value="">Semua</option><option value="pending" @selected(request('verification')==='pending')>Pending</option><option value="verified" @selected(request('verification')==='verified')>Verified</option></select></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Tanggal</th><th>Santri</th><th>Halaqah</th><th>Materi</th><th>Jenis</th><th>Nilai</th><th>Verifikasi</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($setorans as $setoran)
                        <tr>
                            <td>{{ $setoran->setoran_date?->format('d M Y') }}</td>
                            <td><div class="fw-medium">{{ $setoran->student?->name }}</div><div class="text-muted small">{{ $setoran->student?->nis }}</div></td>
                            <td>{{ $setoran->group?->name }}</td>
                            <td class="small">{{ $setoran->juz ? 'Juz '.$setoran->juz : '' }} {{ $setoran->surah_start_name }}{{ $setoran->ayat_start ? ':'.$setoran->ayat_start : '' }}{{ $setoran->surah_end_name ? ' — '.$setoran->surah_end_name : '' }}{{ $setoran->ayat_end ? ':'.$setoran->ayat_end : '' }}</td>
                            <td>{{ ucfirst($setoran->setoran_type) }}</td>
                            <td>{{ $setoran->nilai_setoran ?? '—' }}</td>
                            <td><span class="badge {{ $setoran->verification_status==='verified' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ $setoran->verification_status }}</span>@if($setoran->verifier)<div class="text-muted" style="font-size:10px;">{{ $setoran->verifier->name }}</div>@endif</td>
                            <td class="text-end">
                                @if($setoran->verification_status !== 'verified')
                                    <a href="{{ route('user.tahfidz.setoran.edit', ['userId'=>$userId,'id'=>$setoran->id]) }}" class="btn btn-sm btn-outline-warning"><i class="ri-edit-line"></i></a>
                                    @if($canVerify)
                                        <form method="POST" action="{{ route('user.tahfidz.setoran.verify', ['userId'=>$userId,'id'=>$setoran->id]) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success" title="Verifikasi"><i class="ri-check-double-line"></i></button></form>
                                    @endif
                                    <form method="POST" action="{{ route('user.tahfidz.setoran.destroy', ['userId'=>$userId,'id'=>$setoran->id]) }}" class="d-inline" onsubmit="return confirm('Hapus setoran ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button></form>
                                @else
                                    <span class="text-muted small">Terverifikasi</span>
                                @endif
                            </td>
                        </tr>
                    @empty<tr><td colspan="8" class="text-center text-muted py-4">Belum ada setoran.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        @if($setorans->hasPages())<div class="card-body border-top">{{ $setorans->links() }}</div>@endif
    </div>
@endsection
