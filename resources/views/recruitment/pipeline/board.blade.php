@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Pipeline: {{ $job->judul }}
                        <small class="text-muted">({{ $job->kode_lowongan }})</small>
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('user.ats.pipeline.statistics', ['userId' => $userId, 'jobId' => $job->id]) }}" 
                           class="btn btn-info btn-sm">
                            <i class="fas fa-chart-bar"></i> Statistics
                        </a>
                        <a href="{{ route('user.ats.jobs.show', ['userId' => $userId, 'job' => $job->id]) }}" 
                           class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Job
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    {{-- Pipeline Kanban Board --}}
                    <div class="pipeline-board" style="display: flex; gap: 20px; overflow-x: auto; padding: 10px 0;">
                        @foreach($boardData as $stageId => $data)
                            <div class="pipeline-column" 
                                 style="min-width: 300px; background: #f4f6f9; border-radius: 8px; padding: 15px;">
                                
                                {{-- Stage Header --}}
                                <div class="stage-header" style="margin-bottom: 15px;">
                                    <h5 style="margin: 0; display: flex; align-items: center; gap: 8px;">
                                        <i class="{{ $data['stage']->icon ?? 'fas fa-circle' }}" 
                                           style="color: {{ $data['stage']->warna }}"></i>
                                        {{ $data['stage']->nama_tahapan }}
                                        <span class="badge badge-secondary ml-auto">
                                            {{ $data['applications']->count() }}
                                        </span>
                                    </h5>
                                    <small class="text-muted">
                                        Target: {{ $data['stage']->durasi_hari }} hari
                                    </small>
                                </div>
                                
                                {{-- Applications --}}
                                <div class="stage-applications" 
                                     style="min-height: 400px; max-height: 600px; overflow-y: auto;">
                                    @foreach($data['applications'] as $application)
                                        <div class="card application-card mb-2" 
                                             data-application-id="{{ $application->id }}"
                                             style="cursor: pointer; border-left: 4px solid {{ $data['stage']->warna }};">
                                            
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between">
                                                    <strong>{{ $application->recruitmentProfile->user->name }}</strong>
                                                    <small class="text-muted">
                                                        #{{ $application->no_lamaran }}
                                                    </small>
                                                </div>
                                                
                                                <div class="mt-2">
                                                    <small class="d-block">
                                                        <i class="fas fa-calendar"></i> 
                                                        {{ $application->tanggal_melamar->format('d M Y') }}
                                                    </small>
                                                    
                                                    @if($application->nilai_akhir)
                                                        <small class="d-block">
                                                            <i class="fas fa-star"></i> 
                                                            Nilai: {{ $application->nilai_akhir }}
                                                        </small>
                                                    @endif
                                                </div>
                                                
                                                {{-- Progress Bar --}}
                                                <div class="progress mt-2" style="height: 5px;">
                                                    @php
                                                        $progress = $application->getPipelineProgress();
                                                    @endphp
                                                    <div class="progress-bar" 
                                                         style="width: {{ $progress }}%; 
                                                                background-color: {{ $data['stage']->warna }};">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            {{-- Action Buttons --}}
                                            <div class="card-footer bg-transparent p-2 text-right">
                                                @if(!$loop->last)
                                                    <button class="btn btn-xs btn-success move-next" 
                                                            onclick="moveToNextStage('{{ $application->id }}')">
                                                        <i class="fas fa-arrow-right"></i> Next
                                                    </button>
                                                @endif
                                                
                                                <button class="btn btn-xs btn-info view-details"
                                                        onclick="viewApplication('{{ $application->id }}')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                    
                                    @if($data['applications']->isEmpty())
                                        <div class="text-center text-muted p-4">
                                            <i class="fas fa-empty fa-2x"></i>
                                            <p class="mt-2">No applications in this stage</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function moveToNextStage(applicationId) {
    if (!confirm('Move this application to next stage?')) return;
    
    $.post('/{{ $userId }}/ats/applications/' + applicationId + '/move-next', {
        _token: '{{ csrf_token() }}'
    }).done(function(response) {
        if (response.success) {
            toastr.success('Application moved successfully');
            location.reload();
        }
    }).fail(function(xhr) {
        toastr.error('Failed to move application');
    });
}

function viewApplication(applicationId) {
    window.location.href = '/{{ $userId }}/ats/applications/' + applicationId;
}

// Drag & Drop functionality
$(function() {
    $(".application-card").draggable({
        helper: "clone",
        revert: "invalid",
        start: function(event, ui) {
            $(this).addClass('dragging');
        },
        stop: function(event, ui) {
            $(this).removeClass('dragging');
        }
    });

    $(".stage-applications").droppable({
        accept: ".application-card",
        drop: function(event, ui) {
            var applicationId = ui.draggable.data('application-id');
            var targetStage = $(this).closest('.pipeline-column');

            // Move application to new stage
            $.post('/{{ $userId }}/ats/applications/' + applicationId + '/move-to-stage', {
                _token: '{{ csrf_token() }}',
                stage_id: targetStage.data('stage-id')
            }).done(function() {
                location.reload();
            });
        }
    });
});
</script>

<style>
.application-card.dragging {
    opacity: 0.5;
    transform: rotate(2deg);
}

.pipeline-column {
    transition: all 0.3s ease;
}

.pipeline-column:hover {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.application-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.stage-applications {
    scrollbar-width: thin;
    scrollbar-color: #c0c0c0 #f4f6f9;
}

.stage-applications::-webkit-scrollbar {
    width: 6px;
}

.stage-applications::-webkit-scrollbar-track {
    background: #f4f6f9;
}

.stage-applications::-webkit-scrollbar-thumb {
    background-color: #c0c0c0;
    border-radius: 3px;
}
</style>
@endpush
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/job-list.init.js') }}"></script>
    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection