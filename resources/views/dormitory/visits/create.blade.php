@extends('layouts.master')
@section('title') Ajukan Kunjungan Asrama @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Kunjungan</a> @endslot
        @slot('title') Ajukan Kunjungan @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="ri-error-warning-line me-2 fs-18" aria-hidden="true"></i>
                <strong>Terjadi kesalahan:</strong>
            </div>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1" aria-hidden="true"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <form method="POST"
          action="{{ route('user.asrama.visits.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
          enctype="multipart/form-data"
          id="visitForm">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-user-add-line me-2 text-primary" aria-hidden="true"></i>Data Tamu / Visitor</h5>
                    </div>
                    <div class="card-body">
                        {{-- Student Search / Selection --}}
                        <div class="mb-4">
                            <label for="studentSelect" class="form-label">Santri yang Dikunjungi <span class="text-danger">*</span></label>
                            <select name="student_id" id="studentSelect" class="form-control @error('student_id') is-invalid @enderror" required aria-describedby="studentSelectHelp">
                                <option value="">— Pilih Santri —</option>
                                @foreach($students ?? [] as $s)
                                    @php
                                        $hasConsent = in_array((string) $s->id, $consentedStudentIds ?? [], true);
                                        $consentAllowed = ($consentRequired ?? true) === false || $hasConsent;
                                    @endphp
                                    <option value="{{ $s->id }}" {{ old('student_id') == $s->id ? 'selected' : '' }}
                                            data-name="{{ $s->name }}" data-nisn="{{ $s->nisn }}" data-gender="{{ $s->gender }}"
                                            data-has-consent="{{ $consentAllowed ? '1' : '0' }}">
                                        {{ $s->name }} — {{ $s->nisn ?? 'N/A' }}{{ $consentAllowed ? '' : ' (belum consent PDP)' }}
                                    </option>
                                @endforeach
                            </select>
                            <small id="studentSelectHelp" class="text-muted">Pilih santri yang dikunjungi.</small>
                            <div id="consentHint" class="alert alert-danger py-2 mt-2 d-none mb-0">
                                <i class="ri-shield-cross-line me-1"></i>
                                Santri ini belum punya <strong>persetujuan data wali (PDP)</strong>. Catat dulu di
                                <strong>Detail Santri</strong> → kartu <strong>Persetujuan Data Wali</strong>, baru kunjungan bisa disimpan.
                            </div>
                            @error('student_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @error('consent')
                                <div class="alert alert-danger py-2 mt-2 mb-0">
                                    <i class="ri-shield-cross-line me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <hr class="my-4">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="visitor_name" class="form-label">Nama Tamu <span class="text-danger">*</span></label>
                                <input type="text" name="visitor_name" id="visitor_name" class="form-control @error('visitor_name') is-invalid @enderror"
                                       value="{{ old('visitor_name') }}" placeholder="Nama lengkap tamu" required>
                                @error('visitor_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="visitor_id_number" class="form-label">Nomor Identitas (KTP/Passport/Kartu Kunjungan) <span class="text-danger">*</span></label>
                                <input type="text" name="visitor_id_number" id="visitor_id_number" class="form-control @error('visitor_id_number') is-invalid @enderror"
                                       value="{{ old('visitor_id_number') }}" placeholder="Nomor KTP atau passport" maxlength="30">
                                @error('visitor_id_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="visitor_phone" class="form-label">Nomor Telepon</label>
                                <input type="text" name="visitor_phone" id="visitor_phone" class="form-control @error('visitor_phone') is-invalid @enderror"
                                       value="{{ old('visitor_phone') }}" placeholder="08xxxxxxxxxx" maxlength="20">
                                @error('visitor_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="visitor_gender" class="form-label">Gender Tamu <span class="text-danger">*</span></label>
                                <select name="visitor_gender" id="visitor_gender" class="form-control @error('visitor_gender') is-invalid @enderror" required>
                                    <option value="">— Pilih Gender —</option>
                                    <option value="putra" {{ old('visitor_gender') === 'putra' ? 'selected' : '' }}>Putra</option>
                                    <option value="putri" {{ old('visitor_gender') === 'putri' ? 'selected' : '' }}>Putri</option>
                                </select>
                                @error('visitor_gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="visitor_relationship" class="form-label">Hubungan dengan Santri <span class="text-danger">*</span></label>
                                <select name="visitor_relationship" id="visitor_relationship" class="form-control @error('visitor_relationship') is-invalid @enderror" required>
                                    <option value="">— Pilih Hubungan —</option>
                                    <option value="mahrom" {{ old('visitor_relationship') === 'mahrom' ? 'selected' : '' }}>Mahrom</option>
                                    <option value="wali" {{ old('visitor_relationship') === 'wali' ? 'selected' : '' }}>Wali</option>
                                    <option value="keluarga" {{ old('visitor_relationship') === 'keluarga' ? 'selected' : '' }}>Keluarga</option>
                                    <option value="pihak_pondok" {{ in_array(old('visitor_relationship'), ['pihak_pondok', 'Pihak pondok'], true) ? 'selected' : '' }}>Pihak Pondok</option>
                                    <option value="lainnya" {{ in_array(old('visitor_relationship'), ['lainnya', 'Lainnya', ' Lainnya'], true) ? 'selected' : '' }}>Lainnya</option>
                                </select>
                                @error('visitor_relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-information-line me-2 text-primary" aria-hidden="true"></i>Detail Kunjungan</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="purpose" class="form-label">Tujuan Kunjungan <span class="text-danger">*</span></label>
                                <select name="purpose" id="purpose" class="form-control @error('purpose') is-invalid @enderror" required>
                                    <option value="">— Pilih Tujuan —</option>
                                    <option value="menjenguk" {{ old('purpose') === 'menjenguk' ? 'selected' : '' }}>Menjenguk Santri</option>
                                    <option value="bawa_bantuan" {{ old('purpose') === 'bawa_bantuan' ? 'selected' : '' }}>Membawa Bantuan</option>
                                    <option value="pertemuan_wali" {{ old('purpose') === 'pertemuan_wali' ? 'selected' : '' }}>Pertemuan Wali</option>
                                    <option value="antar_jemput" {{ old('purpose') === 'antar_jemput' ? 'selected' : '' }}>Antar / Jemput Santri</option>
                                    <option value="lainnya" {{ in_array(old('purpose'), ['lainnya', 'Lainnya'], true) ? 'selected' : '' }}>Lainnya</option>
                                </select>
                                @error('purpose')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <label for="visitor_count" class="form-label">Jumlah Pengunjung</label>
                                <input type="number" name="visitor_count" id="visitor_count" class="form-control @error('visitor_count') is-invalid @enderror"
                                       value="{{ old('visitor_count', 1) }}" min="1" max="100">
                                <small class="text-muted">Maks. {{ $visitPolicy->max_visitors_per_visit ?? 'tidak dibatasi' }} orang.</small>
                                @error('visitor_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <label for="expected_arrival_datetime" class="form-label">Rencana Waktu Kunjungan <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="expected_arrival_datetime" id="expected_arrival_datetime" class="form-control @error('expected_arrival_datetime') is-invalid @enderror"
                                       value="{{ old('expected_arrival_datetime') }}" required>
                                <small class="text-muted">Santri akan dipanggil keluar saat waktu kunjungan tiba.</small>
                                @error('expected_arrival_datetime')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <label for="expected_meet_duration_minutes" class="form-label">Durasi Rencana (menit)</label>
                                <input type="number" name="expected_meet_duration_minutes" id="expected_meet_duration_minutes" class="form-control @error('expected_meet_duration_minutes') is-invalid @enderror"
                                       value="{{ old('expected_meet_duration_minutes', 60) }}" min="5" max="480" step="5" aria-describedby="expected_duration_help">
                                <small id="expected_duration_help" class="text-muted">Minimal 5 menit, maksimal 480 menit (8 jam).</small>
                                @error('expected_meet_duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="visit_notes" class="form-label">Catatan / Keterangan</label>
                                <textarea name="notes" id="visit_notes" class="form-control @error('notes') is-invalid @enderror" rows="3"
                                          placeholder="Catatan tambahan atau informasi lain yang perlu disampaikan...">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="alert alert-warning mt-3 mb-0">
                            <div class="form-check">
                                <input type="checkbox" name="schedule_exception" id="schedule_exception"
                                       class="form-check-input @error('schedule_exception_reason') is-invalid @enderror"
                                       value="1" {{ old('schedule_exception') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="schedule_exception">
                                    Pengecualian jadwal (mis. kakak/adik)
                                </label>
                            </div>
                            <input type="text" name="schedule_exception_reason" class="form-control form-control-sm mt-2
                                   @error('schedule_exception_reason') is-invalid @enderror"
                                   value="{{ old('schedule_exception_reason') }}"
                                   placeholder="Alasan pengecualian jadwal dan dasar izin kepala pengasuhan">
                            @error('schedule_exception_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-check mt-3">
                    <input type="checkbox" name="rules_acknowledged" id="rules_acknowledged"
                           class="form-check-input @error('rules_acknowledged') is-invalid @enderror"
                           value="1" required {{ old('rules_acknowledged') ? 'checked' : '' }}>
                    <label class="form-check-label" for="rules_acknowledged">
                        Tamu menyetujui aturan kunjungan: tertib, menjaga kebersihan, tidak merokok/mendengar musik,
                        berpakaian syar’i, serta tidak meminjamkan HP kepada santri tanpa pengawasan.
                    </label>
                    @error('rules_acknowledged')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-2 justify-content-end mt-3">
                    <a href="{{ route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                       class="btn btn-light"
                       aria-label="Batal dan kembali ke daftar kunjungan">
                        <i class="ri-arrow-left-line me-1" aria-hidden="true"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary" aria-label="Ajukan kunjungan baru">
                        <i class="ri-save-line me-1" aria-hidden="true"></i> Ajukan Kunjungan
                    </button>
                </div>
            </div>

            {{-- Sidebar Info --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0"><i class="ri-information-2-line me-2" aria-hidden="true"></i>Info Asrama</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Nama Asrama</label>
                            <div class="fw-semibold">{{ $dormitory->name ?? '—' }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Kode</label>
                            <div><span class="badge bg-dark">{{ $dormitory->code ?? '—' }}</span></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Gender</label>
                            <div>
                                <span class="badge bg-{{ $dormitory->gender === 'putra' ? 'primary' : 'danger' }}">
                                    {{ $dormitory->gender === 'putra' ? 'Putra' : 'Putri' }}
                                </span>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label text-muted small">Penghuni Aktif</label>
                            <div class="fw-semibold">{{ $dormitory->total_residents ?? 0 }} orang</div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0"><i class="ri-notification-3-line me-2" aria-hidden="true"></i>Aturan Kunjungan</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small">
                            <li class="d-flex gap-2 mb-2">
                                <i class="ri-checkbox-circle-line text-success mt-1" aria-hidden="true"></i>
                                <span>Kunjungan diajukan sebelum waktu kedatangan yang direncanakan.</span>
                            </li>
                            <li class="d-flex gap-2 mb-2">
                                <i class="ri-shield-user-line text-success mt-1" aria-hidden="true"></i>
                                <span>Tamu wajib menunjukkan identitas asli (KTP) saat tiba.</span>
                            </li>
                            <li class="d-flex gap-2 mb-2">
                                <i class="ri-group-line text-success mt-1" aria-hidden="true"></i>
                                <span>Maksimal pengunjung: <strong>{{ $visitPolicy->max_visitors_per_visit ?? 'tidak dibatasi' }}</strong>.</span>
                            </li>
                            <li class="d-flex gap-2 mb-2">
                                <i class="ri-time-line text-success mt-1" aria-hidden="true"></i>
                                <span>Lama kunjungan maksimal 8 jam.</span>
                            </li>
                            <li class="d-flex gap-2 mb-2">
                                <i class="ri-calendar-event-line text-success mt-1" aria-hidden="true"></i>
                                <span>
                                    Slot kunjungan:
                                    @foreach(($visitScheduleSlots ?? []) as $slot)
                                        <span class="badge bg-light text-dark me-1">{{ $slot['label'] }} {{ $slot['start'] }}–{{ $slot['end'] }}</span>
                                    @endforeach
                                </span>
                            </li>
                            @if($visitPolicy && $visitPolicy->visit_strategy === 'quota')
                                <li class="d-flex gap-2 mb-2">
                                    <i class="ri-bar-chart-line text-success mt-1" aria-hidden="true"></i>
                                    <span>Kuota: <strong>{{ $visitPolicy->visit_quota }}x / {{ $visitPolicy->visit_quota_period }}</strong>.</span>
                                </li>
                            @elseif($visitPolicy && $visitPolicy->visit_strategy === 'banned')
                                <li class="d-flex gap-2 mb-2 text-danger">
                                    <i class="ri-close-circle-line mt-1" aria-hidden="true"></i>
                                    <span>Kunjungan sedang dinonaktifkan untuk asrama ini.</span>
                                </li>
                            @else
                                <li class="d-flex gap-2 mb-2">
                                    <i class="ri-infinity-line text-success mt-1" aria-hidden="true"></i>
                                    <span>Kuota kunjungan tidak dibatasi.</span>
                                </li>
                            @endif
                            <li class="d-flex gap-2 mb-0">
                                <i class="ri-checkbox-circle-line text-success mt-1" aria-hidden="true"></i>
                                <span>Kunjungan ditutup saat santri kembali ke asrama.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var select2El = document.getElementById('studentSelect');

    if (select2El && typeof $.fn.select2 !== 'undefined') {
        $(select2El).select2({
            placeholder: '— Cari dan pilih santri —',
            allowClear: true,
            width: '100%',
            language: 'id'
        });
    }

    // Peringatan consent PDP: tampilkan bila santri terpilih belum punya consent.
    function syncConsentHint() {
        var hint = document.getElementById('consentHint');
        if (!hint || !select2El) return;
        var opt = select2El.options[select2El.selectedIndex];
        var noConsent = !!opt && opt.value !== '' && opt.dataset.hasConsent === '0';
        hint.classList.toggle('d-none', !noConsent);
    }

    if (select2El) {
        select2El.addEventListener('change', syncConsentHint);
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            $(select2El).on('select2:select select2:clear', syncConsentHint);
        }
        syncConsentHint();
    }

    var form = document.getElementById('visitForm');
    form.addEventListener('submit', function (e) {
        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="ri-loader-4-line me-1 spinner-border spinner-border-sm" aria-hidden="true"></i> Menyimpan...';
        }
    });
});
</script>

<div class="modal fade" id="visitQuotaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title"><i class="ri-alert-line me-1"></i>Kuota Kunjungan Habis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="visitQuotaText" class="mb-0">Jatah kunjungan santri ini sudah habis untuk periode ini.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('visitForm');
    if (!form) return;

    const modalEl = document.getElementById('visitQuotaModal');
    const textEl = document.getElementById('visitQuotaText');
    const quotaUrl = "{{ route('user.asrama.visits.quota.check', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}";
    let passed = false;

    form.addEventListener('submit', async function (e) {
        if (passed) { passed = false; return; }
        e.preventDefault();

        const studentId = document.getElementById('studentSelect')?.value;
        if (!studentId) { form.submit(); return; }

        try {
            const res = await fetch(quotaUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                },
                body: JSON.stringify({ student_id: studentId })
            });
            const data = await res.json();

            if (data.is_banned || (data.remaining !== null && data.remaining <= 0)) {
                textEl.textContent = data.is_banned
                    ? 'Kunjungan sedang dinonaktifkan oleh kebijakan asrama.'
                    : ('Jatah kunjungan santri ini sudah habis untuk ' + (data.period_label_id || 'periode ini')
                        + ' (terpakai ' + data.used + ' dari ' + data.quota + ').');
                new bootstrap.Modal(modalEl).show();
                return;
            }
        } catch (err) {
            // Jika pengecekan gagal, biarkan server yang memblokir (kuota tetap dijaga).
        }

        passed = true;
        form.submit();
    });
});
</script>
@endsection