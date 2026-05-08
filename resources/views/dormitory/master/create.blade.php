@extends('layouts.master')
@section('title') Tambah Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pengelolaan @endslot
        @slot('li_2') <a href="{{ route('user.dormitory-master.index', ['userId' => $userId]) }}">Asrama</a> @endslot
        @slot('title') Tambah Asrama @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.dormitory-master.store', ['userId' => $userId]) }}" enctype="multipart/form-data">
        @csrf

        {{-- Tabs Navigation --}}
        <ul class="nav nav-tabs mb-3" id="asramaTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-identitas" data-bs-toggle="tab" data-bs-target="#identitas" type="button" role="tab">
                    <i class="ri-hotel-line me-1"></i>Identitas Asrama
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-alamat" data-bs-toggle="tab" data-bs-target="#alamat" type="button" role="tab">
                    <i class="ri-map-pin-line me-1"></i>Kontak & Alamat
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-logo" data-bs-toggle="tab" data-bs-target="#logo" type="button" role="tab">
                    <i class="ri-image-line me-1"></i>Logo
                </button>
            </li>
        </ul>

        <div class="tab-content" id="asramaTabContent">

            {{-- ── TAB 1: Identitas Asrama ───────────────────────────── --}}
            <div class="tab-pane fade show active" id="identitas" role="tabpanel">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Identitas Asrama</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Unit Pengasuhan <span class="text-danger">*</span></label>
                                <select name="work_unit_id" id="work_unit_id" class="form-control" required>
                                    <option value="">— Pilih Unit Pengasuhan —</option>
                                    @foreach($workUnits as $wu)
                                        <option value="{{ $wu->id }}" {{ old('work_unit_id') == $wu->id ? 'selected' : '' }}>{{ $wu->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Nama asrama &amp; kode terisi otomatis.</small>
                                <input type="hidden" name="name" id="dormitory_name" value="{{ old('name') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-control" required>
                                    <option value="">— Pilih Gender —</option>
                                    <option value="putra" {{ old('gender') === 'putra' ? 'selected' : '' }}>Putra</option>
                                    <option value="putri" {{ old('gender') === 'putri' ? 'selected' : '' }}>Putri</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kapasitas Penghuni</label>
                                <input type="number" name="capacity" class="form-control" value="{{ old('capacity') }}" min="1" placeholder="100">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sekolah Terkait</label>
                                <select name="school_id" class="form-control">
                                    <option value="">— Tidak ada —</option>
                                    @foreach($schools as $s)
                                        <option value="{{ $s->id }}" {{ old('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kepala Asrama</label>
                                <select name="head_id" class="form-control">
                                    <option value="">— Pilih Kepala Asrama —</option>
                                    @foreach($heads as $h)
                                        <option value="{{ $h->id }}" {{ old('head_id') == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                    <label class="form-check-label">Asrama aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.dormitory-master.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── TAB 2: Kontak & Alamat ────────────────────────────── --}}
            <div class="tab-pane fade" id="alamat" role="tabpanel">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Kontak & Alamat</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="3" placeholder="Alamat lengkap asrama">{{ old('address') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No. Telepon</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="0812-xxxx-xxxx" maxlength="20">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.dormitory-master.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── TAB 3: Logo ───────────────────────────────────────── --}}
            <div class="tab-pane fade" id="logo" role="tabpanel">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Logo Asrama</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Upload Logo</label>
                                <input type="file" name="logo_path" class="form-control" accept="image/*">
                                <small class="text-muted">Format: JPG, PNG. Maksimal 2MB.</small>
                            </div>
                            <div class="col-md-6">
                                <div id="logoPreview" class="text-center">
                                    <div class="doc-preview mb-2" style="height:120px">
                                        <div class="doc-preview-placeholder"><i class="ri-image-add-line"></i></div>
                                    </div>
                                    <small class="text-muted">Logo belum diupload</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.dormitory-master.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelector('input[name="logo_path"]')?.addEventListener('change', function (e) {
        var file = e.target.files[0];
        var preview = document.getElementById('logoPreview');
        if (file && preview) {
            var reader = new FileReader();
            reader.onload = function (e) {
                preview.innerHTML = '<img src="' + e.target.result + '" alt="Logo Preview" class="img-fluid rounded" style="max-height:120px;border:1px solid #dee2e6">';
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>
@endsection
