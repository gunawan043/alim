{{-- User Sidebar Profile Component --}}
{{-- Displays: name, role(s), jabatan (from structural assignment or employment), and additional tasks --}}

@php
    $currentUser = auth()->user();
    $viewAsUserId = session('view_as_user_id');
    $viewAsRoleName = session('view_as_role');

    if ($viewAsUserId && \App\Models\User::where('id', $viewAsUserId)->exists()) {
        $currentUser = \App\Models\User::find($viewAsUserId);
    }

    $primaryRole = $currentUser->getRoleNames()->first();
    $allRoles = $currentUser->getRoleNames()->toArray();

    $jabatan = null;
    if (method_exists($currentUser, 'employment') && $currentUser->employment) {
        $jabatan = $currentUser->employment->jabatan ?? null;
    }
    if (empty($jabatan) && method_exists($currentUser, 'activeAssignments')) {
        $assignment = $currentUser->activeAssignments()->first();
        $jabatan = $assignment?->position?->name ?? null;
    }

    $additionalTasks = [];
    if (method_exists($currentUser, 'additionalTasks')) {
        $additionalTasks = $currentUser->additionalTasks()
            ->get();
    }
@endphp

<div class="sidebar-user-profile">
    <div class="sidebar-user-avatar">
        <img src="{{ $currentUser->avatar ? URL::asset('images/' . $currentUser->avatar) : URL::asset('build/images/users/about.jpg') }}"
             alt="{{ $currentUser->name }}"
             class="rounded-circle">
    </div>
    <div class="sidebar-user-info">
        <div class="sidebar-user-name">{{ $currentUser->name }}</div>
        <div class="sidebar-user-role">
            @if($viewAsRoleName && !$viewAsUserId)
                <span class="badge bg-warning-subtle text-warning fs-11 mb-1">{{ $viewAsRoleName }} (preview)</span>
            @elseif($viewAsUserId)
                <span class="badge bg-info-subtle text-info fs-11 mb-1">Login-As</span>
            @endif
            @if($primaryRole)
                <span class="sidebar-role-badge">{{ $primaryRole }}</span>
            @endif
        </div>
        @if($jabatan || count($additionalTasks) > 0)
            <div class="sidebar-user-tasks">
                @if($jabatan)
                    <div class="sidebar-position">
                        <i class="ri-briefcase-line"></i>
                        <span>{{ $jabatan }}</span>
                    </div>
                @endif
                @if(count($additionalTasks) > 0)
                    <ul class="sidebar-task-list">
                        @foreach($additionalTasks->take(3) as $task)
                            <li class="sidebar-task-item">
                                <i class="ri-checkbox-multiple-line"></i>
                                <span>{{ $task->nama_tugas ?? 'Tugas Tambahan' }}</span>
                            </li>
                        @endforeach
                        @if(count($additionalTasks) > 3)
                            <li class="sidebar-task-more">
                                +{{ count($additionalTasks) - 3 }} tugas lainnya
                            </li>
                        @endif
                    </ul>
                @endif
            </div>
        @endif
    </div>
</div>

<style>
.sidebar-user-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 12px;
    margin-bottom: 8px;
    border-bottom: 1px solid rgba(var(--bs-body-color-rgb), 0.1);
}

.sidebar-user-avatar img {
    width: 44px;
    height: 44px;
    object-fit: cover;
    flex-shrink: 0;
}

.sidebar-user-info {
    flex: 1;
    min-width: 0;
}

.sidebar-user-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
}

.sidebar-user-role {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-bottom: 6px;
}

.sidebar-role-badge {
    display: inline-block;
    padding: 2px 8px;
    background: var(--bs-primary-bg-subtle);
    color: var(--bs-primary-text-emphasis);
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 500;
}

.sidebar-user-tasks {
    margin-top: 6px;
}

.sidebar-position {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    color: var(--bs-secondary-color);
    margin-bottom: 4px;
}

.sidebar-position i {
    font-size: 0.85rem;
    opacity: 0.7;
}

.sidebar-task-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.sidebar-task-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    padding: 2px 0;
}

.sidebar-task-item i {
    font-size: 0.7rem;
    opacity: 0.6;
}

.sidebar-task-more {
    font-size: 0.72rem;
    color: var(--bs-muted);
    font-style: italic;
    padding: 2px 0;
}

[data-bs-theme="dark"] .sidebar-user-profile {
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

[data-bs-theme="dark"] .sidebar-user-name {
    color: var(--bs-light);
}

@media (max-width: 767.98px) {
    .sidebar-user-profile {
        padding: 12px 8px;
    }
    .sidebar-user-avatar img {
        width: 36px;
        height: 36px;
    }
    .sidebar-user-name {
        font-size: 0.82rem;
    }
}
</style>
