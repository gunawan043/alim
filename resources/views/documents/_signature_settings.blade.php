@php
    $signatureService = app(\App\Services\Documents\DocumentVisualService::class);
    $signatureSchool = $school
        ?? request()->attributes->get('schoolContext')
        ?? \App\Models\School::find(request()->attributes->get('schoolContextId'))
        ?? \App\Services\SchoolGroupService::getUserSchool(auth()->user());
    $signatureSettings = $signatureService->settings($signatureSchool, $type);
    $signatureMedia = $signatureService->viewData($signatureSchool, $type);
    $signatureModes = $signatureService->modeOptions();
    $signatureLeader = app(\App\Services\Documents\SchoolLeaderService::class)->forSchool($signatureSchool);
@endphp

@if (! $signatureSchool)
    <div class="alert alert-warning mb-0">Satuan pendidikan belum dipilih, pengaturan tanda tangan belum dapat disimpan.</div>
@else
    <form method="POST" id="signatureSettingsForm-{{ $type }}"
        action="{{ route('user.students.documents.signature-settings', ['userId' => $userId]) }}"
        enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        <input type="hidden" name="school_id" value="{{ $signatureSchool->id }}">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Mode validasi</label>
                <select name="validation_mode" class="form-select">
                    @foreach ($signatureModes as $mode)
                        <option value="{{ $mode['value'] }}"
                            @selected($signatureSettings['validation_mode'] === $mode['value'])
                            @disabled($mode['disabled'])>{{ $mode['label'] }}</option>
                    @endforeach
                </select>
                <div class="form-text">
                    Mode QR/PAdES: TTD &amp; stempel tidak dicetak. Pilih mode visual jika ingin mencetak TTD/stempel.
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanda tangan KSP</label>
                <select name="show_ttd_ksp" class="form-select">
                    <option value="1" @selected($signatureSettings['show_ttd_ksp'])>Tampilkan</option>
                    <option value="0" @selected(! $signatureSettings['show_ttd_ksp'])>Sembunyikan</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Stempel</label>
                <select name="show_stamp" class="form-select">
                    <option value="1" @selected($signatureSettings['show_stamp'])>Tampilkan</option>
                    <option value="0" @selected(! $signatureSettings['show_stamp'])>Sembunyikan</option>
                </select>
            </div>
        </div>

        @php
            $leaderOverride = is_array($signatureSchool?->document_visual_settings['leader'] ?? null)
                ? $signatureSchool->document_visual_settings['leader']
                : [];
        @endphp
        <div class="row g-3 mt-1">
            <div class="col-md-7">
                <label class="form-label">Nama &amp; gelar kepala sekolah (khusus dokumen)</label>
                <input type="text" name="document_leader_name" class="form-control"
                       value="{{ old('document_leader_name', $leaderOverride['name'] ?? $signatureLeader['display_name']) }}"
                       placeholder="Kosongkan untuk otomatis dari data GTK">
                <div class="form-text">Terisi otomatis dari data GTK. Ubah bila perlu; data GTK tidak berubah. Hapus isi kedua kolom untuk kembali otomatis.</div>
            </div>
            <div class="col-md-5">
                <label class="form-label">NUPY (khusus dokumen)</label>
                <input type="text" name="document_leader_nupy" class="form-control"
                       value="{{ old('document_leader_nupy', $leaderOverride['nupy'] ?? $signatureLeader['nupy']) }}"
                       placeholder="Kosongkan untuk otomatis">
            </div>
        </div>

        <div class="row g-3 mt-1">
            @foreach([
                'ttd_width' => ['label' => 'Lebar TTD (mm)', 'min' => 5, 'max' => 100, 'step' => 0.5],
                'ttd_height' => ['label' => 'Tinggi TTD (mm)', 'min' => 5, 'max' => 60, 'step' => 0.5],
                'ttd_margin' => ['label' => 'Margin TTD (mm)', 'min' => 0, 'max' => 30, 'step' => 0.5],
                'stamp_width' => ['label' => 'Lebar stempel (mm)', 'min' => 5, 'max' => 100, 'step' => 0.5],
                'stamp_height' => ['label' => 'Tinggi stempel (mm)', 'min' => 5, 'max' => 100, 'step' => 0.5],
                'stamp_margin' => ['label' => 'Margin stempel (mm)', 'min' => 0, 'max' => 30, 'step' => 0.5],
            ] as $field => $meta)
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">{{ $meta['label'] }}</label>
                    <input type="number" name="{{ $field }}" class="form-control form-control-sm"
                           min="{{ $meta['min'] }}" max="{{ $meta['max'] }}" step="{{ $meta['step'] }}" required
                           value="{{ old($field, $signatureSettings[$field]) }}">
                </div>
            @endforeach
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <label class="form-label">Unggah tanda tangan KSP</label>
                <input type="file" name="ttd_ksp_file" class="form-control form-control-sm" accept="image/png,image/jpeg">
                @if (! empty($signatureMedia['ttd_ksp']))
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <img src="{{ $signatureMedia['ttd_ksp'] }}" alt="TTD KSP" height="48" style="object-fit:contain">
                        <label class="small text-danger mb-0">
                            <input type="checkbox" name="remove_ttd_ksp" value="1"> Hapus tanda tangan lama
                        </label>
                    </div>
                @else
                    <div class="form-text">Belum ada tanda tangan. Unggah PNG/JPG latar transparan.</div>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label">Unggah stempel sekolah</label>
                <input type="file" name="stamp_file" class="form-control form-control-sm" accept="image/png,image/jpeg">
                @if (! empty($signatureMedia['stamp']))
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <img src="{{ $signatureMedia['stamp'] }}" alt="Stempel" height="48" style="object-fit:contain">
                        <label class="small text-danger mb-0">
                            <input type="checkbox" name="remove_stamp" value="1"> Hapus stempel lama
                        </label>
                    </div>
                @else
                    <div class="form-text">Belum ada stempel. Unggah PNG/JPG latar transparan.</div>
                @endif
            </div>
        </div>

        <div class="alert alert-light border small mt-3 mb-0">
            <i class="ri-shield-check-line me-1 text-primary"></i>
            Pada mode QR/PAdES, TTD/stempel tidak dicetak karena QR/signature menjadi pengesahan.
            Pilih mode visual jika ingin mencetak TTD/stempel.
        </div>

        <div class="text-end mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="ri-save-3-line me-1"></i>Simpan Tanda Tangan
            </button>
        </div>
    </form>
@endif

<script>
(function () {
    var form = document.getElementById(@json('signatureSettingsForm-'.$type));
    if (!form) return;
    var mode = form.querySelector('select[name="validation_mode"]');
    var fields = form.querySelectorAll('[name="show_ttd_ksp"], [name="show_stamp"], [name="ttd_width"], [name="ttd_height"], [name="ttd_margin"], [name="stamp_width"], [name="stamp_height"], [name="stamp_margin"]');
    function sync() {
        var visual = mode && mode.value === 'visual';
        fields.forEach(function (field) {
            field.disabled = !visual;
            field.closest('.col-md-3, .col-6, .col-md-2')?.classList.toggle('opacity-50', !visual);
        });
    }
    if (mode) {
        mode.addEventListener('change', sync);
        sync();
    }
})();
</script>
