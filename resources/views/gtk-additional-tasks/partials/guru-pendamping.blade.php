{{--
    Panel khusus tugas tambahan "Guru Pendamping".
    Hanya tampil bila nama tugas yang dipilih adalah Guru Pendamping.
    Mapel + kelas wajib dipilih; JP dihitung dari Peta Kurikulum.
--}}
@php
    $gpSelectedSubjects = collect(old('pendamping_subjects',
        $task && $task->additionalTaskType ? $task->additionalTaskType->subjects->pluck('subject_id')->all() : []
    ))->map(fn ($id) => (string) $id)->all();

    $gpSelectedGroups = collect(old('pendamping_groups',
        $task ? $task->studyGroups->pluck('id')->all() : []
    ))->map(fn ($id) => (string) $id)->all();

    $gpTypeId = $guruPendamping?->id;
@endphp

<div class="card mb-3 d-none" id="guruPendampingPanel"
     data-hours-url="{{ route('user.gtk-additional-tasks.pendamping.hours', ['userId' => $userId]) }}"
     data-type-id="{{ $gpTypeId }}"
     data-peta-url="{{ route('user.kurikulum.index', ['userId' => $userId]) }}">
    <div class="card-header bg-success-subtle d-flex align-items-center gap-2">
        <i class="ri-user-star-line"></i>
        <div>
            <h6 class="mb-0">Guru Pendamping</h6>
            <p class="text-muted small mb-0">Tugas ini hanya memenuhi JP. Tidak membuka Buku Nilai maupun Perangkat Ajar.</p>
        </div>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label fw-semibold">Mapel yang Didampingi <span class="text-danger">*</span></label>
            <p class="text-muted small mb-2">
                Daftar ini berlaku untuk <strong>semua</strong> guru pendamping di satuan pendidikan Anda.
            </p>
            @if($subjects->isEmpty())
                <div class="alert alert-warning mb-0 small">
                    Belum ada mapel aktif di satuan pendidikan ini.
                    <a href="{{ route('user.master-data.mata-pelajaran.index', ['userId' => $userId]) }}">Buka menu Mata Pelajaran</a> untuk menambahkannya.
                </div>
            @else
                <div class="border rounded p-2" style="max-height:240px;overflow:auto">
                    @foreach($subjects as $schoolName => $schoolSubjects)
                        @if($subjects->count() > 1)
                            <div class="text-muted small fw-semibold px-1 pt-1">{{ $schoolName }}</div>
                        @endif
                        <div class="row g-1">
                            @foreach($schoolSubjects as $subject)
                                <div class="col-md-6">
                                    <label class="form-check mb-0">
                                        <input type="checkbox" class="form-check-input gp-subject" name="pendamping_subjects[]"
                                               value="{{ $subject->id }}" @checked(in_array((string) $subject->id, $gpSelectedSubjects, true))>
                                        <span class="form-check-label small">
                                            {{ $subject->name }}
                                            @if($subject->code)<span class="text-muted">({{ $subject->code }})</span>@endif
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="mb-2">
            <label class="form-label fw-semibold">Kelas yang Didampingi <span class="text-danger">*</span></label>
            <p class="text-muted small mb-2">JP muncul otomatis dari Peta Kurikulum untuk mapel di atas.</p>
            @if($studyGroups->isEmpty())
                <div class="alert alert-warning mb-0 small">Belum ada kelas aktif di satuan pendidikan ini.</div>
            @else
                <div class="border rounded p-2" style="max-height:200px;overflow:auto">
                    <div class="row g-1">
                        @foreach($studyGroups as $group)
                            <div class="col-md-4">
                                <label class="form-check mb-0">
                                    <input type="checkbox" class="form-check-input gp-group" name="pendamping_groups[]"
                                           value="{{ $group->id }}" @checked(in_array((string) $group->id, $gpSelectedGroups, true))>
                                    <span class="form-check-label small">{{ $group->name }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div id="gpHoursBox" class="mt-3">
            <div class="text-muted small">Pilih mapel dan kelas untuk melihat JP.</div>
        </div>
    </div>
</div>

<script src="{{ asset('build/js/guru-pendamping.js') }}"></script>
