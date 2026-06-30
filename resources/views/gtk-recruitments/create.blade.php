@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Tambah GTK Recruitment</h4>
        <a href="{{ route('user.recruitment.index', request('userId')) }}" class="btn btn-secondary">Kembali</a>
    </div>
    <div class="card">
        <div class="card-body">
            <p>Form tambah GTK Recruitment akan ditampilkan di sini.</p>
        </div>
    </div>
</div>
@endsection
