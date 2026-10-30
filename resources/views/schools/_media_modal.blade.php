    {{-- Modal Upload Kop, TTD & Stempel --}}
    <div class="modal fade" id="schoolMediaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data"
                      action="{{ route('user.schools.media', ['userId' => $userId, 'schoolId' => $school->id]) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-upload-cloud-2-line me-2 text-primary"></i>Upload Kop, TTD &amp; Stempel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2 small">
                            <i class="ri-information-line me-1"></i>
                            Format gambar JPG/PNG. Logo dan kop maks. 2 MB; TTD/stempel maks. 1 MB. Centang hapus jika ingin menghapus file lama.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Logo Sekolah</label>
                                <input type="file" name="logo_path" class="form-control" accept="image/png,image/jpeg,image/webp">
                                @if($school->logo_path)
                                    <div class="mt-2 p-2 border rounded d-flex align-items-center gap-2">
                                        <img src="{{ $school->logo_url }}" width="52" height="52" class="rounded" style="object-fit:cover" alt="Logo">
                                        <label class="small text-danger mb-0">
                                            <input type="checkbox" name="remove_logo_path" value="1"> Hapus logo lama
                                        </label>
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">File Kop Surat</label>
                                <input type="file" name="kop_path" class="form-control" accept="image/png,image/jpeg,image/webp">
                                @if($school->kop_path)
                                    <div class="mt-2 p-2 border rounded d-flex align-items-center gap-2">
                                        <img src="{{ asset('storage/'.$school->kop_path) }}" height="52" class="rounded" style="object-fit:contain" alt="Kop">
                                        <label class="small text-danger mb-0">
                                            <input type="checkbox" name="remove_kop_path" value="1"> Hapus kop lama
                                        </label>
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">TTD Kepala Sekolah (KSP)</label>
                                <input type="file" name="ttd_ksp_path" class="form-control" accept="image/png,image/jpeg">
                                @if($school->ttd_ksp_path)
                                    <div class="mt-2 p-2 border rounded d-flex align-items-center gap-2">
                                        <img src="{{ asset('storage/'.$school->ttd_ksp_path) }}" height="60" class="rounded" style="object-fit:contain" alt="TTD KSP">
                                        <label class="small text-danger mb-0">
                                            <input type="checkbox" name="remove_ttd_ksp_path" value="1"> Hapus TTD lama
                                        </label>
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Stempel Sekolah</label>
                                <input type="file" name="stamp_path" class="form-control" accept="image/png,image/jpeg">
                                @if($school->stamp_path)
                                    <div class="mt-2 p-2 border rounded d-flex align-items-center gap-2">
                                        <img src="{{ asset('storage/'.$school->stamp_path) }}" height="60" class="rounded" style="object-fit:contain" alt="Stempel">
                                        <label class="small text-danger mb-0">
                                            <input type="checkbox" name="remove_stamp_path" value="1"> Hapus stempel lama
                                        </label>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @php
                            $visualService = app(\App\Services\Documents\DocumentVisualService::class);
                            $visualTypes = \App\Services\Documents\DocumentVisualService::LABELS;
                            $visualModes = $visualService->modeOptions();
                        @endphp
                        <hr class="my-4">
                        <h6 class="mb-2"><i class="ri-shield-check-line me-1 text-primary"></i>Validasi &amp; Tampilan Dokumen</h6>
                        <p class="text-muted small mb-3">
                            Mode <strong>QR/PAdES</strong> menjadikan QR/signature sebagai pengesahan, sehingga TTD dan stempel tidak dicetak. Mode <strong>visual</strong> mencetak TTD/stempel tanpa QR. Rapor diatur terpisah pada pengaturan tata letak rapor.
                        </p>
                        @php
                            $leaderOverride = is_array($school->document_visual_settings['leader'] ?? null)
                                ? $school->document_visual_settings['leader']
                                : [];
                            $autoLeader = app(\App\Services\Documents\SchoolLeaderService::class)->forSchool($school);
                        @endphp
                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label class="form-label small text-muted mb-1">Nama &amp; gelar kepala sekolah (khusus dokumen)</label>
                                <input type="text" name="document_leader_name" class="form-control form-control-sm"
                                       value="{{ old('document_leader_name', $leaderOverride['name'] ?? $autoLeader['display_name']) }}"
                                       placeholder="Kosongkan untuk otomatis dari data GTK">
                                <div class="form-text">Terisi otomatis dari data GTK. Hapus isi untuk kembali otomatis.</div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small text-muted mb-1">NUPY (khusus dokumen)</label>
                                <input type="text" name="document_leader_nupy" class="form-control form-control-sm"
                                       value="{{ old('document_leader_nupy', $leaderOverride['nupy'] ?? $autoLeader['nupy']) }}"
                                       placeholder="Kosongkan untuk otomatis">
                            </div>
                        </div>
                        <div class="row g-3">
                            @foreach($visualTypes as $type => $label)
                                @php $visual = $visualService->settings($school, $type); @endphp
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100" data-visual-type="{{ $type }}">
                                        <div class="fw-semibold mb-2">{{ $label }}</div>
                                        <div class="mb-2">
                                            <label class="form-label small text-muted mb-1">Mode validasi</label>
                                            <select name="document_visual[{{ $type }}][validation_mode]" class="form-select form-select-sm">
                                                @foreach($visualModes as $mode)
                                                    <option value="{{ $mode['value'] }}"
                                                        @selected($visual['validation_mode'] === $mode['value'])
                                                        @disabled($mode['disabled'])>{{ $mode['label'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <label class="form-label small text-muted mb-1">TTD KSP</label>
                                                <select name="document_visual[{{ $type }}][show_ttd_ksp]" class="form-select form-select-sm">
                                                    <option value="1" @selected($visual['show_ttd_ksp'])>Tampilkan</option>
                                                    <option value="0" @selected(! $visual['show_ttd_ksp'])>Sembunyikan</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small text-muted mb-1">Stempel</label>
                                                <select name="document_visual[{{ $type }}][show_stamp]" class="form-select form-select-sm">
                                                    <option value="1" @selected($visual['show_stamp'])>Tampilkan</option>
                                                    <option value="0" @selected(! $visual['show_stamp'])>Sembunyikan</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row g-2 mt-1">
                                            @foreach([
                                                'ttd_width' => ['label' => 'L TTD', 'min' => 5, 'max' => 100],
                                                'ttd_height' => ['label' => 'T TTD', 'min' => 5, 'max' => 60],
                                                'ttd_margin' => ['label' => 'M TTD', 'min' => 0, 'max' => 30],
                                                'stamp_width' => ['label' => 'L Stempel', 'min' => 5, 'max' => 100],
                                                'stamp_height' => ['label' => 'T Stempel', 'min' => 5, 'max' => 100],
                                                'stamp_margin' => ['label' => 'M Stempel', 'min' => 0, 'max' => 30],
                                            ] as $field => $meta)
                                                <div class="col-4">
                                                    <label class="form-label small text-muted mb-1">{{ $meta['label'] }} (mm)</label>
                                                    <input type="number" name="document_visual[{{ $type }}][{{ $field }}]"
                                                           class="form-control form-control-sm" min="{{ $meta['min'] }}"
                                                           max="{{ $meta['max'] }}" step="0.5" required
                                                           value="{{ $visual[$field] }}">
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-3-line me-1"></i>Simpan Dokumen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
(function () {
    document.querySelectorAll('[data-visual-type]').forEach(function (box) {
        var mode = box.querySelector('select[name$="[validation_mode]"]');
        var fields = box.querySelectorAll('select[name$="[show_ttd_ksp]"], select[name$="[show_stamp]"], input[name$="[ttd_width]"], input[name$="[ttd_height]"], input[name$="[ttd_margin]"], input[name$="[stamp_width]"], input[name$="[stamp_height]"], input[name$="[stamp_margin]"]');
        function sync() {
            var visual = mode && mode.value === 'visual';
            fields.forEach(function (field) {
                field.disabled = !visual;
                field.closest('.col-4, .col-6')?.classList.toggle('opacity-50', !visual);
            });
        }
        if (mode) {
            mode.addEventListener('change', sync);
            sync();
        }
    });
})();
</script>
