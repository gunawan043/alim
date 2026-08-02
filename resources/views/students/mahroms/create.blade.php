@extends('layouts.master')
@section('title') Tambah Mahrom @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Santri</a> @endslot
        @slot('li_3') <a href="{{ route('user.students.show', ['userId' => $userId, 'santriUuid' => $student->id]) }}">{{ $student->name ?? 'Santri' }}</a> @endslot
        @slot('li_4') <a href="{{ route('user.students.mahroms.index', ['userId' => $userId, 'santriUuid' => $student->id]) }}">Mahrom</a> @endslot
        @slot('title') Tambah Mahrom @endslot
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
                        • {{ $student->currentClass->full_name }}
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <form method="POST"
          action="{{ route('user.students.mahroms.store', ['userId' => $userId, 'santriUuid' => $student->id]) }}"
          enctype="multipart/form-data"
          id="mahromForm">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-user-settings-line me-2 text-primary"></i>Data Mahrom</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                       value="{{ old('name') }}" placeholder="Nama lengkap mahrom" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NIK (Nomor Induk Kependudukan)</label>
                                <input type="text" name="id_number" class="form-control"
                                       value="{{ old('id_number') }}" placeholder="16 digit NIK"
                                       maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hubungan dengan Santri <span class="text-danger">*</span></label>
                                <select name="relationship" class="form-control" required>
                                    <option value="">— Pilih Hubungan —</option>
                                    <option value="ayah" {{ old('relationship') === 'ayah' ? 'selected' : '' }}>Ayah</option>
                                    <option value="ibu" {{ old('relationship') === 'ibu' ? 'selected' : '' }}>Ibu</option>
                                    <option value="kakak" {{ old('relationship') === 'kakak' ? 'selected' : '' }}>Kakak</option>
                                    <option value="adik" {{ old('relationship') === 'adik' ? 'selected' : '' }}>Adik</option>
                                    <option value="paman" {{ old('relationship') === 'paman' ? 'selected' : '' }}>Paman</option>
                                    <option value="bibi" {{ old('relationship') === 'bibi' ? 'selected' : '' }}>Bibi</option>
                                    <option value="kakek" {{ old('relationship') === 'kakek' ? 'selected' : '' }}>Kakek</option>
                                    <option value="nenek" {{ old('relationship') === 'nenek' ? 'selected' : '' }}>Nenek</option>
                                    <option value="wali" {{ old('relationship') === 'wali' ? 'selected' : '' }}>Wali</option>
                                    <option value="anak" {{ old('relationship') === 'anak' ? 'selected' : '' }}>Anak</option>
                                    <option value="sepupu" {{ old('relationship') === 'sepupu' ? 'selected' : '' }}>Sepupu</option>
                                    <option value="lainnya" {{ old('relationship') === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor Telepon</label>
                                <input type="text" name="phone" class="form-control"
                                       value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" maxlength="20">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="2"
                                          placeholder="Alamat lengkap mahrom">{{ old('address') }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Catatan tambahan jika ada">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Status Options --}}
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-settings-line me-2 text-primary"></i>Pengaturan Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_primary"
                                           id="isPrimarySwitch" value="1" {{ old('is_primary') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isPrimarySwitch">
                                        <strong>Mahrom Utama</strong>
                                        <div class="text-muted small">Mahrom utama adalah kontak utama untuk menerima informasi seputar Santri.</div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active"
                                           id="isActiveSwitch" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActiveSwitch">
                                        <strong>Mahrom Aktif</strong>
                                        <div class="text-muted small">Mahrom nonaktif tidak dapat menjenguk Santri.</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Photo Upload --}}
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-image-add-line me-2 text-primary"></i>Foto Mahrom</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-4">
                            <div class="flex-shrink-0">
                                <div id="photoPreview" class="rounded border p-1 text-center" style="width:120px;height:120px;">
                                    <i class="ri-image-add-line text-muted" style="font-size:2.5rem;line-height:110px;"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <p class="text-muted small mb-2">Unggah foto mahrom (opsional). Format: JPG, PNG. Maksimal 2MB.</p>
                                <input type="file" name="photo" class="form-control" id="photoInput" accept="image/*">
                                <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="clearPhoto()">
                                    <i class="ri-delete-bin-line me-1"></i> Hapus Foto
                                </button>
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
                    <button type="submit" class="btn btn-success">
                        <i class="ri-save-line me-1"></i> Simpan Mahrom
                    </button>
                </div>
            </div>

            {{-- Sidebar Info --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0"><i class="ri-shield-star-line me-2"></i>Tentang Mahrom</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small">
                            <li class="d-flex gap-2 mb-3">
                                <i class="ri-checkbox-circle-line text-success mt-1"></i>
                                <span><strong>Mahrom</strong> adalah orang yang memiliki hubungan darah atau صلاح (walimat) dengan Santri dan diperbolehkan menjengukdi dalam.</span>
                            </li>
                            <li class="d-flex gap-2 mb-3">
                                <i class="ri-checkbox-circle-line text-success mt-1"></i>
                                <span>Hanya mahrom yang diperbolehkan menjengukdi dalam kamar Santri.</span>
                            </li>
                            <li class="d-flex gap-2 mb-3">
                                <i class="ri-checkbox-circle-line text-success mt-1"></i>
                                <span>Batas maksimal <strong>{{ config('alim.max_mahrom', 4) }} mahrom</strong> per Santri.</span>
                            </li>
                            <li class="d-flex gap-2 mb-0">
                                <i class="ri-checkbox-circle-line text-success mt-1"></i>
                                <span>Mahrom utama menerima semua informasi terkait Santri.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0"><i class="ri-user-card-line me-2"></i>Santri</h5>
                    </div>
                    <div class="card-body">
                        @if(isset($student))
                            <div class="mb-2">
                                <label class="form-label text-muted small">Nama</label>
                                <div class="fw-semibold">{{ $student->name }}</div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-muted small">NISN</label>
                                <div class="fw-semibold">{{ $student->nisn ?? '—' }}</div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label text-muted small">JK</label>
                                <div class="fw-semibold">{{ $student->gender_text ?? '—' }}</div>
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
function clearPhoto() {
    document.getElementById('photoInput').value = '';
    document.getElementById('photoPreview').innerHTML =
        '<i class="ri-image-add-line text-muted" style="font-size:2.5rem;line-height:110px;"></i>';
}

document.getElementById('photoInput').addEventListener('change', function (e) {
    var file = e.target.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        Swal.fire({ icon: 'error', title: 'Ukuran File Terlalu Besar', text: 'Maksimal 2MB.' });
        this.value = '';
        return;
    }
    var reader = new FileReader();
    reader.onload = function (e) {
        document.getElementById('photoPreview').innerHTML =
            '<img src="' + e.target.result + '" alt="Preview" class="img-fluid rounded" style="width:110px;height:110px;object-fit:cover;">';
    };
    reader.readAsDataURL(file);
});

document.getElementById('mahromForm').addEventListener('submit', function (e) {
    var submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line me-1 spinner-border spinner-border-sm"></i> Menyimpan...';
    }
});
</script>
@endsection