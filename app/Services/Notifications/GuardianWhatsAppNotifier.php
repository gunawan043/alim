<?php

namespace App\Services\Notifications;

use App\Models\DormitoryPermit;
use App\Models\DormitoryReward;
use App\Models\Kaldik;
use App\Models\Student;
use App\Models\TeacherAdminBook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Notifikasi WhatsApp untuk wali santri / terpusat ke nomor admin ALIM.
 *
 * Mode penerima (settings `wa_delivery_mode` atau config `whatsapp.delivery_mode`):
 *  - central  : semua pesan ke SATU nomor admin ALIM (menyertakan identitas wali)
 *  - guardian : ke nomor wali (mahrom utama)
 *  - both     : keduanya
 *
 * Template dapat ditimpa via settings `wa_template_{key}`.
 */
class GuardianWhatsAppNotifier
{
    public function __construct(private readonly WhatsAppGatewayService $gateway) {}

    public function notifyPickedUp(DormitoryPermit $permit): bool
    {
        if (! $this->eventEnabled('permit_picked_up')) {
            return false;
        }

        $student = $permit->student;

        if (! $student) {
            return false;
        }

        $penjemput = trim((string) $permit->companion_name);
        $relasi = trim((string) $permit->companion_relation);

        $message = $this->template('picked_up', [
            'santri' => $student->name,
            'waktu' => $permit->pickup_scanned_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'),
            'penjemput' => ($penjemput !== '' ? $penjemput : '-').($relasi !== '' ? " ({$relasi})" : ''),
            'tujuan' => $permit->destination ?: '-',
            'estimasi' => $permit->expected_return_datetime?->format('d/m/Y H:i') ?? '-',
        ]);

        return $this->sendToTargets($student, $message, 'permit_picked_up', $permit->id);
    }

    public function notifyReturned(DormitoryPermit $permit): bool
    {
        if (! $this->eventEnabled('permit_returned')) {
            return false;
        }

        $student = $permit->student;

        if (! $student) {
            return false;
        }

        $message = $this->template('returned', [
            'santri' => $student->name,
            'waktu' => $permit->actual_return_datetime?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'),
        ]);

        return $this->sendToTargets($student, $message, 'permit_returned', $permit->id);
    }

    public function notifyPermitApproved(DormitoryPermit $permit): bool
    {
        if (! $this->eventEnabled('permit_approved') || ! $permit->student) {
            return false;
        }

        $message = $this->template('permit_approved', [
            'santri' => $permit->student->name,
            'jenis' => ucfirst(str_replace('_', ' ', (string) $permit->permit_type)),
            'berangkat' => $permit->departure_datetime?->format('d/m/Y H:i') ?? '-',
            'estimasi' => $permit->expected_return_datetime?->format('d/m/Y H:i') ?? '-',
            'penjemput' => trim((string) ($permit->companion_name ?: '-')),
        ]);

        return $this->sendToTargets($permit->student, $message, 'permit_approved', $permit->id);
    }

    public function notifyPermitRejected(DormitoryPermit $permit, ?string $note = null): bool
    {
        if (! $this->eventEnabled('permit_rejected') || ! $permit->student) {
            return false;
        }

        $message = $this->template('permit_rejected', [
            'santri' => $permit->student->name,
            'jenis' => ucfirst(str_replace('_', ' ', (string) $permit->permit_type)),
            'catatan' => $note ?: '-',
        ]);

        return $this->sendToTargets($permit->student, $message, 'permit_rejected', $permit->id);
    }

    public function notifyViolation(Student $student, string $kasus, ?string $tanggal = null, ?string $catatan = null): bool
    {
        if (! $this->eventEnabled('violation')) {
            return false;
        }

        $message = $this->template('violation', [
            'santri' => $student->name,
            'kasus' => $kasus,
            'tanggal' => $tanggal ?: now()->format('d/m/Y'),
            'catatan' => $catatan ? '• Catatan: '.$catatan."\n" : '',
        ]);

        return $this->sendToTargets($student, $message, 'violation', $student->id);
    }

    public function notifyReward(DormitoryReward $reward): bool
    {
        if (! $this->eventEnabled('achievement') || ! $reward->student) {
            return false;
        }

        $message = $this->template('achievement', [
            'santri' => $reward->student->name,
            'prestasi' => $reward->title ?: 'Penghargaan asrama',
            'catatan' => trim(implode(' • ', array_filter([
                $reward->category_text ? 'Kategori: '.$reward->category_text : null,
                $reward->level_text ? 'Level: '.$reward->level_text : null,
                (int) $reward->points > 0 ? 'Poin: +'.$reward->points : null,
                $reward->description,
            ]))),
        ]);

        return $this->sendToTargets($reward->student, $message, 'achievement', (string) $reward->id);
    }

    public function notifyOverdue(DormitoryPermit $permit): bool
    {
        if (! $this->eventEnabled('overdue') || ! $permit->student) {
            return false;
        }

        $message = $this->template('overdue', [
            'santri' => $permit->student->name,
            'estimasi' => $permit->expected_return_datetime?->format('d/m/Y H:i') ?? '-',
        ]);

        return $this->sendToTargets($permit->student, $message, 'overdue', $permit->id);
    }

    public function notifyVisit(\App\Models\DormitoryVisitLog $visit, string $stage = 'approved'): bool
    {
        if (! $this->eventEnabled('visit') || ! $visit->student) {
            return false;
        }

        $rejected = $stage === 'rejected';
        $message = $this->template($rejected ? 'visit_rejected' : 'visit', [
            'santri' => $visit->student->name,
            'pengunjung' => trim((string) ($visit->visitor_name ?: '-')),
            'jadwal' => $visit->expected_arrival_datetime?->format('d/m/Y H:i') ?? '-',
            'catatan' => $visit->approval_note ?: '-',
        ]);

        return $this->sendToTargets($visit->student, $message, 'visit', (string) $visit->id);
    }

    public function notifyReturnReminder(DormitoryPermit $permit): bool
    {
        if (! $this->eventEnabled('reminder') || ! $permit->student) {
            return false;
        }

        $message = $this->template('permit_reminder', [
            'santri' => $permit->student->name,
            'estimasi' => $permit->expected_return_datetime?->format('d/m/Y H:i') ?? '-',
        ]);

        return $this->sendToTargets($permit->student, $message, 'permit_reminder', (string) $permit->id);
    }

    public function notifySick(Student $student, ?string $note = null, string $source = 'uks'): bool
    {
        if (! $this->eventEnabled('student_sick')) {
            return false;
        }

        $message = $this->template('sick', [
            'santri' => $student->name,
            'catatan_sumber' => $source === 'uks' ? 'UKS (klinik sekolah)' : $source,
            'catatan' => $note ? '• Catatan: '.$note."\n" : '',
        ]);

        return $this->sendToTargets($student, $message, 'student_sick', $student->id);
    }

    /**
     * Rapor/nilai final siap ditinjau (dipicu saat buku nilai final dikunci).
     */
    public function notifyRaporReady(TeacherAdminBook $book): bool
    {
        if (! $this->eventEnabled('rapor')) {
            return false;
        }

        $book->loadMissing(['subject:id,name', 'studyGroup:id,name']);

        $message = $this->template('rapor', [
            'mapel' => $book->subject?->name ?? '-',
            'rombel' => $book->studyGroup?->name ?? '-',
            'judul' => 'Nilai final & rapor siap',
            'waktu' => now()->format('d/m/Y H:i'),
        ]);

        return $this->sendCentralOnly($message, 'rapor_ready', (string) $book->id);
    }

    /**
     * Pemberitahuan libur/jadwal (Kaldik).
     */
    public function notifyHoliday(Kaldik $kaldik): bool
    {
        if (! $this->eventEnabled('holiday')) {
            return false;
        }

        $tanggal = ($kaldik->start_date?->format('d/m/Y') ?? '-')
            .($kaldik->end_date ? ' s.d. '.$kaldik->end_date->format('d/m/Y') : '');

        $message = $this->template('holiday', [
            'judul' => $kaldik->name ?? 'Libur',
            'tanggal' => $tanggal,
            'catatan_sumber' => Kaldik::TYPE_OPTIONS[$kaldik->type] ?? 'Libur',
        ]);

        return $this->sendCentralOnly($message, 'holiday', (string) $kaldik->id);
    }

    // ── Penerima & pengiriman ───────────────────────────────────────

    private function sendToTargets(Student $student, string $message, string $event, string $refId): bool
    {
        $targets = $this->resolveTargets($student);

        if ($targets === []) {
            return false;
        }

        $guardianName = $this->guardianName($student);
        $guardianPhone = $this->guardianPhone($student);
        $ok = false;

        foreach ($targets as $target) {
            $body = $target['central']
                ? 'Untuk wali: '.($guardianName ?: 'Wali '.$student->name)
                    .($guardianPhone ? " ({$guardianPhone})" : '')
                    ." — santri: {$student->name}\n\n".$message
                : $message;

            $ok = $this->send($target['phone'], $body, $event, $refId) || $ok;
        }

        return $ok;
    }

    private function sendCentralOnly(string $message, string $event, string $refId): bool
    {
        $central = $this->gateway->centralNumber();

        if (! $central) {
            return false;
        }

        return $this->send($central, $message, $event, $refId);
    }

    /**
     * @return array<int, array{phone: string, central: bool}>
     */
    private function resolveTargets(Student $student): array
    {
        $mode = $this->gateway->deliveryMode();
        $central = $this->gateway->centralNumber();
        $guardian = $this->guardianPhone($student);

        $targets = [];

        if (in_array($mode, ['central', 'both'], true) && $central) {
            $targets[] = ['phone' => $central, 'central' => true];
        }

        if (in_array($mode, ['guardian', 'both'], true) && $guardian) {
            $targets[] = ['phone' => $guardian, 'central' => false];
        }

        // Fallback: mode central tanpa nomor admin → kirim ke nomor wali.
        if ($targets === [] && $guardian) {
            $targets[] = ['phone' => $guardian, 'central' => false];
        }

        return $targets;
    }

    /**
     * Template pesan: settings `wa_template_{key}` / `wa.template.{key}` menimpa config.
     *
     * @param  array<string, string>  $vars
     */
    private function template(string $key, array $vars): string
    {
        $template = null;

        if (function_exists('setting')) {
            foreach (['wa.template.'.$key, 'wa_template_'.$key] as $settingKey) {
                $value = setting($settingKey, null);

                if (is_string($value) && $value !== '') {
                    $template = $value;
                    break;
                }
            }
        }

        $template = $template ?: (string) config('whatsapp.templates.'.$key, '');

        foreach ($vars as $placeholder => $value) {
            $template = str_replace('{'.$placeholder.'}', (string) $value, $template);
        }

        return $template;
    }


    /**
     * Toggle jenis notifikasi: settings `wa_event_{event}` menimpa config.
     */
    private function eventEnabled(string $event): bool
    {
        // Nama event internal tidak selalu sama dengan kunci setting di UI
        // (mis. permit_picked_up disimpan sebagai wa_event_picked_up).
        $aliases = [
            'permit_picked_up' => 'picked_up',
            'permit_returned' => 'returned',
            'student_sick' => 'sick',
        ];

        if (function_exists('setting')) {
            $keys = ['wa_event_'.$event, 'wa.event.'.$event];
            if (isset($aliases[$event])) {
                $keys[] = 'wa_event_'.$aliases[$event];
                $keys[] = 'wa.event.'.$aliases[$event];
            }

            foreach ($keys as $key) {
                $value = setting($key, null);
                if ($value !== null && $value !== '') {
                    return (bool) ((int) $value);
                }
            }
        }

        return (bool) config('whatsapp.events.'.$event, true);
    }

    private function guardianPhone(Student $student): ?string
    {
        $phone = DB::table('student_mahroms')
            ->where('student_id', $student->id)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->value('phone');

        return $phone ?: ($student->mobile_phone ?: ($student->phone ?: null));
    }

    private function guardianName(Student $student): ?string
    {
        return DB::table('student_mahroms')
            ->where('student_id', $student->id)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->value('name');
    }

    private function send(string $phone, string $message, string $event, string $refId): bool
    {
        try {
            return $this->gateway->send($phone, $message, [
                'event' => $event,
                'reference_id' => $refId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('GuardianWhatsAppNotifier gagal', [
                'event' => $event,
                'reference_id' => $refId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
