@extends('layouts.master')
@section('title') Tambah Aset @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pendukung @endslot
        @slot('li_2') <a href="{{ route('user.sarpras.gedung.index', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
        @slot('li_3') <a href="{{ route('user.sarpras.aset.index', ['userId' => $userId]) }}">Aset</a> @endslot
        @slot('title') Tambah Aset @endslot
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

    <form method="POST" action="{{ route('user.sarpras.aset.store', ['userId' => $userId]) }}">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Informasi Aset</h5>
                        <a href="{{ route('user.sarpras.aset.import', ['userId' => $userId]) }}" class="btn btn-outline-primary btn-sm">
                            <i class="ri-upload-cloud-2-line me-1"></i> Import dari Excel
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Ruang <span class="text-danger">*</span></label>
                                <select name="room_id" id="room_id" class="form-control" required>
                                    <option value="">— Pilih Ruang —</option>
                                    @foreach($rooms as $r)
                                        <option value="{{ $r->id }}" {{ (request('room_id') ?? old('room_id')) == $r->id ? 'selected' : '' }}>{{ $r->room_name }} ({{ $r->room_type }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="asset_category_id" id="asset_category_id" class="form-control" required>
                                        <option value="">— Pilih Kategori —</option>
                                        @foreach($categories as $c)
                                            <option value="{{ $c->id }}" {{ old('asset_category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal" title="Tambah Kategori Baru">
                                        <i class="ri-add-line"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Aset <span class="text-danger">*</span></label>
                                <input type="text" name="asset_name" class="form-control" value="{{ old('asset_name') }}" required placeholder="Contoh: Meja Siswa 1">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode Aset</label>
                                <input type="text" name="asset_code" class="form-control" value="{{ old('asset_code') }}" placeholder="AST-001" maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Merk</label>
                                <input type="text" name="brand" class="form-control" value="{{ old('brand') }}" placeholder="Cosco" maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Model / Tipe</label>
                                <input type="text" name="model" class="form-control" value="{{ old('model') }}" placeholder="DX-200" maxlength="100">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nomor Seri</label>
                                <input type="text" name="serial_number" class="form-control" value="{{ old('serial_number') }}" placeholder="SN123456" maxlength="100">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Warna</label>
                                <input type="text" name="color" class="form-control" value="{{ old('color') }}" placeholder="Hitam" maxlength="50">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kondisi <span class="text-danger">*</span></label>
                                <select name="condition" class="form-control" required>
                                    <option value="">— Pilih —</option>
                                    @foreach(App\Models\Asset::CONDITION_OPTIONS as $c)
                                        <option value="{{ $c }}" {{ old('condition', 'baik') == $c ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $c)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    @foreach(App\Models\Asset::STATUS_OPTIONS as $s)
                                        <option value="{{ $s }}" {{ old('status', 'tersedia') == $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sumber Perolehan</label>
                                <select name="acquisition_source" class="form-control">
                                    <option value="">— Pilih —</option>
                                    @foreach(App\Models\Asset::ACQUISITION_SOURCE_OPTIONS as $src)
                                        <option value="{{ $src }}" {{ old('acquisition_source') == $src ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $src)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tahun Perolehan</label>
                                <input type="number" name="acquisition_year" class="form-control" value="{{ old('acquisition_year', date('Y')) }}" min="1900" max="2100">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Harga Perolehan (Rp)</label>
                                <input type="number" name="acquisition_price" class="form-control" value="{{ old('acquisition_price') }}" min="0" placeholder="1500000">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sumber Dana</label>
                                <input type="text" name="funding_source" class="form-control" value="{{ old('funding_source') }}" placeholder="BOS" maxlength="100">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Spesifikasi</label>
                                <textarea name="specification" class="form-control" rows="2">{{ old('specification') }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Pengaturan</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_bookable" value="1" {{ old('is_bookable', '1') ? 'checked' : '' }}>
                                <label class="form-check-label">Aset dapat dipinjam</label>
                            </div>
                        </div>
                        <hr>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                            <label class="form-check-label">Aset aktif</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('user.sarpras.aset.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
            <button type="submit" class="btn btn-success">
                <i class="ri-save-line me-1"></i> Simpan
            </button>
        </div>
    </form>

    {{-- Modal Tambah Kategori --}}
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-folder-add-line me-1"></i> Tambah Kategori Aset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addCategoryForm">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-light border d-flex align-items-center gap-2 mb-3">
                            <i class="ri-information-line text-primary fs-5"></i>
                            <span class="small text-muted">Kategori yang ditambahkan langsung aktif dan bisa digunakan.</span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="catName" class="form-control" required placeholder="Contoh: Meubelair (Alat Rumah Tangga)">
                            <div class="invalid-feedback" id="catNameError"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kode Kategori</label>
                                <input type="text" name="code" id="catCode" class="form-control" placeholder="Contoh: MUB-001">
                                <div class="invalid-feedback" id="catCodeError"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipe Aset <span class="text-danger">*</span></label>
                                <select name="asset_type" id="catType" class="form-control" required>
                                    <option value="bergerak">Bergerak</option>
                                    <option value="tidak_bergerak">Tidak Bergerak</option>
                                    <option value="habis_pakai">Habis Pakai</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Masa Pakai (Tahun)</label>
                            <input type="number" name="depreciation_years" id="catYears" class="form-control" value="5" min="0" max="100">
                            <small class="text-muted">0 = tidak disusutkan</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success" id="btnSaveCategory">
                            <i class="ri-save-line me-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    var userId = '{{ $userId }}';

    document.getElementById('addCategoryForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        var btn = document.getElementById('btnSaveCategory');
        var form = this;

        form.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });

        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line me-1"></i> Menyimpan...';

        fetch('/' + userId + '/sarpras/kategori', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                name: document.getElementById('catName').value,
                code: document.getElementById('catCode').value,
                asset_type: document.getElementById('catType').value,
                depreciation_years: document.getElementById('catYears').value,
            }),
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
                form.reset();
                document.getElementById('catYears').value = '5';

                var catSelect = document.getElementById('asset_category_id');
                if (catSelect) {
                    var opt = document.createElement('option');
                    opt.value = data.category.id;
                    opt.text = data.category.name;
                    catSelect.add(opt);
                    catSelect.value = data.category.id;
                }

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Kategori berhasil ditambahkan!',
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true,
                });
            } else {
                if (data.errors?.name) {
                    var el = document.getElementById('catName');
                    el.classList.add('is-invalid');
                    document.getElementById('catNameError').textContent = data.errors.name[0];
                }
                if (data.errors?.code) {
                    var el = document.getElementById('catCode');
                    el.classList.add('is-invalid');
                    document.getElementById('catCodeError').textContent = data.errors.code[0];
                }
            }
        })
        .catch(function() {
            Swal.fire('Error', 'Terjadi kesalahan. Silakan coba lagi.', 'error');
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-save-line me-1"></i> Simpan';
        });
    });
})();
</script>
@endpush