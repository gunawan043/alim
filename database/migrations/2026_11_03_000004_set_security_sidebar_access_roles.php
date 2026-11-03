<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menu `keamanan` dan `keamanan-pelengkap` sudah punya baris di
 * `sidebar_accesses`, tetapi `allowed_roles` masih kosong. Menurut
 * menu_allowed(), array kosong berarti "diizinkan semua role" — sehingga
 * gating yang dipasang di sidebar satpam tidak pernah menolak siapa pun.
 *
 * Migration ini mengisi daftar role yang berhak; Super Admin tetap
 * selalu lolos lewat bypass di menu_allowed().
 */
return new class extends Migration
{
    private array $menus = [
        'keamanan' => ['display_name' => 'Keamanan', 'roles' => ['Satuan Keamanan']],
        'keamanan-pelengkap' => ['display_name' => 'Keamanan (Pelengkap)', 'roles' => ['Satuan Keamanan']],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('sidebar_accesses')) {
            return;
        }

        foreach ($this->menus as $key => $menu) {
            $existing = DB::table('sidebar_accesses')->where('menu_key', $key)->first();

            if ($existing) {
                DB::table('sidebar_accesses')
                    ->where('id', $existing->id)
                    ->update([
                        'allowed_roles' => json_encode($menu['roles']),
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('sidebar_accesses')->insert([
                'menu_key' => $key,
                'display_name' => $menu['display_name'],
                'allowed_roles' => json_encode($menu['roles']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('sidebar_accesses')) {
            return;
        }

        DB::table('sidebar_accesses')
            ->whereIn('menu_key', array_keys($this->menus))
            ->update(['allowed_roles' => json_encode([]), 'updated_at' => now()]);
    }
};
