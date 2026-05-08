@extends('layouts.master')
@section('title') Edit User @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('li_2') Manajemen User @endslot
        @slot('title') Edit User @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit User</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.sa.users.update', ['userId' => $userId, 'id' => $user->id]) }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-control">
                                    <option value="1" {{ old('is_active', $user->is_active) == '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ old('is_active', $user->is_active) == '0' ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <hr>
                                <p class="text-muted small mb-2">Kosongkan password jika tidak ingin mengubah.</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="password" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Konfirmasi Password</label>
                                <input type="password" name="password_confirmation" class="form-control">
                            </div>

                            <div class="col-12">
                                <hr>
                                <p class="text-muted small mb-2">Pilih role untuk user ini:</p>
                                @foreach($roles as $role)
                                    <div class="form-check form-check-inline mb-2">
                                        <input class="form-check-input" type="checkbox"
                                            name="roles[]" value="{{ $role->id }}"
                                            id="role-{{ $role->id }}"
                                            {{ $user->roles->contains('id', $role->id) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="role-{{ $role->id }}">
                                            {{ $role->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Update</button>
                                <a href="{{ route('user.sa.users.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
