@extends('layouts.master')
@section('title') Mulai Checklist — {{ $template->name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.checklist.index') }}">Checklist</a> @endslot
        @slot('li_2') <a href="{{ route('sarpras.checklist.template', ['templateId' => $template->id]) }}">{{ $template->name }}</a> @endslot
        @slot('title') Pilih Aset
    @endcomponent

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-archive-2-line me-2 text-primary"></i>Checklist {{ $template->name }}</h5><p class="text-muted small mb-0">Pilih aset yang akan diperiksa. Kode aset akan tercatat pada checklist dan rekap.</p></div>
                <div class="card-body">
                    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
                    @if($assets->isEmpty())
                        <div class="alert alert-warning mb-0"><i class="ri-error-warning-line me-1"></i>Belum ada data aset aktif. Tambahkan aset terlebih dahulu.</div>
                    @else
                        <form method="POST" action="{{ route('sarpras.checklist.template.start', ['templateId' => $template->id]) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Aset yang Diperiksa <span class="text-danger">*</span></label>
                                <select name="asset_id" id="assetSelect" class="form-select" required>
                                    <option value="">— Cari / pilih aset —</option>
                                    @foreach($assets as $asset)
                                        <option value="{{ $asset->id }}" {{ old('asset_id', request('asset_id')) === $asset->id ? 'selected' : '' }}>
                                            {{ $asset->asset_code }} — {{ $asset->asset_name }}@if($asset->category) · {{ $asset->category->name }}@endif @if($asset->room) · {{ $asset->room->room_name }}@endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Jika aset AC, pilih unit AC yang akan diperiksa.</div>
                            </div>
                            <div class="alert alert-light border small"><i class="ri-information-line me-1 text-primary"></i>Checklist berisi {{ $template->items->count() }} item. Item harian, pekanan, dan bulanan akan tampil sesuai frekuensinya.</div>
                            <div class="d-flex justify-content-end gap-2"><a href="{{ route('sarpras.checklist.template', ['templateId' => $template->id]) }}" class="btn btn-light">Batal</a><button class="btn btn-success"><i class="ri-play-line me-1"></i>Mulai Checklist</button></div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-primary"><div class="card-header bg-primary-subtle"><h5 class="card-title mb-0 text-primary">Ringkasan Template</h5></div><div class="card-body small">
                <div class="d-flex justify-content-between border-bottom py-1"><span class="text-muted">Kode</span><code>{{ $template->code }}</code></div>
                <div class="d-flex justify-content-between border-bottom py-1"><span class="text-muted">Kategori</span><span>{{ ucfirst(str_replace('-',' ', $template->category_slug ?? '-')) }}</span></div>
                <div class="d-flex justify-content-between border-bottom py-1"><span class="text-muted">Jumlah item</span><strong>{{ $template->items->count() }}</strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Frekuensi</span><span class="text-end">{{ $template->items->pluck('frequency')->filter()->unique()->implode(', ') ?: '-' }}</span></div>
            </div></div>
        </div>
    </div>
@endsection

@push('script')
<script>
    if (window.jQuery && jQuery.fn.select2) {
        jQuery('#assetSelect').select2({ width: '100%', placeholder: 'Cari kode atau nama aset…' });
    }
</script>
@endpush
