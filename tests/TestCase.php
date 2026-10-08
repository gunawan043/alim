<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // SQLite menyimpan definisi FK lama gtk_employments → positions meskipun
        // tabel `positions` sudah di-merge/di-drop oleh migrasi. Buat stub agar
        // insert fixture ke gtk_employments tidak gagal "no such table: positions".
        if (DB::connection()->getDriverName() === 'sqlite'
            && Schema::hasTable('gtk_employments')
            && ! Schema::hasTable('positions')) {
            Schema::create('positions', function ($table) {
                $table->uuid('id')->primary();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }
    }
}
