@extends('layouts.master')
@section('title') Notifikasi Universal @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('title') Notifikasi Universal @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Notifikasi Universal</h5>
                            <p class="text-muted mb-0">Kirim dan kelola notifikasi sistem.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.sa.notifications.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Kirim Notifikasi
                            </a>
                            @if($notifications->total() > 0)
                                <button class="btn btn-soft-primary" id="markAllReadBtn">
                                    <i class="ri-checkbox-line align-bottom me-1"></i> Tandai Semua Dibaca
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Cari title / message..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="priority" class="form-control">
                                <option value="">Semua Priority</option>
                                <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                                <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="is_read" class="form-control">
                                <option value="">Semua</option>
                                <option value="1" {{ request('is_read') === '1' ? 'selected' : '' }}>Sudah Dibaca</option>
                                <option value="0" {{ request('is_read') === '0' ? 'selected' : '' }}>Belum Dibaca</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.sa.notifications.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Priority</th>
                                    <th>Title</th>
                                    <th>User</th>
                                    <th>Module</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($notifications as $notif)
                                    <tr class="{{ !$notif->is_read ? 'table-active' : '' }}">
                                        <td>
                                            @php
                                                $priorityColors = [
                                                    'low' => 'secondary', 'medium' => 'info',
                                                    'high' => 'warning', 'urgent' => 'danger',
                                                ];
                                            @endphp
                                            <span class="badge bg-{{ $priorityColors[$notif->priority] ?? 'secondary' }}-subtle text-{{ $priorityColors[$notif->priority] ?? 'secondary' }}">
                                                {{ ucfirst($notif->priority) }}
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="{{ !$notif->is_read ? 'fw-bold' : '' }}">{{ $notif->title }}</strong>
                                            <br><small class="text-muted">{{ Str::limit($notif->message, 60) }}</small>
                                        </td>
                                        <td>
                                            @if($notif->user)
                                                <small>{{ $notif->user->name }}</small>
                                            @else
                                                <span class="badge bg-success-subtle text-success">All Users</span>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-light text-dark">{{ $notif->module ?? '-' }}</span></td>
                                        <td>
                                            @if($notif->is_read)
                                                <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-fill me-1"></i>Dibaca</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning"><i class="ri-mail-open-line me-1"></i>Baru</span>
                                            @endif
                                        </td>
                                        <td><small>{{ $notif->created_at->format('d/m/Y H:i') }}</small></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary" data-bs-toggle="dropdown">
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item mark-read"
                                                            data-id="{{ $notif->id }}" {{ $notif->is_read ? 'disabled' : '' }}>
                                                            <i class="ri-checkbox-line text-primary me-2"></i>Mark Read
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger delete-notif"
                                                            data-id="{{ $notif->id }}">
                                                            <i class="ri-delete-bin-line text-danger me-2"></i>Hapus
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Belum ada notifikasi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($notifications->hasPages())
    @include('shared._pagination', ['paginator' => $notifications])
@endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Mark as read
        document.querySelectorAll('.mark-read').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                fetch(`/{{ $userId }}/sa/notifications/${id}/mark-read`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'json' }
                }).then(r => r.json()).then(() => location.reload());
            });
        });

        // Mark all read
        document.getElementById('markAllReadBtn')?.addEventListener('click', function () {
            fetch('{{ route('user.sa.notifications.mark-all-read', ['userId' => $userId]) }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'json' }
            }).then(r => r.json()).then(() => location.reload());
        });

        // Delete
        document.querySelectorAll('.delete-notif').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!confirm('Hapus notifikasi ini?')) return;
                fetch(`/{{ $userId }}/sa/notifications/${this.dataset.id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'json' }
                }).then(r => r.json()).then(data => {
                    if (data.success || data.message) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: data.message, timer: 1500, showConfirmButton: false })
                            .then(() => location.reload());
                    }
                });
            });
        });
    });
    </script>
@endsection
