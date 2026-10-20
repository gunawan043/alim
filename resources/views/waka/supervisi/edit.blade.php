@extends('waka.master')
@section('title') Edit Supervisi @endsection

@section('css')
    @include('shared._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Supervisi @endslot
        @slot('li_2') <a href="{{ route('waka.supervisi.index') }}">Daftar</a> @endslot
        @slot('title') Edit Supervisi @endslot
    @endcomponent

    @php
        $jabatanWorkflow = strtoupper((string) (auth()->user()->employment?->jabatan ?? ''));
        $canManageWorkflow = str_contains($jabatanWorkflow, 'KEPALA') || str_contains($jabatanWorkflow, 'WAKIL')
            || str_contains($jabatanWorkflow, 'TATA USAHA') || str_contains($jabatanWorkflow, 'KOORDINATOR KURIKULUM');
    @endphp
    <x-supervisi-workflow current="schedule" :year-id="$supervisi->academic_year_id" :semester="$supervisi->semester" :admin="$canManageWorkflow" />


    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Form Edit Supervisi</h5></div>
                <div class="card-body">
                    <form action="{{ route('waka.supervisi.update', $supervisi->id) }}" method="POST" autocomplete="off" data-schedule-url="{{ route('waka.supervisi.jadwal-guru') }}">
                        @csrf @method('PUT')

                        @if($activeSk)
                            <div class="alert alert-light border small">
                                <i class="ri-file-shield-2-line me-1 text-primary"></i>Tercatat pada <strong>SK {{ $activeSk->nomor }}</strong> tanggal {{ $activeSk->tanggal?->translatedFormat('d F Y') }}.
                            </div>
                        @else
                            <div class="alert alert-warning small">
                                <i class="ri-alert-line me-1"></i>SK untuk tahun ajaran/semester ini belum tersedia.
                                <a href="{{ route('waka.supervisi-sk.index', ['academic_year_id' => $activeYearId]) }}" class="fw-semibold">Buat SK terlebih dahulu</a>.
                            </div>
                        @endif

                        <h6 class="border-bottom pb-2 mb-3">Pasangan Supervisor</h6>
                        @if($selectedPair)
                            <input type="hidden" name="observer_id" value="{{ $selectedPair['observer_id'] }}">
                            <input type="hidden" name="gtk_id" value="{{ $selectedPair['gtk_id'] }}">
                            <div class="alert alert-light border d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <div class="small text-muted">Supervisor</div>
                                    <div class="fw-semibold">{{ $selectedPair['supervisor_name'] }}</div>
                                </div>
                                <i class="ri-arrow-right-line text-muted"></i>
                                <div>
                                    <div class="small text-muted">Guru yang Disupervisi</div>
                                    <div class="fw-semibold">{{ $selectedPair['supervisee_name'] }}</div>
                                </div>
                                <a href="{{ route('waka.supervisi-pengaturan.index', ['academic_year_id' => $supervisi->academic_year_id]) }}" class="btn btn-sm btn-outline-secondary">Ubah Pengaturan</a>
                            </div>
                        @else
                            <div class="alert alert-warning small">
                                <i class="ri-alert-line me-1"></i>Pasangan supervisor untuk jadwal ini belum tersedia. Atur pasangan terlebih dahulu di
                                <a href="{{ route('waka.supervisi-pengaturan.index', ['academic_year_id' => $supervisi->academic_year_id]) }}">Pengaturan Supervisor</a>.
                            </div>
                        @endif

                        <hr>
                        <h6 class="border-bottom pb-2 mb-3">Instrumen Penilaian</h6>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Instrumen <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                @if($hasPenilaian)
                                    <input type="hidden" name="instrumen_id" value="{{ $supervisi->instrumen_id }}">
                                    <div class="alert alert-light border mb-0">
                                        <i class="ri-lock-line me-1"></i>{{ $supervisi->instrumen?->nama ?? 'Instrumen' }}
                                        <span class="text-muted small ms-2">Instrumen dikunci karena penilaian sudah diisi.</span>
                                    </div>
                                @else
                                    <select name="instrumen_id" class="form-select" required @if($instrumenOptions->isEmpty()) disabled @endif>
                                        <option value="">— Pilih Instrumen —</option>
                                        @foreach($instrumenOptions as $instrumen)
                                            <option value="{{ $instrumen->id }}" {{ (string) old('instrumen_id', $supervisi->instrumen_id) === (string) $instrumen->id ? 'selected' : '' }}>
                                                {{ $instrumen->nama }}{{ $instrumen->school_id ? '' : ' (bawaan)' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($instrumenOptions->isEmpty())
                                        <div class="alert alert-warning small mt-2 mb-0">
                                            Belum ada instrumen aktif.
                                            <a href="{{ route('waka.supervisi-instrumen.index') }}" class="fw-semibold">Buat / atur instrumen</a>.
                                        </div>
                                    @endif
                                @endif
                                @error('instrumen_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <hr>
                        <h6 class="border-bottom pb-2 mb-3">Jadwal Supervisi</h6>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="academic_year_id" id="academic_year_id" class="form-select" required>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ (string) old('academic_year_id', $supervisi->academic_year_id) === (string) $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                    @endforeach
                                </select>
                                @error('academic_year_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Semester <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="semester" class="form-select" required>
                                    <option value="1" {{ old('semester', $supervisi->semester)=='1' ? 'selected' : '' }}>1 (Ganjil)</option>
                                    <option value="2" {{ old('semester', $supervisi->semester)=='2' ? 'selected' : '' }}>2 (Genap)</option>
                                </select>
                            </div>
                        </div>
                        @if($hasPenilaian)
                            <input type="hidden" name="jadwal_kbm_id" value="{{ $supervisi->jadwal_kbm_id }}">
                            <input type="hidden" name="tanggal_supervisi" value="{{ $supervisi->tanggal_supervisi?->format('Y-m-d') }}">
                            <div class="alert alert-light border small">
                                <div class="fw-semibold">{{ $supervisi->tanggal_supervisi?->translatedFormat('l, d F Y') }}</div>
                                <div class="text-muted">
                                    Jam {{ $supervisi->slot_index ?: '-' }} ·
                                    {{ $supervisi->jam_mulai ? substr($supervisi->jam_mulai, 0, 5) : '-' }}–{{ $supervisi->jam_selesai ? substr($supervisi->jam_selesai, 0, 5) : '-' }} ·
                                    {{ $supervisi->mata_pelajaran ?: $supervisi->subject?->name ?? '-' }} ·
                                    Kelas {{ $supervisi->studyGroup?->name ?? '-' }}
                                </div>
                                <div class="text-muted mt-1">Jadwal dikunci karena penilaian sudah diisi.</div>
                            </div>
                        @else
                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label">Tanggal <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="date" name="tanggal_supervisi" id="tanggal_supervisi" class="form-control" value="{{ old('tanggal_supervisi', $supervisi->tanggal_supervisi?->format('Y-m-d')) }}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label">Jadwal Mengajar <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="hidden" name="jadwal_kbm_id" id="jadwal_kbm_id" value="{{ old('jadwal_kbm_id', $supervisi->jadwal_kbm_id) }}">
                                    <select id="jadwal_kbm_select" class="form-select" required>
                                        <option value="">Pilih tanggal dan guru terlebih dahulu…</option>
                                    </select>
                                    <div id="scheduleInfo" class="alert alert-light border small mt-2 mb-0 d-none"></div>
                                    @error('jadwal_kbm_id')<span class="text-danger">{{ $message }}</span>@enderror
                                    <div class="form-text">Jam, kelas, dan mata pelajaran terisi otomatis dari jadwal KBM guru.</div>
                                </div>
                            </div>
                        @endif
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Jenis <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="jenis_supervisi" class="form-select" required>
                                    @foreach(['perangkat_pembelajaran'=>'Perangkat Pembelajaran','proses_pembelajaran'=>'Proses Pembelajaran','penilaian'=>'Penilaian','lainnya'=>'Lainnya'] as $v=>$l)
                                        <option value="{{ $v }}" {{ old('jenis_supervisi', $supervisi->jenis_supervisi)==$v?'selected':'' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                                @error('jenis_supervisi')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tujuan</label>
                            <div class="col-sm-9">
                                <textarea name="tujuan" class="form-control" rows="2">{{ old('tujuan', $supervisi->tujuan) }}</textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Status <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="status" class="form-select" required>
                                    @foreach(['terjadwal','berlangsung','selesai','dibatalkan'] as $s)
                                        <option value="{{ $s }}" {{ old('status', $supervisi->status)===$s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                                @error('status')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('waka.supervisi.index') }}" class="btn btn-secondary me-1"><i class="ri-arrow-go-back-line"></i> Kembali</a>
                            <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[data-schedule-url]');
    const date = document.getElementById('tanggal_supervisi');
    const year = document.getElementById('academic_year_id');
    const scheduleSelect = document.getElementById('jadwal_kbm_select');
    const scheduleId = document.getElementById('jadwal_kbm_id');
    const info = document.getElementById('scheduleInfo');
    const gtk = document.getElementById('gtk_id')?.value;
    const currentScheduleId = @json($supervisi->jadwal_kbm_id);

    if (!form || !date || !scheduleSelect || !gtk) return;

    async function loadSchedules() {
        if (!date.value) return;
        scheduleSelect.innerHTML = '<option value="">Memuat jadwal…</option>';
        try {
            const params = new URLSearchParams({ gtk_profile_id: gtk, tanggal: date.value, academic_year_id: year?.value || '' });
            const response = await fetch(form.dataset.scheduleUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            scheduleSelect.innerHTML = '<option value="">— Pilih Jadwal Mengajar —</option>';
            (data.schedules || []).forEach(function (item) {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.label;
                option.dataset.start = item.start_time;
                option.dataset.end = item.end_time;
                option.dataset.subject = item.subject;
                option.dataset.class = item.study_group;
                option.dataset.slot = item.slot_index;
                scheduleSelect.appendChild(option);
            });
            scheduleSelect.value = currentScheduleId || ((data.schedules || []).length === 1 ? scheduleSelect.options[1]?.value : '');
            applySchedule();
        } catch (error) {
            scheduleSelect.innerHTML = '<option value="">Gagal memuat jadwal</option>';
        }
    }

    function applySchedule() {
        const option = scheduleSelect.options[scheduleSelect.selectedIndex];
        if (!option || !option.value) {
            scheduleId.value = '';
            info.classList.add('d-none');
            return;
        }
        scheduleId.value = option.value;
        info.innerHTML = '<strong>Jam ' + (option.dataset.slot || '-') + '</strong> · ' + option.dataset.start + '–' + option.dataset.end + ' · ' + option.dataset.subject + ' · Kelas ' + option.dataset.class;
        info.classList.remove('d-none');
    }

    scheduleSelect.addEventListener('change', applySchedule);
    date.addEventListener('change', loadSchedules);
    year?.addEventListener('change', loadSchedules);
    loadSchedules();
});
</script>
@endsection
