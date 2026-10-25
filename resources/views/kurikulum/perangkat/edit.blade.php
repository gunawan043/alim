@extends('layouts.master')
@section('title', 'Edit Perangkat')
@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $sections = \App\Models\PerangkatPembelajaran::SECTIONS_RPM;
        $legacyFilled = $perangkat->legacyFilledSections();
        $atp = $perangkat->atp;
        $tps = $atp?->items?->map(fn ($item) => $item->tujuanPembelajaran)->filter() ?? collect();
        $cps = $tps->map(fn ($tp) => $tp->capaianPembelajaran)->filter()->unique('id');
        $canEdit = true;
        $disabled = '';
        $isAgama = ($formatType ?? 'umum') === 'agama';
        $alokasiValue = old('desain.alokasi_waktu', $perangkat->desainValue('alokasi_waktu') ?? ($perangkat->atp?->total_jp ? $perangkat->atp->total_jp.' JP' : ''));
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.kurikulum.index', ['userId' => $userId]) }}">Kurikulum</a> @endslot
        @slot('li_2') <a href="{{ route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $perangkat->id]) }}">Preview</a> @endslot
        @slot('title') Edit Perangkat @endslot
    @endcomponent

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">Edit {{ $perangkat->judul }}</h4>
            <p class="text-muted mb-0 small">Perubahan disimpan ke data perangkat yang sama dan langsung dipakai di semua format cetak.</p>
        </div>
        <a href="{{ route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $perangkat->id]) }}" class="btn btn-light btn-sm">
            <i class="ri-eye-line me-1"></i>Kembali ke Preview
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="ri-check-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @include('kurikulum.perangkat.partials.edit-form')
@endsection
