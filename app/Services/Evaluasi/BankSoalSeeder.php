<?php

namespace App\Services\Evaluasi;

use App\Models\BankSoal;
use App\Models\Subject;
use App\Models\User;

/**
 * Seeder for sample Bank Soal.
 * Idempotent: uses (school_id, subject_id, fase, owner_user_id, nama) as natural key.
 */
class BankSoalSeeder
{
    public function seedSample(?string $subjectId = null): int
    {
        $subjects = $subjectId
            ? Subject::where('id', $subjectId)->get()
            : Subject::all();

        if ($subjects->isEmpty()) {
            return 0;
        }

        $ownerId = User::query()->value('id');

        $count = 0;
        foreach ($subjects as $subject) {
            $bank = [
                'subject_id' => $subject->id,
                'school_id' => $subject->school_id ?? null,
                'fase' => 'E',
                'nama' => 'Bank Soal '.($subject->name ?? 'Umum').' Fase E',
                'deskripsi' => 'Bank soal awal untuk '.($subject->name ?? 'mata pelajaran').' fase E. Diperluas oleh guru mapel.',
                'jenis_soal' => 'campuran',
                'target_tingkat_kesulitan' => 'campuran',
                'is_public' => false,
                'owner_user_id' => $ownerId,
                'allow_cross_teacher_clone' => false,
            ];

            BankSoal::updateOrCreate(
                [
                    'subject_id' => $bank['subject_id'],
                    'fase' => $bank['fase'],
                    'owner_user_id' => $bank['owner_user_id'],
                    'nama' => $bank['nama'],
                ],
                $bank
            );
            $count++;
        }

        return $count;
    }
}
