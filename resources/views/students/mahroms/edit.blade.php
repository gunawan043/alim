@extends('layouts.master')
@section('title') Edit Mahrom — {{ $mahrom->name ?? 'Mahrom' }} @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Santri</a> @endslot
        @slot('li_3') <a href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $student->id]) }}">{{ $student->name ?? 'Santri' }}</a> @endslot
        @slot('li_4') <a href="{{ route('user.students.mahroms.index', ['userId' => $userId, 'santriUuid' => $student->id]) }}">Mahrom</a> @endslot
        @slot('title') Edit Mahrom @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="ri-error-warning-line me-2 fs-18"></i>
                <strong>Terjadi kesalahan:</strong>
            </div>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Student Banner --}}
    @if(isset($student))
    <div class="card mb-4 border-primary-subtle bg-primary-subtle">
        <div class="card-body py-2 d-flex align-items-center gap-3">
            <div class="avatar-md">
                @if($student->photo_path)
                    <img src="{{ asset('storage/' . $student->photo_path) }}" alt="{{ $student->name }}"
                         class="rounded-circle object-fit-cover" width="44" height="44">
                @else
                    <span class="avatar-title rounded-circle fs-3 fw-bold bg-{{ $student->gender === 'P' ? 'danger' : 'primary' }} text-white">
                        {{ strtoupper(substr($student->name, 0, 2)) }}
                    </span>
                @endif
            </div>
            <div>
                <div class="fw-semibold">{{ $student->name }}</div>
                <div class="text-muted small">
                    {{ $student->nisn ? 'NISN: ' . $student->nisn : '' }}
                    @if($student->currentClass)
                        &bull; {{ $student->currentClass->full_name }}
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <form method="POST"
          action="{{ route('user.students.mahroms.update', ['userId' => $userId, 'santriUuid' => $student->id, 'mahromUuid' => $mahrom->id]) }}"
          enctype="multipart/form-data"
          id="mahromForm">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                {{-- Main Data Card --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-user-settings-line me-2 text-primary"></i>Data Mahrom</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                       value="{{ old('name', $mahrom->name) }}"
                                       placeholder="Nama lengkap mahrom" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NIK (Nomor Induk Kependudukan)</label>
                                <input type="text" name="id_number" class="form-control"
                                       value="{{ old('id_number', $mahrom->id_number) }}"
                                       placeholder="16 digit NIK" maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hubungan dengan Santri <span class="text-danger">*</span></label>
                                <select name="relationship" class="form-control" required>
                                    <option value="">— Pilih Hubungan —</option>
                                    <option value="ayah"     {{ old('relationship', $mahrom->relationship) === 'ayah'     ? 'selected' : '' }}>Ayah</option>
                                    <option value="ibu"      {{ old('relationship', $mahrom->relationship) === 'ibu'      ? 'selected' : '' }}>Ibu</option>
                                    <option value="kakak"    {{ old('relationship', $mahrom->relationship) === 'kakak'    ? 'selected' : '' }}>Kakak</option>
                                    <option value="adik"     {{ old('relationship', $mahrom->relationship) === 'adik'     ? 'selected' : '' }}>Adik</option>
                                    <option value="paman"    {{ old('relationship', $mahrom->relationship) === 'paman'    ? 'selected' : '' }}>Paman</option>
                                    <option value="bibi"    {{ old('relationship', $mahrom->relationship) === 'bibi'    ? 'selected' : '' }}>Bibi</option>
                                    <option value="kakek"    {{ old('relationship', $mahrom->relationship) === 'kakek'    ? 'selected' : '' }}>Kakek</option>
                                    <option value="nenek"    {{ old('relationship', $mahrom->relationship) === 'nenek'    ? 'selected' : '' }}>Nenek</option>
                                    <option value="wali"     {{ old('relationship', $mahrom->relationship) === 'wali'     ? 'selected' : '' }}>Wali</option>
                                    <option value="anak"     {{ old('relationship', $mahrom->relationship) === 'anak'     ? 'selected' : '' }}>Anak</option>
                                    <option value="sepupu"   {{ old('relationship', $mahrom->relationship) === 'sepupu'   ? 'selected' : '' }}>Sepupu</option>
                                    <option value="lainnya" {{ old('relationship', $mahrom->relationship) === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor Telepon</label>
                                <input type="text" name="phone" class="form-control"
                                       value="{{ old('phone', $mahrom->phone) }}"
                                       placeholder="08xxxxxxxxxx" maxlength="20">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="2"
                                          placeholder="Alamat lengkap mahrom">{{ old('address', $mahrom->address) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Catatan tambahan jika ada">{{ old('notes', $mahrom->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Status Options Card --}}
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-settings-line me-2 text-primary"></i>Pengaturan Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_primary"
                                           id="isPrimarySwitch" value="1"
                                           {{ old('is_primary', $mahrom->is_primary) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isPrimarySwitch">
                                        <strong>Mahrom Utama</strong>
                                        <div class="text-muted small">Mahrom utama adalah kontak utama untuk menerima informasi seputar Santri.</div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active"
                                           id="isActiveSwitch" value="1"
                                           {{ old('is_active', $mahrom->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActiveSwitch">
                                        <strong>Mahrom Aktif</strong>
                                        <div class="text-muted small">Mahrom nonaktif tidak dapat menjenguk Santri.</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Photo Upload Card --}}
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-image-add-line me-2 text-primary"></i>Foto Mahrom</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-4">
                            <div class="flex-shrink-0">
                                @if($mahrom->photo_path)
                                    <div id="photoPreview" class="rounded border p-1" style="width:120px;height:120px;">
                                        <img src="{{ asset('storage/' . $mahrom->photo_path) }}"
                                             alt="Foto {{ $mahrom->name }}"
                                             class="img-fluid rounded"
                                             style="width:110px;height:110px;object-fit:cover;">
                                    </div>
                                @else
                                    <div id="photoPreview" class="rounded border p-1 text-center" style="width:120px;height:120px;background:var(--bs-tertiary-bg);">
                                        <i class="ri-image-add-line text-muted" style="font-size:2.5rem;line-height:110px;"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <p class="text-muted small mb-2">
                                    Unggah foto mahrom (opsional). Format: JPG, PNG. Maksimal 2MB.
                                </p>
                                <input type="file" name="photo" class="form-control" id="photoInput" accept="image/*">
                                @if($mahrom->photo_path)
                                    <div class="form-text text-danger">
                                        <i class="ri-information-line me-1"></i>
                                        Kosongkan foto jika tidak ingin mengubah foto.
                                    </div>
                                @endif
                                @if(!$mahrom->photo_path)
                                    <div class="form-text text-muted">
                                        Tidak ada foto. Kosongkan field ini jika tidak ingin menambahkan foto.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-2 justify-content-end mt-3">
                    <a href="{{ route('user.students.mahroms.index', ['userId' => $userId, 'santriUuid' => $student->id]) }}"
                       class="btn btn-light">
                        <i class="ri-arrow-left-line me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        <i class="ri-save-line me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </div>

            {{-- Sidebar Info --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0"><i class="ri-shield-star-line me-2"></i>Info Mahrom</h5>
                    </div>
                    <div class="card-body">
                        @if(isset($mahrom))
                            <div class="mb-3">
                                <label class="form-label text-muted small">ID Mahrom</label>
                                <div><code class="small">{{ $mahrom->id }}</code></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Hubungan</label>
                                <div class="fw-semibold">
                                    {{ $mahrom->relationship_text ?? ucfirst(str_replace('_', ' ', $mahrom->relationship)) }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Mahrom Utama</label>
                                <div>
                                    @if($mahrom->is_primary)
                                        <span class="badge bg-warning-subtle text-warning">
                                            <i class="ri-star-line me-1"></i>Ya
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Bukan</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Status</label>
                                <div>
                                    @if($mahrom->is_active)
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="ri-checkbox-circle-line me-1"></i>Aktif
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label text-muted small">Terakhir Update</label>
                                <div class="fw-semibold small">{{ $mahrom->updated_at->format('d M Y, H:i') }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0"><i class="ri-user-card-line me-2"></i>Data Santri</h5>
                    </div>
                    <div class="card-body">
                        @if(isset($student))
                            <div class="mb-2">
                                <label class="form-label text-muted small">Nama</label>
                                <div class="fw-semibold">{{ $student->name }}</div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-muted small">NISN</label>
                                <div class="fw-semibold">{{ $student->nisn ?? '&mdash;' }}</div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label text-muted small">JK</label>
                                <div class="fw-semibold">{{ $student->gender_text ?? '&mdash;' }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('js')
<script>
(function() {
    var photoInput = document.getElementById('photoInput');
    var photoPreview = document.getElementById('photoPreview');
    var mahromForm = document.getElementById('mahromForm');
    var submitBtn = document.getElementById('submitBtn');

    // Photo preview on file select
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    title: 'Ukuran File Terlalu Besar',
                    text: 'Maksimal ukuran file adalah 2MB.'
                });
                this.value = '';
                return;
            }

            var reader = new FileReader();
            reader.onload = function(e) {
                if (photoPreview) {
                    photoPreview.innerHTML =
                        '<img src="' + e.target.result + '" alt="Preview" ' +
                        'class="img-fluid rounded" style="width:110px;height:110px;object-fit:cover;">';
                }
            };
            reader.readAsDataURL(file);
        });
    }

    // Form submission feedback
    if (mahromForm && submitBtn) {
        mahromForm.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
        });
    }
})();
</script>
@endsection
