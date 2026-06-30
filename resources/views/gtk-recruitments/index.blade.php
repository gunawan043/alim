@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>GTK Recruitment</h4>
        <a href="{{ route('user.recruitment.create', request('userId')) }}" class="btn btn-primary">Tambah Recruitment</a>
    </div>
    <div class="card">
        <div class="card-body">
            <p>Daftar GTK Recruitment akan ditampilkan di sini.</p>
        </div>
    </div>
</div>
@endsection
