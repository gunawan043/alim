@extends('layouts.master')
@section('title') Mulai Checklist Pemeliharaan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.pemeliharaan.schedule.index') }}">Jadwal Pemeliharaan</a> @endslot
        @slot('li_2') <a href="{{ route('sarpras.pemeliharaan.schedule.show', ['id' => $schedule->id]) }}">Detail Jadwal</a> @endslot
        @slot('title') Mulai Checklist
    @endcomponent

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Checklist untuk Jadwal</h5></div>
                <div class="card-body">
                    <div class="alert alert-light border">
                        <div class="fw-semibold">{{ $schedule->maintenance_type }}</div>
                        <div class="small text-muted">{{ $schedule->workUnit?->name ?? '-' }} · {{ $schedule->next_maintenance_date?->format('d/m/Y') }}</div>
                        <div class="small text-muted mt-1">Target: {{ $schedule->asset?->asset_name ?? $schedule->room?->room_name ?? $schedule->building?->building_name ?? '-' }}</div>
                    </div>
                    <form method="POST" action="{{ route('sarpras.checklist.store', ['scheduleId' => $schedule->id]) }}">
                        @csrf
                        <label class="form-label">Pilih Template Checklist <span class="text-danger">*</span></label>
                        <select name="template_id" class="form-select" required>
                            <option value="">— Pilih Template —</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" {{ old('template_id', $suggested?->id) === $template->id ? 'selected' : '' }}>{{ $template->name }} ({{ $template->items_count }} item)</option>
                            @endforeach
                        </select>
                        @if($suggested)<div class="form-text">Disarankan: <strong>{{ $suggested->name }}</strong> berdasarkan jenis pemeliharaan.</div>@endif
                        <div class="d-flex gap-2 mt-4"><button class="btn btn-success"><i class="ri-play-line me-1"></i>Mulai Checklist</button><a href="{{ route('sarpras.pemeliharaan.schedule.show', ['id' => $schedule->id]) }}" class="btn btn-light">Batal</a></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-info">
                <div class="card-header bg-info-subtle"><h5 class="card-title mb-0 text-info"><i class="ri-information-line me-1"></i>Cara Pengisian</h5></div>
                <div class="card-body small text-muted">
                    <p><strong>V</strong> = sesuai standar. <strong>X</strong> = ada penyimpangan.</p>
                    <p class="mb-0">Item X akan dihitung sebagai temuan. Setelah checklist selesai, temuan dapat ditindaklanjuti menjadi work order perbaikan.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
