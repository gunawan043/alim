@extends('layouts.master')
@section('title') Edit Mahrom — {{ $student->name ?? 'Santri' }} @endsection

@php
    $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null);
    $relasi = [
        'ayah' => 'Ayah', 'ibu' => 'Ibu', 'kakak' => 'Kakak', 'adik' => 'Adik',
        'paman' => 'Paman', 'bibi' => 'Bibi', 'kakek' => 'Kakek', 'nenek' => 'Nenek',
        'suami' => 'Suami', 'istri' => 'Istri', 'sepupu' => 'Sepupu', 'wali' => 'Wali',
        'anak' => 'Anak', 'lainnya' => 'Lainnya',
    ];
    $daftarMahrom = $allMahroms ?? collect([$mahrom]);
@endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Peserta Didik @endslot
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

    {{-- Banner santri --}}
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
            <div class="flex-grow-1">
                <div class="fw-semibold">{{ $student->name }}</div>
                <div class="text-muted small">
                    {{ $student->nisn ? 'NISN: ' . $student->nisn : '' }}
                    @if($student->currentClass)
                        &bull; {{ $student->currentClass->full_name }}
                    @endif
                </div>
            </div>
            <div class="text-end">
                <span class="badge bg-primary-subtle text-primary fs-12">
                    {{ $daftarMahrom->count() }} mahrom
                </span>
            </div>
        </div>
    </div>

    <div class="alert alert-info d-flex align-items-start gap-2 py-2">
        <i class="ri-information-line mt-1"></i>
        <div class="small mb-0">
            Semua mahrom santri ini ditampilkan di halaman ini. Setiap kartu disimpan sendiri —
            Anda bisa memperbaiki beberapa mahrom tanpa berpindah halaman.
        </div>
    </div>

    @foreach($daftarMahrom as $index => $item)
        @php
            $domId = 'mahrom-' . $index;
            $buka = $item->id === $mahrom->id;   // mahrom yang diklik terbuka otomatis
        @endphp

        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2"
                 role="button"
                 data-bs-toggle="collapse"
                 data-bs-target="#{{ $domId }}"
                 aria-expanded="{{ $buka ? 'true' : 'false' }}"
                 aria-controls="{{ $domId }}">
                <div class="flex-grow-1 d-flex align-items-center gap-2 flex-wrap">
                    <span class="fw-semibold">{{ $item->name }}</span>
                    <span class="badge bg-secondary-subtle text-secondary">
                        {{ $relasi[$item->relationship] ?? ucfirst($item->relationship) }}
                    </span>
                    @if($item->is_primary)
                        <span class="badge bg-warning-subtle text-warning">
                            <i class="ri-star-line me-1"></i>Utama
                        </span>
                    @endif
                    @if(! $item->is_active)
                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                    @endif
                </div>
                <i class="ri-arrow-down-s-line fs-18"></i>
            </div>

            <div class="collapse {{ $buka ? 'show' : '' }}" id="{{ $domId }}">
                <div class="card-body">
                    <form method="POST"
                          action="{{ route('user.students.mahroms.update', ['userId' => $userId, 'santriUuid' => $student->id, 'mahromUuid' => $item->id]) }}"
                          enctype="multipart/form-data"
                          class="form-edit-mahrom">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                       value="{{ old('name', $item->name) }}"
                                       placeholder="Nama lengkap mahrom" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NIK (Nomor Induk Kependudukan)</label>
                                <input type="text" name="id_number" class="form-control"
                                       value="{{ old('id_number', $item->id_number) }}"
                                       placeholder="16 digit NIK" maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hubungan dengan Santri <span class="text-danger">*</span></label>
                                <select name="relationship" class="form-control" required>
                                    <option value="">— Pilih Hubungan —</option>
                                    @foreach($relasi as $nilai => $teks)
                                        <option value="{{ $nilai }}" {{ old('relationship', $item->relationship) === $nilai ? 'selected' : '' }}>{{ $teks }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor Telepon</label>
                                <input type="text" name="phone" class="form-control"
                                       value="{{ old('phone', $item->phone) }}"
                                       placeholder="08xxxxxxxxxx" maxlength="20">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="2"
                                          placeholder="Alamat lengkap mahrom">{{ old('address', $item->address) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Catatan tambahan jika ada">{{ old('notes', $item->notes) }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_primary"
                                           id="primary-{{ $index }}" value="1"
                                           {{ old('is_primary', $item->is_primary) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="primary-{{ $index }}">
                                        <strong>Mahrom Utama</strong>
                                        <div class="text-muted small">Kontak utama penerima informasi santri.</div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active"
                                           id="active-{{ $index }}" value="1"
                                           {{ old('is_active', $item->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="active-{{ $index }}">
                                        <strong>Mahrom Aktif</strong>
                                        <div class="text-muted small">Yang nonaktif tidak dapat menjenguk santri.</div>
                                    </label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Foto Mahrom</label>
                                <div class="d-flex align-items-center gap-3">
                                    @if($item->photo_path)
                                        <img src="{{ asset('storage/' . $item->photo_path) }}"
                                             alt="Foto {{ $item->name }}"
                                             class="rounded border"
                                             style="width:64px;height:64px;object-fit:cover;">
                                    @endif
                                    <div class="flex-grow-1">
                                        <input type="file" name="photo" class="form-control" accept="image/*">
                                        <div class="form-text">
                                            JPG/PNG, maks. 2MB.
                                            @if($item->photo_path) Kosongkan bila tidak ingin mengubah foto. @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-3">
                            <button type="submit" class="btn btn-success btn-sm btn-submit-mahrom">
                                <i class="ri-save-line me-1"></i> Simpan
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm"
                                    data-hapus="{{ $index }}">
                                <i class="ri-delete-bin-line me-1"></i> Hapus
                            </button>
                        </div>
                    </form>

                    <form method="POST" id="form-hapus-{{ $index }}"
                          action="{{ route('user.students.mahroms.destroy', ['userId' => $userId, 'santriUuid' => $student->id, 'mahromUuid' => $item->id]) }}"
                          class="d-none" data-nama="{{ $item->name }}">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <div class="d-flex gap-2 justify-content-between mt-4">
        <a href="{{ route('user.students.mahroms.index', ['userId' => $userId, 'santriUuid' => $student->id]) }}"
           class="btn btn-light">
            <i class="ri-arrow-left-line me-1"></i> Kembali
        </a>
        @if($daftarMahrom->count() < (int) config('alim.max_mahrom', \App\Models\StudentMahrom::MAX_PER_STUDENT))
            <a href="{{ route('user.students.mahroms.create', ['userId' => $userId, 'santriUuid' => $student->id]) }}"
               class="btn btn-primary">
                <i class="ri-add-line me-1"></i> Tambah Mahrom
            </a>
        @endif
    </div>
@endsection

@section('js')
<script>
(function () {
    // Nonaktifkan tombol simpan pada form yang sedang dikirim agar tidak
    // terkirim dua kali, tanpa mengganggu form mahrom lain di halaman ini.
    document.querySelectorAll('.form-edit-mahrom').forEach(function (form) {
        form.addEventListener('submit', function () {
            var tombol = form.querySelector('.btn-submit-mahrom');
            if (tombol) {
                tombol.disabled = true;
                tombol.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
            }
        });
    });

    // Konfirmasi hapus per kartu.
    document.querySelectorAll('[data-hapus]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            var index = tombol.getAttribute('data-hapus');
            var form = document.getElementById('form-hapus-' + index);
            if (!form) return;

            var nama = form.getAttribute('data-nama') || 'mahrom ini';
            var pesan = 'Data "' + nama + '" akan dihapus permanen.';

            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Hapus mahrom?',
                    text: pesan,
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#d33',
                }).then(function (hasil) {
                    if (hasil.isConfirmed) form.submit();
                });
            } else if (window.confirm(pesan)) {
                form.submit();
            }
        });
    });
})();
</script>
@endsection
