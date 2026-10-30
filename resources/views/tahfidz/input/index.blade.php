@extends('layouts.master')

@push('styles')
<style>
    .hafalan-modal .modal-dialog { max-height: calc(100vh - 1rem); max-height: calc(100dvh - 1rem); }
    .hafalan-modal .modal-content { display: flex; flex-direction: column; max-height: calc(100vh - 1rem); max-height: calc(100dvh - 1rem); overflow: hidden; }
    .hafalan-modal .modal-body { overflow-y: auto !important; -webkit-overflow-scrolling: touch; overscroll-behavior: contain; flex: 1 1 auto; }
    .hafalan-modal .modal-footer { flex-shrink: 0; }
    @media (max-width: 767.98px) {
        .hafalan-modal .modal-dialog { margin: .5rem; max-height: calc(100vh - 1rem); max-height: calc(100dvh - 1rem); }
        .hafalan-modal .modal-body { max-height: calc(100vh - 170px) !important; max-height: calc(100dvh - 170px) !important; overflow-y: auto !important; padding: 1rem; }
    }
</style>
@endpush
@section('title', 'Input Hafalan Santri')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Operasional @endslot @slot('title') Input Hafalan Santri @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><strong>Periksa data setoran.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <h5 class="card-title mb-0"><i class="ri-search-eye-line text-primary me-1"></i>Pilih Santri</h5>
            <p class="text-muted mb-0 small">Cari seluruh santri di satuan pendidikan. Format: <strong>Kelas | NIS | Nama</strong>.</p>
        </div>
        <div class="card-body">
            <form method="GET">
                <div class="row g-2 align-items-end">
                    <div class="col-md-9"><label class="form-label small">Santri</label><select name="student_id" id="studentPicker" class="form-select" data-placeholder="Ketik kelas, NIS, atau nama santri..."><option value="">— Pilih Santri —</option>@foreach($schoolStudents as $student)<option value="{{ $student->id }}" @selected((string)request('student_id')===(string)$student->id)>{{ $studentClassMap[$student->id] ?? $student->currentClassHistory?->studyGroup?->name ?? '—' }} | {{ $student->nis ?? '—' }} | {{ $student->name }}@if(($presenceToday[$student->id] ?? null)) · {{ $presenceToday[$student->id]['label'] }}@endif</option>@endforeach</select></div>
                    <div class="col-md-3"><button class="btn btn-primary w-100" style="min-height:38px;"><i class="ri-eye-line me-1"></i>Tampilkan Riwayat</button></div>
                </div>
                <div class="form-text mt-1">Pencarian santri mencakup semua santri aktif di satuan pendidikan; tabel di bawah hanya menampilkan santri halaqah Anda. Dropdown surah hanya berisi nama surah, bukan nama santri.</div>
            </form>
        </div>
    </div>

    @if($selected)
        <div class="card mt-3">
            <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-1">{{ $selected->name }} @if(($presenceToday[$selected->id] ?? null))<span class="badge bg-{{ $presenceToday[$selected->id]['color'] }}-subtle text-{{ $presenceToday[$selected->id]['color'] }} ms-1">{{ $presenceToday[$selected->id]['label'] }}</span>@endif</h5>
                    <div class="text-muted small">{{ $selected->nis }} · {{ $selectedGroup?->name }} · {{ $selectedGroup?->school?->name }}</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('user.tahfidz.santri.show', ['userId'=>$userId,'studentId'=>$selected->id]) }}" class="btn btn-outline-primary"><i class="ri-profile-line me-1"></i>Detail Tahfidz</a>
                    @if($canInput)<button type="button" class="btn btn-success" onclick="openHafalanModal()"><i class="ri-add-line me-1"></i>Tambah Setoran</button>@endif
                    <a href="{{ route('user.tahfidz.input.index', ['userId'=>$userId]) }}" class="btn btn-light">Cari Santri Lain</a>
                </div>
            </div>
            <div class="card-body">
                @if($isSubstitute)
                    <div class="alert alert-info mb-3"><i class="ri-user-shared-line me-1"></i>Mode guru pengganti: Anda menginput untuk halaqah <strong>{{ $selectedGroup?->name }}</strong>, bukan halaqah utama Anda.</div>
                @endif
                @unless($canInput)
                    <div class="alert alert-warning mb-3"><i class="ri-lock-line me-1"></i>Anda hanya dapat melihat riwayat santri ini. Input setoran memerlukan penugasan halaqah.</div>
                @endunless
                <div class="row g-3">
                    <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="text-muted small">Setoran Terakhir</div><div class="fw-bold">{{ $lastSetoran?->setoran_date?->format('d M Y') ?? '—' }}</div><div class="text-muted small">{{ $lastSetoran?->setoran_type_label ?? 'Belum ada setoran' }}</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="text-muted small">Juz Selesai</div><div class="fw-bold">{{ $progress->whereIn('status', ['selesai_ziyadah','sudah_tasmi'])->count() }} juz</div><div class="text-muted small">{{ $progress->count() }} juz tercatat</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="text-muted small">Capaian Target</div><div class="fw-bold">{{ $recap?->pencapaian_target_persen !== null ? number_format((float) $recap->pencapaian_target_persen, 1).'%' : '—' }}</div><div class="text-muted small">{{ $target?->juz_start ? 'Juz '.$target->juz_start.'–'.($target->juz_end ?? $target->juz_start) : 'Target belum diatur' }}</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="text-muted small">Rata-rata Nilai</div><div class="fw-bold">{{ $recap?->rata_rata_nilai !== null ? number_format((float) $recap->rata_rata_nilai, 2) : '—' }}</div><div class="text-muted small">Setoran terverifikasi</div></div></div>
                </div>

                @if($progress->isNotEmpty())
                    <div class="d-flex flex-wrap gap-1 mt-3">
                        @foreach($progress as $row)
                            <span class="badge {{ in_array($row->status, ['selesai_ziyadah','sudah_tasmi'], true) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">J{{ $row->juz_number }} · {{ number_format((float) $row->percentage, 0) }}%</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0"><i class="ri-history-line text-primary me-1"></i>Riwayat Hafalan</h5><a href="{{ route('user.tahfidz.setoran.index', ['userId'=>$userId,'student_id'=>$selected->id]) }}" class="btn btn-sm btn-outline-primary">Semua Setoran</a></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tahfidz-table">
                    <thead class="table-light"><tr><th>Tanggal</th><th>Jenis</th><th>Materi</th><th class="text-center">Halaman</th><th class="text-center">Nilai</th><th>Status</th><th>Verifikasi</th><th>Guru</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        @forelse($history as $setoran)
                            <tr>
                                <td>{{ $setoran->setoran_date?->format('d/m/Y') }}</td>
                                <td>{{ $setoran->setoran_type_label }}</td>
                                <td class="small">{{ $setoran->juz ? 'Juz '.$setoran->juz : '' }} {{ $setoran->surah_start_name }}{{ $setoran->ayat_start ? ':'.$setoran->ayat_start : '' }}{{ $setoran->surah_end_name ? ' — '.$setoran->surah_end_name : '' }}{{ $setoran->ayat_end ? ':'.$setoran->ayat_end : '' }}</td>
                                <td class="text-center">{{ $setoran->jumlah_halaman ?? '—' }}</td>
                                <td class="text-center fw-semibold">{{ $setoran->nilai_setoran ?? '—' }}</td>
                                <td><span class="badge {{ $setoran->status==='lulus' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ $setoran->status_label }}</span></td>
                                <td><span class="badge {{ $setoran->verification_status==='verified' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $setoran->verification_status }}</span></td>
                                <td class="small">{{ $setoran->teacher?->name }}</td>
                                <td class="text-end">
                                    @if($setoran->verification_status !== 'verified' || $setoran->verification_notes === 'auto-verified')
                                        <a href="{{ route('user.tahfidz.setoran.edit', ['userId'=>$userId,'id'=>$setoran->id,'return_to'=>'input']) }}" class="btn btn-sm btn-outline-warning" title="Edit setoran"><i class="ri-edit-line"></i></a>
                                    @else
                                        <span class="text-muted small">Terkunci</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">Belum ada riwayat hafalan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="card mt-3">
            <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-user-search-line text-primary me-1"></i>Santri di Halaqah Anda</h5></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tahfidz-table">
                    <thead class="table-light"><tr><th>Nama Santri</th><th>NIS</th><th>Halaqah</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        @forelse($members as $member)
                            <tr>
                                <td class="fw-medium">{{ $member->student?->name }}</td>
                                <td>{{ $member->student?->nis }}</td>
                                <td>{{ $member->group?->name }}</td>
                                <td class="text-end"><a href="{{ route('user.tahfidz.input.index', ['userId'=>$userId,'student_id'=>$member->student_id]) }}" class="btn btn-sm btn-success"><i class="ri-arrow-right-line me-1"></i>Pilih</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-5"><i class="ri-user-search-line fs-3 d-block mb-2"></i>Belum ada santri di halaqah Anda. Gunakan pencarian di atas untuk santri dari halaqah lain.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($selected && $canInput)
        <div class="modal fade hafalan-modal" id="hafalanModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('user.tahfidz.setoran.store', ['userId'=>$userId]) }}">
                        @csrf
                        <input type="hidden" name="return_to" value="input">
                        <input type="hidden" name="tahfidz_group_id" value="{{ $selectedGroup?->id }}">
                        <input type="hidden" name="student_id" value="{{ $selected->id }}">
                        <div class="modal-header bg-success-subtle"><h5 class="modal-title"><i class="ri-add-line me-1"></i>Tambah Setoran — {{ $selected->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                        @php
                            $lastPosition = $lastSetoran ? [
                                'juz' => $lastSetoran->juz,
                                'surah_start_name' => $lastSetoran->surah_end_name ?: $lastSetoran->surah_start_name,
                                'ayat_start' => $lastSetoran->ayat_end ? ((int) $lastSetoran->ayat_end + 1) : $lastSetoran->ayat_start,
                                'halaman_start' => $lastSetoran->halaman_end ? ((float) $lastSetoran->halaman_end + 1) : $lastSetoran->halaman_start,
                            ] : null;
                        @endphp

                        <div class="alert alert-light border small mb-3"><i class="ri-lightbulb-line text-warning me-1"></i>Juz, surah, dan ayat awal otomatis melanjutkan setoran terakhir. Ubah jika tidak sesuai; nilai akhir dan jumlah halaman dihitung otomatis.</div>
                        <div class="row g-3">
                            <div class="col-md-3"><label class="form-label">Tanggal <span class="text-danger">*</span></label><input type="date" name="setoran_date" id="smart_setoran_date" class="form-control" value="{{ old('setoran_date', now()->toDateString()) }}" required></div>
                            <div class="col-md-3"><label class="form-label">Jenis <span class="text-danger">*</span></label><select name="setoran_type" class="form-select" required><option value="ziyadah" @selected(old('setoran_type','ziyadah')==='ziyadah')>Ziyadah (hafalan baru)</option><option value="murajaah" @selected(old('setoran_type')==='murajaah')>Murajaah</option><option value="tikror" @selected(old('setoran_type')==='tikror')>Tikror</option><option value="robet" @selected(old('setoran_type')==='robet')>Robet</option></select></div>
                            <div class="col-md-2"><label class="form-label">Juz</label><input type="number" name="juz" id="smart_juz" min="1" max="30" class="form-control" value="{{ old('juz', $target?->juz_start ?? $lastSetoran?->juz) }}" placeholder="Otomatis"></div>
                            <div class="col-md-2"><label class="form-label">Nilai Hafalan</label><input type="number" step="0.01" min="0" max="100" name="hasil_hafalan" id="smart_hasil" class="form-control" value="{{ old('hasil_hafalan') }}" placeholder="0–100"></div>
                            <div class="col-md-2"><label class="form-label">Status <span class="text-danger">*</span></label><select name="status" class="form-select" required><option value="lulus" @selected(old('status','lulus')==='lulus')>Lulus</option><option value="ulang" @selected(old('status')==='ulang')>Ulang</option><option value="ditunda" @selected(old('status')==='ditunda')>Ditunda</option></select></div>
                            <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                                @if($lastPosition)
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="useLastPosition" data-last="{{ json_encode($lastPosition, JSON_HEX_APOS | JSON_HEX_QUOT) }}"><i class="ri-history-line me-1"></i>Ambil Posisi Terakhir</button>
                                @endif
                                <span class="badge bg-success-subtle text-success ms-auto" id="scorePreview">Nilai akhir: otomatis</span>
                            </div>
                            <div class="col-12"><label class="form-label">Catatan Guru <span class="text-muted small">(opsional)</span></label><textarea name="catatan_guru" rows="2" class="form-control" placeholder="Catatan singkat...">{{ old('catatan_guru') }}</textarea></div>
                        </div>

                        <div class="mt-3" id="setoranDetail">
                            <div class="border rounded p-3 bg-light-subtle">
                                <div class="fw-semibold mb-2"><i class="ri-list-settings-line me-1"></i>Detail Hafalan</div>
                                <div class="row g-3">
                                    <div class="col-md-2"><label class="form-label small">Pekan</label><input type="number" name="week_number" min="1" max="6" class="form-control" value="{{ old('week_number') }}"></div>
                                    <div class="col-md-4"><label class="form-label small">Metode</label><input name="metode_pembelajaran" class="form-control" value="{{ old('metode_pembelajaran') }}" placeholder="Talaqqi/Tasmi/Mandiri"></div>
                                    <div class="col-md-3"><label class="form-label small">Surah Awal</label><select name="surah_start_name" id="smart_surah_start" class="form-select surah-picker" data-placeholder="Cari nama surah..."><option value="">— Pilih Surah —</option>@foreach($surahs as $surah)<option value="{{ $surah->name_latin }}" data-number="{{ $surah->number }}" @selected(old('surah_start_name', $lastPosition['surah_start_name'] ?? null)===$surah->name_latin)>{{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->name_arabic }}</option>@endforeach
@if(old('surah_start_name') && ! $surahs->contains('name_latin', old('surah_start_name')))<option value="{{ old('surah_start_name') }}" selected>{{ old('surah_start_name') }}</option>@endif
</select></div>
                                    <div class="col-md-2"><label class="form-label small">Ayat Awal</label><input type="number" name="ayat_start" id="smart_ayat_start" class="form-control" value="{{ old('ayat_start', $lastPosition['ayat_start'] ?? null) }}"></div>
                                    <div class="col-md-3"><label class="form-label small">Surah Akhir</label><select name="surah_end_name" id="surahEndPicker" class="form-select surah-picker" data-placeholder="Cari nama surah..."><option value="">— Pilih Surah —</option>@foreach($surahs as $surah)<option value="{{ $surah->name_latin }}" data-number="{{ $surah->number }}" @selected(old('surah_end_name')===$surah->name_latin)>{{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->name_arabic }}</option>@endforeach
@if(old('surah_end_name') && ! $surahs->contains('name_latin', old('surah_end_name')))<option value="{{ old('surah_end_name') }}" selected>{{ old('surah_end_name') }}</option>@endif
</select></div>
                                    <div class="col-md-2"><label class="form-label small">Ayat Akhir</label><input type="number" name="ayat_end" class="form-control" value="{{ old('ayat_end') }}"></div>
                                    <div class="col-md-2"><label class="form-label small">Halaman Awal</label><input type="number" step="0.1" name="halaman_start" id="smart_halaman_start" class="form-control" value="{{ old('halaman_start', $lastPosition['halaman_start'] ?? null) }}"></div>
                                    <div class="col-md-1"><label class="form-label small">Baris</label><input type="number" min="1" max="20" name="baris_start" id="smart_baris_start" class="form-control" value="{{ old('baris_start', $lastPosition['baris_start'] ?? null) }}"></div>
                                    <div class="col-md-2"><label class="form-label small">Halaman Akhir</label><input type="number" step="0.1" name="halaman_end" id="smart_halaman_end" class="form-control" value="{{ old('halaman_end') }}"></div>
                                    <div class="col-md-1"><label class="form-label small">Baris</label><input type="number" min="1" max="20" name="baris_end" id="smart_baris_end" class="form-control" value="{{ old('baris_end') }}"></div>
                                    <div class="col-md-2"><label class="form-label small">Jml Halaman</label><input type="number" step="0.1" name="jumlah_halaman" id="smart_jumlah_halaman" class="form-control" value="{{ old('jumlah_halaman') }}" placeholder="Otomatis"></div>
                                    <div class="col-md-2"><label class="form-label small">Jml Baris</label><input type="number" name="jumlah_baris" class="form-control" value="{{ old('jumlah_baris') }}"></div>
                                    <div class="col-md-2"><label class="form-label small">Khofi</label><input type="number" step="0.01" min="0" max="100" name="khofi" id="smart_khofi" class="form-control" value="{{ old('khofi') }}"></div>
                                    <div class="col-md-2"><label class="form-label small">Jali</label><input type="number" step="0.01" min="0" max="100" name="jali" id="smart_jali" class="form-control" value="{{ old('jali') }}"></div>
                                    <div class="col-md-2"><label class="form-label small">Nilai Akhir</label><input type="number" step="0.01" min="0" max="100" name="nilai_setoran" id="smart_nilai_setoran" class="form-control" value="{{ old('nilai_setoran') }}" placeholder="Otomatis"></div>
                                </div>
                            </div>
                        </div>
                    </div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan Setoran</button></div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    function openHafalanModal() {
        if (window.bootstrap) bootstrap.Modal.getOrCreateInstance(document.getElementById('hafalanModal')).show();
    }
    document.addEventListener('DOMContentLoaded', function () {
        const quranPositionUrl = @json(route('user.tahfidz.quran-position', ['userId' => $userId]));
        const pageStart = document.getElementById('smart_halaman_start');
        const pageEnd = document.getElementById('smart_halaman_end');
        const pageCount = document.getElementById('smart_jumlah_halaman');
        const scorePreview = document.getElementById('scorePreview');

        async function fillSmartQuranPosition() {
            const startSurah = document.getElementById('smart_surah_start');
            const endSurah = document.getElementById('surahEndPicker');
            const startAyah = document.getElementById('smart_ayat_start');
            const endAyah = document.querySelector('input[name="ayat_end"]');
            const s = startSurah?.selectedOptions?.[0]?.dataset?.number;
            const a = startAyah?.value;
            if (! s || ! a) return;
            const es = endSurah?.selectedOptions?.[0]?.dataset?.number || s;
            const ea = endAyah?.value || a;
            const url = new URL(quranPositionUrl, window.location.origin);
            url.searchParams.set('surah_start', s);
            url.searchParams.set('ayah_start', a);
            url.searchParams.set('surah_end', es);
            url.searchParams.set('ayah_end', ea);
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                if (! data.found || ! data.position) return;
                document.getElementById('smart_halaman_start').value = data.position.page_start;
                document.getElementById('smart_baris_start').value = data.position.line_start;
                document.getElementById('smart_halaman_end').value = data.position.page_end;
                document.getElementById('smart_baris_end').value = data.position.line_end;
                calculatePages();
            } catch (e) {}
        }

        function calculatePages() {
            if (! pageStart || ! pageEnd || ! pageCount) return;
            const start = parseFloat(pageStart.value);
            const end = parseFloat(pageEnd.value);
            if (! Number.isNaN(start) && ! Number.isNaN(end) && end >= start) {
                pageCount.value = Math.max(0, end - start + 1).toFixed(1).replace('.0', '');
            }
        }

        function calculateScore() {
            if (! scorePreview) return;
            const values = ['smart_hasil', 'smart_khofi', 'smart_jali']
                .map(id => parseFloat(document.getElementById(id)?.value))
                .filter(value => ! Number.isNaN(value));
            if (! values.length) {
                scorePreview.textContent = 'Nilai akhir: otomatis';
                return;
            }
            const average = values.reduce((sum, value) => sum + value, 0) / values.length;
            scorePreview.textContent = 'Nilai akhir: ' + average.toFixed(2);
        }

        [pageStart, pageEnd].forEach(input => input?.addEventListener('input', calculatePages));
        ['smart_surah_start', 'surahEndPicker', 'smart_ayat_start'].forEach(function (id) {
            const field = document.getElementById(id);
            if (! field) return;
            field.addEventListener('change', fillSmartQuranPosition);
            if (window.jQuery) jQuery(field).on('change', fillSmartQuranPosition);
        });
        document.querySelector('input[name="ayat_end"]')?.addEventListener('change', fillSmartQuranPosition);
        ['smart_hasil', 'smart_khofi', 'smart_jali'].forEach(id => document.getElementById(id)?.addEventListener('input', calculateScore));
        calculatePages();
        calculateScore();

        document.getElementById('useLastPosition')?.addEventListener('click', function () {
            const data = JSON.parse(this.dataset.last || '{}');
            if (data.juz) document.getElementById('smart_juz').value = data.juz;
            if (data.surah_start_name) document.getElementById('smart_surah_start').value = data.surah_start_name;
            if (data.ayat_start) document.getElementById('smart_ayat_start').value = data.ayat_start;
            if (data.halaman_start) document.getElementById('smart_halaman_start').value = data.halaman_start;
        });

        if (window.jQuery && jQuery.fn.select2) {
            jQuery('#studentPicker').select2({ width: '100%', placeholder: 'Ketik kelas, NIS, atau nama santri...', allowClear: true });
            jQuery('#smart_surah_start, #surahEndPicker').select2({ width: '100%', placeholder: 'Cari nama surah...', allowClear: true });
        }
        @if($selected && $canInput && $errors->any())
            openHafalanModal();
        @endif

        const hafalanModal = document.getElementById('hafalanModal');
        function fitHafalanModal() {
            if (! hafalanModal) return;
            const body = hafalanModal.querySelector('.modal-body');
            const header = hafalanModal.querySelector('.modal-header');
            const footer = hafalanModal.querySelector('.modal-footer');
            if (! body) return;
            const available = (window.visualViewport?.height || window.innerHeight) - (header?.offsetHeight || 70) - (footer?.offsetHeight || 70) - 24;
            body.style.maxHeight = Math.max(220, available) + 'px';
            body.style.overflowY = 'auto';
        }
        hafalanModal?.addEventListener('shown.bs.modal', fitHafalanModal);
        window.addEventListener('resize', fitHafalanModal);
        window.visualViewport?.addEventListener('resize', fitHafalanModal);
    });
</script>
@endpush
