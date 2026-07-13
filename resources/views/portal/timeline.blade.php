@extends('layouts.master')

@section('title', 'Timeline Wali Santri')

@section('breadcrumb')
    @component('components.breadcrumb')
        @slot('title') Timeline Wali Santri @endslot
        <a href="{{ route('portal.notifications', ['token' => $token]) }}" class="btn btn-sm btn-outline-primary">
            <i class="ri-notification-3-line me-1"></i> Notifikasi
        </a>
    @endcomponent
@endsection

@section('content')
<div class="page-body">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="ri-history-line me-1"></i> Kronologi Aktivitas</h5>

                    @if($waliStudents->isNotEmpty())
                        <div class="mt-2">
                            <form method="GET" action="{{ route('portal.timeline', ['token' => $token]) }}" class="d-flex gap-2">
                                <select name="student_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">-- Pilih Anak --</option>
                                    @foreach($waliStudents as $ws)
                                        <option value="{{ $ws->student_id }}" {{ request('student_id') == $ws->student_id ? 'selected' : '' }}>
                                            {{ $ws->student->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    @endif
                </div>

                <div class="card-body">
                    @if($selectedStudent)
                        <div class="mb-3">
                            <strong>{{ $selectedStudent->name }}</strong>
                            @if($selectedStudent->dormitory)
                                — {{ $selectedStudent->dormitory->name }}
                            @endif
                        </div>

                        @if($timeline->isEmpty())
                            <div class="text-center py-5">
                                <i class="ri-time-line display-6 text-muted"></i>
                                <p class="text-muted mt-2">Belum ada riwayat aktivitas.</p>
                            </div>
                        @else
                            <div class="position-relative">
                                <div class="border-start border-2 border-secondary position-absolute" style="left: 1.5rem; top: 0; bottom: 0;"></div>

                                @foreach($timeline as $idx => $item)
                                    @php
                                        $iconMap = [
                                            'leave' => ['icon' => 'ri-suitcase-line', 'badge' => 'bg-primary', 'label' => 'Izin Pulang'],
                                            'visit' => ['icon' => 'ri-user-heart-line', 'badge' => 'bg-info', 'label' => 'Penjengukan'],
                                            'health'=> ['icon' => 'ri-heart-pulse-line', 'badge' => 'bg-warning', 'label' => 'Izin Sakit'],
                                        ];
                                        $icons = $iconMap[$item['kind']] ?? ['icon' => 'ri-information-line', 'badge' => 'bg-secondary', 'label' => 'Aktivitas'];
                                    @endphp
                                    <div class="position-relative mb-4 d-flex" style="padding-left: 3.5rem;">
                                        <span class="badge {{ $icons['badge'] }} p-2 rounded-circle" style="width:2.5rem;height:2.5rem;display:flex;align-items:center;justify-content:center;z-index:1;margin-left:-1.25rem;">
                                            <i class="{{ $icons['icon'] }} text-white"></i>
                                        </span>
                                        <div class="card flex-fill w-100">
                                            <div class="card-body py-3">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1 fw-bold">{{ $item['title'] }}</h6>
                                                        <p class="mb-1 small text-muted">{{ $item['subtitle'] }}</p>
                                                    </div>
                                                    <span class="badge {{ $item['status'] === 'approved' ? 'bg-success' : ($item['status'] === 'rejected' ? 'bg-danger' : 'bg-info') }}">
                                                        {{ ucfirst($item['status']) }}
                                                    </span>
                                                </div>
                                                @if($item['note'])
                                                    <div class="mt-2 small text-muted border-top pt-1">
                                                        <i class="ri-file-text-line me-1"></i> {{ $item['note'] }}
                                                    </div>
                                                @endif
                                                <div class="mt-1 small text-muted">
                                                    {{ $item['date'] ? $item['date']->format('d M Y H:i') : '-' }}
                                                    &middot; {{ $icons['label'] }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="ri-group-line display-6 text-muted"></i>
                            <p class="text-muted mt-2">Pilih anak untuk melihat timeline aktivitas.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection