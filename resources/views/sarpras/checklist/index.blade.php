@extends('layouts.master')
@section('title') Checklist Pemeliharaan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Checklist Pemeliharaan
    @endcomponent

    <div class="row g-3 mb-3">
        @foreach([
            ['Template', $templates->count(), 'primary', 'ri-file-list-3-line'],
            ['Item Pemeriksaan', $templates->sum('items_count'), 'info', 'ri-checkbox-multiple-line'],
            ['Checklist Saya', $myInstances->count(), 'success', 'ri-clipboard-check-line'],
            ['Sedang Berjalan', $myInstances->where('status', 'in_progress')->count(), 'warning', 'ri-time-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-6 col-xl-3"><div class="card h-100 border-0 shadow-sm"><div class="card-body py-3 d-flex align-items-center gap-3"><span class="avatar-sm"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span><div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ number_format($value) }}</div></div></div></div></div>
        @endforeach
    </div>

    <div class="d-flex justify-content-end mb-2"><a href="{{ route('sarpras.checklist.recap') }}" class="btn btn-outline-primary btn-sm"><i class="ri-history-line me-1"></i>Rekap Checklist &amp; Aset</a></div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sarpras.checklist.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small fw-semibold">Cari Template</label><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama / kode / kategori…"></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Kategori</label><select name="category_slug" class="form-select"><option value="">Semua</option>@foreach($categories as $category)<option value="{{ $category }}" {{ request('category_slug') === $category ? 'selected' : '' }}>{{ ucfirst(str_replace('-', ' ', $category)) }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Frekuensi</label><select name="frequency_group" class="form-select"><option value="">Semua</option>@foreach(array_keys($frequencyGroups) as $group)<option value="{{ $group }}" {{ request('frequency_group') === $group ? 'selected' : '' }}>{{ $group }}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button></div>
                <div class="col-md-1"><a href="{{ route('sarpras.checklist.index') }}" class="btn btn-light w-100"><i class="ri-refresh-line"></i></a></div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @forelse($templates as $template)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <span class="avatar-sm"><span class="avatar-title rounded bg-primary-subtle text-primary"><i class="ri-clipboard-line"></i></span></span>
                            <span class="badge bg-light text-dark border">{{ $template->items_count }} item</span>
                        </div>
                        <h5 class="mt-3 mb-1">{{ $template->name }}</h5>
                        <div class="text-muted small"><code>{{ $template->code }}</code> · {{ ucfirst(str_replace('-', ' ', $template->category_slug ?? '-')) }}</div>
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            <span class="badge bg-success-subtle text-success">Harian {{ $template->harian_count }}</span>
                            <span class="badge bg-warning-subtle text-warning">Pekanan {{ $template->pekanan_count }}</span>
                            <span class="badge bg-info-subtle text-info">Bulanan {{ $template->bulanan_count }}</span>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <a href="{{ route('sarpras.checklist.template', ['templateId' => $template->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line me-1"></i>Lihat Item</a>
                            <a href="{{ route('sarpras.checklist.template.start.form', ['templateId' => $template->id]) }}" class="btn btn-sm btn-success ms-auto"><i class="ri-play-line me-1"></i>Isi Checklist</a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card"><div class="card-body"><x-empty-state title="Belum ada template checklist" message="Jalankan seeder template atau tambahkan template pemeliharaan." /></div></div></div>
        @endforelse
    </div>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-history-line me-1"></i>Checklist Terakhir Saya</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>No</th><th>Checklist</th><th>Kode Aset</th><th>Konteks</th><th>Status</th><th>Temuan</th><th>Tanggal</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    @forelse($myInstances as $i => $instance)
                        @php
                            $tone = ['in_progress'=>'warning','completed'=>'success','failed'=>'danger','cancelled'=>'secondary'][$instance->status] ?? 'secondary';
                            $label = ['in_progress'=>'Berjalan','completed'=>'Selesai','failed'=>'Ada Temuan','cancelled'=>'Dibatalkan'][$instance->status] ?? ucfirst($instance->status);
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-medium">{{ $instance->template?->name ?? '-' }}</td>
                            @php $context = $instance->context; $asset = $context instanceof \App\Models\AssetMaintenanceSchedule ? $context->asset : ($context instanceof \App\Models\Asset ? $context : null); @endphp
                            <td>@if($asset)<code>{{ $asset->asset_code }}</code>@else <span class="text-muted">-</span>@endif</td>
                            <td class="small text-muted">{{ $asset?->asset_name ?? ($context instanceof \App\Models\ChecklistTemplate ? 'Checklist mandiri' : class_basename($instance->context_type)) }}</td>
                            <td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ $label }}</span></td>
                            <td>{{ $instance->failed_items_count }} / {{ $instance->total_items_count }}</td>
                            <td class="small">{{ $instance->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="text-end"><a href="{{ route('sarpras.checklist.instance', ['instanceId' => $instance->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td>
                        </tr>
                    @empty
                        <x-empty-state colspan="8" title="Belum ada checklist" message="Mulai checklist dari halaman detail jadwal pemeliharaan." />
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
