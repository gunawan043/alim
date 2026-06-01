@extends('layouts.master')
@section('title')
    Hasil Tes & Seleksi Akhir
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Rekrutmen
        @endslot
        @slot('title')
            Hasil Tes & Seleksi Akhir
        @endslot
    @endcomponent

    {{-- ========== STATISTICS ROW ========== --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card card-body bg-primary bg-opacity-10 border-primary border-opacity-25">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="avatar-sm"><div class="avatar-title bg-primary text-primary rounded fs-22"><i class="ri-user-follow-line"></i></div></div>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-0">Total Kandidat Dipanggil</p>
                        <h4 class="fw-bold mb-0">{{ $totalKandidat }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-body bg-success bg-opacity-10 border-success border-opacity-25">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="avatar-sm"><div class="avatar-title bg-success text-success rounded fs-22"><i class="ri-user-check-line"></i></div></div>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-0">Diterima</p>
                        <h4 class="fw-bold mb-0">{{ $totalDiterima }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-body bg-danger bg-opacity-10 border-danger border-opacity-25">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="avatar-sm"><div class="avatar-title bg-danger text-danger rounded fs-22"><i class="ri-user-close-line"></i></div></div>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-0">Ditolak</p>
                        <h4 class="fw-bold mb-0">{{ $totalDitolak }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-body bg-warning bg-opacity-10 border-warning border-opacity-25">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="avatar-sm"><div class="avatar-title bg-warning text-warning rounded fs-22"><i class="ri-bookmark-line"></i></div></div>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-0">Cadangan</p>
                        <h4 class="fw-bold mb-0">{{ $totalCadangan }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== INFO HARI TES ========== --}}
    @if ($hariTes)
    <div class="alert alert-info d-flex align-items-center gap-3 mb-4" role="alert">
        <i class="ri-calendar-check-line fs-24 flex-shrink-0"></i>
        <div>
            <strong>Jadwal Hari Tes:</strong> {{ $hariTes }}
            @if ($lokasiTes) &nbsp;|&nbsp; <strong>Lokasi:</strong> {{ $lokasiTes }} @endif
            <span class="text-muted ms-2">— Kandidat yang telah dinyatakan lolos administrasi</span>
        </div>
    </div>
    @endif

    {{-- ========== MAIN CARD ========== --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="interviewList">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Daftar Kandidat Tes</h5>
                        <div class="flex-shrink-0 d-flex gap-2">
                            <a href="{{ route('user.ats.interviews.export', ['userId' => $userId]) }}?_job_id={{ request('job_id') }}&_search={{ request('search') }}" class="btn btn-success">
                                <i class="ri-file-excel-line"></i> Export Excel
                            </a>
                            <button class="btn btn-primary" onclick="saveAll()">
                                <i class="ri-save-line"></i> Simpan Semua
                            </button>
                            <button class="btn btn-warning" onclick="announceAll()">
                                <i class="ri-notification-3-line"></i> Pengumuman Akhir
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Filter Bar --}}
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form method="GET" action="{{ route('user.ats.interviews.index', ['userId' => $userId]) }}" id="filterForm">
                        <div class="row g-3 align-items-end">
                            <div class="col-xxl-4 col-sm-6">
                                <div class="search-box">
                                    <input type="text" class="form-control search" id="searchInput"
                                        name="search" placeholder="Cari nama kandidat..." value="{{ request('search') }}">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-xxl-3 col-sm-6">
                                <select class="form-control" data-choices id="filterJob" name="job_id">
                                    <option value="">Semua Posisi</option>
                                    @foreach ($jobs as $job)
                                        <option value="{{ $job->id }}" {{ request('job_id') == $job->id ? 'selected' : '' }}>
                                            {{ $job->judul }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary"><i class="ri-filter-line"></i> Filter</button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Table --}}
                <div class="card-body p-0">
                    @if ($applications->isEmpty())
                        <div class="text-center py-5">
                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" style="width:72px;height:72px"></lord-icon>
                            <h5 class="mt-3">Belum Ada Kandidat</h5>
                            <p class="text-muted">Kandidat yang lolos administrasi akan muncul di halaman ini.</p>
                        </div>
                    @else
                        <form id="resultsForm">
                            @csrf
                            <div class="table-responsive">
                                <table class="table table-bordered table-nowrap table-striped align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width:40px">#</th>
                                            <th>Nama Kandidat</th>
                                            <th>Posisi</th>
                                            <th class="text-center">Tes Tulis<br><small class="fw-normal text-muted">(0-100)</small></th>
                                            <th class="text-center">Tes Praktikum<br><small class="fw-normal text-muted">(0-100)</small></th>
                                            <th class="text-center">Wawancara<br><small class="fw-normal text-muted">(0-100)</small></th>
                                            <th class="text-center">Rata-rata<br><small class="fw-normal text-muted">(auto)</small></th>
                                            <th class="text-center">Status Akhir</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($applications as $i => $app)
                                            @php
                                                $nilaiTes   = $app->nilai_tes ?? null;
                                                $nilaiPrak  = $app->nilai_praktikum ?? null;
                                                $nilaiWaw   = $app->nilai_wawancara ?? null;
                                                $values = array_filter([$nilaiTes, $nilaiPrak, $nilaiWaw]);
                                                $rataRata = count($values) > 0 ? round(array_sum($values) / count($values), 1) : '-';
                                                $statusColor = match($app->status_akhir) {
                                                    'diterima' => 'success',
                                                    'ditolak'  => 'danger',
                                                    'cadangan' => 'warning',
                                                    default    => 'secondary',
                                                };
                                            @endphp
                                            <tr data-app-id="{{ $app->id }}">
                                                <td class="text-center text-muted">{{ $i + 1 }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="{{ $app->recruitmentProfile->user->avatar ? asset('storage/' . $app->recruitmentProfile->user->avatar) : asset('build/images/users/avatar-1.jpg') }}"
                                                            class="rounded-circle avatar-xs" alt="">
                                                        <div>
                                                            <span class="fw-medium">{{ $app->recruitmentProfile->user->name }}</span><br>
                                                            <small class="text-muted">{{ $app->recruitmentProfile->user->email }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-primary-subtle text-primary">{{ $app->recruitmentJob->judul }}</span></td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" max="100"
                                                        class="form-control form-control-sm text-center nilai-input"
                                                        name="results[{{ $i }}][nilai_tes_tulis]"
                                                        value="{{ $nilaiTes }}"
                                                        placeholder="0-100"
                                                        data-index="{{ $i }}"
                                                        oninput="updateAvg({{ $i }})">
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" max="100"
                                                        class="form-control form-control-sm text-center nilai-input"
                                                        name="results[{{ $i }}][nilai_tes_praktikum]"
                                                        value="{{ $nilaiPrak }}"
                                                        placeholder="0-100"
                                                        data-index="{{ $i }}"
                                                        oninput="updateAvg({{ $i }})">
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" max="100"
                                                        class="form-control form-control-sm text-center nilai-input"
                                                        name="results[{{ $i }}][nilai_wawancara]"
                                                        value="{{ $nilaiWaw }}"
                                                        placeholder="0-100"
                                                        data-index="{{ $i }}"
                                                        oninput="updateAvg({{ $i }})">
                                                </td>
                                                <td class="text-center">
                                                    <input type="hidden" name="results[{{ $i }}][application_id]" value="{{ $app->id }}">
                                                    <span class="badge bg-secondary-subtle text-secondary fs-6 fw-bold avg-badge" id="avg-{{ $i }}">{{ $rataRata }}</span>
                                                </td>
                                                <td>
                                                    <select class="form-control form-control-sm status-select"
                                                        name="results[{{ $i }}][status_akhir]"
                                                        style="min-width:130px"
                                                        data-index="{{ $i }}"
                                                        onchange="highlightRow({{ $i }})">
                                                        <option value="menunggu" {{ $app->status_akhir == 'menunggu' || !$app->status_akhir ? 'selected' : '' }}>Menunggu</option>
                                                        <option value="diterima" {{ $app->status_akhir == 'diterima' ? 'selected' : '' }}>Diterima</option>
                                                        <option value="ditolak" {{ $app->status_akhir == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                                                        <option value="cadangan" {{ $app->status_akhir == 'cadangan' ? 'selected' : '' }}>Cadangan</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm"
                                                        name="results[{{ $i }}][catatan]"
                                                        value="{{ $app->catatan_rekruter }}"
                                                        placeholder="Catatan...">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/select2/select2.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        // Auto-calculate average
        function updateAvg(index) {
            const tulis = parseFloat(document.querySelector(`[name="results[${index}][nilai_tes_tulis]"]`).value) || null;
            const prak  = parseFloat(document.querySelector(`[name="results[${index}][nilai_tes_praktikum]"]`).value) || null;
            const wawancara = parseFloat(document.querySelector(`[name="results[${index}][nilai_wawancara]"]`).value) || null;
            const values = [tulis, prak, wawancara].filter(v => v !== null && !isNaN(v));
            const badge  = document.getElementById(`avg-${index}`);
            if (values.length > 0) {
                const avg = (values.reduce((a, b) => a + b, 0) / values.length).toFixed(1);
                badge.textContent = avg;
                badge.className = 'badge bg-info-subtle text-info fs-6 fw-bold';
            } else {
                badge.textContent = '-';
                badge.className = 'badge bg-secondary-subtle text-secondary fs-6 fw-bold';
            }
        }

        // Highlight row based on status
        function highlightRow(index) {
            const select = document.querySelector(`.status-select[data-index="${index}"]`);
            const status = select.value;
            const rowEl  = select.closest('tr');
            rowEl.classList.remove('table-success', 'table-danger', 'table-warning');
            if (status === 'diterima') rowEl.classList.add('table-success');
            else if (status === 'ditolak') rowEl.classList.add('table-danger');
            else if (status === 'cadangan') rowEl.classList.add('table-warning');
        }

        // Apply highlight on page load
        document.querySelectorAll('.status-select').forEach(sel => {
            highlightRow(sel.dataset.index);
        });

        // Save all results
        function saveAll() {
            const form = document.getElementById('resultsForm');
            const formData = new FormData(form);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route('user.ats.interviews.save-all', ['userId' => $userId]) }}', {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: d.message }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: d.message });
                }
            }).catch(err => {
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan: ' + err.message });
            });
        }

        // Announce all
        function announceAll() {
            const form = document.getElementById('resultsForm');
            const formData = new FormData(form);
            formData.append('_token', '{{ csrf_token() }}');

            // First save, then announce
            Swal.fire({
                title: 'Simpan & Kirim Pengumuman?',
                text: 'Nilai akan disimpan dan hasil akhir akan dikirimkan ke semua kandidat yang sudah memiliki status akhir.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Simpan & Kirim',
                cancelButtonText: 'Batal'
            }).then(result => {
                if (!result.isConfirmed) return;

                fetch('{{ route('user.ats.interviews.save-all', ['userId' => $userId]) }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                }).then(r => r.json()).then(saveResult => {
                    if (!saveResult.success) {
                        Swal.fire({ icon: 'error', title: 'Gagal Menyimpan', text: saveResult.message });
                        return;
                    }

                    // Now announce
                    const ids = Array.from(document.querySelectorAll('.status-select'))
                        .filter(s => ['diterima', 'ditolak', 'cadangan'].includes(s.value))
                        .map(s => s.closest('tr').querySelector('[name*="[application_id]"]').value);

                    if (ids.length === 0) {
                        Swal.fire({ icon: 'info', title: 'Tidak Ada Kandidat', text: 'Belum ada kandidat dengan status akhir.' });
                        return;
                    }

                    fetch('{{ route('user.ats.interviews.announce-all', ['userId' => $userId]) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify({ application_ids: ids })
                    }).then(r => r.json()).then(d => {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: d.message });
                    });
                });
            });
        }

        // Auto-submit filter on change
        document.getElementById('filterJob').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    </script>
@endsection