{{-- Mutaba'ah harian satu halaqah dalam satu layar.
     Menyimpan bertahap per santri agar guru bisa langsung lanjut ke santri
     berikutnya tanpa menunggu halaman dimuat ulang. --}}
@php
    $userId = $userId ?? auth()->id();
    $mutabaahRoute = 'user.tahfidz.mutabaah';
    $sholatOptions = ['berjamaah' => 'Berjamaah', 'sendiri' => 'Sendiri', 'qadha' => 'Qadha', 'tidak' => 'Tidak', 'uzur' => 'Uzur'];
    $mutabaahStoreUrl = route($mutabaahRoute.'.store', ['userId' => $userId]);
    $mutabaahPrintBase = route($mutabaahRoute.'.print', ['userId' => $userId, 'studentId' => '__SID__']);
@endphp

<div class="table-responsive">
    <table class="table table-sm align-middle mb-0 bulk-table">
        <thead class="table-light">
            <tr>
                <th style="width:44px" class="text-center">#</th>
                <th>Santri</th>
                @foreach(['sholat_subuh' => 'Subuh', 'sholat_dzuhur' => 'Dzuhur', 'sholat_ashar' => 'Ashar', 'sholat_maghrib' => 'Maghrib', 'sholat_isya' => 'Isya'] as $field => $label)
                    <th class="text-center" style="width:118px">{{ $label }}</th>
                @endforeach
                <th class="text-center" style="width:86px">Tahajud</th>
                <th class="text-center" style="width:86px">Dhuha</th>
                <th class="text-center" style="width:86px">Puasa</th>
                <th class="text-center" style="width:86px">Sedekah</th>
                <th class="text-center" style="width:86px">Wirid P/S</th>
                <th class="text-center" style="width:96px">Tilawah</th>
                <th class="text-center" style="width:96px">Tikror</th>
                <th style="width:150px">Catatan Musyrif</th>
                <th class="text-end" style="width:96px">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mutabaahRows as $index => $row)
                @php
                    $student = $row['student'];
                    $record = $row['record'];
                    $value = fn ($field) => $record?->{$field};
                    $checked = fn ($field) => (int) ($record?->{$field} ?? 0) === 1;
                @endphp
                <tr class="{{ $record ? 'is-filled' : '' }}">
                    <td class="text-center text-muted">{{ $index + 1 }}</td>
                    <td>
                        <div class="fw-medium">{{ $student->name }}</div>
                        <div class="text-muted small">{{ $student->nis }}</div>
                    </td>
                    @foreach(['sholat_subuh', 'sholat_dzuhur', 'sholat_ashar', 'sholat_maghrib', 'sholat_isya'] as $field)
                        <td class="text-center">
                            <select class="form-select form-select-sm mutabaah-field" data-field="{{ $field }}">
                                <option value="">—</option>
                                @foreach($sholatOptions as $optValue => $optLabel)
                                    <option value="{{ $optValue }}" @selected($value($field) === $optValue)>{{ $optLabel }}</option>
                                @endforeach
                            </select>
                        </td>
                    @endforeach
                    @foreach(['sholat_tahajud' => 'Tahajud', 'sholat_dhuha' => 'Dhuha', 'puasa_sunnah' => 'Puasa', 'sedekah' => 'Sedekah'] as $field => $label)
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block m-0">
                                <input type="checkbox" class="form-check-input mutabaah-field" data-field="{{ $field }}" @checked($checked($field)) aria-label="{{ $label }} {{ $student->name }}">
                            </div>
                        </td>
                    @endforeach
                    <td class="text-center text-nowrap">
                        <div class="form-check form-switch d-inline-block m-0" title="Wirid pagi">
                            <input type="checkbox" class="form-check-input mutabaah-field" data-field="wirid_pagi" @checked($checked('wirid_pagi')) aria-label="Wirid pagi {{ $student->name }}">
                        </div>
                        <div class="form-check form-switch d-inline-block m-0 ms-1" title="Wirid sore">
                            <input type="checkbox" class="form-check-input mutabaah-field" data-field="wirid_sore" @checked($checked('wirid_sore')) aria-label="Wirid sore {{ $student->name }}">
                        </div>
                    </td>
                    <td class="text-center">
                        <input type="number" min="0" max="604" class="form-control form-control-sm text-center mutabaah-field" data-field="tilawah_halaman" value="{{ $value('tilawah_halaman') }}" aria-label="Tilawah {{ $student->name }}">
                    </td>
                    <td class="text-center">
                        <input type="number" min="0" max="604" class="form-control form-control-sm text-center mutabaah-field" data-field="tikror_mandiri_halaman" value="{{ $value('tikror_mandiri_halaman') }}" aria-label="Tikror {{ $student->name }}">
                    </td>
                    <td>
                        <input type="text" maxlength="3000" class="form-control form-control-sm mutabaah-field" data-field="catatan_musyrif" value="{{ $value('catatan_musyrif') }}" aria-label="Catatan {{ $student->name }}">
                    </td>
                    <td class="text-end text-nowrap">
                        <button type="button" class="btn btn-sm btn-success mutabaah-save" title="Simpan mutaba'ah santri ini"
                                data-student="{{ $student->id }}" data-name="{{ $student->name }}"><i class="ri-save-line"></i></button>
                        <a class="btn btn-sm btn-outline-dark" target="_blank"
                           href="{{ str_replace('__SID__', $student->id, $mutabaahPrintBase) }}?month={{ \Carbon\Carbon::parse($dateValue)->format('Y-m') }}&group_id={{ $group->id }}"
                           title="Cetak waraqat bulan ini"><i class="ri-printer-line"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="14" class="text-center text-muted py-5"><i class="ri-calendar-check-line fs-3 d-block mb-2"></i>Belum ada santri aktif di halaqah ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($mutabaahRows->isNotEmpty())
    <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="text-muted small"><i class="ri-information-line me-1"></i>Tekan tombol simpan pada baris santri. Isian lain dibiarkan.</span>
        <span class="text-muted small" id="mutabaahStatus"></span>
    </div>
@endif

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const storeUrl = @json($mutabaahStoreUrl);
        const groupId = @json($group?->id);
        const recordDate = @json($dateValue);
        const status = document.getElementById('mutabaahStatus');
        const token = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());

        function collect(row) {
            const payload = {
                tahfidz_group_id: groupId,
                student_id: row.dataset.student,
                record_date: recordDate,
            };

            row.querySelectorAll('.mutabaah-field').forEach(function (field) {
                const name = field.dataset.field;
                if (field.type === 'checkbox') {
                    payload[name] = field.checked ? 1 : 0;
                } else {
                    payload[name] = field.value === '' ? null : field.value;
                }
            });

            return payload;
        }

        document.querySelectorAll('.mutabaah-save').forEach(function (button) {
            button.addEventListener('click', async function () {
                const row = this.closest('tr');
                const originalHtml = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                try {
                    const response = await fetch(storeUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(collect(row)),
                    });

                    if (! response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }

                    row.classList.add('is-filled');
                    if (status) {
                        status.textContent = 'Tersimpan: ' + this.dataset.name;
                    }
                } catch (e) {
                    if (status) {
                        status.textContent = 'Gagal menyimpan ' + this.dataset.name + '. Coba lagi.';
                    }
                } finally {
                    this.disabled = false;
                    this.innerHTML = originalHtml;
                }
            });
        });
    });
</script>
@endpush
