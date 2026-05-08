{{-- Shared: Tambah Kategori Aset Modal + AJAX Script --}}

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

{{-- Tabel Daftar Kategori (samping form atau di sidebar) --}}
<div class="card mt-3" id="categorySidebar">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Kategori Tersedia</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="ri-add-line me-1"></i> Tambah
        </button>
    </div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush small" id="categoryListSidebar">
            @forelse($categories as $cat)
                <li class="list-group-item px-3 py-2">
                    <div class="fw-medium">{{ $cat->name }}</div>
                    @if($cat->code)
                        <div class="text-muted"><code>{{ $cat->code }}</code></div>
                    @endif
                </li>
            @empty
                <li class="list-group-item text-muted text-center py-3">Belum ada kategori.</li>
            @endforelse
        </ul>
    </div>
</div>

@section('modalScripts')
<script>
(function() {
    var userId = '{{ $userId }}';

    function refreshCategoryList() {
        fetch('/' + userId + '/sarpras/kategori/list', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.categories) {
                var list = document.getElementById('categoryListSidebar');
                if (list) {
                    var html = '';
                    data.categories.forEach(function(c) {
                        html += '<li class="list-group-item px-3 py-2">' +
                            '<div class="fw-medium">' + c.name + '</div>' +
                            (c.code ? '<div class="text-muted"><code>' + c.code + '</code></div>' : '') +
                            '</li>';
                    });
                    list.innerHTML = html || '<li class="list-group-item text-muted text-center py-3">Belum ada kategori.</li>';
                }
            }
        })
        .catch(function() {});
    }

    document.getElementById('addCategoryForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        var btn = document.getElementById('btnSaveCategory');
        var form = this;

        // Reset errors
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
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
                form.reset();
                document.getElementById('catYears').value = '5';

                // Refresh category dropdown if exists
                var catSelect = document.getElementById('asset_category_id');
                if (catSelect) {
                    var opt = document.createElement('option');
                    opt.value = data.category.id;
                    opt.text = data.category.name;
                    catSelect.add(opt);
                    catSelect.value = data.category.id;
                }

                // Refresh sidebar list
                refreshCategoryList();

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
@endsection
