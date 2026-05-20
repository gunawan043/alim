@extends('layouts.master')
@section('title') Import Aset @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pendukung @endslot
        @slot('li_2') <a href="{{ route('sarpras.gedung.index') }}">Sarana Prasarana</a> @endslot
        @slot('li_3') <a href="{{ route('sarpras.aset.index') }}">Aset</a> @endslot
        @slot('title') Import Aset @endslot
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
    @php $importErrors = session('import_errors', []); @endphp
    @if(count($importErrors) > 0)
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>{{ count($importErrors) }} baris tidak bisa diimport:</strong>
            <ul class="mb-0 mt-1">
                @foreach($importErrors as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
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

    <div class="row">
        <div class="col-lg-8">
            {{-- Forced Room Info --}}
            @if($forcedRoom)
                <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                    <i class="ri-information-line fs-5"></i>
                    <div>
                        Semua aset yang di-import akan masuk ke
                        <strong>{{ $forcedRoom->room_name }}</strong>.
                        Kolom <code>ruang</code> di Excel tidak perlu diisi.
                    </div>
                </div>
            @endif

            {{-- Upload Form --}}
            <div class="card mt-3">
                <div class="card-header"><h5 class="mb-0">Upload File Excel</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('sarpras.aset.import') }}"
                          enctype="multipart/form-data">
                        @csrf

                        {{-- Hidden room_id when forced --}}
                        @if($forcedRoom)
                            <input type="hidden" name="room_id" value="{{ $forcedRoom->id }}">
                        @endif

                        <div class="mb-3">
                            <label class="form-label">File Excel (.xlsx, .xls, .csv) <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                            @error('file')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ $forcedRoom
                                ? route('sarpras.aset.index', ['room_id' => $forcedRoom->id])
                                : route('sarpras.aset.index') }}"
                               class="btn btn-light">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-success" id="btnImport">
                                <i class="ri-upload-cloud-line me-1"></i> Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Instructions Card --}}
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Panduan Import Aset</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <a href="{{ route('sarpras.aset.template') }}{{ $forcedRoom ? '?room_id=' . $forcedRoom->id : '' }}" class="btn btn-outline-primary">
                            <i class="ri-download-line me-1"></i> Download Template Excel
                        </a>
                    </div>

                    <h6>Format Kolom Template:</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kolom</th>
                                    <th>Wajib</th>
                                    <th>Deskripsi</th>
                                    <th>Contoh</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>nama_aset</code></td>
                                    <td><span class="badge bg-danger">Ya</span></td>
                                    <td>Nama aset/inventaris</td>
                                    <td>Meja Siswa</td>
                                </tr>
                                <tr>
                                    <td><code>kode_aset</code></td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                    <td>Kode inventaris unik</td>
                                    <td>AST-001</td>
                                </tr>
                                <tr>
                                    <td><code>ruang</code></td>
                                    <td>
                                        @if($forcedRoom)
                                            <span class="badge bg-secondary">Tidak</span>
                                        @else
                                            <span class="badge bg-danger">Ya</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($forcedRoom)
                                            <span class="text-muted">Diisi otomatis — tidak perlu diisi</span>
                                        @else
                                            Nama atau kode ruang (exact/partial match)
                                        @endif
                                    </td>
                                    <td>{{ $forcedRoom ? 'Diisi otomatis' : 'Kelas 10 X-A / RG-ABC123' }}</td>
                                </tr>
                                <tr>
                                    <td><code>kategori</code></td>
                                    <td><span class="badge bg-danger">Ya</span></td>
                                    <td>Nama kategori aset (fuzzy match)</td>
                                    <td>Meubelair</td>
                                </tr>
                                <tr>
                                    <td><code>merk</code></td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                    <td>Merk / Brand</td>
                                    <td>Cosco</td>
                                </tr>
                                <tr>
                                    <td><code>model</code></td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                    <td>Model / Tipe</td>
                                    <td>DX-200</td>
                                </tr>
                                <tr>
                                    <td><code>nomor_seri</code></td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                    <td>Serial number</td>
                                    <td>SN123456</td>
                                </tr>
                                <tr>
                                    <td><code>warna</code></td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                    <td>Warna aset</td>
                                    <td>Hitam</td>
                                </tr>
                                <tr>
                                    <td><code>kondisi</code></td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                    <td>baik / rusak_ringan / rusak_sedang / rusak_berat</td>
                                    <td>baik</td>
                                </tr>
                                <tr>
                                    <td><code>status</code></td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                    <td>tersedia / dipinjam / dalam_perbaikan</td>
                                    <td>tersedia</td>
                                </tr>
                                <tr>
                                    <td><code>tahun_perolehan</code></td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                    <td>Tahun perolehan (4 digit)</td>
                                    <td>2024</td>
                                </tr>
                                <tr>
                                    <td><code>harga_perolehan</code></td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                    <td>Harga dalam angka (tanpa Rp)</td>
                                    <td>1500000</td>
                                </tr>
                                <tr>
                                    <td><code>sumber_perolehan</code></td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                    <td>pembelian / hibah / sumbangan / bos / pemerintah</td>
                                    <td>pembelian</td>
                                </tr>
                                <tr>
                                    <td><code>sumber_dana</code></td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                    <td>Sumber dana</td>
                                    <td>APBD</td>
                                </tr>
                                <tr>
                                    <td><code>spesifikasi</code></td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                    <td>Spesifikasi detail</td>
                                    <td>Ukuran 60x40cm</td>
                                </tr>
                                <tr>
                                    <td><code>catatan</code></td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                    <td>Catatan tambahan</td>
                                    <td>-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        <i class="ri-information-line me-1"></i>
                        @if($forcedRoom)
                            <strong>Tips:</strong> Kolom wajib hanya <code>nama_aset</code> dan <code>kategori</code>. Kolom <code>ruang</code> diabaikan — semua aset otomatis masuk ke <strong>{{ $forcedRoom->room_name }}</strong>.
                        @else
                            <strong>Tips:</strong> Kolom wajib hanya <code>nama_aset</code>, <code>ruang</code>, dan <code>kategori</code>.
                            Ruangan dan kategori akan dicari otomatis (fuzzy match). Baris dengan error akan dilewati.
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar: Ruangan & Kategori tersedia --}}
        <div class="col-lg-4">
            @if($forcedRoom)
                <div class="card border-primary">
                    <div class="card-header bg-primary-subtle"><h5 class="mb-0 text-primary">Ruang Tujuan</h5></div>
                    <div class="card-body">
                        <div class="fw-bold fs-5">{{ $forcedRoom->room_name }}</div>
                        <div class="text-muted small mb-2">{{ $forcedRoom->school?->name ?? '' }}</div>
                        @if($forcedRoom->room_code)
                            <div><code>{{ $forcedRoom->room_code }}</code></div>
                        @endif
                        <div class="mt-2">
                            <span class="badge bg-{{ ['kelas'=>'info','laboratorium'=>'primary','perpustakaan'=>'warning','kantor'=>'secondary'][$forcedRoom->room_type] ?? 'secondary' }}-subtle">
                                {{ ucfirst($forcedRoom->room_type) }}
                            </span>
                            @if($forcedRoom->capacity)
                                <span class="badge bg-secondary-subtle">{{ $forcedRoom->capacity }} org</span>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Ruangan Tersedia</h5></div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush small">
                            @forelse($rooms as $room)
                                <li class="list-group-item px-3 py-2">
                                    <div class="fw-medium">{{ $room->room_name }}</div>
                                    @if($room->room_code)
                                        <div class="text-muted"><code>{{ $room->room_code }}</code></div>
                                    @endif
                                </li>
                            @empty
                                <li class="list-group-item text-muted text-center py-3">Belum ada ruang.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            @endif

            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Kategori Tersedia</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="ri-add-line me-1"></i> Tambah
                    </button>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small" id="categoryList">
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
        </div>
    </div>

    {{-- Modal Tambah Kategori --}}
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kategori Aset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addCategoryForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="catName" class="form-control" required placeholder="Contoh: Meubelair">
                            <div class="invalid-feedback" id="catNameError"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kode Kategori</label>
                            <input type="text" name="code" id="catCode" class="form-control" placeholder="Contoh: MUB-001">
                            <div class="invalid-feedback" id="catCodeError"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipe Aset <span class="text-danger">*</span></label>
                            <select name="asset_type" id="catType" class="form-control" required>
                                <option value="bergerak">Bergerak</option>
                                <option value="tidak_bergerak">Tidak Bergerak</option>
                                <option value="habis_pakai">Habis Pakai</option>
                            </select>
                        </div>
                        <div class="mb-3">
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

@section('script')
<script>
    document.getElementById('btnImport')?.addEventListener('click', function () {
        this.disabled = true;
        this.innerHTML = '<i class="ri-loader-4-line me-1"></i> Mengimport...';
        this.closest('form').submit();
    });

    // ── Tambah Kategori via AJAX ──────────────────────────────────────
    document.getElementById('addCategoryForm')?.addEventListener('submit', function (e) {
        e.preventDefault();

        const btn = document.getElementById('btnSaveCategory');
        const form = this;
        const userId = \'\' ;

        // Reset errors
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line me-1"></i> Menyimpan...';

        fetch('/sarpras/kategori', {
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
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Add to sidebar list
                const list = document.getElementById('categoryList');
                const emptyItem = list.querySelector('.text-muted.text-center');
                if (emptyItem) emptyItem.remove();

                const li = document.createElement('li');
                li.className = 'list-group-item px-3 py-2';
                li.innerHTML = '<div class="fw-medium text-success">' + data.category.name + '</div>' +
                    (data.category.code ? '<div class="text-muted"><code>' + data.category.code + '</code></div>' : '');
                list.appendChild(li);

                // Close modal & reset form
                bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
                form.reset();
                document.getElementById('catYears').value = '5';

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
                // Show validation errors
                if (data.errors?.name) {
                    const el = document.getElementById('catName');
                    el.classList.add('is-invalid');
                    document.getElementById('catNameError').textContent = data.errors.name[0];
                }
                if (data.errors?.code) {
                    const el = document.getElementById('catCode');
                    el.classList.add('is-invalid');
                    document.getElementById('catCodeError').textContent = data.errors.code[0];
                }
            }
        })
        .catch(() => {
            Swal.fire('Error', 'Terjadi kesalahan. Silakan coba lagi.', 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-save-line me-1"></i> Simpan';
        });
    });
</script>
@endsection
