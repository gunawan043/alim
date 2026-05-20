{{-- Kehadiran: Pergantian Jam GTK --}}
@extends('layouts.metronics.master')

@section('content')
<div class="d-flex flex-column flex-lg-row flex-column-fluid">
    @include('layouts.sidebar.personalia')
    <div class="d-flex flex-column flex-column-fluid pt-10">
        <div class="toolbar px-6 py-2 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fs-4 fw-bold text-dark mb-0">Pergantian Jam Mengajar</h3>
                <span class="text-muted fs-7">Rekap pergantian jam mengajar GTK</span>
            </div>
        </div>
        <div class="px-6 pb-10">
            <div class="card">
                <div class="card-body">
                    <div class="text-center text-muted py-10">
                        <i class="ri-time-line" style="font-size: 3rem;"></i>
                        <p class="mt-3">Halaman dalam pengembangan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection