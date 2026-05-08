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

    <form method="POST"
          action="{{ route('user.asrama.visits.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
          enctype="multipart/form-data"
          id="visitForm">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-user-add-line me-2 text-primary"></i>Data Tamu / Visitor</h5>
                    </div>
                    <div class="card-body">
                        {{-- Student Search / Selection --}}
                        <div class="mb-4">
                            <label class="form-label">Santri yang Ditanam <span class="text-danger">*</span></label>
                            <select name="student_id" id="studentSelect" class="form-control" required>
                                <option value="">— Pilih Santri —</option>
                                @foreach($students ?? [] as $s)
                                    <option value="{{ $s->id }}" {{ old('student_id') == $s->id ? 'selected' : '' }}
                                            data-name="{{ $s->name }}" data-nisn="{{ $s->nisn }}" data-gender="{{ $s->gender }}">
                                        {{ $s->name }} — {{ $s->nisn ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Pilih santri yang dikunjungi.</div>
                        </div>

                        <hr class="my-4">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Tamu <span class="text-danger">*</span></label>
                                <input type="text" name="visitor_name" class="form-control"
                                       value="{{ old('visitor_name') }}" placeholder="Nama lengkap tamu" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor Identitas (KTP/Passport)</label>
                                <input type="text" name="visitor_id_number" class="form-control"
                                       value="{{ old('visitor_id_number') }}" placeholder="Nomor KTP atau passport" maxlength="30">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor Telepon</label>
                                <input type="text" name="visitor_phone" class="form-control"
                                       value="{{ old('visitor_phone') }}" placeholder="08xxxxxxxxxx" maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hubungan dengan Santri <span class="text-danger">*</span></label>
                                <select name="visitor_relationship" class="form-control" required>
                                    <option value="">— Pilih Hubungan —</option>
                                    <option value="mahrom" {{ old('visitor_relationship') === 'mahrom' ? 'selected' : '' }}>Mahrom</option>
                                    <option value="wali" {{ old('visitor_relationship') === 'wali' ? 'selected' : '' }}>Wali</option>
                                    <option value="keluarga" {{ old('visitor_relationship') === 'keluarga' ? 'selected' : '' }}>Keluarga</option>
                                    <option value="pihak_pondok" {{ old('visitor_relationship') === 'pihak_pondok' ? 'selected' : '' }}>Pihak Pondok</option>
                                    <option value="lainnya" {{ old('visitor_relationship') === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-information-line me-2 text-primary"></i>Detail Kunjungan</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tujuan Kunjungan <span class="text-danger">*</span></label>
                                <select name="purpose" class="form-control" required>
                                    <option value="">— Pilih Tujuan —</option>
                                    <option value="menjenguk" {{ old('purpose') === 'menjenguk' ? 'selected' : '' }}>Menjenguk Santri</option>
                                    <option value="bawa_bantuan" {{ old('purpose') === 'bawa_bantuan' ? 'selected' : '' }}>Membawa Bantuan</option>
                                    <option value="pertemuan_wali" {{ old('purpose') === 'pertemuan_wali' ? 'selected' : '' }}>Pertemuan Wali</option>
                                    <option value="antar_jemput" {{ old('purpose') === 'antar_jemput' ? 'selected' : '' }}>Antar / Jemput Santri</option>
                                    <option value="lainnya" {{ old('purpose') === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal & Waktu Datang <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="expected_arrival" class="form-control"
                                       value="{{ old('expected_arrival') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Durasi Rencana (menit)</label>
                                <input type="number" name="expected_duration_minutes" class="form-control"
                                       value="{{ old('expected_duration_minutes', 60) }}" min="5" max="480" step="5">
                                <div class="form-text">Minimal 5 menit, maksimal 480 menit (8 jam).</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan / Keterangan</label>
                                <textarea name="notes" class="form-control" rows="3"
                                          placeholder="Catatan tambahan atau informasi lain yang perlu disampaikan...">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-2 justify-content-end mt-3">
                    <a href="{{ route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                       class="btn btn-light">
                        <i class="ri-arrow-left-line me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="ri-save-line me-1"></i> Ajukan Kunjungan
                    </button>
                </div>
            </div>

            {{-- Sidebar Info --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0"><i class="ri-information-2-line me-2"></i>Info Asrama</h5>
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
                        <h5 class="mb-0"><i class="ri-notification-3-line me-2"></i>Aturan Kunjungan</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small">
                            <li class="d-flex gap-2 mb-2">
                                <i class="ri-checkbox-circle-line text-success mt-1"></i>
                                <span>Kunjungan harus diajukan minimal 1 jam vorher.</span>
                            </li>
                            <li class="d-flex gap-2 mb-2">
                                <i class="ri-checkbox-circle-line text-success mt-1"></i>
                                <span>Tamu harus menunjukkan identitas asli (KTP).</span>
                            </li>
                            <li class="d-flex gap-2 mb-2">
                                <i class="ri-checkbox-circle-line text-success mt-1"></i>
                                <span>Hanya mahrom yang boleh menjenguk di dalam kamar.</span>
                            </li>
                            <li class="d-flex gap-2 mb-2">
                                <i class="ri-checkbox-circle-line text-success mt-1"></i>
                                <span>Lama kunjungan maksimal 8 jam.</span>
                            </li>
                            <li class="d-flex gap-2 mb-0">
                                <i class="ri-checkbox-circle-line text-success mt-1"></i>
                                <span>Tamu wajib checkout saat meninggalkan asrama.</span>
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

    // If Select2 is available, enhance the student select
    if (select2El && typeof $.fn.select2 !== 'undefined') {
        $(select2El).select2({
            placeholder: '— Cari dan pilih santri —',
            allowClear: true,
            width: '100%',
            language: 'id'
        });
    }

    // Form validation
    var form = document.getElementById('visitForm');
    form.addEventListener('submit', function (e) {
        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="ri-loader-4-line me-1 spinner-border spinner-border-sm"></i> Menyimpan...';
        }
    });
});
</script>
@endsection