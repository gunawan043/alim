@extends('layouts.master')

@section('title') Analisis Akademik @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('li_2') Analisis Akademik @endslot
        @slot('title') Analisis Nilai Murni STS/SAS @endslot
    @endcomponent

    @include('user.laporan.partials.academic-tabs')

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <p class="text-muted mb-0 small">
                Perbandingan nilai murni STS dan SAS per santri, kelas, dan mata pelajaran.
                Klik batang grafik untuk memfilter kelas secara otomatis.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.laporan.akademik.export', array_merge(['userId' => $userId], request()->query())) }}"
               class="btn btn-success btn-sm">
                <i class="ri-file-excel-2-line me-1"></i>Export Excel
            </a>
            <a href="{{ route('user.laporan.akademik.pdf', array_merge(['userId' => $userId], request()->query())) }}"
               target="_blank" class="btn btn-danger btn-sm">
                <i class="ri-file-pdf-2-line me-1"></i>PDF
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('user.laporan.akademik', ['userId' => $userId]) }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select">
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ $filters['academic_year_id'] === $year->id ? 'selected' : '' }}>
                                {{ $year->name }}{{ $year->is_active ? ' (aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="ganjil" {{ $filters['semester'] === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                        <option value="genap" {{ $filters['semester'] === 'genap' ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Jenjang</label>
                    <select name="grade_level_id" class="form-select">
                        <option value="">Semua jenjang</option>
                        @foreach($gradeLevels as $grade)
                            <option value="{{ $grade->id }}" {{ $filters['grade_level_id'] === $grade->id ? 'selected' : '' }}>
                                {{ $grade->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Kelas</label>
                    <select name="study_group_id" class="form-select">
                        <option value="">Semua kelas</option>
                        @foreach($studyGroups as $group)
                            <option value="{{ $group->id }}" {{ $filters['study_group_id'] === $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select">
                        <option value="">Semua mapel</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ $filters['subject_id'] === $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Cari Santri</label>
                    <input type="text" name="q" class="form-control" value="{{ $filters['q'] }}" placeholder="Nama santri…">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Pembanding</label>
                    <select name="compare_mode" class="form-select">
                        <option value="previous_period" {{ $filters['compare_mode'] === 'previous_period' ? 'selected' : '' }}>Semester sebelumnya</option>
                        <option value="previous_year" {{ $filters['compare_mode'] === 'previous_year' ? 'selected' : '' }}>Tahun ajaran sebelumnya</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ri-filter-3-line me-1"></i>Terapkan
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('user.laporan.akademik', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-light border small d-flex flex-wrap justify-content-between gap-2">
        <span><i class="ri-calendar-line me-1"></i>Periode: <strong>{{ $analysis['current_label'] }}</strong></span>
        <span><i class="ri-git-compare-line me-1"></i>Pembanding: <strong>{{ $analysis['previous_label'] ?? 'Tidak ada periode pembanding' }}</strong></span>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Rata-rata STS</div>
                <div class="fs-3 fw-bold">{{ $analysis['summary']['avg_sts'] !== null ? number_format($analysis['summary']['avg_sts'], 2) : '—' }}</div>
                <div class="small text-muted">Median {{ $analysis['summary']['median_sts'] !== null ? number_format($analysis['summary']['median_sts'], 2) : '—' }}</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Rata-rata SAS</div>
                <div class="fs-3 fw-bold">{{ $analysis['summary']['avg_sas'] !== null ? number_format($analysis['summary']['avg_sas'], 2) : '—' }}</div>
                <div class="small {{ $analysis['summary']['avg_delta'] !== null && $analysis['summary']['avg_delta'] < 0 ? 'text-danger' : 'text-success' }}">
                    Selisih {{ $analysis['summary']['avg_delta'] !== null ? number_format($analysis['summary']['avg_delta'], 2) : '—' }}
                </div>
            </div></div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Meningkat</div>
                <div class="fs-3 fw-bold text-success">{{ number_format($analysis['summary']['increased']) }}</div>
                <div class="small text-muted">dari {{ number_format($analysis['summary']['total_rows']) }} data</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Menurun</div>
                <div class="fs-3 fw-bold text-danger">{{ number_format($analysis['summary']['decreased']) }}</div>
                <div class="small text-muted">perlu perhatian</div>
            </div></div>
        </div>
        <div class="col-12 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Belum Lengkap</div>
                <div class="fs-3 fw-bold text-warning">{{ number_format($analysis['summary']['missing']) }}</div>
                <div class="small text-muted">STS/SAS kosong</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="ri-bar-chart-grouped-line me-1 text-primary"></i>Rata-rata per Kelas</h5>
                    <span class="small text-muted">Klik batang untuk memfilter kelas</span>
                </div>
                <div class="card-body">
                    <div id="academicClassChart"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-git-compare-line me-1 text-info"></i>Perbandingan Periode</h5></div>
                <div class="card-body">
                    @if($analysis['previous_label'])
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted small">Rata-rata STS</span>
                            <span>
                                <strong>{{ $analysis['summary']['avg_sts'] !== null ? number_format($analysis['summary']['avg_sts'], 2) : '—' }}</strong>
                                <span class="{{ ($analysis['comparison']['delta_avg_sts'] ?? 0) < 0 ? 'text-danger' : 'text-success' }} ms-2">
                                    {{ $analysis['comparison']['delta_avg_sts'] !== null ? (($analysis['comparison']['delta_avg_sts'] > 0 ? '+' : '').number_format($analysis['comparison']['delta_avg_sts'], 2)) : '—' }}
                                </span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted small">Rata-rata SAS</span>
                            <span>
                                <strong>{{ $analysis['summary']['avg_sas'] !== null ? number_format($analysis['summary']['avg_sas'], 2) : '—' }}</strong>
                                <span class="{{ ($analysis['comparison']['delta_avg_sas'] ?? 0) < 0 ? 'text-danger' : 'text-success' }} ms-2">
                                    {{ $analysis['comparison']['delta_avg_sas'] !== null ? (($analysis['comparison']['delta_avg_sas'] > 0 ? '+' : '').number_format($analysis['comparison']['delta_avg_sas'], 2)) : '—' }}
                                </span>
                            </span>
                        </div>
                        <div class="text-muted small mt-2">
                            Sebelumnya: STS {{ $analysis['previous_summary']['avg_sts'] !== null ? number_format($analysis['previous_summary']['avg_sts'], 2) : '—' }}
                            · SAS {{ $analysis['previous_summary']['avg_sas'] !== null ? number_format($analysis['previous_summary']['avg_sas'], 2) : '—' }}
                        </div>
                    @else
                        <div class="text-muted small">Belum ada periode pembanding yang dapat digunakan.</div>
                    @endif
                    <hr>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="text-muted small">Santri</div>
                            <div class="fw-bold">{{ number_format($analysis['summary']['students']) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Kelas</div>
                            <div class="fw-bold">{{ number_format($analysis['summary']['classes']) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Mapel</div>
                            <div class="fw-bold">{{ number_format($analysis['summary']['subjects']) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Di bawah KKTP</div>
                            <div class="fw-bold text-danger">{{ number_format($analysis['summary']['below_kktp']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(! empty($analysis['findings']))
        <div class="card mb-3" id="findings">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-alert-line me-1 text-warning"></i>Temuan Otomatis</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($analysis['findings'] as $finding)
                        <div class="col-lg-4">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="fw-semibold small">{{ $finding['title'] }}</div>
                                    <span class="badge bg-warning-subtle text-warning">{{ $finding['count'] }}</span>
                                </div>
                                <p class="text-muted small mt-1 mb-2">{{ $finding['description'] }}</p>
                                @foreach($finding['rows']->take(4) as $findingRow)
                                    <div class="small text-muted">
                                        {{ $findingRow['student_name'] }} · {{ $findingRow['subject_name'] }} · {{ $findingRow['class_name'] }}
                                    </div>
                                @endforeach
                                @if($finding['rows']->count() > 4)
                                    <div class="small text-muted mt-1">+{{ $finding['rows']->count() - 4 }} lainnya</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-table-line me-1 text-primary"></i>Detail Nilai Murni</h5>
            <span class="small text-muted">{{ number_format($analysis['row_count']) }} baris data</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:45px">No</th>
                            <th>Santri</th>
                            <th>Kelas</th>
                            <th>Mapel</th>
                            <th class="text-center">STS</th>
                            <th class="text-center">SAS</th>
                            <th class="text-center">Rata-rata</th>
                            <th class="text-center">Δ SAS-STS</th>
                            <th class="text-center">Tren</th>
                            <th class="text-center">Pembanding</th>
                            <th class="text-center">KKTP</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $rows->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-medium">{{ $row['student_name'] }}</div>
                                    <div class="small text-muted">{{ $row['nisn'] ?: $row['nis'] ?: '—' }}</div>
                                </td>
                                <td>{{ $row['class_name'] }}</td>
                                <td>
                                    <div>{{ $row['subject_name'] }}</div>
                                    <div class="small text-muted">{{ $row['subject_code'] }}</div>
                                </td>
                                <td class="text-center">{{ $row['sts'] !== null ? number_format($row['sts'], 2) : '—' }}</td>
                                <td class="text-center">{{ $row['sas'] !== null ? number_format($row['sas'], 2) : '—' }}</td>
                                <td class="text-center">{{ $row['average'] !== null ? number_format($row['average'], 2) : '—' }}</td>
                                <td class="text-center {{ $row['delta'] !== null && $row['delta'] < 0 ? 'text-danger' : '' }}">
                                    {{ $row['delta'] !== null ? (($row['delta'] > 0 ? '+' : '').number_format($row['delta'], 2)) : '—' }}
                                </td>
                                <td class="text-center">
                                    @php
                                        $tone = match ($row['trend']) {
                                            'increased' => 'success',
                                            'decreased' => 'danger',
                                            'stable' => 'secondary',
                                            default => 'warning',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">
                                        {{ ['increased' => 'Meningkat', 'decreased' => 'Menurun', 'stable' => 'Tetap', 'missing' => 'Belum lengkap'][$row['trend']] ?? $row['trend'] }}
                                    </span>
                                </td>
                                <td class="text-center small text-muted">
                                    @if($row['previous_avg'] !== null)
                                        {{ number_format($row['previous_avg'], 2) }}
                                        @if($row['period_delta'] !== null)
                                            <span class="{{ $row['period_delta'] < 0 ? 'text-danger' : 'text-success' }}">
                                                ({{ $row['period_delta'] > 0 ? '+' : '' }}{{ number_format($row['period_delta'], 2) }})
                                            </span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-center">{{ $row['kktp'] !== null ? number_format($row['kktp'], 2) : '—' }}</td>
                                <td>
                                    @if($row['below_kktp'])
                                        <span class="badge bg-danger-subtle text-danger">Di bawah KKTP</span>
                                    @elseif($row['missing'])
                                        <span class="badge bg-warning-subtle text-warning">Belum lengkap</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success">Terisi</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-sm btn-light border history-button"
                                            data-url="{{ route('user.laporan.akademik.riwayat', ['userId' => $userId, 'studentId' => $row['student_id'], 'subject_id' => $row['subject_id']]) }}"
                                            title="Riwayat lintas semester">
                                        <i class="ri-history-line"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="13" class="text-center text-muted py-5">Belum ada data nilai pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($rows->hasPages())
            <div class="card-footer">@include('shared._pagination', ['paginator' => $rows])</div>
        @endif
    </div>

    <div class="modal fade" id="studentHistoryModal" tabindex="-1" aria-labelledby="studentHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentHistoryModalLabel"><i class="ri-history-line me-1"></i>Riwayat Nilai Santri</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="studentHistoryBody">
                    <div class="text-center text-muted py-4">Memuat riwayat…</div>
                </div>
                <div class="modal-footer">
                    <span class="small text-muted me-auto">Menampilkan riwayat mapel terpilih lintas semester dan tahun ajaran.</span>
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    (function () {
        const classStats = @json($analysis['class_stats']);
        const el = document.querySelector('#academicClassChart');
        if (!el) return;
        if (!classStats.length) {
            el.innerHTML = '<div class="text-center text-muted py-5">Belum ada data kelas untuk grafik.</div>';
            return;
        }

        new ApexCharts(el, {
            chart: {
                type: 'bar',
                height: 320,
                toolbar: { show: false },
                events: {
                    dataPointSelection: function (event, chartContext, config) {
                        const selected = classStats[config.dataPointIndex];
                        if (!selected || !selected.id) return;
                        const url = new URL(window.location.href);
                        url.searchParams.set('study_group_id', selected.id);
                        window.location.href = url.toString();
                    }
                }
            },
            series: [
                { name: 'STS', data: classStats.map(function (row) { return row.avg_sts; }) },
                { name: 'SAS', data: classStats.map(function (row) { return row.avg_sas; }) }
            ],
            xaxis: { categories: classStats.map(function (row) { return row.name; }) },
            colors: ['#116891', '#ffae01'],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
            dataLabels: { enabled: false },
            legend: { position: 'top' },
            grid: { borderColor: '#e2e8f0' },
            yaxis: { min: 0, max: 100, title: { text: 'Nilai' } }
        }).render();
    })();

    (function () {
        const modalEl = document.querySelector('#studentHistoryModal');
        const body = document.querySelector('#studentHistoryBody');
        if (!modalEl || !body) return;

        const modal = new bootstrap.Modal(modalEl);

        document.querySelectorAll('.history-button').forEach(function (button) {
            button.addEventListener('click', async function () {
                body.innerHTML = '<div class="text-center text-muted py-4">Memuat riwayat…</div>';
                modal.show();

                try {
                    const response = await fetch(button.dataset.url, {
                        headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!response.ok) {
                        throw new Error('Gagal memuat riwayat.');
                    }
                    body.innerHTML = await response.text();
                } catch (error) {
                    body.innerHTML = '<div class="alert alert-danger mb-0">Riwayat tidak dapat dimuat. Silakan coba lagi.</div>';
                }
            });
        });
    })();
</script>
@endsection
