@extends('layouts.master')
@section('title') Alur Kerja Unit Rumah Tangga @endsection

@section('css')
<style>
    .flow-hero { background: linear-gradient(135deg, #1f3b73 0%, #2f6fb3 100%); color: #fff; border-radius: 14px; }
    .flow-hero .stat { background: rgba(255,255,255,.12); border-radius: 10px; padding: .75rem 1rem; }
    .flow-hero .stat .value { font-size: 1.35rem; font-weight: 700; line-height: 1.1; }
    .flow-hero .stat .label { font-size: .78rem; opacity: .85; }
    .flow-nav .btn { border-radius: 999px; }
    .flow-step { position: relative; padding-left: 2.6rem; padding-bottom: 1.15rem; }
    .flow-step:last-child { padding-bottom: 0; }
    .flow-step::before { content: ''; position: absolute; left: .95rem; top: 1.9rem; bottom: 0; width: 2px; background: #e3e8ef; }
    .flow-step:last-child::before { display: none; }
    .flow-step .dot { position: absolute; left: 0; top: .15rem; width: 2rem; height: 2rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .85rem; font-weight: 700; background: #eef2f7; color: #33475b; }
    .flow-step.is-done .dot { background: #d1e7dd; color: #0f5132; }
    .flow-card .card-header { cursor: pointer; }
    .flow-card .card-header .proc-no { width: 2.4rem; height: 2.4rem; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; }
    .chain-step { border: 1px dashed #cfd8e3; border-radius: 10px; padding: .75rem; height: 100%; }
    .chain-arrow { color: #98a6b8; font-size: 1.2rem; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Alur Kerja URT @endslot
@endcomponent

@php
    $link = function (string $name, array $params = []) {
        try { return route($name, $params); } catch (Throwable $e) { return '#'; }
    };
    $stats = $chain['stats'];
@endphp

<div class="flow-hero p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h4 class="mb-1 text-white">Peta Alur Kerja Unit Rumah Tangga</h4>
            <p class="mb-0 opacity-75">Sembilan prosedur dari satuan kerja sampai eksekusi di URT. Setiap tahap terhubung ke modulnya.</p>
        </div>
        <a href="{{ $link('sarpras.workflow.assignment') }}" class="btn btn-light btn-sm">
            <i class="ri-flow-chart me-1"></i>Buka Penugasan &amp; Area Kerja
        </a>
    </div>
    <div class="row g-2 mt-3">
        <div class="col-6 col-lg-3"><div class="stat"><div class="value">{{ $stats['units_covered'] }}/{{ $stats['units'] }}</div><div class="label">Satuan kerja punya petugas</div></div></div>
        <div class="col-6 col-lg-3"><div class="stat"><div class="value">{{ $stats['officers'] }}</div><div class="label">Petugas Sarpras aktif</div></div></div>
        <div class="col-6 col-lg-3"><div class="stat"><div class="value">{{ $stats['areas_covered'] }}/{{ $stats['areas'] }}</div><div class="label">Area kerja sudah berpetsugas</div></div></div>
        <div class="col-6 col-lg-3"><div class="stat"><div class="value">{{ $stats['gaps'] }}</div><div class="label">Titik alur perlu dilengkapi</div></div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-links-line me-2 text-primary"></i>Rantai Penugasan</h5>
                    <p class="text-muted small mb-0">Semua prosedur bertumpu pada rantai ini. Jika satu tautan kosong, pekerjaan tidak akan sampai ke petugas.</p>
                </div>
                <a href="{{ $link('sarpras.workflow.assignment') }}" class="btn btn-sm btn-outline-primary">Periksa Kesiapan</a>
            </div>
            <div class="card-body">
                <div class="row g-2 align-items-stretch">
                    @foreach([
                        ['1', 'Satuan Kerja', 'Unit pemilik aset & ruang', $stats['units'], 'ri-community-line', 'sarpras.petugas.index', 'Tunjuk Petugas'],
                        ['2', 'Petugas', 'GTK yang ditunjuk unit/URT', $stats['officers'], 'ri-user-star-line', 'sarpras.petugas.index', 'Kelola Petugas'],
                        ['3', 'Area Kerja', 'Gedung/ruang/taman yang dibagi', $stats['areas'], 'ri-map-pin-line', 'sarpras.kebersihan.areas', 'Bagi Area'],
                        ['4', 'Uraian Pekerjaan', 'Standar, metode, frekuensi', $stats['tasks'], 'ri-list-check-2', 'sarpras.kebersihan.areas', 'Susun Uraian'],
                        ['5', 'Eksekusi & Verifikasi', 'Checklist, WO, laporan', $stats['areas_covered'], 'ri-check-double-line', 'sarpras.kebersihan.report.create', 'Isi Checklist'],
                    ] as [$no, $title, $desc, $value, $icon, $route, $cta])
                        <div class="col-12 col-md-6 col-xl">
                            <div class="chain-step d-flex flex-column h-100">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-primary-subtle text-primary">{{ $no }}</span>
                                    <i class="{{ $icon }} text-primary"></i>
                                    <span class="fw-semibold">{{ $title }}</span>
                                </div>
                                <div class="text-muted small flex-grow-1">{{ $desc }}</div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="fw-bold">{{ number_format($value) }}</span>
                                    <a href="{{ $link($route) }}" class="btn btn-sm btn-light border">{{ $cta }}</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="flow-nav d-flex flex-wrap gap-2 mb-3">
    @foreach($procedures as $proc)
        <a href="#prosedur-{{ $proc['no'] }}" class="btn btn-sm btn-outline-secondary">
            <span class="badge bg-{{ $proc['tone'] }}-subtle text-{{ $proc['tone'] }} me-1">{{ $proc['no'] }}</span>{{ Str::limit($proc['title'], 34) }}
        </a>
    @endforeach
</div>

<div class="accordion" id="flowAccordion">
    @foreach($procedures as $proc)
    <div class="accordion-item flow-card mb-3 border rounded" id="prosedur-{{ $proc['no'] }}">
        <h2 class="accordion-header">
            <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $proc['no'] }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                <span class="proc-no bg-{{ $proc['tone'] }}-subtle text-{{ $proc['tone'] }} me-3">{{ $proc['no'] }}</span>
                <span class="flex-grow-1">
                    <span class="d-block fw-semibold"><i class="{{ $proc['icon'] }} me-1"></i>{{ $proc['title'] }}</span>
                    <span class="d-block small text-muted fw-normal">{{ $proc['summary'] }}</span>
                </span>
                <span class="badge bg-light text-dark border ms-2 d-none d-md-inline">{{ $proc['owner'] }}</span>
            </button>
        </h2>
        <div id="collapse-{{ $proc['no'] }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#flowAccordion">
            <div class="accordion-body">
                @foreach($proc['steps'] as $i => $step)
                    @php $badgeCount = isset($step['badge']) ? ($counts[$step['badge']] ?? 0) : null; @endphp
                    <div class="flow-step {{ $badgeCount === 0 ? 'is-done' : '' }}">
                        <span class="dot">{{ $i + 1 }}</span>
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="fw-semibold">{{ $step['label'] }}</span>
                                    <span class="badge bg-light text-dark border">{{ $step['actor'] }}</span>
                                    @if($badgeCount !== null)
                                        @if($badgeCount > 0)
                                            <span class="badge bg-warning-subtle text-warning">{{ number_format($badgeCount) }} menunggu</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success">Tidak ada antrean</span>
                                        @endif
                                    @endif
                                </div>
                                <div class="text-muted small mt-1">{{ $step['desc'] }}</div>
                            </div>
                            <a href="{{ $link($step['route'], $step['params'] ?? []) }}" class="btn btn-sm btn-{{ $proc['tone'] }}">
                                {{ $step['cta'] }} <i class="ri-arrow-right-line ms-1"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="alert alert-light border d-flex gap-2 align-items-start">
    <i class="ri-information-line fs-5 text-primary"></i>
    <div class="small">
        Alur ini memakai modul yang sudah aktif. Bila sebuah tombol menuju halaman kosong, artinya tahap sebelumnya belum diisi —
        mulai dari <a href="{{ $link('sarpras.workflow.assignment') }}">Penugasan &amp; Area Kerja</a>.
    </div>
</div>
@endsection
