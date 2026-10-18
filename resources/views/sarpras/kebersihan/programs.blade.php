@extends('layouts.master')
@section('title') Program Kebersihan Tahunan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.kebersihan.index') }}">Kebersihan</a> @endslot
        @slot('title') Program Tahunan
    @endcomponent

    @php $months = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des']; @endphp

    <div class="card mb-3"><div class="card-body d-flex flex-wrap justify-content-between align-items-end gap-2">
        <form method="GET" action="{{ route('sarpras.kebersihan.programs') }}" class="d-flex gap-2 align-items-end"><div><label class="form-label small fw-semibold">Tahun Program</label><input type="number" name="year" class="form-control" value="{{ $year }}" min="2000" max="2100"></div><button class="btn btn-primary">Tampilkan</button></form>
        <div class="d-flex gap-2"><a href="{{ route('sarpras.kebersihan.index') }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Kembali</a>@if($canManage)<button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addProgramModal"><i class="ri-add-line me-1"></i>Tambah Program</button>@endif</div>
    </div></div>

    <div class="alert alert-light border small"><strong>Keterangan:</strong> <span class="badge bg-success-subtle text-success">✓</span> sudah terlaksana · <span class="badge bg-primary-subtle text-primary">○</span> direncanakan · <span class="text-muted">·</span> tidak dijadwalkan. Koordinator mencentang bulan pelaksanaan.</div>

    <div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-calendar-todo-line me-2 text-primary"></i>Program Kebersihan {{ $year }}</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0" style="min-width:1100px">
        <thead class="table-light"><tr><th style="min-width:260px">Program</th><th>Kategori</th><th>Frekuensi</th><th>Petugas</th>@foreach($months as $m)<th class="text-center" style="width:48px">{{ $m }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse($programs as $program)
            <tr>
                <td><div class="fw-medium">{{ $program->title }}</div><small class="text-muted">{{ $program->area?->name ?? $program->building?->building_name ?? '-' }}</small></td>
                <td class="small">{{ ucfirst(str_replace('_',' ', $program->category)) }}</td>
                <td class="small">{{ ucfirst($program->frequency) }}</td>
                <td class="small">{{ $program->assignee?->name ?? '-' }}</td>
                @foreach($months as $m => $label)
                    @php
                        $planned = in_array($m, $program->months ?? [], true);
                        $check = $program->checks->firstWhere('month', $m);
                        $done = (bool) ($check?->is_done);
                    @endphp
                    <td class="text-center p-1">
                        @if($canManage)
                            <form method="POST" action="{{ route('sarpras.kebersihan.program.check', $program->id) }}">
                                @csrf
                                <input type="hidden" name="year" value="{{ $year }}">
                                <input type="hidden" name="month" value="{{ $m }}">
                                <input type="hidden" name="is_done" value="{{ $done ? 0 : 1 }}">
                                <button type="submit" class="btn btn-sm p-1 {{ $done ? 'btn-success' : ($planned ? 'btn-outline-primary' : 'btn-light text-muted') }}" title="{{ $done ? 'Batalkan checklist' : 'Tandai terlaksana' }}" style="min-width:30px">{{ $done ? '✓' : ($planned ? '○' : '·') }}</button>
                            </form>
                        @else
                            <span class="badge {{ $done ? 'bg-success-subtle text-success' : ($planned ? 'bg-primary-subtle text-primary' : 'bg-light text-muted') }}">{{ $done ? '✓' : ($planned ? '○' : '·') }}</span>
                        @endif
                    </td>
                @endforeach
            </tr>
        @empty
            <x-empty-state colspan="17" title="Belum ada program" message="Tambahkan program kebersihan tahunan." />
        @endforelse
        </tbody>
    </table></div></div></div>

    @if($canManage)
        <div class="modal fade" id="addProgramModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><form method="POST" action="{{ route('sarpras.kebersihan.program.store') }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Program Tahunan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3">
            <div class="col-md-8"><label class="form-label">Nama Program <span class="text-danger">*</span></label><input name="title" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Kategori <span class="text-danger">*</span></label><select name="category" class="form-select" required>@foreach(App\Models\CleaningProgram::CATEGORY_OPTIONS as $category)<option value="{{ $category }}">{{ ucfirst(str_replace('_',' ', $category)) }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Area</label><select name="area_id" class="form-select"><option value="">— Tidak ada —</option>@foreach($areas as $area)<option value="{{ $area->id }}">{{ $area->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Gedung</label><select name="building_id" class="form-select"><option value="">— Tidak ada —</option>@foreach($buildings as $building)<option value="{{ $building->id }}">{{ $building->building_name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Frekuensi <span class="text-danger">*</span></label><select name="frequency" class="form-select" required>@foreach(App\Models\CleaningTask::FREQUENCY_OPTIONS as $frequency)<option value="{{ $frequency }}">{{ ucfirst($frequency) }}</option>@endforeach</select></div>
            <div class="col-md-8"><label class="form-label">Petugas</label><select name="assigned_to" class="form-select"><option value="">— Pilih —</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label">Bulan Rencana</label><div class="d-flex flex-wrap gap-2">@foreach($months as $m=>$label)<label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="months[]" value="{{ $m }}"><span class="form-check-label">{{ $label }}</span></label>@endforeach</div></div>
            <div class="col-md-6"><label class="form-label">Standar</label><input name="standard" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Metode</label><input name="method" class="form-control" placeholder="Visual / Tes fungsi"></div>
            <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
        </div></div><div class="modal-footer"><button class="btn btn-primary">Simpan Program</button></div></form></div></div>
    @endif
@endsection
