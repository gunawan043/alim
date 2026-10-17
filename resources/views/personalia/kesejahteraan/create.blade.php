@extends('layouts.master')
@section('title') Tambah Kesejahteraan @endsection

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@include('components.personalia-page-header', [
    'title' => 'Tambah Program Kesejahteraan',
    'description' => 'Daftarkan program kesejahteraan baru untuk GTK',
    'icon' => 'ri-heart-pulse-line',
    'iconColor' => '#10B981',
    'breadcrumbs' => [
        ['label' => 'Personalia', 'url' => route('user.dashboard', $userId)],
        ['label' => 'Kesejahteraan', 'url' => route('user.kesejahteraan.index', $userId)],
        ['label' => 'Tambah'],
    ],
    'tabs' => [
        ['label' => 'Daftar', 'route' => 'user.kesejahteraan.index', 'userId' => $userId],
        ['label' => 'Benefit', 'route' => 'user.kesejahteraan.benefit', 'userId' => $userId],
        ['label' => 'BPJS', 'route' => 'user.kesejahteraan.asuransi', 'userId' => $userId],
        ['label' => 'Laporan', 'route' => 'user.kesejahteraan.laporan', 'userId' => $userId],
    ],
])

<form action="{{ route('user.kesejahteraan.store', $userId) }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            @include('personalia.kesejahteraan._form', ['penerima' => new \App\Models\KesejahteraanPenerima()])
        </div>
        <div class="card-footer bg-light d-flex justify-content-between">
            <a href="{{ route('user.kesejahteraan.index', $userId) }}" class="btn btn-light">Batal</a>
            <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
        </div>
    </div>
</form>
@endsection
