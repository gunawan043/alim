@extends('layouts.master')
@section('title') Preview Kontrak @endsection

@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@section('content')
@include('components.personalia-page-header', [
    'title' => 'Preview Dokumen Kontrak',
    'description' => $kontrak->gtk->nama ?? 'Preview kontrak',
    'icon' => 'ri-file-text-line',
    'iconColor' => '#0EA5E9',
    'breadcrumbs' => [
        ['label' => 'Personalia', 'url' => route('user.dashboard', $userId)],
        ['label' => 'Kontrak', 'url' => route('user.ats.kontrak.index', $userId)],
        ['label' => 'Preview'],
    ],
])

<div class="row">
    <div class="col-lg-10 mx-auto">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Dokumen Kontrak</h5>
                <div>
                    <button onclick="window.print()" class="btn btn-light btn-sm"><i class="ri-printer-line me-1"></i>Print</button>
                    <a href="{{ route('user.ats.kontrak.show', [$userId, $kontrak->id]) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
                </div>
            </div>
            <div class="card-body" style="background:#f8fafc; padding:2rem;">
                <div class="mx-auto bg-white p-5 shadow-sm" style="max-width:800px; min-height:1000px; line-height:1.8;">
                    {!! nl2br(e($isi)) !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
