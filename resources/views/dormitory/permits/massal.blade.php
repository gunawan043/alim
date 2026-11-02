@extends('layouts.master')
@section('title', 'Kepulangan Masal — '.($dormitory->name ?? 'Asrama'))
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Daftar Perizinan</a> @endslot
        @slot('title') Kepulangan Masal @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.asrama.permits.massal.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" id="massalForm">
        @csrf
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header border-bottom-dashed d-flex flex-wrap gap-2 align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-0"><i class="ri-user-shared-line me-1 text-primary"></i>Pilih Santri</h5>
                            <p class="text-muted mb-0 small">{{ $dormitory->name }} · {{ $residents->count() }} santri siap dibuatkan izin</p>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="search" id="massalSearch" class="form-control form-control-sm" placeholder="Cari nama/NIS…" style="max-width:220px">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="massalSelectAll">Pilih semua</button>
                        </div>
                    </div>
                    <div class="card-body" style="max-height:520px;overflow:auto;">
                        @if($excludedCount > 0)
                            <div class="alert alert-info py-2 px-3 small">
                                <i class="ri-information-line me-1"></i>
                                {{ $excludedCount }} santri tidak ditampilkan karena sudah punya izin aktif.
                            </div>
                        @endif

                        @forelse($residents as $resident)
                            <label class="d-flex align-items-center gap-2 border-bottom py-2 massal-row"
                                   data-search="{{ strtolower(($resident->student?->name ?? '').' '.($resident->student?->nisn ?? '')) }}">
                                <input type="checkbox" class="form-check-input massal-check" name="student_ids[]"
                                       value="{{ $resident->student_id }}">
                                <span class="flex-grow-1">
                                    <span class="fw-semibold">{{ $resident->student?->name ?? '—' }}</span>
                                    <span class="text-muted small d-block">
                                        {{ $resident->room?->name ?? 'Tanpa Kamar' }}
                                        @if($resident->student?->nisn) · NISN {{ $resident->student->nisn }} @endif
                                        @if($resident->student?->class_name && $resident->student->class_name !== '-')
                                            · {{ $resident->student->class_name }}
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @empty
                            <div class="text-center text-muted py-5">
                                <i class="ri-user-unfollow-line fs-1 d-block mb-2"></i>
                                Tidak ada santri yang bisa dibuatkan izin masal.
                            </div>
                        @endforelse
                    </div>
                    <div class="card-footer">
                        <span class="text-muted small" id="massalCount">0 santri dipilih</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0"><i class="ri-settings-3-line me-1 text-primary"></i>Pengaturan Izin</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="massal_permit_type" class="form-label">Jenis Izin <span class="text-danger">*</span></label>
                            <select name="permit_type" id="massal_permit_type" class="form-select" required>
                                @foreach($permitTypes as $type)
                                    <option value="{{ $type->code }}" @selected(old('permit_type', 'pulang') === $type->code)>{{ $type->name ?? $type->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="massal_departure" class="form-label">Tanggal &amp; Jam Berangkat <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="departure_datetime" id="massal_departure"
                                   class="form-control @error('departure_datetime') is-invalid @enderror"
                                   value="{{ old('departure_datetime') }}" required>
                            @error('departure_datetime')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="massal_return" class="form-label">Tanggal &amp; Jam Kembali <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="expected_return_datetime" id="massal_return"
                                   class="form-control @error('expected_return_datetime') is-invalid @enderror"
                                   value="{{ old('expected_return_datetime') }}" required>
                            <div class="form-text">
                                Satu tanggal kembali untuk semua santri terpilih.
                                <span class="text-primary">Izin pulang otomatis 24 jam sejak berangkat (bisa diubah).</span>
                            </div>
                            @error('expected_return_datetime')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="massal_destination" class="form-label">Tujuan</label>
                            <input type="text" name="destination" id="massal_destination" class="form-control"
                                   value="{{ old('destination') }}" maxlength="191" placeholder="mis. Rumah wali / kota asal">
                        </div>
                        <div class="mb-3">
                            <label for="massal_purpose" class="form-label">Keperluan</label>
                            <textarea name="purpose" id="massal_purpose" rows="2" class="form-control" placeholder="mis. Kepulangan bulanan">{{ old('purpose') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="massal_notes" class="form-label">Catatan</label>
                            <textarea name="notes" id="massal_notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="guardian_consent_confirmed" value="1" id="massal_consent"
                                   class="form-check-input @error('guardian_consent_confirmed') is-invalid @enderror"
                                   {{ old('guardian_consent_confirmed') ? 'checked' : '' }} required>
                            <label class="form-check-label" for="massal_consent">
                                Persetujuan orang tua/wali sudah dicatat untuk seluruh santri terpilih.
                            </label>
                            @error('guardian_consent_confirmed')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="alert alert-warning small mb-0">
                            <i class="ri-alert-line me-1"></i>
                            Kuota dan izin aktif tetap diperiksa per santri. Santri yang tidak memenuhi syarat akan dilewati dan dilaporkan.
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i> Buat Izin Masal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checks = Array.from(document.querySelectorAll('.massal-check'));
    const countEl = document.getElementById('massalCount');
    const searchEl = document.getElementById('massalSearch');
    const selectAll = document.getElementById('massalSelectAll');

    function refreshCount() {
        const n = checks.filter(c => c.checked).length;
        countEl.textContent = n + ' santri dipilih';
    }

    checks.forEach(c => c.addEventListener('change', refreshCount));

    selectAll?.addEventListener('click', function () {
        const visible = checks.filter(c => c.closest('.massal-row').style.display !== 'none');
        const allChecked = visible.every(c => c.checked);
        visible.forEach(c => { c.checked = !allChecked; });
        refreshCount();
    });

    searchEl?.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        document.querySelectorAll('.massal-row').forEach(function (row) {
            row.style.display = !q || (row.dataset.search || '').includes(q) ? '' : 'none';
        });
    });

    // Tanggal kembali otomatis 24 jam (izin pulang) — dapat diubah manual.
    const departure = document.getElementById('massal_departure');
    const ret = document.getElementById('massal_return');
    const MONTHLY_HOURS = {{ (int) config('asrama.monthly_return_hours', 24) }};
    let touched = false;
    const pad = (n) => String(n).padStart(2, '0');

    function syncReturn(force) {
        if (!departure || !ret || departure.value === '' || (touched && !force)) return;
        const dep = new Date(departure.value);
        if (isNaN(dep.getTime())) return;
        const r = new Date(dep.getTime() + MONTHLY_HOURS * 3600000);
        ret.value = r.getFullYear() + '-' + pad(r.getMonth() + 1) + '-' + pad(r.getDate())
            + 'T' + pad(r.getHours()) + ':' + pad(r.getMinutes());
    }

    if (departure && !departure.value) {
        const now = new Date();
        const rem = now.getMinutes() % 15;
        const rounded = new Date(now.getTime() + (15 - rem) * 60000);
        departure.value = rounded.getFullYear() + '-' + pad(rounded.getMonth() + 1) + '-' + pad(rounded.getDate())
            + 'T' + pad(rounded.getHours()) + ':' + pad(rounded.getMinutes());
    }
    syncReturn(true);
    departure?.addEventListener('change', function () { syncReturn(false); });
    ret?.addEventListener('input', function () { touched = true; });
    refreshCount();
});
</script>
@endsection
