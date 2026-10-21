@extends('layouts.master')
@section('title', 'Ganti Password')
@section('content')
    <div class="container py-5" style="max-width: 560px;">
        <div class="card shadow-sm">
            <div class="card-header bg-warning-subtle border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-lock-password-line me-1"></i>Ganti Password Awal</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning small"><i class="ri-information-line me-1"></i>Password awal wajib diganti sebelum menggunakan aplikasi. Gunakan password baru yang mudah Anda ingat.</div>
                @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
                <form method="POST" action="{{ route('password.force.update') }}">
                    @csrf
                    <div class="mb-3"><label class="form-label">Password Saat Ini</label><input type="password" name="current_password" class="form-control" required autocomplete="current-password"><div class="form-text">Masukkan password awal (NIS) atau password yang sedang aktif.</div></div>
                    <div class="mb-3"><label class="form-label">Password Baru</label><input type="password" name="password" class="form-control" required minlength="6" autocomplete="new-password"></div>
                    <div class="mb-3"><label class="form-label">Konfirmasi Password Baru</label><input type="password" name="password_confirmation" class="form-control" required minlength="6" autocomplete="new-password"></div>
                    <div class="d-flex justify-content-end gap-2"><a href="{{ route('logout') }}" class="btn btn-light" onclick="event.preventDefault();document.getElementById('force-logout').submit();">Keluar</a><button class="btn btn-primary">Simpan Password</button></div>
                </form>
                <form id="force-logout" method="POST" action="{{ route('logout') }}" class="d-none">@csrf</form>
            </div>
        </div>
    </div>
@endsection
