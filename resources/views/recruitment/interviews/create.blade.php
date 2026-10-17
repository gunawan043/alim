@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Jadwal Wawancara</h4>
        <a href="{{ route('user.ats.interviews.index', request('userId')) }}" class="btn btn-secondary">Kembali</a>
    </div>
    <div class="card">
        <div class="card-body">
            <p>Form jadwal wawancara akan ditampilkan di sini.</p>
        </div>
    </div>
</div>
@endsection
