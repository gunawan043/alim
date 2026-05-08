@extends('layouts.master')
@section('title')
    Export Report
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Reports
        @endslot
        @slot('title')
            Export Report
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Export Recruitment Report</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('user.ats.reports.export', ['userId' => $userId, 'type' => 'excel') }}" method="GET" id="exportForm">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">Tipe Report</label>
                                <select class="form-control" data-choices name="type" id="reportType">
                                    <option value="overview">Overview Report</option>
                                    <option value="hiring-funnel">Hiring Funnel</option>
                                    <option value="time-to-hire">Time to Hire</option>
                                    <option value="jobs">Jobs Report</option>
                                    <option value="candidates">Candidates Report</option>
                                    <option value="applications">Applications Report</option>
                                    <option value="interviews">Interviews Report</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Format File</label>
                                <select class="form-control" data-choices name="format" id="format">
                                    <option value="excel">Excel (.xlsx)</option>
                                    <option value="csv">CSV (.csv)</option>
                                    <option value="pdf">PDF (.pdf)</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Periode</label>
                                <select class="form-control" data-choices name="period" id="period">
                                    <option value="week">Minggu Ini</option>
                                    <option value="month" selected>Bulan Ini</option>
                                    <option value="quarter">3 Bulan Terakhir</option>
                                    <option value="year">Tahun Ini</option>
                                    <option value="custom">Kustom</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Job (Opsional)</label>
                                <select class="form-control" data-choices name="job_id">
                                    <option value="">Semua Job</option>
                                    @foreach ($jobs as $job)
                                        <option value="{{ $job->id }}">{{ $job->judul }} ({{ $job->kode_lowongan }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 custom-date-range" style="display: none;">
                                <label class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" name="start_date" id="start_date">
                            </div>

                            <div class="col-md-6 custom-date-range" style="display: none;">
                                <label class="form-label">Tanggal Akhir</label>
                                <input type="date" class="form-control" name="end_date" id="end_date">
                            </div>

                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="ri-information-line"></i>
                                    Report akan menampilkan data sesuai dengan periode dan filter yang dipilih.
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="hstack justify-content-end gap-2">
                                    <a href="{{ route('user.ats.reports.index', ['userId' => $userId]) }}" class="btn btn-ghost-danger">
                                        <i class="ri-close-line align-bottom"></i> Batal
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="ri-file-excel-line align-bottom"></i> Export
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Scheduled Reports</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead>
                                <tr>
                                    <th>Nama Report</th>
                                    <th>Tipe</th>
                                    <th>Format</th>
                                    <th>Frekuensi</th>
                                    <th>Last Sent</th>
                                    <th>Next Send</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($scheduledReports ?? [] as $report)
                                    <tr>
                                        <td>{{ $report->name }}</td>
                                        <td>{{ ucfirst($report->type) }}</td>
                                        <td>{{ strtoupper($report->format) }}</td>
                                        <td>{{ ucfirst($report->frequency) }}</td>
                                        <td>{{ $report->last_sent_at ? $report->last_sent_at->format('d M Y H:i') : '-' }}
                                        </td>
                                        <td>{{ $report->next_send_at->format('d M Y H:i') }}</td>
                                        <td>
                                            @if ($report->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-soft-primary"
                                                onclick="editSchedule('{{ $report->id }}')">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                            <button class="btn btn-sm btn-soft-danger"
                                                onclick="deleteSchedule('{{ $report->id }}')">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <p class="text-muted">Belum ada report terjadwal</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-primary" onclick="showScheduleModal()">
                            <i class="ri-add-line"></i> Schedule New Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Schedule Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('user.ats.reports.schedule', ['userId' => $userId]) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Report</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipe Report</label>
                            <select class="form-control" name="type" required>
                                <option value="overview">Overview</option>
                                <option value="hiring-funnel">Hiring Funnel</option>
                                <option value="time-to-hire">Time to Hire</option>
                                <option value="jobs">Jobs Report</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Format</label>
                            <select class="form-control" name="format" required>
                                <option value="excel">Excel</option>
                                <option value="pdf">PDF</option>
                                <option value="csv">CSV</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Frekuensi</label>
                            <select class="form-control" name="frequency" required>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Penerima</label>
                            <input class="form-control" name="recipients" data-choices
                                data-choices-multiple-remove="true" type="text" value="admin@example.com" multiple />
                            <small class="text-muted">Pisahkan dengan koma untuk multiple email</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/job-list.init.js') }}"></script>
    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        document.getElementById('period').addEventListener('change', function() {
            let customRanges = document.querySelectorAll('.custom-date-range');
            if (this.value == 'custom') {
                customRanges.forEach(el => el.style.display = 'block');
            } else {
                customRanges.forEach(el => el.style.display = 'none');
            }
        });

        function showScheduleModal() {
            $('#scheduleModal').modal('show');
        }

        function editSchedule(id) {
            // Implement edit functionality
        }

        function deleteSchedule(id) {
            Swal.fire({
                title: 'Hapus Schedule?',
                text: "Schedule yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Delete schedule via AJAX
                }
            });
        }
    </script>
@endsection
