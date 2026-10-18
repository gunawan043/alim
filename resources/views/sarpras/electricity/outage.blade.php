@extends('layouts.master')
@section('title') Penanganan Listrik Padam @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.electricity.index') }}">Listrik & Token</a> @endslot
        @slot('title') Penanganan Padam
    @endcomponent

    @php
        $photoUrl = function ($path, $url) {
            if ($url) return $url;
            return $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;
        };
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div><h4 class="mb-1">{{ $outage->location }}</h4><div class="text-muted small">{{ $outage->occurred_at?->format('d/m/Y H:i') }} · {{ ucfirst(str_replace('_',' ', $outage->outage_type)) }} · Pelapor: {{ $outage->reporter_name ?? $outage->reporter?->name ?? '-' }}</div></div>
                <span class="badge bg-{{ $outage->status === 'resolved' ? 'success' : 'danger' }}-subtle text-{{ $outage->status === 'resolved' ? 'success' : 'danger' }} fs-6">{{ $outage->status === 'resolved' ? 'Menyala Kembali' : 'Padam' }}</span>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-6"><div class="text-muted small">Penyebab</div><div>{{ $outage->cause ?? '-' }}</div></div>
                <div class="col-md-3"><div class="text-muted small">Waktu Menyala</div><div>{{ $outage->restored_at?->format('d/m/Y H:i') ?? '-' }}</div></div>
                <div class="col-md-3"><div class="text-muted small">Foto Sebelum</div>@if($photoUrl($outage->before_photo_path, $outage->before_photo_url))<a href="{{ $photoUrl($outage->before_photo_path, $outage->before_photo_url) }}" target="_blank" class="btn btn-sm btn-outline-info">Lihat</a>@else - @endif</div>
            </div>
            @if($outage->notes)<div class="alert alert-light border mt-3 mb-0 small">{{ $outage->notes }}</div>@endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-tools-line me-1"></i>Tindakan Penanganan</h5></div>
                <div class="card-body p-0">
                    @forelse($outage->actions as $action)
                        <div class="border-bottom p-3"><div class="d-flex justify-content-between"><div class="fw-medium">{{ $action->action }}</div><small class="text-muted">{{ $action->created_at?->format('d/m H:i') }}</small></div><div class="small text-muted">Petugas: {{ $action->officer?->name ?? '-' }}{{ $action->notes ? ' · '.$action->notes : '' }}</div></div>
                    @empty
                        <div class="p-3"><x-empty-state title="Belum ada tindakan" message="Tambahkan tindakan yang dilakukan petugas." /></div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Tambah Tindakan</h5></div>
                <div class="card-body"><form method="POST" action="{{ route('sarpras.electricity.outage.action', ['outageId' => $outage->id]) }}">@csrf<div class="mb-2"><textarea name="action" class="form-control" rows="2" required placeholder="Tindakan…"></textarea></div><div class="mb-2"><select name="officer_id" class="form-select"><option value="">Petugas (saya)</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div><div class="mb-2"><input name="notes" class="form-control" placeholder="Keterangan"></div><button class="btn btn-primary w-100">Simpan Tindakan</button></form></div>
            </div>
            @if($outage->status !== 'resolved')
                <div class="card border-success"><div class="card-header bg-success-subtle"><h5 class="card-title mb-0 text-success">Listrik Menyala</h5></div><div class="card-body"><form method="POST" action="{{ route('sarpras.electricity.outage.resolve', ['outageId' => $outage->id]) }}" enctype="multipart/form-data">@csrf<div class="mb-2"><label class="form-label">Waktu Menyala</label><input type="datetime-local" name="restored_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div><div class="mb-2"><label class="form-label">Foto Sesudah / Link Drive</label><input type="url" name="after_url" class="form-control mb-1" placeholder="Link Google Drive"><input type="file" name="after_photo" class="form-control" accept="image/*"></div><div class="mb-2"><textarea name="notes" class="form-control" rows="2" placeholder="Catatan hasil…"></textarea></div><button class="btn btn-success w-100">Tandai Menyala</button></form></div></div>
            @endif
        </div>
    </div>

    @include('sarpras.partials.camera-modal')
@endsection
