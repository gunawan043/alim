<?php

namespace App\Services;

use App\Models\TahfidzCertificate;
use App\Models\TahfidzTasmianParticipant;
use App\Models\User;
use InvalidArgumentException;

class TahfidzTasmiService
{
    /**
     * Nilai akhir tasmi' memakai rata-rata tiga komponen.
     */
    public function calculate(float $tahfizh, float $tajwid, float $fashohah): array
    {
        $tahfizh = max(0, min(100, $tahfizh));
        $tajwid = max(0, min(100, $tajwid));
        $fashohah = max(0, min(100, $fashohah));
        $final = round(($tahfizh + $tajwid + $fashohah) / 3, 2);

        return [
            'tahfizh' => $tahfizh,
            'tajwid' => $tajwid,
            'fashohah' => $fashohah,
            'nilai_akhir' => $final,
            'predikat' => $this->predicate($final),
        ];
    }

    public function predicate(float $score): string
    {
        return match (true) {
            $score >= 90 => 'mumtaz',
            $score >= 80 => 'jayyid_jiddan',
            $score >= 70 => 'jayyid',
            $score >= 60 => 'maqbul',
            default => 'rasib',
        };
    }

    public function predicateArabic(?string $predicate): string
    {
        return match ($predicate) {
            'mumtaz' => 'ممتاز',
            'jayyid_jiddan' => 'جيد جدًا',
            'jayyid' => 'جيد',
            'maqbul' => 'مقبول',
            'rasib' => 'راسب',
            default => '—',
        };
    }

    public function issueCertificate(TahfidzTasmianParticipant $participant, User $user): TahfidzCertificate
    {
        $participant->loadMissing(['session.school', 'session.academicYear', 'student', 'score']);

        $score = $participant->score;
        if (! $score || $score->nilai_akhir === null) {
            throw new InvalidArgumentException('Peserta belum memiliki nilai tasmi’.');
        }

        if ($participant->verification_status !== 'verified') {
            throw new InvalidArgumentException('Nilai peserta harus diverifikasi koordinator sebelum syahadah diterbitkan.');
        }

        $existing = TahfidzCertificate::where('tasmian_participant_id', $participant->id)->first();
        if ($existing) {
            return $existing;
        }

        $juzList = $participant->juz_list;
        $totalJuz = count($juzList);
        if ($totalJuz === 0) {
            $totalJuz = 1;
        }

        $type = $this->certificateType($totalJuz);

        return TahfidzCertificate::create([
            'student_id' => $participant->student_id,
            'school_id' => $participant->session?->school_id,
            'academic_year_id' => $participant->academic_year_id,
            'tasmian_participant_id' => $participant->id,
            'certificate_type' => $type,
            'certificate_number' => $this->certificateNumber($participant),
            'issued_date' => now()->toDateString(),
            'total_juz_completed' => $totalJuz,
            'juz_detail' => $juzList,
            'nilai_akhir' => $score->nilai_akhir,
            'predikat' => $score->predikat,
            'golongan' => $totalJuz.' Juz',
            'nama_lembaga' => $participant->session?->school?->name ?: 'Departemen Tahfidz',
            'kode_lembaga' => $participant->session?->school?->npsn ?: TahfidzAccess::DEPARTMENT_CODE,
            'kode_bulan_tahun_cetak' => now()->format('m-Y'),
            'issued_by' => $user->id,
            'notes' => 'Diterbitkan dari sesi '.($participant->session?->session_name ?? '-'),
        ]);
    }

    public function certificateType(int $totalJuz): string
    {
        return match (true) {
            $totalJuz > 30 => 'khatam',
            $totalJuz >= 30 => '30_juz',
            $totalJuz >= 25 => '25_juz',
            $totalJuz >= 20 => '20_juz',
            $totalJuz >= 15 => '15_juz',
            $totalJuz >= 10 => '10_juz',
            $totalJuz >= 5 => '5_juz',
            default => 'juz_tunggal',
        };
    }

    private function certificateNumber(TahfidzTasmianParticipant $participant): string
    {
        $school = $participant->session?->school;
        $code = $school?->npsn ?: TahfidzAccess::DEPARTMENT_CODE;
        $year = $participant->session?->academicYear?->name ?: now()->format('Y');
        $year = preg_replace('/[^0-9A-Za-z]/', '', (string) $year);
        $sequence = TahfidzCertificate::where('school_id', $participant->session?->school_id)
            ->where('academic_year_id', $participant->academic_year_id)
            ->count() + 1;

        return sprintf('TASMI/%s/%s/%03d', $code, $year, $sequence);
    }
}
