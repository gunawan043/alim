@extends('layouts.master')

@section('title', 'Detail Mufradat')

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') <a href="{{ route('user.bahasa.mufradat.index', ['userId' => $userId]) }}">Mufradat</a> @endslot
        @slot('title') {{ $period->title }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-3">
                <div>
                    <h4 class="mb-1">{{ $period->title }}</h4>
                    <div class="lang-muted">
                        {{ $period->period_start?->format('d/m/Y') ?? '-' }} — {{ $period->period_end?->format('d/m/Y') ?? '-' }}
                        · {{ $period->academicYear?->name ?? '-' }}
                        · Minimal {{ $period->min_words }} kosakata
                    </div>
                    <div class="mt-2 small">
                        <span class="me-3"><i class="ri-school-line me-1"></i>{{ $period->location_school ?: 'Mading sekolah belum diisi' }}</span>
                        <span><i class="ri-home-heart-line me-1"></i>{{ $period->location_dorm ?: 'Mading asrama belum diisi' }}</span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if($canRecord ?? $canManage)
                        <a href="{{ route('user.bahasa.mufradat.attendance', ['userId' => $userId, 'period' => $period->id]) }}" class="btn btn-success"><i class="ri-check-double-line me-1"></i>Input Setoran</a>
                    @endif
                    <a href="{{ route('user.bahasa.mufradat.report', ['userId' => $userId, 'period' => $period->id]) }}" class="btn btn-soft-primary"><i class="ri-file-chart-line me-1"></i>Laporan</a>
                    <a href="{{ route('user.bahasa.mufradat.print-list', ['userId' => $userId, 'period' => $period->id]) }}" target="_blank" class="btn btn-light"><i class="ri-printer-line me-1"></i>Cetak Daftar</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div><h5 class="card-title mb-0">Daftar Mufradat</h5><div class="lang-muted">{{ $period->vocabularies->count() }} kosakata</div></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th style="width:45px">No</th><th>Kata (Arab)</th><th>Arti</th><th>Contoh Kalimat</th>@if($canManage)<th></th>@endif</tr></thead>
                        <tbody>
                            @forelse($period->vocabularies as $word)
                                <tr>
                                    <td>{{ $word->position }}</td>
                                    <td class="lang-word-ar">{{ $word->arabic }}</td>
                                    <td>{{ $word->meaning }}</td>
                                    <td class="lang-muted">{{ $word->example_sentence ?: '-' }}</td>
                                    @if($canManage)
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-soft-danger js-delete" data-action="{{ route('user.bahasa.mufradat.words.destroy', ['userId' => $userId, 'period' => $period->id, 'word' => $word->id]) }}" data-title="Mufradat {{ $word->arabic }}"><i class="ri-delete-bin-line"></i></button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="{{ $canManage ? 5 : 4 }}" class="text-center text-muted py-4">Belum ada kosakata.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($canManage)
                    <div class="card-footer bg-light-subtle">
                        <form method="POST" action="{{ route('user.bahasa.mufradat.words.store', ['userId' => $userId, 'period' => $period->id]) }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-3"><label class="form-label small mb-1">Kata Arab</label><input name="arabic" class="form-control form-control-sm" required></div>
                            <div class="col-md-3"><label class="form-label small mb-1">Arti</label><input name="meaning" class="form-control form-control-sm" required></div>
                            <div class="col-md-4"><label class="form-label small mb-1">Contoh Kalimat</label><input name="example_sentence" class="form-control form-control-sm"></div>
                            <div class="col-md-2"><button class="btn btn-sm btn-success w-100"><i class="ri-add-line"></i> Tambah</button></div>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Jadwal Setoran</h5><div class="lang-muted">Minimal 3× per pekan</div></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Hari</th><th>Jam</th><th>Tempat</th><th>Mustami</th>@if($canManage)<th></th>@endif</tr></thead>
                        <tbody>
                            @forelse($period->schedules as $schedule)
                                <tr>
                                    <td>{{ $schedule->day_label }}</td>
                                    <td>{{ $schedule->time_start ? substr($schedule->time_start, 0, 5) : '-' }}{{ $schedule->time_end ? ' - '.substr($schedule->time_end, 0, 5) : '' }}</td>
                                    <td>{{ $schedule->place ?: '-' }}</td>
                                    <td>{{ $schedule->mustami?->name ?? '-' }}</td>
                                    @if($canManage)
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-soft-danger js-delete" data-action="{{ route('user.bahasa.mufradat.schedules.destroy', ['userId' => $userId, 'period' => $period->id, 'schedule' => $schedule->id]) }}" data-title="Jadwal {{ $schedule->day_label }}"><i class="ri-delete-bin-line"></i></button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="{{ $canManage ? 5 : 4 }}" class="text-center text-muted py-3">Belum ada jadwal.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($canManage)
                    <div class="card-footer">
                        <form method="POST" action="{{ route('user.bahasa.mufradat.schedules.store', ['userId' => $userId, 'period' => $period->id]) }}" class="row g-2">
                            @csrf
                            <div class="col-6"><select name="day_of_week" class="form-select form-select-sm">@foreach($days as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                            <div class="col-3"><input type="time" name="time_start" class="form-control form-control-sm"></div>
                            <div class="col-3"><input type="time" name="time_end" class="form-control form-control-sm"></div>
                            <div class="col-6"><input name="place" class="form-control form-control-sm" placeholder="Tempat"></div>
                            <div class="col-6"><select name="mustami_id" class="form-select form-select-sm"><option value="">— Mustami —</option>@foreach($mustamiOptions as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                            <div class="col-12"><button class="btn btn-sm btn-success w-100"><i class="ri-add-line"></i> Tambah Jadwal</button></div>
                        </form>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Pembagian Mustami</h5><div class="lang-muted">Ditetapkan awal semester</div></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Mustami</th><th>Scope</th><th>Asrama</th>@if($canManageStructure)<th></th>@endif</tr></thead>
                        <tbody>
                            @forelse($period->mustamis as $assignment)
                                <tr>
                                    <td>{{ $assignment->mustami?->name ?? '-' }}</td>
                                    <td>{{ $assignment->scope ?: '-' }}</td>
                                    <td>{{ $assignment->dormitory?->name ?? '-' }}</td>
                                    @if($canManageStructure)
                                        <td class="text-end"><button type="button" class="btn btn-sm btn-soft-danger js-delete" data-action="{{ route('user.bahasa.mufradat.mustamis.destroy', ['userId' => $userId, 'period' => $period->id, 'assignment' => $assignment->id]) }}" data-title="Mustami {{ $assignment->mustami?->name }}"><i class="ri-delete-bin-line"></i></button></td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="{{ $canManageStructure ? 4 : 3 }}" class="text-center text-muted py-3">Belum ada mustami.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($canManageStructure)
                    <div class="card-footer">
                        <form method="POST" action="{{ route('user.bahasa.mufradat.mustamis.store', ['userId' => $userId, 'period' => $period->id]) }}" class="row g-2">
                            @csrf
                            <div class="col-7"><select name="mustami_id" class="form-select form-select-sm" required><option value="">— Pilih Mustami —</option>@foreach($mustamiOptions as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                            <div class="col-5"><input name="scope" class="form-control form-control-sm" placeholder="Kelas / kelompok"></div>
                            <div class="col-8"><select name="dormitory_id" class="form-select form-select-sm"><option value="">— Asrama —</option>@foreach($dormitories as $dormitory)<option value="{{ $dormitory->id }}">{{ $dormitory->name }}</option>@endforeach</select></div>
                            <div class="col-4"><button class="btn btn-sm btn-success w-100"><i class="ri-add-line"></i></button></div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><h5 class="card-title mb-0">Setoran Terbaru</h5><div class="lang-muted">30 catatan terakhir</div></div>
            <a href="{{ route('user.bahasa.mufradat.report', ['userId' => $userId, 'period' => $period->id]) }}" class="btn btn-sm btn-soft-primary">Laporan Evaluasi</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Tanggal</th><th>Santri</th><th>Mustami</th><th class="text-center">Benar</th><th class="text-center">Nilai</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($setorans as $setoran)
                        <tr>
                            <td>{{ $setoran->setoran_date?->format('d/m/Y') }}</td>
                            <td>{{ $setoran->student?->name ?? '-' }}</td>
                            <td>{{ $setoran->mustami?->name ?? '-' }}</td>
                            <td class="text-center">{{ $setoran->correct_words }}/{{ $setoran->total_words }}</td>
                            <td class="text-center">{{ $setoran->score_label }}</td>
                            <td><span class="badge lang-badge {{ $setoran->status === 'lulus' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ $setoran->status_label }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada setoran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="deleteItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" id="deleteItemForm" action="#">
                @csrf @method('DELETE')
                <div class="modal-body text-center p-4">
                    <div class="avatar-lg mx-auto mb-3"><span class="avatar-title bg-danger-subtle rounded-circle fs-1 text-danger"><i class="ri-delete-bin-6-line"></i></span></div>
                    <h5>Hapus Data?</h5>
                    <p class="text-muted mb-0"><strong id="deleteItemTitle">Item ini</strong> akan dihapus permanen.</p>
                </div>
                <div class="modal-footer justify-content-center"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger">Hapus</button></div>
            </form>
        </div>
    </div>
@endsection

@section('script')
<script>
document.querySelectorAll('.js-delete').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById('deleteItemForm').action = button.dataset.action;
        document.getElementById('deleteItemTitle').textContent = button.dataset.title || 'item ini';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteItemModal')).show();
    });
});
</script>
@endsection
