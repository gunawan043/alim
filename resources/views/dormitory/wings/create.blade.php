@extends('layouts.master')
@section('title') Tambah Lantai Blok @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.wings.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Lantai Blok</a> @endslot
        @slot('title') Tambah Lantai Blok @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.asrama.wings.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="ri-building-line me-1" aria-hidden="true"></i> Form Lantai Blok Asrama</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="building_select" class="form-label">Gedung <span class="text-danger">*</span></label>
                                <select name="sarpras_building_id" id="building_select" class="form-select @error('sarpras_building_id') is-invalid @enderror" onchange="updatePreview()">
                                    <option value="">— Pilih Gedung —</option>
                                    @foreach($buildings as $b)
                                        <option value="{{ $b->id }}" {{ old('sarpras_building_id') == $b->id ? 'selected' : '' }}>
                                            {{ $b->name }} ({{ $b->gender ?? '-' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('sarpras_building_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="wing_code" class="form-label">Kode Blok</label>
                                <input type="text" name="code" id="wing_code" class="form-control" value="{{ old('code') }}" placeholder="A" maxlength="20">
                                <small class="text-muted">Diisi otomatis dari kode gedung Sarpras</small>
                            </div>
                            <div class="col-md-6">
                                <label for="wing_display_name" class="form-label">Nama Blok (Otomatis)</label>
                                <input type="text" id="wing_display_name" class="form-control" value="{{ old('name') }}" disabled placeholder="Gedung Abu Bakar — Lantai 1">
                                <input type="hidden" name="name" id="wing_name_input" value="{{ old('name') }}">
                                <small class="text-muted">Dibuat otomatis dari gedung + lantai</small>
                            </div>
                            <div class="col-md-4">
                                <label for="wing_floor" class="form-label">Lantai <span class="text-danger">*</span></label>
                                <input type="number" name="floor" id="wing_floor" class="form-control @error('floor') is-invalid @enderror" value="{{ old('floor', 1) }}" min="1" placeholder="1" required onchange="updatePreview()">
                                @error('floor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="wing_capacity" class="form-label">Kapasitas</label>
                                <input type="number" name="capacity" id="wing_capacity" class="form-control @error('capacity') is-invalid @enderror" value="{{ old('capacity') }}" min="0" placeholder="0">
                                @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="wing_supervisor" class="form-label">Supervisor</label>
                                <select name="supervisor_id" id="wing_supervisor" class="form-control @error('supervisor_id') is-invalid @enderror">
                                    <option value="">— Pilih Supervisor —</option>
                                    @foreach($supervisors as $s)
                                        <option value="{{ $s->id }}" {{ old('supervisor_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                                @error('supervisor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="wing_notes" class="form-label">Catatan</label>
                                <textarea name="notes" id="wing_notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="wing_is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="wing_is_active">Lantai gedung aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.asrama.wings.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light" aria-label="Batal dan kembali ke daftar lantai gedung">
                                <i class="ri-close-line me-1" aria-hidden="true"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary" aria-label="Simpan lantai gedung baru">
                                <i class="ri-save-line me-1" aria-hidden="true"></i> Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        function updatePreview() {
            var sel = document.getElementById('building_select');
            var floor = document.getElementById('wing_floor');
            var preview = document.getElementById('wing_display_name');
            var nameInput = document.getElementById('wing_name_input');
            if (sel.value) {
                var buildingName = sel.options[sel.selectedIndex].text.split(' (')[0];
                var floorVal = floor.value || '1';
                var displayName = buildingName + ' — Lantai ' + floorVal;
                preview.value = displayName;
                nameInput.value = displayName;
            } else {
                preview.value = '';
                nameInput.value = '';
            }
        }
    </script>
@endsection
