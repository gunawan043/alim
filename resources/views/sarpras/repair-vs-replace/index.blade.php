@extends('layouts.master')
@section('title') Repair vs Replace @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Repair vs Replace Engine
    @endcomponent

    <x-sarpras-stats :items="[
        ['label'=>'Sehat (GOOD)','value'=>$summary['GOOD'] ?? 0,'tone'=>'success','icon'=>'ri-shield-check-line'],
        ['label'=>'Pantau (MONITOR)','value'=>$summary['MONITOR'] ?? 0,'tone'=>'info','icon'=>'ri-eye-line'],
        ['label'=>'Perbaiki (REPAIR)','value'=>$summary['REPAIR'] ?? 0,'tone'=>'warning','icon'=>'ri-tools-line'],
        ['label'=>'Ganti (REPLACE)','value'=>($summary['REPLACE'] ?? 0)+($summary['CRITICAL'] ?? 0),'tone'=>'danger','icon'=>'ri-delete-bin-2-line'],
    ]" />

    <x-sarpras-filter-card :action="route('sarpras.rvr.index')" :reset="route('sarpras.rvr.index')">
        <div class="col-md-3"><label class="form-label small fw-semibold">Rekomendasi</label><select name="recommendation" class="form-select"><option value="">Semua</option>@foreach(['GOOD','MONITOR','REPAIR','REPLACE','CRITICAL'] as $rec)<option value="{{ $rec }}" @selected(($filters['recommendation'] ?? '') === $rec)>{{ $rec }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Prioritas</label><label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="critical_only" value="1" {{ ($filters['critical_only'] ?? false) ? 'checked' : '' }}><span class="form-check-label">Hanya kritis</span></label></div>
        <div class="col-md-auto ms-auto"><button form="rvrBulkForm" class="btn btn-primary" type="submit"><i class="ri-refresh-line me-1"></i>Evaluasi Ulang</button></div>
    </x-sarpras-filter-card>

    <form id="rvrBulkForm" method="POST" action="{{ route('sarpras.rvr.bulk') }}">@csrf</form>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-scales-3-line me-2 text-primary"></i>Rekomendasi Perbaikan atau Penggantian</h5><p class="text-muted small mb-0">Skor 0–100, semakin tinggi semakin sehat.</p></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Kode</th><th>Nama Aset</th><th>Kategori</th><th>Lokasi</th><th class="text-center">Skor</th><th class="text-center">Rekomendasi</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($recommendations as $r)
                @php $badge = ['GOOD'=>'success','MONITOR'=>'info','REPAIR'=>'warning','REPLACE'=>'danger'][$r->recommendation] ?? 'dark'; @endphp
                <tr>
                    <td><code>{{ $r->asset?->asset_code }}</code></td>
                    <td class="fw-medium">{{ $r->asset?->asset_name }}</td>
                    <td class="small">{{ $r->asset?->category?->name ?? '—' }}</td>
                    <td class="small">{{ $r->asset?->room?->building?->building_name ?? '—' }}<div class="text-muted">{{ $r->asset?->room?->room_name }}</div></td>
                    <td class="text-center fw-bold">{{ $r->health_score }}</td>
                    <td class="text-center"><span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $r->recommendation }}</span></td>
                    <td class="text-end"><a href="{{ route('sarpras.rvr.show', ['asset' => $r->asset_id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td>
                </tr>
            @empty
                <x-empty-state colspan="7" title="Belum ada rekomendasi" message="Jalankan evaluasi untuk menghitung repair vs replace." />
            @endforelse
        </tbody></table></div></div>
        @if($recommendations->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $recommendations])</div>@endif
    </div>
@endsection
