@extends('layouts.master')

@section('title') Dashboard GTK @endsection

@section('content')
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Dashboard GTK</h2>
        <span class="badge bg-primary">Selamat Datang, {{ $user->name ?? 'Pengguna' }}</span>
    </div>

    <!-- Main Content - Simple Cards -->
    <div class="row">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total GTK</h5>
                    <p class="card-text display-6">15</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Guru</h5>
                    <p class="card-text display-6">10</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Tendik</h5>
                    <p class="card-text display-6">5</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mt-3">
        <div class="card-body">
            <h5 class="card-title">Menu Cepat</h5>
            <div class="row g-2">
                <div class="col-md-4">
                    <a href="#" class="btn btn-outline-secondary d-block">Data Guru</a>
                </div>
                <div class="col-md-4">
                    <a href="#" class="btn btn-outline-secondary d-block">Data Tendik</a>
                </div>
                <div class="col-md-4">
                    <a href="#" class="btn btn-outline-secondary d-block">Profil Saya</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer note -->
    <div class="text-muted small mt-4">
        Dashboard sederhana - dikembangkan lebih lanjut sesuai kebutuhan.
    </div>
</div>
@endsection