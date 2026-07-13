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
    // User bisa subscribe kalau punya akses ke satuan kerja tsb
    // Bisa dicek via relasi user -> work_units
    if ($user->role()->hasPermission('approvals-channel') || $user->role()->hasPermission('super-admin-only')) {
        return true;
    }

    return $user->workUnits()->where('work_units.id', $workUnitId)->exists();
});
