{{--
    Modal form untuk Tambah / Edit Master Jenis Izin.

    Dipakai oleh PermitTypeController::create (mode create) dan edit (mode edit).
    Field label, category, icon, color, is_active, sort_order selalu dipakai.
    Field code opsional: jika kosong, otomatis di-generate dari label.
--}}
<div class="modal fade" id="permitTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="permitTypeForm" method="POST" action="">
                @csrf
                <input type="hidden" name="_method" id="permitTypeFormMethod" value="POST" />
                <input type="hidden" name="id" id="permitTypeId" value="" />

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ri-file-list-3-line me-1"></i>
                        <span id="permitTypeModalTitle">Tambah Jenis Izin</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="ri-information-line me-1"></i>
                        Perubahan master jenis izin akan berlaku untuk semua asrama. Data konfigurasi lama tetap aman walau jenis izin dihapus.
                    </div>

                    <div class="row g-3">
                        {{-- Label --}}
                        <div class="col-md-6">
                            <label for="ptLabel" class="form-label fw-semibold">Nama Tampil <span class="text-danger">*</span></label>
                            <input type="text" id="ptLabel" name="label" maxlength="100"
                                   class="form-control @error('label') is-invalid @enderror"
                                   value="{{ old('label') }}" required
                                   placeholder="Contoh: Izin Pulang Kampung">
                            @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Code (auto-generated, editable) --}}
                        <div class="col-md-6">
                            <label for="ptCode" class="form-label fw-semibold">Kode Unik</label>
                            <input type="text" id="ptCode" name="code" maxlength="50"
                                   class="form-control @error('code') is-invalid @enderror"
                                   value="{{ old('code') }}"
                                   placeholder="izin_pulang">
                            <small class="text-muted">Otomatis dari nama (snake_case). Boleh diubah.</small>
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Kategori --}}
                        <div class="col-md-6">
                            <label for="ptCategory" class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                            <select id="ptCategory" name="category" class="form-select @error('category') is-invalid @enderror" required>
                                <option value="default" {{ old('category') === 'default' ? 'selected' : '' }}>Default — untuk semua asrama</option>
                                <option value="special" {{ old('category') === 'special' ? 'selected' : '' }}>Khusus — izin khusus (punya mode kuota)</option>
                                <option value="emergency" {{ old('category') === 'emergency' ? 'selected' : '' }}>Darurat — bypass & notifikasi WA</option>
                                <option value="custom" {{ old('category', 'custom') === 'custom' ? 'selected' : '' }}>Kustom — tambahan opsional</option>
                            </select>
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Sort Order --}}
                        <div class="col-md-6">
                            <label for="ptSortOrder" class="form-label fw-semibold">Urutan Tampil</label>
                            <input type="number" id="ptSortOrder" name="sort_order" min="0" max="9999"
                                   class="form-control @error('sort_order') is-invalid @enderror"
                                   value="{{ old('sort_order', 100) }}">
                            <small class="text-muted">Angka kecil = tampil lebih dulu.</small>
                            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Ikon & Warna --}}
                        <div class="col-md-6">
                            <label for="ptIcon" class="form-label fw-semibold">Ikon (Remix Icon)</label>
                            <input type="text" id="ptIcon" name="icon" maxlength="50"
                                   class="form-control @error('icon') is-invalid @enderror"
                                   value="{{ old('icon', 'ri-file-list-3-line') }}"
                                   placeholder="ri-file-list-3-line">
                            <small class="text-muted">Lihat: <a href="https://remixicon.com/" target="_blank" rel="noopener">remixicon.com</a></small>
                            @error('icon')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="ptColor" class="form-label fw-semibold">Warna Badge</label>
                            <select id="ptColor" name="color" class="form-select @error('color') is-invalid @enderror">
                                @php
                                    $colorChoices = ['primary','success','danger','warning','info','secondary','dark'];
                                @endphp
                                @foreach ($colorChoices as $c)
                                    <option value="{{ $c }}" {{ old('color', 'primary') === $c ? 'selected' : '' }}>{{ ucfirst($c) }}</option>
                                @endforeach
                            </select>
                            @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Deskripsi --}}
                        <div class="col-12">
                            <label for="ptDescription" class="form-label fw-semibold">Deskripsi</label>
                            <textarea id="ptDescription" name="description" maxlength="500" rows="2"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Penjelasan singkat (opsional)">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Aktif / Nonaktif --}}
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="ptIsActive" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="ptIsActive">
                                    <strong>Aktifkan</strong>
                                    <small class="d-block text-muted">Jika nonaktif, jenis izin ini tidak muncul di pilihan pengajuan dan di tabel konfigurasi (tetap bisa diaktifkan kembali).</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary fw-medium material-shadow-none" data-bs-dismiss="modal">
                        <i class="ri-close-line me-1 align-middle"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
