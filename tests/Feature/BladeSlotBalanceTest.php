<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Setiap `@slot(...)` tanpa konten inline wajib ditutup `@endslot`.
 *
 * Slot yang tidak ditutup membuat output buffer Blade tertinggal terbuka: isi
 * sesudahnya ikut tertelan ke slot default dan judul halaman hilang. Kasus
 * nyata: 68 halaman menulis `@slot('title') Judul` lalu langsung
 * `@endcomponent`, sehingga judul breadcrumb tampil kosong.
 */
class BladeSlotBalanceTest extends TestCase
{
    public function test_semua_slot_blade_ditutup_dengan_endslot(): void
    {
        $mismatches = [];

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($files as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            preg_match_all('/@slot\(([^)]*)\)/', $content, $calls);

            $needsEnd = 0;

            foreach ($calls[1] as $args) {
                // Slot dengan argumen kedua sudah berisi konten inline.
                if (! str_contains($args, ',')) {
                    $needsEnd++;
                }
            }

            $ends = preg_match_all('/@endslot\b/', $content);

            if ($needsEnd !== $ends) {
                $mismatches[] = str_replace(resource_path('views').'/', '', $file->getPathname())
                    ." (@slot butuh @endslot: {$needsEnd}, @endslot: {$ends})";
            }
        }

        $this->assertSame([], $mismatches, "Slot Blade tidak seimbang:\n".implode("\n", $mismatches));
    }
}
