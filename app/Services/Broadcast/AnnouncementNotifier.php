<?php

namespace App\Services\Broadcast;

use App\Models\HumasAnnouncement;
use App\Models\User;
use App\Models\WaliSantri;
use App\Services\NotificationBroadcastService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Pengirim pengumuman/broadcast ke wali dan/atau GTK sesuai target:
 * global, sekolah, asrama, atau kelas.
 */
class AnnouncementNotifier
{
    public function notify(HumasAnnouncement $announcement, User $publisher): int
    {
        $channels = $announcement->channels ?: ['inapp'];
        $sendWhatsapp = in_array('wa', $channels, true);
        $sendPush = in_array('push', $channels, true);
        $group = $announcement->recipient_group ?: 'wali';
        $priority = in_array($announcement->severity, ['urgent', 'emergency'], true) ? 'high' : 'medium';

        $service = app(NotificationBroadcastService::class);
        $sent = 0;

        if (in_array($group, ['wali', 'both'], true)) {
            foreach ($this->waliLinks($announcement) as $link) {
                $token = $link->access_token;
                $url = $token
                    ? route('portal.announcements.show', ['token' => $token, 'id' => $announcement->id])
                    : route('user.announcements.show', ['userId' => $link->user_id, 'id' => $announcement->id]);

                $service->send((string) $link->user_id, [
                    'module' => 'system',
                    'type' => in_array($announcement->severity, ['urgent', 'emergency'], true) ? 'danger' : 'info',
                    'priority' => $priority,
                    'title' => $announcement->title,
                    'message' => $announcement->excerpt ?: Str::limit(strip_tags((string) $announcement->content), 180),
                    'action_url' => $url,
                    'reference_type' => HumasAnnouncement::class,
                    'reference_id' => $announcement->id,
                    'action_text' => 'Baca Pengumuman',
                    'send_whatsapp' => $sendWhatsapp,
                    'send_push' => $sendPush,
                    'data' => [
                        'announcement_id' => $announcement->id,
                        'student_id' => $link->student_id,
                        'student_name' => $link->student?->name,
                        'school_id' => $link->school_id,
                        'school_name' => $link->student?->school?->name,
                        'target_type' => $announcement->target_type,
                        'dormitory_id' => $announcement->dormitory_id,
                        'study_group_id' => $announcement->study_group_id,
                        'ack_required' => (bool) $announcement->ack_required,
                        'severity' => $announcement->severity,
                    ],
                ]);
                $sent++;
            }
        }

        if (in_array($group, ['gtk', 'both'], true)) {
            foreach ($this->gtkUsers($announcement) as $recipientId) {
                if ((string) $recipientId === (string) $publisher->id) {
                    continue;
                }

                $service->send((string) $recipientId, [
                    'module' => 'system',
                    'type' => in_array($announcement->severity, ['urgent', 'emergency'], true) ? 'danger' : 'info',
                    'priority' => $priority,
                    'title' => $announcement->title,
                    'message' => $announcement->excerpt ?: Str::limit(strip_tags((string) $announcement->content), 180),
                    'action_url' => route('user.announcements.show', ['userId' => $recipientId, 'id' => $announcement->id]),
                    'reference_type' => HumasAnnouncement::class,
                    'reference_id' => $announcement->id,
                    'action_text' => 'Baca Pengumuman',
                    'send_whatsapp' => $sendWhatsapp,
                    'send_push' => $sendPush,
                    'data' => [
                        'announcement_id' => $announcement->id,
                        'target_type' => $announcement->target_type,
                        'ack_required' => (bool) $announcement->ack_required,
                        'severity' => $announcement->severity,
                    ],
                ]);
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @return Collection<int, WaliSantri>
     */
    private function waliLinks(HumasAnnouncement $announcement)
    {
        return WaliSantri::with(['student:id,name,school_id', 'student.school:id,name'])
            ->where('status', WaliSantri::STATUS_ACTIVE)
            ->whereNotNull('user_id')
            ->when($announcement->target_type === 'school' && $announcement->school_id,
                fn ($query) => $query->where('school_id', $announcement->school_id))
            ->when($announcement->target_type === 'dormitory' && $announcement->dormitory_id,
                fn ($query) => $query->whereHas('student', fn ($student) => $student->whereHas(
                    'activeDormitoryResident',
                    fn ($resident) => $resident->where('dormitory_id', $announcement->dormitory_id)
                )))
            ->when($announcement->target_type === 'class' && $announcement->study_group_id,
                fn ($query) => $query->whereHas('student', fn ($student) => $student->whereHas(
                    'classHistories',
                    fn ($history) => $history->where('study_group_id', $announcement->study_group_id)
                        ->where('is_active', true)
                )))
            ->get();
    }

    /**
     * @return Collection<int, string>
     */
    private function gtkUsers(HumasAnnouncement $announcement)
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('employment')
            ->when($announcement->school_id, fn (Builder $query) => $query->whereHas(
                'employment',
                fn (Builder $employment) => $employment->where('school_id', $announcement->school_id)
            ))
            ->pluck('id')
            ->unique()
            ->values();
    }
}
