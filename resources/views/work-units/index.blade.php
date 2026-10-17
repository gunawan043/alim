@extends('layouts.master')
@section('title')
    Satuan Kerja
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Apps
        @endslot
        @slot('title')
            Satuan Kerja
        @endslot
    @endcomponent

    <!-- Start Widgets Section -->
    <div class="row">
        <div class="col-xl-12">
            <div class="row">
                <!-- Widget Total Satuan Kerja -->
                <div class="col-xl-4 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Total Satuan Kerja</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <h5 class="text-success fs-14 mb-0">
                                        {{-- <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +{{ $growthPercentage ?? 0 }}% --}}
                                    </h5>
                                </div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between mt-4">
                                <div>
                                    <h4 class="fs-22 fw-semibold ff-secondary mb-4"><span class="counter-value"
                                            data-target="{{ $totalWorkUnits }}">{{ $totalWorkUnits }}</span></h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary-subtle rounded fs-3">
                                        <i class="bx bx-building-house text-primary"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Widget Satuan Kerja Aktif -->
                <div class="col-xl-4 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Aktif</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <h5 class="text-success fs-14 mb-0">
                                        {{-- <i class="ri-checkbox-circle-line fs-13 align-middle"></i> {{ $activePercentage }}% --}}
                                    </h5>
                                </div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between mt-4">
                                <div>
                                    <h4 class="fs-22 fw-semibold ff-secondary mb-4"><span class="counter-value"
                                            data-target="{{ $activeWorkUnits }}">{{ $activeWorkUnits }}</span></h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success-subtle rounded fs-3">
                                        <i class="bx bx-check-circle text-success"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Widget Satuan Kerja Nonaktif -->
                <div class="col-xl-4 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Nonaktif</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <h5 class="text-danger fs-14 mb-0">
                                        {{-- <i class="ri-close-circle-line fs-13 align-middle"></i> {{ $inactivePercentage }}% --}}
                                    </h5>
                                </div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between mt-4">
                                <div>
                                    <h4 class="fs-22 fw-semibold ff-secondary mb-4"><span class="counter-value"
                                            data-target="{{ $inactiveWorkUnits }}">{{ $inactiveWorkUnits }}</span></h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-danger-subtle rounded fs-3">
                                        <i class="bx bx-x-circle text-danger"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Widgets Section -->


    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="workUnitList">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">Data Satuan Kerja</h5>
                    <div class="d-flex gap-1 flex-wrap">
                        <button class="btn btn-soft-danger d-none" id="remove-actions" onclick="deleteMultiple()">
                            <i class="ri-delete-bin-2-line"></i> Hapus Terpilih
                        </button>
                        <button type="button" class="btn btn-primary create-btn" onclick="showAddModal()">
                            <i class="ri-add-line align-bottom me-1"></i> Tambah Satuan Kerja
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div>
                        <div class="table-responsive table-card mb-3">
                            <table class="table align-middle table-nowrap mb-0" id="workUnitTable">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 50px;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="checkAll"
                                                    onclick="checkAll(this)">
                                            </div>
                                        </th>
                                        <th scope="col">No</th>
                                        <th scope="col">Nama</th>
                                        <th scope="col">Kode</th>
                                        <th scope="col">Divisi</th>
                                        <th scope="col">Induk</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Dibuat</th>
                                        <th scope="col">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($workUnits as $item)
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="chk_child"
                                                        value="{{ $item->id }}" onchange="checkChange(this)">
                                                </div>
                                            </td>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $item->name }}</td>
                                            <td><span class="badge bg-info-subtle text-info">{{ $item->code }}</span>
                                            </td>
                                            <td>
                                                @if($item->divisi)
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $item->divisi->nama }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $item->induk }}</td>
                                            <td>
                                                @if ($item->is_active)
                                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                                @endif
                                            </td>
                                            <td>{{ $item->created_at->format('d M Y') }}</td>
                                            <td style="z-index: 999">
                                                <div class="dropdown">
                                                    <button class="btn btn-soft-secondary btn-sm dropdown" type="button"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ri-more-fill align-middle"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item edit-item-btn" href="#"
                                                                onclick="editWorkUnit('{{ $item->id }}')">
                                                                <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                                                Edit
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="#"
                                                                onclick="toggleStatus('{{ $item->id }}')">
                                                                <i class="ri-toggle-fill align-bottom me-2 text-muted"></i>
                                                                {{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item remove-item-btn" href="#"
                                                                onclick="deleteWorkUnit('{{ $item->id }}')">
                                                                <i
                                                                    class="ri-delete-bin-fill align-bottom me-2 text-muted"></i>
                                                                Hapus
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center">
                                                <div class="py-5">
                                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop"
                                                        colors="primary:#121331,secondary:#08a88a"
                                                        style="width:75px;height:75px"></lord-icon>
                                                    <h5 class="mt-2">Belum ada data</h5>
                                                    <p class="text-muted mb-0">Tambah satuan kerja untuk memulai</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Work Unit -->
    <div class="modal fade" id="workUnitModal" tabindex="-1" aria-labelledby="workUnitModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="workUnitModalLabel">Tambah Satuan Kerja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="workUnitForm" novalidate>
                    <div class="modal-body">
                        <div id="workUnitErrorMsg" class="alert alert-danger py-2 d-none"></div>
                        <input type="hidden" id="workUnitId">

                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Satuan Kerja <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                placeholder="Masukkan nama satuan kerja" required>
                            <div class="invalid-feedback">Nama satuan kerja wajib diisi</div>
                        </div>

                        <div class="mb-3">
                            <label for="divisi_id" class="form-label">Divisi <span class="text-danger">*</span></label>
                            <select class="form-control" id="divisi_id" name="divisi_id"
                                onchange="generateCodeAutomatic()">
                                <option value="">Pilih Divisi</option>
                                @foreach ($divisiOptions as $id => $nama)
                                    <option value="{{ $id }}">{{ $nama }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Divisi wajib dipilih</div>
                        </div>

                        <div class="mb-3">
                            <label for="induk" class="form-label">Induk</label>
                            <select class="form-control" id="induk" name="induk"
                                onchange="generateCodeAutomatic()">
                                <option value="">Tidak Ada Induk</option>
                                @foreach ($parentOptions as $id => $name)
                                    @if ($id !== '')
                                        <option value="{{ $name }}">{{ $name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="text-muted">Kosongkan jika tidak memiliki induk</small>
                        </div>

                        <div class="mb-3">
                            <label for="code" class="form-label">Kode</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="code" name="code"
                                    placeholder="Kode akan digenerate otomatis" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="generateCodeAutomatic()">
                                    <i class="ri-refresh-line"></i> Generate
                                </button>
                            </div>
                            <small class="text-muted">Kode akan otomatis digenerate berdasarkan Divisi dan induk</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">Status Aktif</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="hstack gap-2 justify-content-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="saveBtn">Simpan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade zoomIn" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        id="deleteRecord-close"></button>
                </div>
                <div class="modal-body">
                    <div class="mt-2 text-center">
                        <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                            colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                            <h4>Apakah Anda yakin?</h4>
                            <p class="text-muted mx-4 mb-0" id="deleteMessage">Anda akan menghapus data ini secara
                                permanen</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                        <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn w-sm btn-danger" id="delete-confirm-btn">Ya, Hapus!</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Modal -->
    <div class="modal fade zoomIn" id="bulkDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mt-2 text-center">
                        <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                            colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                            <h4>Apakah Anda yakin?</h4>
                            <p class="text-muted mx-4 mb-0">Anda akan menghapus <span id="selectedCount">0</span> data
                                secara permanen</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                        <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn w-sm btn-danger" id="bulk-delete-confirm-btn">Ya,
                            Hapus!</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="https://cdn.lordicon.com/lordicon.js"></script>

    <script>
        // Counter animation for widgets
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize counter animation
            const counters = document.querySelectorAll('.counter-value');
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-target'));
                if (!isNaN(target)) {
                    const increment = target / 200;
                    let current = 0;

                    const updateCounter = () => {
                        if (current < target) {
                            current += increment;
                            counter.textContent = Math.ceil(current);
                            setTimeout(updateCounter, 1);
                        } else {
                            counter.textContent = target;
                        }
                    };

                    updateCounter();
                }
            });
        });

        // VARIABLES
        let currentDeleteId = null;

        // INISIALISASI FORM SUBMIT
        document.addEventListener('DOMContentLoaded', function() {
            const workUnitForm = document.getElementById('workUnitForm');
            const saveBtn = document.getElementById('saveBtn');

            workUnitForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Validasi form
                if (!workUnitForm.checkValidity()) {
                    e.stopPropagation();
                    workUnitForm.classList.add('was-validated');
                    return;
                }

                // Prepare form data
                const formData = {
                    name: document.getElementById('name').value.toUpperCase(),
                    divisi_id: document.getElementById('divisi_id').value,
                    induk: document.getElementById('induk').value || '',
                    is_active: document.getElementById('is_active').checked ? 1 : 0
                };

                const workUnitId = document.getElementById('workUnitId').value;
                let url = '/work-units';
                let method = 'POST';

                // Jika edit, update URL dan method
                if (workUnitId) {
                    url = `/work-units/${workUnitId}`;
                    method = 'PUT';
                }

                // Tampilkan loading state
                saveBtn.disabled = true;
                saveBtn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menyimpan...';

                try {
                    // Kirim request ke server
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(formData)
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Tampilkan success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: result.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            // Refresh halaman
                            location.reload();
                        });
                    } else {
                        // Tampilkan error message
                        showError(result.errors || result.message);
                    }
                } catch (error) {
                    // Handle network error
                    showError('Terjadi kesalahan jaringan. Silakan coba lagi.');
                    console.error('Error:', error);
                } finally {
                    // Reset button state
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = 'Simpan';
                }
            });

            // Fungsi untuk menampilkan error
            function showError(message) {
                const errorDiv = document.getElementById('workUnitErrorMsg');

                if (typeof message === 'object') {
                    // Jika error berupa object (validation errors)
                    let errorHtml = '<ul class="mb-0">';
                    for (const key in message) {
                        if (Array.isArray(message[key])) {
                            message[key].forEach(msg => {
                                errorHtml += `<li>${msg}</li>`;
                            });
                        }
                    }
                    errorHtml += '</ul>';
                    errorDiv.innerHTML = errorHtml;
                } else {
                    // Jika error berupa string
                    errorDiv.innerHTML = message;
                }

                errorDiv.classList.remove('d-none');
            }

            // Reset form ketika modal ditutup
            document.getElementById('workUnitModal').addEventListener('hidden.bs.modal', function() {
                workUnitForm.reset();
                workUnitForm.classList.remove('was-validated');
                document.getElementById('workUnitId').value = '';
                document.getElementById('workUnitErrorMsg').classList.add('d-none');
                document.getElementById('workUnitModalLabel').textContent = 'Tambah Satuan Kerja';
                document.getElementById('code').value = '';
                document.getElementById('code').placeholder = 'Kode akan digenerate otomatis';
                document.getElementById('is_active').checked = true;
            });
        });

        // FUNGSI GENERATE KODE OTOMATIS
        async function generateCodeAutomatic() {
            const divisiId = document.getElementById('divisi_id').value;
            const induk = document.getElementById('induk').value;
            const workUnitId = document.getElementById('workUnitId').value;
            const codeInput = document.getElementById('code');

            if (!divisiId) {
                codeInput.value = '';
                codeInput.placeholder = 'Pilih Divisi dulu';
                return;
            }

            // Jika mode EDIT (workUnitId ada), biarkan kode tetap
            if (workUnitId) {
                return;
            }

            // Generate kode baru menggunakan API
            await generateCodeFromServer(divisiId, induk);
        }

        async function generateCodeFromServer(divisiId, induk) {
            try {
                const response = await fetch('/work-units/generate-code', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        divisi_id: divisiId,
                        induk: induk || ''
                    })
                });

                if (!response.ok) throw new Error('Server error');

                const result = await response.json();

                if (result.success) {
                    document.getElementById('code').value = result.code || '';
                }
            } catch (error) {
                console.error(error);
            }
        }


        // Tampilkan modal tambah
        function showAddModal() {
            document.getElementById('workUnitId').value = '';
            document.getElementById('workUnitModalLabel').textContent = 'Tambah Satuan Kerja';
            document.getElementById('workUnitForm').reset();
            document.getElementById('workUnitErrorMsg').classList.add('d-none');
            document.getElementById('code').value = '';
            document.getElementById('code').placeholder = 'Kode akan digenerate otomatis';
            document.getElementById('is_active').checked = true;

            const modal = new bootstrap.Modal(document.getElementById('workUnitModal'));
            modal.show();
        }

        // Edit function dengan ID
        async function editWorkUnit(id) {
            try {
                const response = await fetch(`/work-units/${id}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    const workUnit = result.data;

                    document.getElementById('workUnitId').value = workUnit.id;
                    document.getElementById('name').value = workUnit.name;
                    document.getElementById('divisi_id').value = workUnit.divisi_id || '';
                    document.getElementById('induk').value = workUnit.induk || '';
                    document.getElementById('code').value = workUnit.code;
                    document.getElementById('is_active').checked = workUnit.is_active;

                    document.getElementById('workUnitModalLabel').textContent = 'Edit Satuan Kerja';

                    const modal = new bootstrap.Modal(document.getElementById('workUnitModal'));
                    modal.show();
                } else {
                    Swal.fire('Error', result.message || 'Gagal memuat data', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
            }
        }

        // Delete function dengan ID
        function deleteWorkUnit(id) {
            currentDeleteId = id;
            document.getElementById('delete-confirm-btn').onclick = performDelete;

            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        async function performDelete() {
            if (!currentDeleteId) return;

            try {
                const response = await fetch(`/work-units/${currentDeleteId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: result.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', result.message || 'Gagal menghapus data', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
            } finally {
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                if (modal) modal.hide();
                currentDeleteId = null;
            }
        }

        // Toggle status dengan ID
        async function toggleStatus(id) {
            const result = await Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Anda akan mengubah status satuan kerja ini',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Ubah!',
                cancelButtonText: 'Batal'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch(`/work-units/${id}/toggle-status`, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Gagal mengubah status', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
                }
            }
        }

        // Fungsi untuk checkbox
        function checkAll(source) {
            const checkboxes = document.querySelectorAll('input[name="chk_child"]');
            const removeBtn = document.getElementById('remove-actions');

            checkboxes.forEach(checkbox => {
                checkbox.checked = source.checked;
            });

            removeBtn.classList.toggle('d-none', !source.checked);
        }

        function checkChange(source) {
            const checkboxes = document.querySelectorAll('input[name="chk_child"]:checked');
            const removeBtn = document.getElementById('remove-actions');
            const checkAllBox = document.getElementById('checkAll');

            removeBtn.classList.toggle('d-none', checkboxes.length === 0);
            checkAllBox.checked = checkboxes.length === document.querySelectorAll('input[name="chk_child"]').length;
        }

        // Bulk delete dengan ID
        async function deleteMultiple() {
            const checkboxes = document.querySelectorAll('input[name="chk_child"]:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);

            if (ids.length === 0) {
                Swal.fire('Info', 'Pilih data yang akan dihapus', 'info');
                return;
            }

            // Tampilkan modal konfirmasi
            document.getElementById('selectedCount').textContent = ids.length;
            const modal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
            modal.show();

            // Set up confirm button
            document.getElementById('bulk-delete-confirm-btn').onclick = async function() {
                try {
                    const response = await fetch('/work-units/bulk-delete', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ids: ids
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        modal.hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        modal.hide();
                        Swal.fire('Error', data.message || 'Gagal menghapus data', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    modal.hide();
                    Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
                }
            };
        }
    </script>

    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
