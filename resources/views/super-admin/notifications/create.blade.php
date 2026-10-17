@extends('layouts.master')
@section('title') Kirim Notifikasi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('li_2') Notifikasi Universal @endslot
        @slot('title') Kirim Notifikasi @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Kirim Notifikasi</h5></div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('user.sa.notifications.store', ['userId' => $userId]) }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Kirim Kepada</label>
                            <select name="user_id" class="form-control">
                                <option value="">Semua User</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Kosongkan untuk mengirim ke semua user.</small>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="Contoh: Maintenance Sistem" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-control">
                                    <option value="low" {{ old('priority', 'medium') == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="4" placeholder="Isi notifikasi..." required>{{ old('message') }}</textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Module</label>
                                <input type="text" name="module" class="form-control" value="{{ old('module') }}" placeholder="Contoh: GTK, Master Data">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Type</label>
                                <input type="text" name="type" class="form-control" value="{{ old('type', 'info') }}" placeholder="info, warning, dll">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Action</label>
                                <input type="text" name="action" class="form-control" value="{{ old('action', 'system') }}" placeholder="created, submitted, dll">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Expired At</label>
                                <input type="datetime-local" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Action URL (opsional)</label>
                                <input type="url" name="action_url" class="form-control" value="{{ old('action_url') }}" placeholder="https://...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Action Text</label>
                                <input type="text" name="action_text" class="form-control" value="{{ old('action_text') }}" placeholder="Contoh: Lihat Detail">
                            </div>
                        </div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="send_email" id="send_email" value="1" {{ old('send_email') ? 'checked' : '' }}>
                            <label class="form-check-label" for="send_email">
                                Kirim juga via Email
                            </label>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success"><i class="ri-send-plane-line me-1"></i> Kirim Notifikasi</button>
                            <a href="{{ route('user.sa.notifications.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
