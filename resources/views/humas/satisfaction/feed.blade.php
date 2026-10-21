@extends('layouts.master')
@section('title', 'Survei Kepuasan')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('personalia._gtk-ui')
    @component('components.breadcrumb') @slot('li_1') Informasi @endslot @slot('title') Survei Kepuasan Layanan @endslot @endcomponent
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    <div class="row g-3">
        @forelse($surveys as $survey)
            @php $response = $survey->responses->first(); @endphp
            <div class="col-lg-6"><div class="card card-animate h-100"><div class="card-body"><div class="d-flex justify-content-between gap-2 mb-2"><span class="badge bg-info-subtle text-info">{{ $survey->audience === 'all' ? 'Wali & GTK' : ucfirst($survey->audience) }}</span>@if($response)<span class="badge bg-success-subtle text-success">Sudah diisi</span>@endif</div><h5>{{ $survey->title }}</h5><p class="text-muted small">{{ $survey->description ?: 'Mohon berikan penilaian layanan.' }}</p>@if($response)<div class="alert alert-light border mb-0"><i class="ri-star-fill text-warning"></i> Nilai Anda: <strong>{{ $response->rating }}/5</strong>@if($response->feedback)<div class="text-muted small mt-1">{{ $response->feedback }}</div>@endif</div>@else<form method="POST" action="{{ route('user.satisfaction.submit',['userId'=>$userId,'id'=>$survey->id]) }}">@csrf<div class="mb-2"><label class="form-label">Penilaian</label><select name="rating" class="form-select" required><option value="5">5 — Sangat puas</option><option value="4">4 — Puas</option><option value="3">3 — Cukup</option><option value="2">2 — Kurang</option><option value="1">1 — Sangat kurang</option></select></div><div class="mb-2"><textarea name="feedback" rows="2" class="form-control" placeholder="Masukan (opsional)"></textarea></div><button class="btn btn-primary btn-sm">Kirim Penilaian</button></form>@endif</div></div></div>
        @empty<div class="col-12"><div class="card"><div class="card-body text-center py-5 text-muted">Belum ada survei aktif.</div></div></div>@endforelse
    </div>
@endsection
