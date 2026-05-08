@extends('layouts.master')
@section('title') Edit Inventaris — {{ $item->item_name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.inventories.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Inventaris Kamar</a> @endslot
        @slot('title') Edit Item @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST"
          action="{{ route('user.asrama.inventories.update', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'itemUuid' => $item->id]) }}">
        @csrf @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="ri-archive-line me-1"></i> Edit Item Inventaris</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Kamar <span class="text-danger">*</span></label>
                                <select name="room_id" class="form-control" required>
                                    <option value="">— Pilih Kamar —</option>
                                    @foreach($rooms as $r)
                                        <option value="{{ $r->id }}" {{ old('room_id', $item->room_id) == $r->id ? 'selected' : '' }}>{{ $r->code }} — {{ $r->name ?? $r->room_type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Item <span class="text-danger">*</span></label>
                                <input type="text" name="item_name" class="form-control" value="{{ old('item_name', $item->item_name) }}" required placeholder="Contoh: Kasur Busa">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode Item</label>
                                <input type="text" name="item_code" class="form-control" value="{{ old('item_code', $item->item_code) }}" placeholder="Contoh: INV-ASR-001">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" class="form-control" value="{{ old('quantity', $item->quantity) }}" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kondisi <span class="text-danger">*</span></label>
                                <select name="condition" class="form-control" required>
                                    <option value="">— Pilih —</option>
                                    <option value="baik" {{ old('condition', $item->condition) == 'baik' ? 'selected' : '' }}>Baik</option>
                                    <option value="rusak" {{ old('condition', $item->condition) == 'rusak' ? 'selected' : '' }}>Rusak</option>
                                    <option value="perbaikan" {{ old('condition', $item->condition) == 'perbaikan' ? 'selected' : '' }}>Perlu Perbaikan</option>
                                    <option value="hibahan" {{ old('condition', $item->condition) == 'hibahan' ? 'selected' : '' }}>Hibahan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Terakhir Dicek</label>
                                <input type="date" name="last_checked_at" class="form-control" value="{{ old('last_checked_at', $item->last_checked_at?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $item->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                        <a href="{{ route('user.asrama.inventories.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">Batal</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0"><i class="ri-information-line me-1"></i> Info Item</h6></div>
                    <div class="card-body">
                        <dl class="row small">
                            <dt class="col-5 text-muted">Kode</dt>
                            <dd class="col-7">{{ $item->item_code ?: '—' }}</dd>
                            <dt class="col-5 text-muted">Dicek Oleh</dt>
                            <dd class="col-7">{{ $item->checkedBy?->name ?? '—' }}</dd>
                            <dt class="col-5 text-muted">Dibuat</dt>
                            <dd class="col-7">{{ $item->created_at?->format('d/m/Y') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
