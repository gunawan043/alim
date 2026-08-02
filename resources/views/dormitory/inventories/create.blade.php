@extends('layouts.master')
@section('title') Tambah Item Inventaris — Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.my-profile', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.inventories.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('li_4') Inventaris @endslot
        @slot('title') Tambah Item @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>Terjadi kesalahan pada formulir. Silakan perbaiki input Anda.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <form method="POST"
          action="{{ route('user.asrama.inventories.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
          id="inventoryForm"
          novalidate>
        @csrf

        <div class="row">
            {{-- ============================================================
                 MAIN FORM
            ============================================================ --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="ri-file-add-line me-2 text-primary"></i>Form Tambah Item Inventaris
                        </h5>
                        <p class="text-muted mb-0 mt-1 small">
                            Asrama: <strong>{{ $dormitory->name ?? 'Asrama' }}</strong>
                        </p>
                    </div>
                    <div class="card-body">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="room_id">
                                    Kamar <span class="text-danger">*</span>
                                </label>
                                <select name="room_id"
                                        id="room_id"
                                        class="form-select @error('room_id') is-invalid @enderror"
                                        required>
                                    <option value="">-- Pilih Kamar --</option>
                                    @foreach($rooms as $room)
                                        <option value="{{ $room->id }}"
                                                {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                            {{ $room->name }}
                                            @if(($room->residents_count ?? 0) >= ($room->capacity ?? 0))
                                                <span class="text-danger"> (Penuh)</span>
                                            @else
                                                ({{ $room->residents_count ?? 0 }}/{{ $room->capacity ?? 0 }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('room_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="item_name">
                                    Nama Item <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       name="item_name"
                                       id="item_name"
                                       class="form-control @error('item_name') is-invalid @enderror"
                                       placeholder="Contoh: Kasur, Lemari, Meja Belajar"
                                       value="{{ old('item_name') }}"
                                       required>
                                @error('item_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label" for="item_code">
                                    Kode Item
                                </label>
                                <input type="text"
                                       name="item_code"
                                       id="item_code"
                                       class="form-control @error('item_code') is-invalid @enderror"
                                       placeholder="Contoh: INV-A101-001"
                                       value="{{ old('item_code') }}">
                                <div class="form-text small">Kode inventaris unik untuk item ini (opsional).</div>
                                @error('item_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-2">
                                <label class="form-label" for="quantity">
                                    Jumlah <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                       name="quantity"
                                       id="quantity"
                                       class="form-control @error('quantity') is-invalid @enderror"
                                       placeholder="1"
                                       min="1"
                                       max="9999"
                                       value="{{ old('quantity', 1) }}"
                                       required>
                                @error('quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-2">
                                <label class="form-label" for="condition">
                                    Kondisi <span class="text-danger">*</span>
                                </label>
                                <select name="condition"
                                        id="condition"
                                        class="form-select @error('condition') is-invalid @enderror"
                                        required>
                                    <option value="">-- Pilih --</option>
                                    <option value="baik" {{ old('condition') === 'baik' ? 'selected' : '' }}>Baik</option>
                                    <option value="rusak" {{ old('condition') === 'rusak' ? 'selected' : '' }}>Rusak</option>
                                    <option value="perbaikan" {{ old('condition') === 'perbaikan' ? 'selected' : '' }}>Perlu Perbaikan</option>
                                    <option value="hilang" {{ old('condition') === 'hilang' ? 'selected' : '' }}>Hilang</option>
                                </select>
                                @error('condition')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-2">
                                <label class="form-label" for="category_id">
                                    Kategori <span class="text-danger">*</span>
                                </label>
                                <select name="category_id"
                                        id="category_id"
                                        class="form-select @error('category_id') is-invalid @enderror"
                                        required>
                                    <option value="">-- Pilih --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}"
                                                {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label" for="last_checked_at">
                                    Tanggal Terakhir Dicek
                                </label>
                                <input type="date"
                                       name="last_checked_at"
                                       id="last_checked_at"
                                       class="form-control @error('last_checked_at') is-invalid @enderror"
                                       value="{{ old('last_checked_at', now()->toDateString()) }}">
                                @error('last_checked_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-12">
                                <label class="form-label" for="notes">Catatan</label>
                                <textarea name="notes"
                                          id="notes"
                                          class="form-control @error('notes') is-invalid @enderror"
                                          rows="3"
                                          placeholder="Catatan tambahan tentang kondisi atau informasi lain...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================================
                 RIGHT COLUMN — INFO
            ============================================================ --}}
            <div class="col-lg-4">
                <div class="card bg-light border-0">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="ri-information-line text-primary me-2"></i>Petunjuk
                        </h6>
                        <ul class="ps-3 mb-0 small">
                            <li class="mb-2">Pilih kamar tempat item inventaris berada.</li>
                            <li class="mb-2">Nama item harus jelas dan deskriptif (misal: "Kasur Single", bukan hanya "Kasur").</li>
                            <li class="mb-2">Kode item bersifat opsional tetapi sangat membantu dalam pelacakan.</li>
                            <li class="mb-2">Pilih kondisi item yang sesuai: baik, rusak, perlu perbaikan, atau hilang.</li>
                            <li class="mb-2"><strong>Kategori</strong> barang mengacu pada klasifikasi Asset SARPRAS (misal: Meubelair, Elektronik, dll).</li>
                            <li>Update tanggal pengecekan secara berkala untuk menjaga akurasi data.</li>
                        </ul>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="ri-lightbulb-line me-2 text-warning"></i>Tips
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="ps-3 mb-0 small text-muted">
                            <li class="mb-2">Item dalam kondisi <strong>"Perlu Perbaikan"</strong> akan masuk dalam daftar prioritas maintenance.</li>
                            <li>Item <strong>"Rusak"</strong> sebaiknya segera diganti atau dihapus dari inventaris.</li>
                            <li>Gunakan kode item yang konsisten, misalnya: <code>INV-[KAMAR]-[NOMOR]</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             ACTION BUTTONS
        ============================================================ --}}
        <div class="row mt-3">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <a href="{{ route('user.asrama.inventories.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                           class="btn btn-light">
                            <i class="ri-arrow-left-line align-middle me-1"></i> Kembali
                        </a>
                        <div class="d-flex gap-2">
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="ri-reset-right-line align-middle me-1"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="ri-save-line align-middle me-1"></i> Simpan Item
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
