<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel registry jenis izin (permit types).
     *
     * Memisahkan konfigurasi jenis izin dari tabel dormitory_leave_policies
     * agar admin pondok / kepala asrama bisa menambah, mengubah,
     * mengaktifkan, dan menonaktifkan jenis izin secara dinamis.
     *
     * Pendekatan:
     *   - permit_types (tabel baru) menyimpan definisi master: code, label,
     *     deskripsi, kategori, status aktif, dan urutan tampil.
     *   - dormitory_leave_policies tetap menyimpan konfigurasi per-asrama
     *     (quota, auto-approve, dll) tetapi nilai permit_type sekarang adalah
     *     string code yang cocok dengan permit_types.code.
     *
     * Catatan penting:
     *   - Tabel dormitory_leave_policies.permit_type TIDAK dijadikan foreign
     *     key constraint agar data lama (yang permit_type-nya mungkin sudah
     *     dihapus / tidak dikenal lagi) tetap aman terbaca.
     *   - Orphan detection ditangani di level aplikasi, bukan DB constraint,
     *     supaya menghapus jenis izin tidak merusak histori perizinan.
     */
    public function up(): void
    {
        Schema::create('permit_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('label', 100);
            $table->text('description')->nullable();
            $table->enum('category', ['default', 'special', 'emergency', 'custom'])
                ->default('custom')
                ->comment('default=semua asrama, special=izin khusus, emergency=darurat, custom=tambahan kustom');
            $table->string('icon', 50)->nullable()
                ->comment('Nama ikon Remix Icon, mis: ri-home-4-line');
            $table->string('color', 30)->nullable()
                ->comment('Warna badge, mis: primary, success, danger');
            $table->boolean('is_active')->default(true)
                ->comment('Apakah jenis izin ini aktif dan ditampilkan di pilihan');
            $table->unsignedInteger('sort_order')->default(0);
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
            $table->index('sort_order');
        });

        // Backfill master data jenis izin bawaan sistem (idempotent):
        // 7 jenis izin standar agar sistem langsung punya baseline
        // yang bisa diaktifkan/dinonaktifkan oleh admin.
        $now = now();
        $defaults = [
            ['code' => 'pulang',              'label' => 'Izin Pulang',                  'category' => 'default',   'icon' => 'ri-home-4-line',     'color' => 'primary', 'sort_order' => 10],
            ['code' => 'keluar_kota',         'label' => 'Izin Keluar Kota',             'category' => 'special',   'icon' => 'ri-roadster-line',   'color' => 'info',    'sort_order' => 20],
            ['code' => 'berobat',             'label' => 'Izin Berobat',                 'category' => 'special',   'icon' => 'ri-stethoscope-line', 'color' => 'success', 'sort_order' => 30],
            ['code' => 'sakit',               'label' => 'Izin Sakit',                   'category' => 'special',   'icon' => 'ri-hospital-line',   'color' => 'danger',  'sort_order' => 40],
            ['code' => 'keperluan_keluarga',  'label' => 'Izin Keperluan Keluarga',      'category' => 'special',   'icon' => 'ri-family-line',     'color' => 'warning', 'sort_order' => 50],
            ['code' => 'darurat',             'label' => 'Izin Darurat',                 'category' => 'emergency', 'icon' => 'ri-alarm-warning-line', 'color' => 'danger', 'sort_order' => 60],
            ['code' => 'lainnya',             'label' => 'Lainnya',                      'category' => 'special',   'icon' => 'ri-more-line',       'color' => 'secondary', 'sort_order' => 70],
        ];

        $rows = array_map(fn ($d) => array_merge($d, [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'description' => null,
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $defaults);

        DB::table('permit_types')->insertOrIgnore($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('permit_types');
    }
};
