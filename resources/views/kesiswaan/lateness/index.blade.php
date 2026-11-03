@extends('layouts.master')

@section('title', 'Keterlambatan Santri')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Kesiswaan @endslot
        @slot('li_2') Kedisiplinan @endslot
        @slot('title') Keterlambatan Santri @endslot
    @endcomponent

    @component('components.hrd-page-header', [
        'stats' => [
            ['label' => 'Terlambat Hari Ini', 'value' => number_format($stats['today']), 'icon' => 'ri-time-line', 'color' => 'danger'],
            ['label' => 'Minggu Ini', 'value' => number_format($stats['week']), 'icon' => 'ri-calendar-line', 'color' => 'warning'],
            ['label' => 'Bulan Ini', 'value' => number_format($stats['month']), 'icon' => 'ri-calendar-2-line', 'color' => 'primary'],
        ],
    ])
    @endcomponent

    @unless($activeYear)
        <div class="alert alert-warning">
            <i class="ri-error-warning-line me-1"></i>
            Tidak ada tahun ajaran aktif. Catatan keterlambatan tidak dapat disimpan sampai tahun ajaran diaktifkan.
        </div>
    @endunless

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Catatan Keterlambatan</h5>
                            <p class="text-muted mb-0 small">Tersimpan ke absensi harian dengan status terlambat.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap gap-2">
                                <form method="GET" class="d-flex gap-2"
                                      action="{{ route('user.kesiswaan.lateness.index', ['userId' => $userId]) }}">
                                    <input type="date" name="date" class="form-control" value="{{ $date }}">
                                    <input type="text" name="search" class="form-control" value="{{ $search }}"
                                           placeholder="Cari nama / NIS" style="width: 170px;">
                                    <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line"></i></button>
                                </form>
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#lateModal">
                                    <i class="ri-add-line me-1"></i> Catat
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if($records->isEmpty())
                        <x-empty-state icon="ri-time-line" title="Tidak ada keterlambatan"
                                       message="Belum ada santri yang tercatat terlambat pada tanggal ini." />
                    @else
                        <div class="table-container table-freeze">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Nama Santri</th>
                                        <th>NIS / NISN</th>
                                        <th>Rombel</th>
                                        <th>Jam Datang</th>
                                        <th>Catatan</th>
                                        <th class="text-end" style="width: 70px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($records as $index => $record)
                                        <tr>
                                            <td>{{ $records->firstItem() + $index }}</td>
                                            <td class="fw-semibold">{{ $record->student?->name ?? '-' }}</td>
                                            <td class="text-muted small">{{ $record->student?->nis ?: $record->student?->nisn ?: '-' }}</td>
                                            <td>{{ $record->studyGroup?->name ?? '-' }}</td>
                                            <td>{{ $record->created_at?->format('H:i') ?? '-' }}</td>
                                            <td class="text-muted small">{{ $record->notes ?: '-' }}</td>
                                            <td class="text-end">
                                                <form method="POST"
                                                      action="{{ route('user.kesiswaan.lateness.destroy', ['userId' => $userId, 'recordId' => $record->id]) }}"
                                                      data-confirm="Hapus catatan keterlambatan ini?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-soft-danger">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                @if($records->hasPages())
                    <div class="card-footer border-top-dashed">
                        {{ $records->links() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">Paling Sering Terlambat</h5>
                    <p class="text-muted mb-0 small">30 hari terakhir</p>
                </div>
                <div class="card-body">
                    @if($recap->isEmpty())
                        <p class="text-muted mb-0">Belum ada data.</p>
                    @else
                        <ol class="list-group list-group-numbered list-group-flush">
                            @foreach($recap as $row)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold">{{ $row->student?->name ?? '-' }}</div>
                                        <div class="text-muted small">{{ $row->student?->nis ?: '-' }}</div>
                                    </div>
                                    <span class="badge bg-danger-subtle text-danger">{{ $row->total }}x</span>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="lateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.kesiswaan.lateness.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-time-line me-1"></i> Catat Keterlambatan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Santri <span class="text-danger">*</span></label>
                                <select name="student_id" class="form-select" required>
                                    <option value="">— Pilih santri —</option>
                                    @foreach($candidates as $student)
                                        <option value="{{ $student->id }}" @selected(old('student_id') === $student->id)>
                                            {{ $student->name }} ({{ $student->nis ?: $student->nisn ?: '-' }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Gunakan kolom pencarian di atas untuk mempersempit daftar.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="attendance_date" class="form-control" required value="{{ $date }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jam Datang <span class="text-danger">*</span></label>
                                <input type="time" name="arrival_time" class="form-control" required value="{{ now()->format('H:i') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <input type="text" name="notes" class="form-control" maxlength="255"
                                       placeholder="Mis. terlambat 15 menit tanpa keterangan">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
