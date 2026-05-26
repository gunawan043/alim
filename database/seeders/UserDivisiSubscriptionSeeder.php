<?php

namespace Database\Seeders;

use App\Models\Divisi;
use App\Models\User;
use App\Models\UserDivisiSubscription;
use Illuminate\Database\Seeder;

class UserDivisiSubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $divisis = Divisi::all();

        // 1. Semua Super Admin → subscribe ke semua divisi
        $superAdmins = User::role('Super Admin')->get();
        foreach ($superAdmins as $user) {
            foreach ($divisis as $divisi) {
                UserDivisiSubscription::firstOrCreate([
                    'user_id'   => $user->id,
                    'divisi_id' => $divisi->id,
                ]);
            }
        }

        // 2. User biasa → subscribe ke divisi sesuai WorkUnit → Divisi
        $users = User::with('gtkWorkUnits.workUnit.divisi')->get();
        $subscribed = 0;

        foreach ($users as $user) {
            foreach ($user->gtkWorkUnits as $gwu) {
                if ($gwu->workUnit && $gwu->workUnit->divisi_id) {
                    $created = UserDivisiSubscription::firstOrCreate([
                        'user_id'   => $user->id,
                        'divisi_id' => $gwu->workUnit->divisi_id,
                    ]);
                    if ($created->wasRecentlyCreated) {
                        $subscribed++;
                    }
                }
            }
        }

        $total = UserDivisiSubscription::count();
        $this->command->info("UserDivisiSubscription seeder done. Total subscriptions: $total "
            . "($subscribed dari WorkUnit, "
            . ($total - $subscribed) . " dari Super Admin)");
    }
}