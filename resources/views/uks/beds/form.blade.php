@php
    $isEdit = isset($bed) && $bed;
@endphp

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-2"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST"
      action="{{ $isEdit
          ? route('user.uks.beds.update', ['userId' => auth()->id(), 'uuid' => $bed->id])
          : route('user.uks.beds.store', ['userId' => auth()->id()]) }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <h5 class="card-title mb-0"><i class="ri-hotel-bed-line me-2 text-primary"></i>Data Bed / Ranjang UKS</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Asrama / Unit</label>
                    <select name="dormitory_id" class="form-select">
                        <option value="">— Tanpa Asrama —</option>
                        @foreach($dormitories as $dormitory)
                            <option value="{{ $dormitory->id }}" {{ old('dormitory_id', $bed->dormitory_id ?? null) == $dormitory->id ? 'selected' : '' }}>
                                {{ $dormitory->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select" required>
                        <option value="L" {{ old('gender', $bed->gender ?? 'L') === 'L' ? 'selected' : '' }}>Putra</option>
                        <option value="P" {{ old('gender', $bed->gender ?? 'L') === 'P' ? 'selected' : '' }}>Putri</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="tersedia" {{ old('status', $bed->status ?? 'tersedia') === 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                        <option value="dipakai" {{ old('status', $bed->status ?? '') === 'dipakai' ? 'selected' : '' }}>Dipakai</option>
                        <option value="perbaikan" {{ old('status', $bed->status ?? '') === 'perbaikan' ? 'selected' : '' }}>Perbaikan</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Gedung / Label Ruang <span class="text-danger">*</span></label>
                    <input type="text" name="building_or_room" class="form-control" required
                           value="{{ old('building_or_room', $bed->building_or_room ?? '') }}"
                           placeholder="cth: UKS Putra / Ruang A">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gedung</label>
                    <input type="text" name="building" class="form-control"
                           value="{{ old('building', $bed->building ?? '') }}"
                           placeholder="cth: UKS Putra">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ruangan</label>
                    <input type="text" name="room" class="form-control"
                           value="{{ old('room', $bed->room ?? '') }}"
                           placeholder="cth: Ruang A">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Bagian / Section</label>
                    <input type="text" name="section" class="form-control"
                           value="{{ old('section', $bed->section ?? '') }}"
                           placeholder="cth: Isolasi / Anak">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nomor Bed <span class="text-danger">*</span></label>
                    <input type="text" name="bed_number" class="form-control" required
                           value="{{ old('bed_number', $bed->bed_number ?? '') }}"
                           placeholder="cth: A-01">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Urutan Tampil</label>
                    <input type="number" name="sort_order" class="form-control" min="0"
                           value="{{ old('sort_order', $bed->sort_order ?? 0) }}">
                </div>

                <div class="col-12">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" rows="2" class="form-control" placeholder="Catatan kondisi bed...">{{ old('notes', $bed->notes ?? '') }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('user.uks.beds.index', ['userId' => auth()->id()]) }}" class="btn btn-light">Batal</a>
            <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan</button>
        </div>
    </div>
</form>
