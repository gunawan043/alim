@extends('layouts.master')

@section('title', 'Jadwal Latihan')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Ekstrakurikuler @endslot
        @slot('li_2') Pembinaan @endslot
        @slot('title') Jadwal Latihan @endslot
    @endcomponent

    @component('components.hrd-page-header', [
        'stats' => [
            ['label' => 'Total Ekstrakurikuler', 'value' => number_format($ekskuls->count()), 'icon' => 'ri-basketball-line', 'color' => 'primary'],
            ['label' => 'Sudah Terjadwal', 'value' => number_format($ekskuls->count() - $tanpaJadwal), 'icon' => 'ri-calendar-check-line', 'color' => 'success'],
            ['label' => 'Belum Terjadwal', 'value' => number_format($tanpaJadwal), 'icon' => 'ri-calendar-close-line', 'color' => 'warning'],
        ],
    ])
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Jadwal Latihan Ekstrakurikuler</h5>
                            <p class="text-muted mb-0 small">Atur hari, jam, dan lokasi latihan tiap ekstrakurikuler.</p>
                        </div>
                        <div class="col-sm-auto">
                            <form method="GET" class="d-flex gap-2"
                                  action="{{ route('user.kesiswaan.ekskul-schedules.index', ['userId' => $userId]) }}">
                                <input type="text" name="search" class="form-control" value="{{ $search }}"
                                       placeholder="Cari ekstrakurikuler..." style="width: 240px;">
                                <button type="submit" class="btn btn-primary"><i class="ri-search-line"></i></button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if($ekskuls->isEmpty())
                        <x-empty-state icon="ri-basketball-line" title="Belum ada ekstrakurikuler"
                                       message="Data ekstrakurikuler belum tersedia untuk unit ini." />
                    @else
                        <div class="table-container table-freeze">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ekstrakurikuler</th>
                                        <th>Pembina</th>
                                        <th style="width: 110px;">Hari</th>
                                        <th style="width: 140px;">Jam</th>
                                        <th>Lokasi</th>
                                        <th class="text-center" style="width: 90px;">Anggota</th>
                                        <th class="text-end" style="width: 80px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($ekskuls as $ekskul)
                                        <tr>
                                            <td class="fw-semibold">{{ $ekskul->nama }}</td>
                                            <td>{{ $ekskul->pembimbing ?: '-' }}</td>
                                            <td>
                                                @if($ekskul->hari)
                                                    {{ $hari[strtolower($ekskul->hari)] ?? ucfirst($ekskul->hari) }}
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning">Belum diatur</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($ekskul->jam_mulai)
                                                    {{ substr($ekskul->jam_mulai, 0, 5) }} – {{ $ekskul->jam_selesai ? substr($ekskul->jam_selesai, 0, 5) : '?' }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $ekskul->lokasi ?: '-' }}</td>
                                            <td class="text-center">{{ $ekskul->anggota_aktif_count ?? 0 }}</td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-soft-primary btn-schedule"
                                                        data-bs-toggle="modal" data-bs-target="#scheduleModal"
                                                        data-id="{{ $ekskul->id }}"
                                                        data-nama="{{ $ekskul->nama }}"
                                                        data-hari="{{ strtolower((string) $ekskul->hari) }}"
                                                        data-mulai="{{ $ekskul->jam_mulai ? substr($ekskul->jam_mulai, 0, 5) : '' }}"
                                                        data-selesai="{{ $ekskul->jam_selesai ? substr($ekskul->jam_selesai, 0, 5) : '' }}"
                                                        data-lokasi="{{ $ekskul->lokasi }}"
                                                        data-pembimbing="{{ $ekskul->pembimbing }}">
                                                    <i class="ri-edit-line"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="scheduleForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-calendar-2-line me-1"></i> Atur Jadwal Latihan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Ekstrakurikuler</label>
                            <input type="text" class="form-control" id="scheduleNama" disabled>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Hari</label>
                                <select name="hari" class="form-select" id="scheduleHari">
                                    <option value="">— Belum diatur —</option>
                                    @foreach($hari as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jam Mulai</label>
                                <input type="time" name="jam_mulai" class="form-control" id="scheduleMulai">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jam Selesai</label>
                                <input type="time" name="jam_selesai" class="form-control" id="scheduleSelesai">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Lokasi</label>
                                <input type="text" name="lokasi" class="form-control" maxlength="120" id="scheduleLokasi">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pembina</label>
                                <input type="text" name="pembimbing" class="form-control" maxlength="120" id="schedulePembimbing">
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

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('scheduleForm');
    var baseUrl = '{{ url('/'.$userId.'/kesiswaan/jadwal-latihan') }}';

    document.querySelectorAll('.btn-schedule').forEach(function (button) {
        button.addEventListener('click', function () {
            form.action = baseUrl + '/' + this.dataset.id;
            document.getElementById('scheduleNama').value = this.dataset.nama || '';
            document.getElementById('scheduleHari').value = this.dataset.hari || '';
            document.getElementById('scheduleMulai').value = this.dataset.mulai || '';
            document.getElementById('scheduleSelesai').value = this.dataset.selesai || '';
            document.getElementById('scheduleLokasi').value = this.dataset.lokasi || '';
            document.getElementById('schedulePembimbing').value = this.dataset.pembimbing || '';
        });
    });
});
</script>
@endpush
