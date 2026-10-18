@extends('layouts.master')
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
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-9"><label class="form-label small">Santri</label><select name="student_id" class="form-select select2" data-placeholder="Ketik kelas, NIS, atau nama santri..."><option value="">— Pilih Santri —</option>@foreach($schoolStudents as $student)<option value="{{ $student->id }}" @selected((string)request('student_id')===(string)$student->id)>{{ $studentClassMap[$student->id] ?? $student->currentClassHistory?->studyGroup?->name ?? '—' }} | {{ $student->nis ?? '—' }} | {{ $student->name }}</option>@endforeach</select><div class="form-text">Pencarian mencakup semua santri aktif di satuan pendidikan; tabel di bawah hanya menampilkan santri halaqah Anda.</div></div>
                <div class="col-md-3 d-grid"><button class="btn btn-primary"><i class="ri-eye-line me-1"></i>Tampilkan Riwayat</button></div>
            </form>
        </div>
    </div>

    @if($selected)
        <div class="card mt-3">
            <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-1">{{ $selected->name }}</h5>
                    <div class="text-muted small">{{ $selected->nis }} · {{ $selectedGroup?->name }} · {{ $selectedGroup?->school?->name }}</div>
                </div>
                <div class="d-flex gap-2">
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
                    <thead class="table-light"><tr><th>Tanggal</th><th>Jenis</th><th>Materi</th><th class="text-center">Halaman</th><th class="text-center">Nilai</th><th>Status</th><th>Verifikasi</th><th>Guru</th></tr></thead>
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
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada riwayat hafalan.</td></tr>
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
        <div class="modal fade" id="hafalanModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('user.tahfidz.setoran.store', ['userId'=>$userId]) }}">
                        @csrf
                        <input type="hidden" name="return_to" value="input">
                        <input type="hidden" name="tahfidz_group_id" value="{{ $selectedGroup?->id }}">
                        <input type="hidden" name="student_id" value="{{ $selected->id }}">
                        <div class="modal-header bg-success-subtle"><h5 class="modal-title"><i class="ri-add-line me-1"></i>Tambah Setoran — {{ $selected->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body"><div class="row g-3">
                            <div class="col-md-3"><label class="form-label">Tanggal <span class="text-danger">*</span></label><input type="date" name="setoran_date" class="form-control" value="{{ old('setoran_date', now()->toDateString()) }}" required></div>
                            <div class="col-md-2"><label class="form-label">Pekan</label><input type="number" name="week_number" min="1" max="5" class="form-control" value="{{ old('week_number') }}"></div>
                            <div class="col-md-3"><label class="form-label">Jenis <span class="text-danger">*</span></label><select name="setoran_type" class="form-select" required><option value="ziyadah" @selected(old('setoran_type','ziyadah')==='ziyadah')>Ziyadah</option><option value="murajaah" @selected(old('setoran_type')==='murajaah')>Murajaah</option><option value="tikror" @selected(old('setoran_type')==='tikror')>Tikror</option></select></div>
                            <div class="col-md-4"><label class="form-label">Metode</label><input name="metode_pembelajaran" class="form-control" value="{{ old('metode_pembelajaran') }}" placeholder="Talaqqi/Tasmi/Mandiri"></div>
                            <div class="col-md-2"><label class="form-label">Juz</label><input type="number" name="juz" min="1" max="30" class="form-control" value="{{ old('juz', $target?->juz_start) }}"></div>
                            <div class="col-md-2"><label class="form-label">Status <span class="text-danger">*</span></label><select name="status" class="form-select" required><option value="lulus" @selected(old('status','lulus')==='lulus')>Lulus</option><option value="ulang" @selected(old('status')==='ulang')>Ulang</option><option value="ditunda" @selected(old('status')==='ditunda')>Ditunda</option></select></div>
                            <div class="col-md-4"><label class="form-label">Surah Awal</label><input name="surah_start_name" class="form-control" value="{{ old('surah_start_name') }}" placeholder="Mis. Al-Fatihah"></div>
                            <div class="col-md-2"><label class="form-label">Ayat</label><input type="number" name="ayat_start" class="form-control" value="{{ old('ayat_start') }}"></div>
                            <div class="col-md-4"><label class="form-label">Surah Akhir</label><input name="surah_end_name" class="form-control" value="{{ old('surah_end_name') }}"></div>
                            <div class="col-md-2"><label class="form-label">Ayat</label><input type="number" name="ayat_end" class="form-control" value="{{ old('ayat_end') }}"></div>
                            <div class="col-md-2"><label class="form-label">Halaman Awal</label><input type="number" step="0.1" name="halaman_start" class="form-control" value="{{ old('halaman_start') }}"></div>
                            <div class="col-md-2"><label class="form-label">Halaman Akhir</label><input type="number" step="0.1" name="halaman_end" class="form-control" value="{{ old('halaman_end') }}"></div>
                            <div class="col-md-2"><label class="form-label">Jml Halaman</label><input type="number" step="0.1" name="jumlah_halaman" class="form-control" value="{{ old('jumlah_halaman') }}"></div>
                            <div class="col-md-2"><label class="form-label">Jml Baris</label><input type="number" name="jumlah_baris" class="form-control" value="{{ old('jumlah_baris') }}"></div>
                            <div class="col-md-2"><label class="form-label">Hasil Hafalan</label><input type="number" step="0.01" min="0" max="100" name="hasil_hafalan" class="form-control" value="{{ old('hasil_hafalan') }}"></div>
                            <div class="col-md-2"><label class="form-label">Khofi</label><input type="number" step="0.01" min="0" max="100" name="khofi" class="form-control" value="{{ old('khofi') }}"></div>
                            <div class="col-md-2"><label class="form-label">Jali</label><input type="number" step="0.01" min="0" max="100" name="jali" class="form-control" value="{{ old('jali') }}"></div>
                            <div class="col-md-2"><label class="form-label">Nilai Akhir</label><input type="number" step="0.01" min="0" max="100" name="nilai_setoran" class="form-control" value="{{ old('nilai_setoran') }}" placeholder="Otomatis"></div>
                            <div class="col-12"><label class="form-label">Catatan Guru</label><textarea name="catatan_guru" rows="2" class="form-control">{{ old('catatan_guru') }}</textarea></div>
                        </div></div>
                        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan Setoran</button></div>
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
        if (window.jQuery && jQuery.fn.select2) {
            jQuery('.select2').select2({ width: '100%', placeholder: 'Ketik kelas, NIS, atau nama santri...', allowClear: true });
        }
        @if($selected && $canInput && $errors->any())
            openHafalanModal();
        @endif
    });
</script>
@endpush
