@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Edit Kandidat</h4>
        <a href="{{ route('user.ats.candidates.show', [request('userId', ''), $candidate]) }}" class="btn btn-secondary">Kembali</a>
    </div>
    <div class="card">
        <div class="card-body">
            <p>Form edit kandidat akan ditampilkan di sini.</p>
        </div>
    </div>
</div>
@endsection
