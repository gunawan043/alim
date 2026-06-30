@extends('layouts.master')
@section('title') Detail Tugas Tambahan GTK @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Administrasi @endslot
        @slot('li_2') <a href="{{ route('user.gtk-additional-tasks.index', ['userId' => $userId]) }}">Tugas Tambahan GTK</a> @endslot
        @slot('title') Detail Tugas @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Detail Tugas Tambahan</h5>
                            <p class="text-muted mb-0">{{ $task->nama_tugas }} — {{ $task->user?->name ?? '-' }}</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-2">
                                <a href="{{ route('user.gtk-additional-tasks.edit', ['userId' => $userId, 'id' => $task->id]) }}" class="btn btn-primary btn-sm">
                                    <i class="ri-pencil-line me-1"></i> Edit
                                </a>
                                <a href="{{ route('user.gtk-additional-tasks.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
                                    <i class="ri-arrow-left-line me-1"></i> Kembali
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-4">
                        {{-- GURU --}}
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-primary mb-3"><i class="ri-user-line me-1"></i> Informasi Guru</h6>
                                <table class="table table-borderless table-sm mb-0">
                                    <tr>
                                        <th class="text-muted" style="width:140px">Nama Guru</th>
                                        <td>
                                            <a href="{{ route('user.gtk.show', ['userId' => $userId, 'uuid' => $task->user_id]) }}" class="text-reset fw-medium">
                                                {{ $task->user?->name ?? 'Tidak ditemukan' }}
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Role</th>
                                        <td>{{ $task->user?->getRoleNames()->first() ?? '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        {{-- TUGAS --}}
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-primary mb-3"><i class="ri-briefcase-line me-1"></i> Detail Tugas</h6>
                                <table class="table table-borderless table-sm mb-0">
                                    <tr>
                                        <th class="text-muted" style="width:140px">Nama Tugas</th>
                                        <td class="fw-medium">{{ $task->nama_tugas }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Jam/Minggu</th>
                                        <td class="text-center">{{ $task->hours_per_week ?? '-' }} JP</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        {{-- SK REFERENSI --}}
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-primary mb-3"><i class="ri-file-text-line me-1"></i> Surat Keputusan</h6>
                                <table class="table table-borderless table-sm mb-0">
                                    <tr>
                                        <th class="text-muted" style="width:140px">Nomor SK</th>
                                        <td>
                                            @if($task->nomor_sk)
                                                <code>{{ $task->nomor_sk }}</code>
                                            @else
                                                <span class="text-muted">Belum diisi</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">SK Referensi</th>
                                        <td>
                                            @if($task->decree)
                                                <code>{{ $task->decree->decree_number }}</code>
                                                <br><small class="text-muted">{{ $task->decree->title }}</small>
                                            @else
                                                <span class="text-muted">Tidak ada SK referensi</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        {{-- PERIODE --}}
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-primary mb-3"><i class="ri-calendar-line me-1"></i> Periode Tugas</h6>
                                <table class="table table-borderless table-sm mb-0">
                                    <tr>
                                        <th class="text-muted" style="width:140px">TMT (Mulai)</th>
                                        <td>{{ $task->tmt?->format('d F Y') ?? '<span class="text-muted">Belum diisi</span>' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">TST (Selesai)</th>
                                        <td>{{ $task->tst?->format('d F Y') ?? '<span class="text-muted">Belum diisi</span>' }}</td>
                                    </tr>
                                    @if($task->tmt && !$task->tst)
                                    <tr>
                                        <th class="text-muted">Status</th>
                                        <td><span class="badge bg-success-subtle text-success">Aktif</span></td>
                                    </tr>
                                    @elseif($task->tmt && $task->tst)
                                    <tr>
                                        <th class="text-muted">Status</th>
                                        <td>
                                            @if(\Carbon\Carbon::parse($task->tst)->isPast())
                                                <span class="badge bg-secondary-subtle text-secondary">Selesai</span>
                                            @else
                                                <span class="badge bg-info-subtle text-info">Akan Selesai</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted mb-2">Meta Informasi</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <th class="text-muted" style="width:140px">Dibuat</th>
                                    <td>{{ $task->created_at?->format('d F Y H:i') ?? '-' }} WIB</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Terakhir Diubah</th>
                                    <td>{{ $task->updated_at?->format('d F Y H:i') ?? '-' }} WIB</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('user.gtk-additional-tasks.edit', ['userId' => $userId, 'id' => $task->id]) }}" class="btn btn-primary">
                        <i class="ri-pencil-line me-1"></i> Edit Tugas
                    </a>
                    <form method="POST" action="{{ route('user.gtk-additional-tasks.destroy', ['userId' => $userId, 'id' => $task->id]) }}"
                          class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tugas ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="ri-delete-bin-line me-1"></i> Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
