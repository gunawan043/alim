<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| ALIM Custom Channels
|--------------------------------------------------------------------------
*/

// Private channel: hanya user yang login bisa subscribe
// Channel name: user.{userId} — e.g. user.abc-123-def
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});

// Presence channel: semua approver bisa lihat siapa yang online
Broadcast::channel('approvals', function ($user) {
    return $user->role()->hasPermission('approvals-channel') || $user->role()->hasPermission('super-admin-only') || $user->role()->hasPermission('kepala-sekolah');
});

// Work unit channel: channel per satuan kerja
Broadcast::channel('workunit.{workUnitId}', function ($user, $workUnitId) {
    if ($user->role()->hasPermission('approvals-channel') || $user->role()->hasPermission('super-admin-only')) {
        return true;
    }
    return $user->workUnits()->where('work_units.id', $workUnitId)->exists();
});

// Waka Teacher Attendance channel: per-school public broadcast
// Teachers can subscribe to see real-time check-in/check-out events for their school
Broadcast::channel('waka-teacher-absensi.{schoolId}', function ($user, $schoolId) {
    if (!$user->id) return false;
    $userSchoolId = (string) $user->school_id;
    $targetSchoolId = (string) $schoolId;
    return $userSchoolId === $targetSchoolId;
});
