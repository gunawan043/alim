@extends('layouts.master')
@section('title') Rekap Checklist Pemeliharaan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.checklist.index') }}">Checklist</a> @endslot
        @slot('title') Rekap Checklist
    @endcomponent

    <x-sarpras-stats :items="[
        ['label'=>'Total Checklist','value'=>$stats['total'],'tone'=>'primary','icon'=>'ri-clipboard-check-line'],
        ['label'=>'Berjalan','value'=>$stats['running'],'tone'=>'warning','icon'=>'ri-time-line'],
        ['label'=>'Selesai','value'=>$stats['completed'],'tone'=>'success','icon'=>'ri-check-double-line'],
        ['label'=>'Ada Temuan','value'=>$stats['failed'],'tone'=>'danger','icon'=>'ri-error-warning-line'],
    ]" />

    <x-sarpras-filter-card :action="route('sarpras.checklist.recap')" :reset="route('sarpras.checklist.recap')">
        <div class="col-md-2"><label class="form-label small fw-semibold">Frekuensi</label><select name="frequency_group" class="form-select"><option value="">Semua</option>@foreach(array_keys($frequencyGroups) as $group)<option value="{{ $group }}" {{ request('frequency_group')===$group?'selected':'' }}>{{ $group }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Semua</option>@foreach(['in_progress'=>'Berjalan','completed'=>'Selesai','failed'=>'Ada Temuan','cancelled'=>'Dibatalkan'] as $key=>$label)<option value="{{ $key }}" {{ request('status')===$key?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label small fw-semibold">Dari</label><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
        <div class="col-md-2"><label class="form-label small fw-semibold">Sampai</label><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
    </x-sarpras-filter-card>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0"><i class="ri-history-line me-2 text-primary"></i>Rekap Checklist Pemeliharaan</h5><p class="text-muted small mb-0">Harian, pekanan, bulanan — lengkap dengan aset yang diperiksa.</p></div><a href="{{ route('sarpras.checklist.index') }}" class="btn btn-sm btn-outline-primary"><i class="ri-arrow-left-line me-1"></i>Kembali</a></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>No</th><th>Tanggal</th><th>Checklist</th><th>Kode Aset</th><th>Aset</th><th>Frekuensi</th><th>Pelaksana</th><th class="text-center">Temuan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($instances as $i => $instance)
                @php
                    $tone = ['in_progress'=>'warning','completed'=>'success','failed'=>'danger','cancelled'=>'secondary'][$instance->status] ?? 'secondary';
                    $label = ['in_progress'=>'Berjalan','completed'=>'Selesai','failed'=>'Ada Temuan','cancelled'=>'Dibatalkan'][$instance->status] ?? ucfirst($instance->status);
                    $context = $instance->context;
                    $asset = $context instanceof \App\Models\AssetMaintenanceSchedule ? $context->asset : null;
                    $frequencies = $instance->template?->items?->pluck('frequency')->filter()->unique()->take(4)->implode(', ') ?: '-';
                @endphp
                <tr>
                    <td>{{ $instances->firstItem() + $i }}</td>
                    <td class="small">{{ $instance->created_at?->format('d/m/Y H:i') }}</td>
                    <td><div class="fw-medium">{{ $instance->template?->name ?? '-' }}</div><small class="text-muted">{{ $instance->template?->code }}</small></td>
                    <td>@if($asset)<code>{{ $asset->asset_code }}</code>@else <span class="text-muted">-</span>@endif</td>
                    <td class="small">{{ $asset?->asset_name ?? ($context instanceof \App\Models\ChecklistTemplate ? 'Checklist mandiri' : '-') }}</td>
                    <td class="small">{{ $frequencies }}</td>
                    <td class="small">{{ $instance->executor?->name ?? '-' }}</td>
                    <td class="text-center">{{ $instance->failed_items_count }} / {{ $instance->total_items_count }}</td>
                    <td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ $label }}</span></td>
                    <td class="text-end"><a href="{{ route('sarpras.checklist.instance', ['instanceId' => $instance->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td>
                </tr>
            @empty
                <x-empty-state colspan="10" title="Belum ada checklist" message="Checklist yang diisi akan tampil di sini beserta kode asetnya." />
            @endforelse
        </tbody></table></div></div>
        @if($instances->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $instances])</div>@endif
    </div>
@endsection
