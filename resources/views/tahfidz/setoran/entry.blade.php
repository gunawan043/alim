@extends('layouts.master')
@section('title', 'Input Setoran Santri')

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $active = $jenis === 'robet' ? $rabt : $ziyadah;
        $lastPosition = $last ? [
            'juz' => $last['juz'],
            'surah_start_name' => $last['surah_start_name'],
            'ayat_start' => $last['ayat_start'],
            'halaman_start' => $last['halaman_start'],
        ] : null;
    @endphp
    @include('tahfidz._styles')

    @component('components.breadcrumb')
        @slot('li_1') Tahfidz @endslot
        @slot('li_2') Setoran Harian @endslot
        @slot('title') Input Setoran @endslot
    @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show"><strong>Periksa kembali data setoran.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-0">{{ $student->name }}</h5>
                <div class="text-muted small">
                    {{ $student->nis }} · {{ $group->name }} · {{ \Illuminate\Support\Carbon::parse($date)->format('d M Y') }}
                </div>
            </div>
            <a href="{{ route('user.tahfidz.setoran-bulk.index', ['userId' => $userId, 'group_id' => $group->id, 'date' => $date]) }}"
               class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Kembali ke Halaqah</a>
        </div>

        <form method="POST" action="{{ route('user.tahfidz.setoran-bulk.store', ['userId' => $userId]) }}">
            @csrf
            <input type="hidden" name="tahfidz_group_id" value="{{ $group->id }}">
            <input type="hidden" name="setoran_date" value="{{ $date }}">
            <input type="hidden" name="entries[0][student_id]" value="{{ $student->id }}">

            <div class="card-body">
                @if($active)
                    <div class="alert alert-light border small mb-3">
                        <i class="ri-information-line text-primary me-1"></i>
                        Santri ini sudah punya setoran {{ $jenis === 'robet' ? 'Rabt' : 'Ziyadah' }} pada tanggal tersebut.
                        Menyimpan akan <strong>memperbarui</strong> data yang sudah ada.
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label d-block">Jenis Setoran <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group" aria-label="Jenis setoran">
                            @foreach(['ziyadah' => 'Ziyadah', 'robet' => 'Rabt'] as $value => $label)
                                <input type="radio" class="btn-check" name="entries[0][jenis]" id="jenis_{{ $value }}"
                                       value="{{ $value }}" autocomplete="off" @checked($jenis === $value) required>
                                <label class="btn btn-outline-success" for="jenis_{{ $value }}">{{ $label }}</label>
                            @endforeach
                        </div>
                        <div class="form-text">
                            Ziyadah = hafalan baru. Rabt = mengikat hafalan lama agar tetap terjaga.
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Juz</label>
                        <input type="number" name="entries[0][{{ $jenis }}][juz]" id="smart_juz" min="1" max="30"
                               class="form-control" value="{{ $active?->juz }}" placeholder="Otomatis">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" id="smart_nilai_label">{{ $jenis === 'robet' ? 'Nilai Rabt' : 'Nilai Hafalan' }}</label>
                        <input type="number" step="0.01" min="0" max="100" id="smart_nilai" class="form-control"
                               name="entries[0][{{ $jenis }}][{{ $jenis === 'robet' ? 'nilai_rabt' : 'hasil_hafalan' }}]"
                               value="{{ $jenis === 'robet' ? $rabt?->nilai_setoran : $ziyadah?->hasil_hafalan }}" placeholder="0–100">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nilai Tikrar</label>
                        <input type="number" step="0.01" min="0" max="100" name="entries[0][{{ $jenis }}][nilai_tikrar]"
                               class="form-control" value="{{ $active?->nilai_tikrar }}" placeholder="0–100">
                        <div class="form-text">Dipakai bersama oleh Ziyadah dan Rabt.</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        @if($jenis === 'robet')
                            <select name="entries[0][robet][robet_status]" class="form-select">
                                <option value="">—</option>
                                @foreach($rabtLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(($rabt?->robet_status ?? null) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        @else
                            <select name="entries[0][ziyadah][status]" class="form-select">
                                @foreach($statusLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(($ziyadah?->status ?? 'lulus') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="col-md-9">
                        @if($lastPosition)
                            <label class="form-label d-block">Materi terakhir</label>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="useLastPosition"
                                    data-last="{{ json_encode($lastPosition, JSON_HEX_APOS | JSON_HEX_QUOT) }}">
                                <i class="ri-history-line me-1"></i>Ambil Posisi Terakhir
                            </button>
                        @endif
                    </div>

                    <div class="col-12">
                        <label class="form-label">Catatan Guru <span class="text-muted small">(opsional)</span></label>
                        <textarea name="entries[0][{{ $jenis }}][catatan_guru]" rows="2" class="form-control"
                                  placeholder="Catatan singkat...">{{ $active?->catatan_guru }}</textarea>
                    </div>
                </div>

                <div class="border rounded p-3 bg-light-subtle mt-3">
                    <div class="fw-semibold mb-2"><i class="ri-list-settings-line me-1"></i>Detail Hafalan</div>
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label small">Surah Awal</label>
                            <select name="entries[0][{{ $jenis }}][surah_start_name]" id="smart_surah_start" class="form-select">
                                <option value="">— Pilih Surah —</option>
                                @foreach($surahs as $surah)
                                    <option value="{{ $surah->name_latin }}" data-number="{{ $surah->number }}"
                                            @selected(($active?->surah_start_name ?? $lastPosition['surah_start_name'] ?? null) === $surah->name_latin)>{{ $surah->number }}. {{ $surah->name_latin }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2"><label class="form-label small">Ayat Awal</label>
                            <input type="number" name="entries[0][{{ $jenis }}][ayat_start]" id="smart_ayat_start" class="form-control"
                                   value="{{ $active?->ayat_start ?? $lastPosition['ayat_start'] ?? null }}">
                        </div>
                        <div class="col-md-3"><label class="form-label small">Surah Akhir</label>
                            <select name="entries[0][{{ $jenis }}][surah_end_name]" id="smart_surah_end" class="form-select">
                                <option value="">— Sama dengan surah awal —</option>
                                @foreach($surahs as $surah)
                                    <option value="{{ $surah->name_latin }}" data-number="{{ $surah->number }}"
                                            @selected($active?->surah_end_name === $surah->name_latin)>{{ $surah->number }}. {{ $surah->name_latin }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2"><label class="form-label small">Ayat Akhir</label>
                            <input type="number" name="entries[0][{{ $jenis }}][ayat_end]" id="smart_ayat_end" class="form-control"
                                   value="{{ $active?->ayat_end }}">
                        </div>
                        <div class="col-md-2"><label class="form-label small">Halaman Awal</label>
                            <input type="number" step="1" name="entries[0][{{ $jenis }}][halaman_start]" id="smart_halaman_start" class="form-control"
                                   value="{{ $active?->halaman_start ?? $lastPosition['halaman_start'] ?? null }}">
                        </div>
                        <div class="col-md-2"><label class="form-label small">Baris</label>
                            <input type="number" min="1" max="15" name="entries[0][{{ $jenis }}][baris_start]" id="smart_baris_start" class="form-control"
                                   value="{{ $active?->baris_start }}">
                        </div>
                        <div class="col-md-2"><label class="form-label small">Halaman Akhir</label>
                            <input type="number" step="1" name="entries[0][{{ $jenis }}][halaman_end]" id="smart_halaman_end" class="form-control"
                                   value="{{ $active?->halaman_end }}">
                        </div>
                        <div class="col-md-2"><label class="form-label small">Baris</label>
                            <input type="number" min="1" max="15" name="entries[0][{{ $jenis }}][baris_end]" id="smart_baris_end" class="form-control"
                                   value="{{ $active?->baris_end }}">
                        </div>
                        <div class="col-md-2"><label class="form-label small">Jml Halaman</label>
                            <input type="number" step="1" name="entries[0][{{ $jenis }}][jumlah_halaman]" id="smart_jumlah_halaman" class="form-control"
                                   value="{{ $active?->jumlah_halaman }}" placeholder="Otomatis">
                        </div>
                    </div>
                    <div class="small text-muted mt-2" id="pageHint">
                        <i class="ri-bookmark-line me-1"></i>Halaman dan baris terisi otomatis dari Mushaf Madinah setelah surah dan ayat dipilih.
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('user.tahfidz.setoran-bulk.index', ['userId' => $userId, 'group_id' => $group->id, 'date' => $date]) }}"
                   class="btn btn-light">Batal</a>
                <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan Setoran</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const positionUrl = @json(route('user.tahfidz.quran-position', ['userId' => $userId]));
        const surahStart = document.getElementById('smart_surah_start');
        const surahEnd = document.getElementById('smart_surah_end');
        const ayatStart = document.getElementById('smart_ayat_start');
        const ayatEnd = document.getElementById('smart_ayat_end');
        const hint = document.getElementById('pageHint');

        // Ganti jenis berarti ganti nama field (ziyadah/robet) dan label nilai,
        // karena controller memisahkan keduanya.
        document.querySelectorAll('input[name="entries[0][jenis]"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                const isRabt = this.value === 'robet';
                const oldType = isRabt ? 'ziyadah' : 'robet';
                const newType = this.value;

                document.querySelectorAll(`[name^="entries[0][${oldType}]"]`).forEach(function (field) {
                    if (field.type === 'radio') return;
                    field.name = field.name.replace(`entries[0][${oldType}]`, `entries[0][${newType}]`);
                });

                const nilai = document.getElementById('smart_nilai');
                if (nilai) {
                    nilai.name = `entries[0][${newType}][${isRabt ? 'nilai_rabt' : 'hasil_hafalan'}]`;
                }
                const label = document.getElementById('smart_nilai_label');
                if (label) label.textContent = isRabt ? 'Nilai Rabt' : 'Nilai Hafalan';

                // Status memakai field berbeda per jenis.
                const status = document.querySelector('[name$="[status]"], [name$="[robet_status]"]');
                if (status) status.name = isRabt ? 'entries[0][robet][robet_status]' : 'entries[0][ziyadah][status]';
            });
        });

        async function fillPosition() {
            const s = surahStart?.selectedOptions?.[0]?.dataset?.number;
            const a = ayatStart?.value;
            if (! s || ! a) return;

            const es = surahEnd?.selectedOptions?.[0]?.dataset?.number || s;
            const ea = ayatEnd?.value || a;
            const url = new URL(positionUrl, window.location.origin);
            url.searchParams.set('surah_start', s);
            url.searchParams.set('ayah_start', a);
            url.searchParams.set('surah_end', es);
            url.searchParams.set('ayah_end', ea);

            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                if (! data.found || ! data.position) {
                    if (hint) hint.innerHTML = '<i class="ri-error-warning-line me-1"></i>Posisi ayat tidak ditemukan, isi halaman dan baris manual.';
                    return;
                }
                document.getElementById('smart_halaman_start').value = data.position.page_start;
                document.getElementById('smart_baris_start').value = data.position.line_start;
                document.getElementById('smart_halaman_end').value = data.position.page_end;
                document.getElementById('smart_baris_end').value = data.position.line_end;
                if (hint) {
                    hint.innerHTML = `<i class="ri-bookmark-line me-1"></i>Halaman ${data.position.page_start}–${data.position.page_end} · baris ${data.position.line_start}–${data.position.line_end}`;
                }
            } catch (e) {
                if (hint) hint.innerHTML = '<i class="ri-error-warning-line me-1"></i>Gagal mengambil posisi ayat, isi halaman dan baris manual.';
            }
        }

        [surahStart, surahEnd, ayatStart, ayatEnd].forEach(function (field) {
            field?.addEventListener('change', fillPosition);
            field?.addEventListener('input', fillPosition);
        });

        document.getElementById('useLastPosition')?.addEventListener('click', function () {
            const data = JSON.parse(this.dataset.last || '{}');
            if (data.juz) document.getElementById('smart_juz').value = data.juz;
            if (data.surah_start_name) {
                surahStart.value = data.surah_start_name;
                surahEnd.value = data.surah_start_name;
            }
            if (data.ayat_start) {
                ayatStart.value = data.ayat_start;
                ayatEnd.value = data.ayat_start;
            }
            if (data.halaman_start) document.getElementById('smart_halaman_start').value = data.halaman_start;
            fillPosition();
        });
    });
</script>
@endpush
