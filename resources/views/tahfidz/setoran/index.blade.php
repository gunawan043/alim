@extends('layouts.master')
@section('title', 'Setoran Tahfizh')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Operasional @endslot @slot('title') Setoran Tahfizh @endslot @endcomponent
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><strong>Periksa kembali data setoran.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row tahfidz-stat">
        @foreach([
            ['Total Setoran', $stats['total'], 'ri-file-list-3-line', 'primary', 'Sesuai filter aktif'],
            ['Pending / Audit Manual', $stats['pending'], 'ri-time-line', 'warning', 'Data lama atau manual'],
            ['Terverifikasi', $stats['verified'], 'ri-check-double-line', 'success', 'Termasuk otomatis'],
            ['Hari Ini', $stats['today'], 'ri-calendar-check-line', 'info', 'Setoran tanggal ini'],
        ] as [$label, $value, $icon, $color, $note])
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate"><div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-{{ $color }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $color }}"></i></span></div>
                        <div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0 stat-label">{{ $label }}</p><h3 class="fw-bold ff-secondary mb-0">{{ number_format($value) }}</h3></div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>{{ $note }}</p>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-file-add-line text-primary me-1"></i>Setoran Tahfizh</h5><p class="text-muted mb-0 small">Input harian guru halaqah; setoran baru otomatis masuk progres. Verifikasi manual tersedia untuk audit.</p></div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.tahfidz.halaqah.index', ['userId'=>$userId]) }}" class="btn btn-soft-info"><i class="ri-team-line me-1"></i>Halaqah</a>
                @if($groups->isNotEmpty())
                    <button type="button" class="btn btn-success" onclick="openSetoranModal()"><i class="ri-add-line me-1"></i>Input Setoran</button>
                @endif
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small">Halaqah</label><select name="group_id" class="form-select" onchange="this.form.submit()"><option value="">Semua</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected(request('group_id')===$group->id)>{{ $group->name }} — {{ $group->school?->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label small">Dari</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
                <div class="col-md-3"><label class="form-label small">Sampai</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
                <div class="col-md-2"><label class="form-label small">Verifikasi</label><select name="verification" class="form-select" onchange="this.form.submit()"><option value="">Semua</option><option value="pending" @selected(request('verification')==='pending')>Pending</option><option value="verified" @selected(request('verification')==='verified')>Verified</option></select></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tahfidz-table">
                <thead class="table-light"><tr><th>Tanggal</th><th>Santri</th><th>Halaqah</th><th>Materi</th><th>Jenis</th><th>Nilai</th><th>Verifikasi</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($setorans as $setoran)
                        <tr>
                            <td>{{ $setoran->setoran_date?->format('d M Y') }}</td>
                            <td><div class="fw-medium">{{ $setoran->student?->name }}</div><div class="text-muted small">{{ $setoran->student?->nis }}</div></td>
                            <td>{{ $setoran->group?->name }}</td>
                            <td class="small">{{ $setoran->juz ? 'Juz '.$setoran->juz : '' }} {{ $setoran->surah_start_name }}{{ $setoran->ayat_start ? ':'.$setoran->ayat_start : '' }}{{ $setoran->surah_end_name ? ' — '.$setoran->surah_end_name : '' }}{{ $setoran->ayat_end ? ':'.$setoran->ayat_end : '' }}</td>
                            <td>{{ ucfirst($setoran->setoran_type) }}</td>
                            <td>{{ $setoran->nilai_setoran ?? '—' }}</td>
                            <td><span class="badge {{ $setoran->verification_status==='verified' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ $setoran->verification_status }}</span>@if($setoran->verifier)<div class="text-muted" style="font-size:10px;">{{ $setoran->verifier->name }}</div>@endif</td>
                            <td class="text-end">
                                @if($setoran->verification_status !== 'verified' || $setoran->verification_notes === 'auto-verified')
                                    <a href="{{ route('user.tahfidz.setoran.edit', ['userId'=>$userId,'id'=>$setoran->id]) }}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="ri-edit-line"></i></a>
                                    @if($canVerify && $setoran->verification_status === 'pending')
                                        <form method="POST" action="{{ route('user.tahfidz.setoran.verify', ['userId'=>$userId,'id'=>$setoran->id]) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success" title="Verifikasi manual"><i class="ri-check-double-line"></i></button></form>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-setoran" title="Hapus"
                                            data-action="{{ route('user.tahfidz.setoran.destroy', ['userId'=>$userId,'id'=>$setoran->id]) }}"
                                            data-name="{{ $setoran->student?->name }}"><i class="ri-delete-bin-line"></i></button>
                                @else
                                    <span class="text-muted small">Terkunci</span>
                                @endif
                            </td>
                        </tr>
                    @empty<tr><td colspan="8" class="text-center text-muted py-4">Belum ada setoran.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        @if($setorans->hasPages())<div class="card-body border-top">{{ $setorans->links() }}</div>@endif
    </div>

    @if($groups->isNotEmpty())
        <div class="modal fade" id="setoranModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('user.tahfidz.setoran.store', ['userId'=>$userId]) }}">
                        @csrf
                        <div class="modal-header bg-primary-subtle"><h5 class="modal-title"><i class="ri-file-add-line me-1"></i>Input Setoran</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="alert alert-light border small mb-3 d-flex flex-wrap justify-content-between gap-2"><span><i class="ri-lightbulb-line text-warning me-1"></i>Pilih santri; juz, surah, dan ayat awal otomatis melanjutkan setoran terakhir. Detail dapat diubah.</span><span class="badge bg-success-subtle text-success" id="setoranScorePreview">Nilai akhir otomatis</span></div>
                            <div class="row g-3">
                                <div class="col-md-5"><label class="form-label">Halaqah <span class="text-danger">*</span></label><select name="tahfidz_group_id" id="setoranGroup" class="form-select" required onchange="fillSetoranStudents(this.value)">@foreach($groups as $g)<option value="{{ $g->id }}" @selected((string)old('tahfidz_group_id')===(string)$g->id)>{{ $g->name }} — {{ $g->school?->name }}</option>@endforeach</select></div>
                                <div class="col-md-4"><label class="form-label">Santri <span class="text-danger">*</span></label><select name="student_id" id="setoranStudent" class="form-select" required onchange="applySetoranLastPosition(this.value)"><option value="">—</option></select></div>
                                <div class="col-md-3"><label class="form-label">Tanggal <span class="text-danger">*</span></label><input type="date" name="setoran_date" class="form-control" value="{{ old('setoran_date', now()->toDateString()) }}" required></div>
                                <div class="col-md-2"><label class="form-label">Pekan</label><input type="number" name="week_number" min="1" max="5" class="form-control" value="{{ old('week_number') }}"></div>
                                <div class="col-md-3"><label class="form-label">Jenis <span class="text-danger">*</span></label><select name="setoran_type" class="form-select" required><option value="ziyadah" @selected(old('setoran_type','ziyadah')==='ziyadah')>Ziyadah</option><option value="murajaah" @selected(old('setoran_type')==='murajaah')>Murajaah</option><option value="tikror" @selected(old('setoran_type')==='tikror')>Tikror</option></select></div>
                                <div class="col-md-3"><label class="form-label">Metode</label><input name="metode_pembelajaran" class="form-control" value="{{ old('metode_pembelajaran') }}" placeholder="Talaqqi/Tasmi/Mandiri"></div>
                                <div class="col-md-2"><label class="form-label">Juz</label><input type="number" name="juz" id="setoranJuz" min="1" max="30" class="form-control" value="{{ old('juz') }}"></div>
                                <div class="col-md-2"><label class="form-label">Status <span class="text-danger">*</span></label><select name="status" class="form-select" required><option value="lulus" @selected(old('status','lulus')==='lulus')>Lulus</option><option value="ulang" @selected(old('status')==='ulang')>Ulang</option><option value="ditunda" @selected(old('status')==='ditunda')>Ditunda</option></select></div>
                                <div class="col-md-3"><label class="form-label">Surah Awal</label><select name="surah_start_name" id="setoranSurahStart" class="form-select select2-surah"><option value="">— Pilih Surah —</option>@foreach($surahs as $surah)<option value="{{ $surah->name_latin }}" @selected(old('surah_start_name')===$surah->name_latin)>{{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->name_arabic }}</option>@endforeach</select></div>
                                <div class="col-md-2"><label class="form-label">Ayat Awal</label><input type="number" name="ayat_start" id="setoranAyatStart" class="form-control" value="{{ old('ayat_start') }}"></div>
                                <div class="col-md-3"><label class="form-label">Surah Akhir</label><select name="surah_end_name" id="setoranSurahEnd" class="form-select select2-surah"><option value="">— Pilih Surah —</option>@foreach($surahs as $surah)<option value="{{ $surah->name_latin }}" @selected(old('surah_end_name')===$surah->name_latin)>{{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->name_arabic }}</option>@endforeach</select></div>
                                <div class="col-md-2"><label class="form-label">Ayat Akhir</label><input type="number" name="ayat_end" class="form-control" value="{{ old('ayat_end') }}"></div>
                                <div class="col-md-2"><label class="form-label">Halaman Awal</label><input type="number" step="0.1" name="halaman_start" id="setoranHalamanStart" class="form-control" value="{{ old('halaman_start') }}"></div>
                                <div class="col-md-2"><label class="form-label">Halaman Akhir</label><input type="number" step="0.1" name="halaman_end" id="setoranHalamanEnd" class="form-control" value="{{ old('halaman_end') }}"></div>
                                <div class="col-md-2"><label class="form-label">Jml Halaman</label><input type="number" step="0.1" name="jumlah_halaman" id="setoranHalamanCount" class="form-control" value="{{ old('jumlah_halaman') }}" placeholder="Otomatis"></div>
                                <div class="col-md-2"><label class="form-label">Jml Baris</label><input type="number" name="jumlah_baris" class="form-control" value="{{ old('jumlah_baris') }}"></div>
                                <div class="col-md-2"><label class="form-label">Hasil Hafalan</label><input type="number" step="0.01" min="0" max="100" name="hasil_hafalan" id="setoranHasil" class="form-control" value="{{ old('hasil_hafalan') }}"></div>
                                <div class="col-md-2"><label class="form-label">Khofi</label><input type="number" step="0.01" min="0" max="100" name="khofi" id="setoranKhofi" class="form-control" value="{{ old('khofi') }}"></div>
                                <div class="col-md-2"><label class="form-label">Jali</label><input type="number" step="0.01" min="0" max="100" name="jali" id="setoranJali" class="form-control" value="{{ old('jali') }}"></div>
                                <div class="col-md-2"><label class="form-label">Nilai Akhir</label><input type="number" step="0.01" min="0" max="100" name="nilai_setoran" class="form-control" value="{{ old('nilai_setoran') }}" placeholder="Otomatis"></div>
                                <div class="col-12"><label class="form-label">Catatan Guru</label><textarea name="catatan_guru" class="form-control" rows="2">{{ old('catatan_guru') }}</textarea></div>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Setoran</button></div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <form id="deleteSetoranForm" method="POST" class="d-none">@csrf @method('DELETE')</form>
    <x-modal-confirm id="deleteSetoranModal" title="Hapus Setoran?" message="Setoran <strong id='deleteSetoranName'>ini</strong> akan dihapus. Setoran terverifikasi tidak dapat dihapus."
        submit-label="Ya, Hapus" submit-icon="ri-delete-bin-line" submit-class="btn-danger" form-id="deleteSetoranForm" />
@endsection

@push('scripts')
<script>
    const setoranMembers = @json($membersByGroup);
    function fillSetoranStudents(groupId, selected) {
        const select = document.getElementById('setoranStudent');
        if (! select) return;
        const $select = window.jQuery ? jQuery(select) : null;
        if ($select && $select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
        select.innerHTML = '<option value="">—</option>';
        (setoranMembers[groupId] || []).forEach(function (member) {
            const option = document.createElement('option');
            option.value = member.id;
            option.textContent = member.name + (member.nis ? ' (' + member.nis + ')' : '');
            if (selected && String(selected) === String(member.id)) option.selected = true;
            select.appendChild(option);
        });
        if ($select && jQuery.fn.select2) {
            $select.select2({ width: '100%', placeholder: 'Cari nama santri...', allowClear: true });
        }
    }
    function applySetoranLastPosition(studentId) {
        if (! studentId) return;
        let member = null;
        Object.values(setoranMembers).forEach(function (members) {
            const found = members.find(function (row) { return String(row.id) === String(studentId); });
            if (found) member = found;
        });
        const last = member?.last;
        if (! last) return;
        const juz = document.getElementById('setoranJuz');
        const surah = document.getElementById('setoranSurahStart');
        const ayat = document.getElementById('setoranAyatStart');
        const page = document.getElementById('setoranHalamanStart');
        if (juz && last.juz) juz.value = last.juz;
        if (surah && last.surah_start_name) {
            surah.value = last.surah_start_name;
            if (window.jQuery) jQuery(surah).trigger('change');
        }
        if (ayat && last.ayat_start) ayat.value = last.ayat_start;
        if (page && last.halaman_start) page.value = last.halaman_start;
    }
    function calculateSetoranPages() {
        const start = parseFloat(document.getElementById('setoranHalamanStart')?.value);
        const end = parseFloat(document.getElementById('setoranHalamanEnd')?.value);
        if (! Number.isNaN(start) && ! Number.isNaN(end) && end >= start) {
            document.getElementById('setoranHalamanCount').value = Math.max(0, end - start + 1).toFixed(1).replace('.0', '');
        }
    }
    function calculateSetoranScore() {
        const values = ['setoranHasil', 'setoranKhofi', 'setoranJali']
            .map(id => parseFloat(document.getElementById(id)?.value))
            .filter(value => ! Number.isNaN(value));
        if (values.length) {
            const average = values.reduce((sum, value) => sum + value, 0) / values.length;
            const badge = document.getElementById('setoranScorePreview');
            if (badge) badge.textContent = 'Nilai akhir otomatis: ' + average.toFixed(2);
        }
    }
    function openSetoranModal() {
        const group = document.getElementById('setoranGroup');
        if (! group || ! window.bootstrap) return;
        fillSetoranStudents(group.value, @json(old('student_id')));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('setoranModal')).show();
    }
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && jQuery.fn.select2) {
            jQuery('.select2-surah').select2({ width: '100%', placeholder: 'Cari nama surah...', allowClear: true });
        }
        const group = document.getElementById('setoranGroup');
        if (group) fillSetoranStudents(group.value, @json(old('student_id')));
        document.getElementById('setoranStudent')?.addEventListener('change', function () { applySetoranLastPosition(this.value); });
        document.getElementById('setoranHalamanStart')?.addEventListener('input', calculateSetoranPages);
        document.getElementById('setoranHalamanEnd')?.addEventListener('input', calculateSetoranPages);
        ['setoranHasil', 'setoranKhofi', 'setoranJali'].forEach(id => document.getElementById(id)?.addEventListener('input', calculateSetoranScore));
        calculateSetoranPages();
        calculateSetoranScore();
        @if($errors->any() && $groups->isNotEmpty())
            openSetoranModal();
        @endif
        document.querySelectorAll('.delete-setoran').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('deleteSetoranForm').action = this.dataset.action;
                document.getElementById('deleteSetoranName').textContent = this.dataset.name;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteSetoranModal')).show();
            });
        });
    });
</script>
@endpush
