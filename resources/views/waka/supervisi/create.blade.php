@extends('waka.master')
@section('title') Tambah Supervisi @endsection

@section('css')
    @include('shared._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Supervisi @endslot
        @slot('title') Tambah Data Supervisi @endslot
    @endcomponent

    <div class="alert alert-light border small">
        <i class="ri-information-line me-1 text-primary"></i>
        Instrumen penilaian tidak dipilih di form ini. Setelah jadwal disimpan, buka detail supervisi → <strong>Isi Penilaian</strong> → pilih instrumen → isi skor.
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Form Tambah Supervisi</h5></div>
                <div class="card-body">
                    <form action="{{ route('waka.supervisi.store') }}" method="POST" autocomplete="off" data-schedule-url="{{ route('waka.supervisi.jadwal-guru') }}">
                        @csrf
                        <input type="hidden" name="redirect_to" value="{{ request('redirect_to') }}">
                        @php
                            $today = old('_today') ?? date('Y-m-d');
                        @endphp

                        @if(! $hasSettings)
                            <div class="alert alert-warning small">
                                <i class="ri-alert-line me-1"></i>Belum ada pengaturan supervisor untuk tahun ajaran ini. Tambahkan pasangan supervisor → guru terlebih dahulu sebelum membuat jadwal.
                                <a href="{{ route('waka.supervisi-pengaturan.index', ['academic_year_id' => $activeYearId]) }}" class="fw-semibold ms-1">Atur Supervisor</a>
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
                                <a href="{{ route('waka.supervisi-pengaturan.index', ['academic_year_id' => $activeYearId]) }}" class="btn btn-sm btn-outline-secondary">Ubah Pengaturan</a>
                            </div>
                        @elseif($hasSettings)
                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label">Penugasan Supervisor <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <select id="assignment_pair" class="form-select" required>
                                        <option value="">— Pilih Pasangan Supervisor → Guru —</option>
                                        @foreach($assignmentOptions as $pair)
                                            <option value="{{ $pair['value'] }}"
                                                    data-observer="{{ $pair['observer_id'] }}"
                                                    data-gtk="{{ $pair['gtk_id'] }}"
                                                    {{ old('observer_id') === $pair['observer_id'] && old('gtk_id') === $pair['gtk_id'] ? 'selected' : '' }}>
                                                {{ $pair['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="observer_id" id="observer_id" value="{{ old('observer_id') }}">
                                    <input type="hidden" name="gtk_id" id="gtk_id" value="{{ old('gtk_id') }}">
                                    @error('observer_id')<span class="text-danger">{{ $message }}</span>@enderror
                                    @error('gtk_id')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        @endif

                        <hr>
                        <h6 class="border-bottom pb-2 mb-3">Instrumen Penilaian</h6>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Instrumen <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="instrumen_id" class="form-select" required @if($instrumenOptions->isEmpty()) disabled @endif>
                                    <option value="">— Pilih Instrumen —</option>
                                    @foreach($instrumenOptions as $instrumen)
                                        <option value="{{ $instrumen->id }}" {{ (string) old('instrumen_id') === (string) $instrumen->id ? 'selected' : '' }}>
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
                                @error('instrumen_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <hr>
                        <h6 class="border-bottom pb-2 mb-3">Jadwal Supervisi</h6>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="academic_year_id" id="academic_year_id" class="form-select" required>
                                    <option value="">— Pilih —</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ (string) old('academic_year_id', $activeYearId) === (string) $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                    @endforeach
                                </select>
                                @error('academic_year_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Semester <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="semester" class="form-select" required>
                                    <option value="1" {{ old('semester')=='1' ? 'selected' : '' }}>1 (Ganjil)</option>
                                    <option value="2" {{ old('semester')=='2' ? 'selected' : '' }}>2 (Genap)</option>
                                </select>
                                @error('semester')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tanggal Supervisi <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_supervisi" id="tanggal_supervisi" class="form-control" value="{{ old('tanggal_supervisi') }}" required>
                                @error('tanggal_supervisi')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Jadwal Mengajar <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="hidden" name="jadwal_kbm_id" id="jadwal_kbm_id" value="{{ old('jadwal_kbm_id', $selectedScheduleId) }}">
                                <select id="jadwal_kbm_select" class="form-select" required>
                                    <option value="">Pilih tanggal dan guru terlebih dahulu…</option>
                                </select>
                                <div id="scheduleInfo" class="alert alert-light border small mt-2 mb-0 d-none"></div>
                                @error('jadwal_kbm_id')<span class="text-danger">{{ $message }}</span>@enderror
                                <div class="form-text">Jam, kelas, dan mata pelajaran terisi otomatis dari jadwal KBM guru pada tanggal tersebut.</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Jenis <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="jenis_supervisi" class="form-select" required>
                                    <option value="">— Pilih Jenis —</option>
                                    @foreach(['perangkat_pembelajaran'=>'Perangkat Pembelajaran','proses_pembelajaran'=>'Proses Pembelajaran','penilaian'=>'Penilaian','lainnya'=>'Lainnya'] as $v => $l)
                                        <option value="{{ $v }}" {{ old('jenis_supervisi')==$v?'selected':'' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                                @error('jenis_supervisi')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tujuan</label>
                            <div class="col-sm-9">
                                <textarea name="tujuan" class="form-control" rows="2">{{ old('tujuan') }}</textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Status <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="status" class="form-select" required>
                                    <option value="terjadwal" {{ old('status')==='terjadwal'?'selected':'' }}>Terjadwal</option>
                                    <option value="berlangsung" {{ old('status')==='berlangsung'?'selected':'' }}>Berlangsung</option>
                                    <option value="selesai" {{ old('status')==='selesai'?'selected':'' }}>Selesai</option>
                                    <option value="dibatalkan" {{ old('status')==='dibatalkan'?'selected':'' }}>Dibatalkan</option>
                                </select>
                                @error('status')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('waka.supervisi.index') }}" class="btn btn-secondary me-1"><i class="ri-arrow-go-back-line"></i> Kembali</a>
                            <button type="submit" class="btn btn-primary" @if(! $hasSettings || $instrumenOptions->isEmpty()) disabled @endif><i class="ri-save-line"></i> Simpan</button>
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
    const pair = document.getElementById('assignment_pair');
    const observer = document.getElementById('observer_id');
    const supervisee = document.getElementById('gtk_id');

    if (!observer || !supervisee) return;

    function syncPair() {
        if (!pair) return;
        const option = pair.options[pair.selectedIndex];
        observer.value = option?.dataset.observer || '';
        supervisee.value = option?.dataset.gtk || '';
    }

    if (pair) {
        pair.addEventListener('change', function () {
            syncPair();
            loadSchedules();
        });
        syncPair();
    }

    const form = pair.closest('form');
    const date = document.getElementById('tanggal_supervisi');
    const year = document.getElementById('academic_year_id');
    const scheduleSelect = document.getElementById('jadwal_kbm_select');
    const scheduleId = document.getElementById('jadwal_kbm_id');
    const info = document.getElementById('scheduleInfo');
    const currentScheduleId = @json($selectedScheduleId);

    async function loadSchedules() {
        const gtk = document.getElementById('gtk_id')?.value;
        if (!form || !date?.value || !gtk) return;

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

            if ((data.schedules || []).length === 1) {
                scheduleSelect.selectedIndex = 1;
            } else if (currentScheduleId) {
                scheduleSelect.value = currentScheduleId;
            }
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
            info.textContent = '';
            return;
        }
        scheduleId.value = option.value;
        info.innerHTML = '<strong>Jam ' + (option.dataset.slot || '-') + '</strong> · ' +
            option.dataset.start + '–' + option.dataset.end + ' · ' +
            option.dataset.subject + ' · Kelas ' + option.dataset.class;
        info.classList.remove('d-none');
    }

    scheduleSelect.addEventListener('change', applySchedule);
    date?.addEventListener('change', loadSchedules);
    year?.addEventListener('change', loadSchedules);
    loadSchedules();
});
</script>
@endsection
