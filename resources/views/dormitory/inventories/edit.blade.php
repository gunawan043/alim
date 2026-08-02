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
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <form method="POST"
          action="{{ route('user.asrama.inventories.update', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'itemUuid' => $item->id]) }}">
        @csrf @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="ri-archive-line me-1" aria-hidden="true"></i> Edit Item Inventaris</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="edit_room" class="form-label">Kamar <span class="text-danger">*</span></label>
                                <select name="room_id" id="edit_room" class="form-control @error('room_id') is-invalid @enderror" required aria-describedby="edit_roomHelp">
                                    <option value="">— Pilih Kamar —</option>
                                    @foreach($rooms as $r)
                                        <option value="{{ $r->id }}" {{ old('room_id', $item->room_id) == $r->id ? 'selected' : '' }}>{{ $r->code }} — {{ $r->name ?? $r->room_type }}</option>
                                    @endforeach
                                </select>
                                <small id="edit_roomHelp" class="text-muted">Pilih kamar untuk item inventaris ini.</small>
                                @error('room_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="edit_item_name" class="form-label">Nama Item <span class="text-danger">*</span></label>
                                <input type="text" name="item_name" id="edit_item_name" class="form-control @error('item_name') is-invalid @enderror" value="{{ old('item_name', $item->item_name) }}" required placeholder="Contoh: Kasur Busa">
                                @error('item_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="edit_item_code" class="form-label">Kode Item</label>
                                <input type="text" name="item_code" id="edit_item_code" class="form-control @error('item_code') is-invalid @enderror" value="{{ old('item_code', $item->item_code) }}" placeholder="Contoh: INV-ASR-001">
                                @error('item_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="edit_quantity" class="form-label">Jumlah <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" id="edit_quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', $item->quantity) }}" min="0" required aria-describedby="edit_qtyHelp">
                                <small id="edit_qtyHelp" class="text-muted">Jumlah fisik item yang tersedia.</small>
                                @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="edit_condition" class="form-label">Kondisi <span class="text-danger">*</span></label>
                                <select name="condition" id="edit_condition" class="form-control @error('condition') is-invalid @enderror" required>
                                    <option value="">— Pilih —</option>
                                    <option value="baik" {{ old('condition', $item->condition) == 'baik' ? 'selected' : '' }}>Baik</option>
                                    <option value="rusak" {{ old('condition', $item->condition) == 'rusak' ? 'selected' : '' }}>Rusak</option>
                                    <option value="perbaikan" {{ old('condition', $item->condition) == 'perbaikan' ? 'selected' : '' }}>Perlu Perbaikan</option>
                                    <option value="hilang" {{ old('condition', $item->condition) == 'hilang' ? 'selected' : '' }}>Hilang</option>
                                </select>
                                @error('condition')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="edit_category" class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select name="category_id" id="edit_category" class="form-control @error('category_id') is-invalid @enderror" required>
                                    <option value="">— Pilih Kategori —</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('category_id', $item->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }} ({{ $cat->code }})</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Kategori klasifikasi Asset SARPRAS.</small>
                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="edit_last_checked" class="form-label">Terakhir Dicek</label>
                                <input type="date" name="last_checked_at" id="edit_last_checked" class="form-control @error('last_checked_at') is-invalid @enderror" value="{{ old('last_checked_at', $item->last_checked_at?->format('Y-m-d')) }}">
                                @error('last_checked_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="edit_notes" class="form-label">Catatan</label>
                                <textarea name="notes" id="edit_notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $item->notes) }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-primary" aria-label="Simpan perubahan item inventaris">
                                <i class="ri-save-line me-1" aria-hidden="true"></i> Simpan
                            </button>
                            <a href="{{ route('user.asrama.inventories.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light" aria-label="Batal dan kembali ke daftar inventaris">
                                <i class="ri-close-line me-1" aria-hidden="true"></i> Batal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0"><i class="ri-information-line me-1" aria-hidden="true"></i> Info Item</h6></div>
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
