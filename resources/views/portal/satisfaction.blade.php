@extends('layouts.master')
@section('title', 'Survei Kepuasan')
@section('content')
    @component('components.breadcrumb') @slot('title') Survei Kepuasan @endslot <a href="{{ route('portal.dashboard',['token'=>$token]) }}" class="btn btn-sm btn-outline-secondary ms-3"><i class="ri-arrow-left-line me-1"></i>Portal</a> @endcomponent
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="row g-3">
        @forelse($surveys as $survey)
            @php $response = $survey->responses->first(); @endphp
            <div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-body"><h5>{{ $survey->title }}</h5><p class="text-muted small">{{ $survey->description }}</p>@if($response)<div class="alert alert-light border mb-0"><i class="ri-star-fill text-warning"></i> Nilai Anda: <strong>{{ $response->rating }}/5</strong></div>@else<form method="POST" action="{{ route('portal.satisfaction.submit',['token'=>$token,'id'=>$survey->id]) }}">@csrf<div class="mb-2"><select name="rating" class="form-select" required><option value="5">5 — Sangat puas</option><option value="4">4 — Puas</option><option value="3">3 — Cukup</option><option value="2">2 — Kurang</option><option value="1">1 — Sangat kurang</option></select></div><div class="mb-2"><textarea name="feedback" rows="2" class="form-control" placeholder="Masukan (opsional)"></textarea></div><button class="btn btn-primary btn-sm">Kirim</button></form>@endif</div></div></div>
        @empty<div class="col-12"><div class="card"><div class="card-body text-center py-5 text-muted">Belum ada survei aktif.</div></div></div>@endforelse
    </div>
@endsection
