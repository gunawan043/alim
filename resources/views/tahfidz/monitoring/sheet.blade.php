@extends('layouts.master')

@section('title', 'Lembar Monitoring Hafalan')

@push('styles')
<style>
    .monitoring-sheet-table { min-width: 1320px; }
    .monitoring-sheet-table th { font-size: .72rem; vertical-align: middle; }
    .monitoring-sheet-table td { font-size: .78rem; vertical-align: middle; }
    .monitoring-sheet-table thead th { white-space: normal; }
    .monitoring-meta { font-size: .84rem; }
    .monitoring-meta dt { color: #6c757d; font-weight: 500; }
    @media print {
        .monitoring-sheet-table { min-width: 0; font-size: 8px; }
        .monitoring-sheet-table th, .monitoring-sheet-table td { font-size: 8px; }
    }
</style>
@endpush

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $printQuery = array_filter([
            'userId' => $userId,
            'student_id' => $selectedStudentId,
            'group_id' => $selectedGroupId,
            'month' => $monthInput,
        ], fn ($value) => $value !== null && $value !== '');
    @endphp
    @include('tahfidz._styles')

    @component('components.breadcrumb')
        @slot('li_1') Tahfidz @endslot
        @slot('li_2') Monitoring @endslot
        @slot('title') Lembar Monitoring Hafalan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="ri-check-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Periksa catatan monitoring.</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h5 class="card-title mb-1"><i class="ri-file-chart-line text-primary me-1"></i>Lembar Monitoring Hafalan Al-Qur’an</h5>
                    <p class="text-muted mb-0 small">
                        Catatan hafalan per santri, bulan, dan pekan. Nilai tikrar dan nilai lahn disimpan terpisah;
                        sistem tidak menghitung nilai akhir otomatis.
                    </p>
                </div>
                @if($selectedMember)
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('user.tahfidz.monitoring.sheet.print', $printQuery) }}"
                           class="btn btn-soft-danger">
                            <i class="ri-printer-line me-1"></i>Cetak / PDF
                        </a>
                        @if($canInput)
                            <button type="button" class="btn btn-primary" onclick="openMonitoringEntryModal()">
                                <i class="ri-add-line me-1"></i>Tambah Catatan
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('user.tahfidz.monitoring.sheet', ['userId' => $userId]) }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small">Santri</label>
                    <select name="student_id" class="form-select" onchange="this.form.submit()">
                        <option value="">— Pilih Santri —</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" @selected((string) $selectedStudentId === (string) $student->id)>
                                {{ $student->name }}{{ $student->nis ? ' · '.$student->nis : '' }}
                                @if(($presenceToday[$student->id] ?? null)) · {{ $presenceToday[$student->id]['label'] }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Bulan</label>
                    <input type="month" name="month" value="{{ $monthInput }}" class="form-control" onchange="this.form.submit()">
                </div>
                @if($selectedGroupId)
                    <input type="hidden" name="group_id" value="{{ $selectedGroupId }}">
                @endif
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="ri-filter-3-line me-1"></i>Terapkan</button>
                </div>
            </form>
            @if($students->isEmpty())
                <div class="alert alert-info mt-3 mb-0 py-2">
                    <i class="ri-information-line me-1"></i>Belum ada santri aktif pada halaqah yang dapat Anda akses.
                </div>
            @endif
        </div>
    </div>

    @if($selectedMember)
        <div class="card mt-3">
            <div class="card-header border-bottom-dashed">
                <div class="row g-2 monitoring-meta">
                    <div class="col-md-4">
                        <div class="text-muted small">Santri</div>
                        <div class="fw-semibold">{{ $selectedStudent?->name ?? '—' }}</div>
                        <div class="text-muted small">{{ $selectedStudent?->nis ?? '—' }}</div>
                        @if($selectedPresence)
                            <span class="badge bg-{{ $selectedPresence['color'] }}-subtle text-{{ $selectedPresence['color'] }} mt-1">
                                {{ $selectedPresence['label'] }}
                            </span>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Halaqah</div>
                        <div class="fw-semibold">{{ $selectedMember->group?->name ?? '—' }}</div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small">Guru/Pembimbing</div>
                        <div class="fw-semibold">{{ $selectedMember->group?->teacher?->name ?? '—' }}</div>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <div class="text-muted small">Periode</div>
                        <div class="fw-semibold">{{ $monthLabel }}</div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    @include('tahfidz.monitoring._sheet-table', ['print' => false])
                </div>
            </div>
            <div class="card-footer border-top-dashed bg-light">
                <div class="d-flex flex-wrap justify-content-between gap-2 small text-muted">
                    <span><strong>Keterangan:</strong> &lt; di bawah target · = sesuai target · &gt; di atas target</span>
                    <span><strong>Lahn:</strong> Khofi = 1 poin/kesalahan · Jali = 2 poin/kesalahan. Nilai akhir tidak dihitung otomatis.</span>
                </div>
            </div>
        </div>
    @else
        <div class="card mt-3">
            <div class="card-body text-center text-muted py-5">
                <i class="ri-file-chart-line fs-2 d-block mb-2"></i>
                Pilih santri dan bulan untuk menampilkan lembar monitoring.
            </div>
        </div>
    @endif

    @if($selectedMember && $canInput)
        <div class="modal fade" id="monitoringEntryModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('user.tahfidz.monitoring.sheet.store', ['userId' => $userId]) }}" id="monitoringEntryForm">
                        @csrf
                        <input type="hidden" name="_method" value="PUT" id="monitoringEntryMethod" disabled>
                        <input type="hidden" name="tahfidz_group_id" value="{{ $selectedMember->tahfidz_group_id }}">
                        <input type="hidden" name="student_id" value="{{ $selectedMember->student_id }}">
                        <div class="modal-header bg-primary-subtle">
                            <h5 class="modal-title" id="monitoringEntryTitle"><i class="ri-add-line me-1"></i>Tambah Catatan Monitoring</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-light border small mb-3">
                                <i class="ri-information-line text-primary me-1"></i>
                                Pekan dihitung otomatis dari tanggal. Nilai tikrar dan nilai lahn diisi manual;
                                tidak ada rumus nilai akhir pada tahap ini.
                            </div>
                            <div class="alert alert-primary-subtle border small mb-3" id="monitoringTypeHint">
                                <strong>Ziyadah:</strong> isi materi hafalan baru, target, dan hasil.
                            </div>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Hari/Tanggal <span class="text-danger">*</span></label>
                                    <input type="date" name="setoran_date" id="monitoring_date" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Jenis Setoran <span class="text-danger">*</span></label>
                                    <select name="setoran_type" id="monitoring_type" class="form-select" required>
                                        <option value="ziyadah">Ziyadah — Hafalan Baru</option>
                                        <option value="tikror">Tikrar — Pengulangan Hafalan</option>
                                        <option value="murajaah">Murajaah Tasmi’ — Hafalan Lama</option>
                                        <option value="robet">Robet — Persiapan Pertemuan Berikutnya</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Metode Pembelajaran</label>
                                    <input name="metode_pembelajaran" id="monitoring_method" class="form-control" list="monitoringMethodOptions" placeholder="Talaqqi / Tasmi / Mandiri">
                                    <datalist id="monitoringMethodOptions">
                                        <option value="Talaqqi">
                                        <option value="Tasmi’">
                                        <option value="Sima’">
                                        <option value="Mandiri">
                                        <option value="Setoran">
                                    </datalist>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Juz</label>
                                    <input type="number" name="juz" id="monitoring_juz" min="1" max="30" class="form-control">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Surah Awal</label>
                                    <select name="surah_start_name" id="monitoring_surah_start" class="form-select">
                                        <option value="">— Pilih Surah —</option>
                                        @foreach($surahs as $surah)
                                            <option value="{{ $surah->name_latin }}" data-number="{{ $surah->number }}">
                                                {{ $surah->number }}. {{ $surah->name_latin }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Ayat Awal</label>
                                    <input type="number" name="ayat_start" id="monitoring_ayat_start" min="0" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Surah Akhir</label>
                                    <select name="surah_end_name" id="monitoring_surah_end" class="form-select">
                                        <option value="">— Sama dengan awal —</option>
                                        @foreach($surahs as $surah)
                                            <option value="{{ $surah->name_latin }}" data-number="{{ $surah->number }}">
                                                {{ $surah->number }}. {{ $surah->name_latin }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Ayat Akhir</label>
                                    <input type="number" name="ayat_end" id="monitoring_ayat_end" min="0" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Halaman Awal</label>
                                    <input type="number" step="0.1" name="halaman_start" id="monitoring_page_start" class="form-control">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">Baris</label>
                                    <input type="number" min="1" max="20" name="baris_start" id="monitoring_line_start" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Halaman Akhir</label>
                                    <input type="number" step="0.1" name="halaman_end" id="monitoring_page_end" class="form-control">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">Baris</label>
                                    <input type="number" min="1" max="20" name="baris_end" id="monitoring_line_end" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Penguasaan <span class="text-danger">*</span></label>
                                    <select name="capaian_target" id="monitoring_mastery" class="form-select" required>
                                        <option value="belum_tercapai">&lt; — di bawah target</option>
                                        <option value="tercapai">= — sesuai target</option>
                                        <option value="melampaui">&gt; — di atas target</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Keterangan Target Hafalan</label>
                                    <input name="keterangan_capaian" id="monitoring_target_note" class="form-control" placeholder="Mis. target pekan ini 5 baris">
                                </div>

                                <div class="col-md-6" data-monitoring-types="ziyadah,tikrar">
                                    <label class="form-label">Tugas Tikrar Hafalan</label>
                                    <textarea name="tikrar_task" id="monitoring_tikrar_task" rows="2" class="form-control" placeholder="Tugas pengulangan yang diberikan"></textarea>
                                </div>
                                <div class="col-md-2" data-monitoring-types="ziyadah,tikrar">
                                    <label class="form-label">Pengulangan Tikrar</label>
                                    <input type="number" min="0" max="1000" name="tikrar_repetitions" id="monitoring_tikrar_repetitions" class="form-control" placeholder="Jumlah">
                                </div>
                                <div class="col-md-2" data-monitoring-types="ziyadah,tikrar">
                                    <label class="form-label">Nilai Tikrar</label>
                                    <input type="number" step="0.01" min="0" max="100" name="nilai_tikrar" id="monitoring_tikrar_score" class="form-control">
                                </div>
                                <div class="col-md-2" data-monitoring-types="robet">
                                    <label class="form-label">Status Robet</label>
                                    <select name="robet_status" id="monitoring_robet_status" class="form-select">
                                        <option value="">—</option>
                                        <option value="siap">Siap</option>
                                        <option value="belum_siap">Belum siap</option>
                                        <option value="perlu_diulang">Perlu diulang</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Hasil Hafalan</label>
                                    <textarea name="hasil_hafalan_catatan" id="monitoring_result" rows="2" class="form-control" placeholder="Catatan hasil hafalan"></textarea>
                                </div>

                                <div class="col-md-2" data-monitoring-types="ziyadah,tikrar,murajaah">
                                    <label class="form-label">Lahn Khofi</label>
                                    <input type="number" min="0" max="1000" name="lahn_khofi_count" id="monitoring_lahn_khofi" class="form-control" value="0">
                                    <div class="form-text">Jumlah kesalahan (-1).</div>
                                </div>
                                <div class="col-md-2" data-monitoring-types="ziyadah,tikrar,murajaah">
                                    <label class="form-label">Lahn Jali</label>
                                    <input type="number" min="0" max="1000" name="lahn_jali_count" id="monitoring_lahn_jali" class="form-control" value="0">
                                    <div class="form-text">Jumlah kesalahan (-2).</div>
                                </div>
                                <div class="col-md-2" data-monitoring-types="ziyadah,tikrar,murajaah">
                                    <label class="form-label">Nilai Lahn</label>
                                    <input type="number" step="0.01" min="0" max="100" name="nilai_lahn" id="monitoring_lahn_score" class="form-control">
                                    <div class="form-text">Diisi manual.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Catatan Guru</label>
                                    <textarea name="catatan_guru" id="monitoring_notes" rows="2" class="form-control"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Catatan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    const monitoringStoreUrl = @json(route('user.tahfidz.monitoring.sheet.store', ['userId' => $userId]));
    const monitoringUpdateUrl = @json(route('user.tahfidz.monitoring.sheet.update', ['userId' => $userId, 'id' => '__ID__']));
    const monitoringDefaultDate = @json(now()->toDateString());
    const quranPositionUrl = @json(route('user.tahfidz.quran-position', ['userId' => $userId]));

    function selectedSurahNumber(id) {
        const field = document.getElementById(id);
        return field?.selectedOptions?.[0]?.dataset?.number || '';
    }

    async function fillMonitoringQuranPosition() {
        const surahStart = selectedSurahNumber('monitoring_surah_start');
        const ayatStart = document.getElementById('monitoring_ayat_start')?.value;
        if (! surahStart || ! ayatStart) return;

        const surahEnd = selectedSurahNumber('monitoring_surah_end') || surahStart;
        const ayatEnd = document.getElementById('monitoring_ayat_end')?.value || ayatStart;
        const url = new URL(quranPositionUrl, window.location.origin);
        url.searchParams.set('surah_start', surahStart);
        url.searchParams.set('ayah_start', ayatStart);
        url.searchParams.set('surah_end', surahEnd);
        url.searchParams.set('ayah_end', ayatEnd);

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (! response.ok) return;
            const data = await response.json();
            if (! data.found || ! data.position) return;
            const position = data.position;
            setMonitoringValue('monitoring_page_start', position.page_start);
            setMonitoringValue('monitoring_line_start', position.line_start);
            setMonitoringValue('monitoring_page_end', position.page_end);
            setMonitoringValue('monitoring_line_end', position.line_end);
        } catch (error) {
            // Biarkan kolom halaman/baris diisi manual jika lookup gagal.
        }
    }

    function setMonitoringValue(id, value) {
        const field = document.getElementById(id);
        if (! field) return;
        field.value = value === null || value === undefined ? '' : value;
    }

    function syncMonitoringTypeFields() {
        const type = document.getElementById('monitoring_type')?.value || 'ziyadah';
        const hints = {
            ziyadah: 'Ziyadah: isi materi hafalan baru, target, hasil, dan penilaian.',
            tikrar: 'Tikrar: isi materi yang diulang, tugas tikrar, jumlah pengulangan, dan nilai tikrar.',
            murajaah: 'Murajaah Tasmi’: isi materi hafalan lama, hasil, serta catatan lahn.',
            robet: 'Robet: isi materi pertemuan berikutnya dan status kesiapan.',
        };
        const labels = {
            ziyadah: 'Hafalan baru yang menjadi target penambahan.',
            tikrar: 'Pengulangan hafalan untuk memperkuat daya ingat.',
            murajaah: 'Memperdengarkan hafalan lama untuk menjaga kualitas.',
            robet: 'Persiapan materi hafalan pertemuan berikutnya.',
        };
        const hint = document.getElementById('monitoringTypeHint');
        if (hint) {
            hint.innerHTML = '<strong>'+labels[type]+'</strong> '+hints[type];
        }

        document.querySelectorAll('[data-monitoring-types]').forEach(function (section) {
            const allowed = (section.dataset.monitoringTypes || '').split(',');
            const visible = allowed.includes(type);
            section.classList.toggle('d-none', ! visible);
            section.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.disabled = ! visible;
            });
        });
    }

    function openMonitoringEntryModal(entry) {
        const form = document.getElementById('monitoringEntryForm');
        if (! form || ! window.bootstrap) return;

        form.reset();
        form.action = entry?.id
            ? monitoringUpdateUrl.replace('__ID__', entry.id)
            : monitoringStoreUrl;
        document.getElementById('monitoringEntryMethod').disabled = ! entry?.id;
        document.getElementById('monitoringEntryTitle').innerHTML = entry?.id
            ? '<i class="ri-edit-line me-1"></i>Edit Catatan Monitoring'
            : '<i class="ri-add-line me-1"></i>Tambah Catatan Monitoring';

        setMonitoringValue('monitoring_date', entry?.setoran_date || monitoringDefaultDate);
        setMonitoringValue('monitoring_type', entry?.setoran_type || 'ziyadah');
        setMonitoringValue('monitoring_method', entry?.metode_pembelajaran);
        setMonitoringValue('monitoring_juz', entry?.juz);
        setMonitoringValue('monitoring_surah_start', entry?.surah_start_name);
        setMonitoringValue('monitoring_ayat_start', entry?.ayat_start);
        setMonitoringValue('monitoring_surah_end', entry?.surah_end_name);
        setMonitoringValue('monitoring_ayat_end', entry?.ayat_end);
        setMonitoringValue('monitoring_page_start', entry?.halaman_start);
        setMonitoringValue('monitoring_line_start', entry?.baris_start);
        setMonitoringValue('monitoring_page_end', entry?.halaman_end);
        setMonitoringValue('monitoring_line_end', entry?.baris_end);
        setMonitoringValue('monitoring_mastery', entry?.capaian_target || 'tercapai');
        setMonitoringValue('monitoring_target_note', entry?.keterangan_capaian);
        setMonitoringValue('monitoring_tikrar_task', entry?.tikrar_task);
        setMonitoringValue('monitoring_tikrar_repetitions', entry?.tikrar_repetitions);
        setMonitoringValue('monitoring_tikrar_score', entry?.nilai_tikrar);
        setMonitoringValue('monitoring_robet_status', entry?.robet_status);
        setMonitoringValue('monitoring_result', entry?.hasil_hafalan_catatan);
        setMonitoringValue('monitoring_lahn_khofi', entry?.lahn_khofi_count ?? 0);
        setMonitoringValue('monitoring_lahn_jali', entry?.lahn_jali_count ?? 0);
        setMonitoringValue('monitoring_lahn_score', entry?.nilai_lahn);
        setMonitoringValue('monitoring_notes', entry?.catatan_guru);
        syncMonitoringTypeFields();

        bootstrap.Modal.getOrCreateInstance(document.getElementById('monitoringEntryModal')).show();
    }

    document.addEventListener('DOMContentLoaded', function () {
        ['monitoring_surah_start', 'monitoring_ayat_start', 'monitoring_surah_end', 'monitoring_ayat_end'].forEach(function (id) {
            const field = document.getElementById(id);
            if (field) {
                field.addEventListener('change', fillMonitoringQuranPosition);
            }
        });

        document.getElementById('monitoring_type')?.addEventListener('change', syncMonitoringTypeFields);

        @if($errors->any() && $selectedMember && $canInput)
            openMonitoringEntryModal();
        @endif
    });
</script>
@endpush
