<?php

namespace Database\Seeders;

use App\Models\AlurTujuanPembelajaran;
use App\Models\AlurTujuanPembelajaranItem;
use App\Models\CapaianPembelajaran;
use App\Models\PerangkatPembelajaran;
use App\Models\Prosem;
use App\Models\ProsemItem;
use App\Models\Prota;
use App\Models\ProtaItem;
use App\Models\TeacherAdminBook;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder Kurikulum untuk GTK: Lalu Sopian Harianto.
 * Membuat rantai: CP → TP → ATP (+item) → Prota (+item) → Prosem (+item) → Perangkat (RPM),
 * untuk setiap konteks buku milik GTK tersebut (mapel × tingkat × TA × semester). Idempoten.
 *
 * Jalankan: php artisan db:seed --class=KurikulumLaluSopianSeeder
 */
class KurikulumLaluSopianSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('name', 'like', '%Sopian%')->first();

        if (! $user) {
            $this->error('GTK "Lalu Sopian Harianto" tidak ditemukan — seeder dilewati.');

            return;
        }

        $books = TeacherAdminBook::with(['subject', 'studyGroup.gradeLevel'])
            ->where('teacher_id', $user->id)
            ->where('is_active', true)
            ->get();

        if ($books->isEmpty()) {
            $this->error("Tidak ada buku aktif untuk {$user->name} — seeder dilewati.");

            return;
        }

        $contexts = $books->groupBy(fn ($b) => implode('|', [
            $b->school_id,
            $b->academic_year_id,
            $b->semester,
            $b->subject_id,
            $b->study_group?->grade_level_id,
        ]));

        $stats = array_fill_keys(['cp', 'tp', 'atp', 'atp_item', 'prota', 'prota_item', 'prosem', 'prosem_item', 'perangkat'], 0);

        foreach ($contexts as $ctxBooks) {
            $b = $ctxBooks->first();
            $grade = $b->studyGroup?->gradeLevel;

            if (! $b->subject || ! $grade) {
                continue;
            }

            $fase = $grade->fase ?: 'D';

            // ── CP (Capaian Pembelajaran) ──
            $cp = CapaianPembelajaran::firstOrCreate(
                [
                    'school_id' => $b->school_id,
                    'subject_id' => $b->subject_id,
                    'fase' => $fase,
                    'elemen' => 'Pembelajaran Utama',
                ],
                [
                    'deskripsi' => "Capaian pembelajaran {$b->subject->name} fase {$fase} untuk {$grade->name}.",
                    'urutan' => 1,
                    'is_active' => true,
                    'created_by' => $user->id,
                ]
            );
            if ($cp->wasRecentlyCreated) {
                $stats['cp']++;
            }

            // ── TP (Tujuan Pembelajaran) ──
            $tpQuery = fn () => TujuanPembelajaran::where('school_id', $b->school_id)
                ->where('subject_id', $b->subject_id)
                ->where('grade_level_id', $grade->id)
                ->where('academic_year_id', $b->academic_year_id)
                ->where('semester', $b->semester)
                ->orderBy('urutan');

            $tpList = $tpQuery()->get();

            for ($i = $tpList->count() + 1; $i <= 6; $i++) {
                TujuanPembelajaran::create([
                    'school_id' => $b->school_id,
                    'subject_id' => $b->subject_id,
                    'grade_level_id' => $grade->id,
                    'academic_year_id' => $b->academic_year_id,
                    'semester' => $b->semester,
                    'fase' => $fase,
                    'kode_tp' => 'TP-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    'deskripsi' => "Peserta didik mampu memahami dan menerapkan materi {$b->subject->name} unit {$i}.",
                    'elemen' => 'Pembelajaran Utama',
                    'alokasi_waktu' => 4,
                    'urutan' => $i,
                    'is_active' => true,
                    'created_by' => $user->id,
                ]);
                $stats['tp']++;
            }

            $tpList = $tpQuery()->get();
            $totalJp = (int) $tpList->sum('alokasi_waktu');

            // ── ATP (Alur Tujuan Pembelajaran) + item ──
            $atp = AlurTujuanPembelajaran::firstOrCreate(
                [
                    'school_id' => $b->school_id,
                    'academic_year_id' => $b->academic_year_id,
                    'semester' => $b->semester,
                    'subject_id' => $b->subject_id,
                    'grade_level_id' => $grade->id,
                    'teacher_id' => $user->id,
                ],
                [
                    'fase' => $fase,
                    'total_jp' => $totalJp,
                    'status' => 'published',
                    'catatan' => 'Dibuat otomatis oleh seeder kurikulum.',
                    'created_by' => $user->id,
                ]
            );
            if ($atp->wasRecentlyCreated) {
                $stats['atp']++;
            }

            $urutan = 0;
            foreach ($tpList as $tp) {
                $urutan++;
                $item = AlurTujuanPembelajaranItem::firstOrCreate(
                    [
                        'alur_tujuan_pembelajaran_id' => $atp->id,
                        'tujuan_pembelajaran_id' => $tp->id,
                    ],
                    [
                        'urutan' => $urutan,
                        'jp_alokasi' => $tp->alokasi_waktu ?: 2,
                    ]
                );
                if ($item->wasRecentlyCreated) {
                    $stats['atp_item']++;
                }
            }
            $atp->update(['total_jp' => $totalJp]);

            // ── Prota + item ──
            $jpPerMinggu = (int) ($b->subject->credit_hours ?: 4);
            $mingguEfektif = 18;

            $prota = Prota::firstOrCreate(
                [
                    'school_id' => $b->school_id,
                    'academic_year_id' => $b->academic_year_id,
                    'semester' => $b->semester,
                    'subject_id' => $b->subject_id,
                    'grade_level_id' => $grade->id,
                    'teacher_id' => $user->id,
                ],
                [
                    'fase' => $fase,
                    'atp_id' => $atp->id,
                    'minggu_efektif' => $mingguEfektif,
                    'jp_per_minggu' => $jpPerMinggu,
                    'jp_efektif' => $mingguEfektif * $jpPerMinggu,
                    'total_jp' => $totalJp,
                    'synced_at' => now(),
                    'status' => 'final',
                    'created_by' => $user->id,
                ]
            );
            if ($prota->wasRecentlyCreated) {
                $stats['prota']++;
            }

            foreach ($tpList as $i => $tp) {
                $pi = ProtaItem::firstOrCreate(
                    [
                        'prota_id' => $prota->id,
                        'tujuan_pembelajaran_id' => $tp->id,
                    ],
                    [
                        'bab' => 'Bab '.($i + 1),
                        'materi' => "Materi {$b->subject->name} — {$tp->kode_tp}",
                        'alokasi_jp' => $tp->alokasi_waktu ?: 4,
                        'urutan' => $i + 1,
                    ]
                );
                if ($pi->wasRecentlyCreated) {
                    $stats['prota_item']++;
                }
            }

            // ── Prosem + item (distribusi mingguan) ──
            $prosem = Prosem::firstOrCreate(
                ['prota_id' => $prota->id],
                [
                    'school_id' => $b->school_id,
                    'academic_year_id' => $b->academic_year_id,
                    'semester' => $b->semester,
                    'subject_id' => $b->subject_id,
                    'grade_level_id' => $grade->id,
                    'teacher_id' => $user->id,
                    'synced_at' => now(),
                    'status' => 'final',
                    'created_by' => $user->id,
                ]
            );
            if ($prosem->wasRecentlyCreated) {
                $stats['prosem']++;
            }

            $protaItems = ProtaItem::where('prota_id', $prota->id)->orderBy('urutan')->get();
            $minggu = 1;
            foreach ($protaItems as $i => $pi) {
                $jp = (int) ($pi->alokasi_jp ?: $jpPerMinggu);
                $span = max(1, (int) ceil($jp / max(1, $jpPerMinggu)));
                $selesai = min($mingguEfektif, $minggu + $span - 1);

                $psi = ProsemItem::firstOrCreate(
                    [
                        'prosem_id' => $prosem->id,
                        'prota_item_id' => $pi->id,
                    ],
                    [
                        'tujuan_pembelajaran_id' => $pi->tujuan_pembelajaran_id,
                        'urutan' => $i + 1,
                        'mulai_minggu_ke' => $minggu,
                        'selesai_minggu_ke' => $selesai,
                        'jp' => $jp,
                        'keterangan' => 'Distribusi otomatis seeder.',
                    ]
                );
                if ($psi->wasRecentlyCreated) {
                    $stats['prosem_item']++;
                }

                $minggu = $selesai + 1;
                if ($minggu > $mingguEfektif) {
                    break;
                }
            }

            // ── Perangkat Pembelajaran (contoh RPM) ──
            $perangkat = PerangkatPembelajaran::firstOrCreate(
                [
                    'school_id' => $b->school_id,
                    'academic_year_id' => $b->academic_year_id,
                    'semester' => $b->semester,
                    'subject_id' => $b->subject_id,
                    'grade_level_id' => $grade->id,
                    'study_group_id' => $b->study_group_id,
                    'teacher_id' => $user->id,
                    'judul' => "RPM {$b->subject->name} — {$b->studyGroup?->name}",
                ],
                [
                    'atp_id' => $atp->id,
                    'status' => 'draft',
                    'catatan' => 'Dibuat otomatis oleh seeder kurikulum.',
                    'created_by' => $user->id,
                ]
            );
            if ($perangkat->wasRecentlyCreated) {
                $stats['perangkat']++;
            }
        }

        if ($this->command) {
            $this->command->info("Seeder kurikulum untuk {$user->name} selesai:");
            foreach ($stats as $key => $count) {
                $this->command->line("  - {$key}: {$count} dibuat (baru)");
            }

            $atpIds = AlurTujuanPembelajaran::where('teacher_id', $user->id)->pluck('id');
            $protaIds = Prota::where('teacher_id', $user->id)->pluck('id');
            $prosemIds = Prosem::where('teacher_id', $user->id)->pluck('id');

            $this->command->line('Total saat ini (termasuk yang sudah ada sebelumnya):');
            $this->command->line('  CP: '.CapaianPembelajaran::where('created_by', $user->id)->count());
            $this->command->line('  TP: '.TujuanPembelajaran::where('created_by', $user->id)->count());
            $this->command->line('  ATP: '.$atpIds->count().' (item: '.AlurTujuanPembelajaranItem::whereIn('alur_tujuan_pembelajaran_id', $atpIds)->count().')');
            $this->command->line('  Prota: '.$protaIds->count().' (item: '.ProtaItem::whereIn('prota_id', $protaIds)->count().')');
            $this->command->line('  Prosem: '.$prosemIds->count().' (item: '.ProsemItem::whereIn('prosem_id', $prosemIds)->count().')');
            $this->command->line('  Perangkat: '.PerangkatPembelajaran::where('teacher_id', $user->id)->count());
        }
    }
}
