@extends('layouts.master')
@section('title')
    Interview Schedule
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Recruitment
        @endslot
        @slot('title')
            Jadwal Interview
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Daftar Interview</h5>
                        <div class="flex-shrink-0">
                            <button class="btn btn-primary" onclick="showScheduleModal()">
                                <i class="ri-add-line"></i> Buat Jadwal
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Calendar View -->
                    <div id="calendar"></div>

                    <!-- List View -->
                    <div class="table-responsive mt-4">
                        <table class="table table-nowrap align-middle">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Kandidat</th>
                                    <th>Posisi</th>
                                    <th>Tahapan</th>
                                    <th>Penilai</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($interviews as $interview)
                                    <tr>
                                        <td>{{ $interview->jadwal_mulai->format('d M Y H:i') }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="{{ $interview->application->recruitmentProfile->user->avatar ? URL::asset('images/' . $interview->application->recruitmentProfile->user->avatar) : URL::asset('build/images/users/avatar-1.jpg') }}"
                                                    class="avatar-xs rounded-circle me-2">
                                                {{ $interview->application->recruitmentProfile->user->name }}
                                            </div>
                                        </td>
                                        <td>{{ $interview->application->recruitmentJob->judul }}</td>
                                        <td>{{ $interview->recruitmentPipelineStage->nama_tahapan }}</td>
                                        <td>{{ $interview->penilai->name ?? '-' }}</td>
                                        <td>
                                            @if ($interview->status == 'selesai')
                                                <span class="badge bg-success">Selesai</span>
                                            @elseif($interview->status == 'sedang_berlangsung')
                                                <span class="badge bg-warning">Berlangsung</span>
                                            @else
                                                <span class="badge bg-info">Menunggu</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-soft-primary"
                                                onclick="editInterview('{{ $interview->id }}')">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                            <button class="btn btn-sm btn-soft-success"
                                                onclick="markComplete('{{ $interview->id }}')">
                                                <i class="ri-check-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Modal (reuse from previous) -->
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/job-list.init.js') }}"></script>
    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script src="{{ URL::asset('build/libs/fullcalendar/index.global.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: '/{{ $userId }}/ats/interviews/calendar-events',
                eventClick: function(info) {
                    alert('Interview: ' + info.event.title);
                }
            });
            calendar.render();
        });

        function showScheduleModal(applicationId) {
            $('#scheduleModal').modal('show');
        }

        function editInterview(id) {
            window.location.href = '/{{ $userId }}/ats/interviews/' + id + '/edit';
        }

        function markComplete(id) {
            if (!confirm('Mark this interview as complete?')) return;
            $.post('/{{ $userId }}/ats/interviews/' + id + '/complete', {
                _token: '{{ csrf_token() }}'
            }).done(function() {
                location.reload();
            });
        }
    </script>
@endsection
