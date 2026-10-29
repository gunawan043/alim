@php
    $ps = $printSettings ?? [];
    $val = fn ($key, $default = '') => old($key, $ps[$key] ?? $default);
@endphp

<div class="modal fade" id="printSettingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <form method="POST"
                  action="{{ route('user.institution-decrees.print-settings', ['userId' => $userId, 'id' => $decree->id]) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-printer-settings-line me-2 text-primary"></i>Pengaturan Cetak PDF SK</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Ukuran Kertas</label>
                            <select name="paper_size" class="form-select form-select-sm">
                                @foreach(['a4' => 'A4', 'folio' => 'F4 / Folio', 'letter' => 'Letter'] as $value => $label)
                                    <option value="{{ $value }}" {{ (string) $val('paper_size', 'a4') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Orientasi</label>
                            <select name="orientation" class="form-select form-select-sm">
                                <option value="landscape" {{ $val('orientation', 'landscape') === 'landscape' ? 'selected' : '' }}>Landscape</option>
                                <option value="portrait" {{ $val('orientation', 'landscape') === 'portrait' ? 'selected' : '' }}>Portrait</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Kota Penandatanganan</label>
                            <input type="text" name="city" class="form-control form-control-sm" maxlength="100"
                                   value="{{ $val('city', $decree->school?->city?->display_name ?? '') }}" placeholder="Mataram">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input type="hidden" name="show_kop" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="ps_show_kop" name="show_kop" value="1"
                                       {{ $val('show_kop', false) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="ps_show_kop">Tampilkan kop sekolah</label>
                            </div>
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        <div class="col-12">
                            <h6 class="small fw-semibold mb-2">Margin halaman (mm)</h6>
                            <div class="row g-2">
                                @foreach(['margin_top' => 'Atas', 'margin_right' => 'Kanan', 'margin_bottom' => 'Bawah', 'margin_left' => 'Kiri'] as $field => $label)
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small mb-1">{{ $label }}</label>
                                        <input type="number" name="{{ $field }}" class="form-control form-control-sm" min="0" max="50" step="0.5" required
                                               value="{{ $val($field, 15) }}">
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-12">
                            <h6 class="small fw-semibold mb-2">Ukuran font (pt) &amp; spasi</h6>
                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <label class="form-label small mb-1">Judul</label>
                                    <input type="number" name="title_font_size" class="form-control form-control-sm" min="6" max="30" step="0.5" required value="{{ $val('title_font_size', 13) }}">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small mb-1">Info</label>
                                    <input type="number" name="info_font_size" class="form-control form-control-sm" min="5" max="24" step="0.5" required value="{{ $val('info_font_size', 10) }}">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small mb-1">Tabel</label>
                                    <input type="number" name="table_font_size" class="form-control form-control-sm" min="5" max="20" step="0.5" required value="{{ $val('table_font_size', 9) }}">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small mb-1">Spasi baris</label>
                                    <input type="number" name="line_height" class="form-control form-control-sm" min="1" max="2.5" step="0.05" required value="{{ $val('line_height', 1.25) }}">
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <h6 class="small fw-semibold mb-2">Tanda tangan &amp; stempel</h6>
                            <div class="row g-2 mb-2">
                                @foreach(['show_ttd_ksp' => 'Tampilkan TTD', 'show_stamp' => 'Tampilkan stempel'] as $field => $label)
                                    <div class="col-6 col-md-3">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="{{ $field }}" value="0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="ps_{{ $field }}" name="{{ $field }}" value="1"
                                                   {{ $val($field, true) ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="ps_{{ $field }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="row g-2">
                                @foreach([
                                    'ttd_width' => 'TTD lebar', 'ttd_height' => 'TTD tinggi', 'ttd_margin' => 'TTD margin',
                                    'stamp_width' => 'Stempel lebar', 'stamp_height' => 'Stempel tinggi', 'stamp_margin' => 'Stempel margin',
                                ] as $field => $label)
                                    <div class="col-6 col-md-2">
                                        <label class="form-label small mb-1">{{ $label }} (mm)</label>
                                        <input type="number" name="{{ $field }}" class="form-control form-control-sm" min="0" max="100" step="0.5"
                                               value="{{ $val($field, $field === 'ttd_height' ? 15 : 30) }}" {{ str_contains($field, 'margin') ? '' : 'required' }}>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>
</div>
