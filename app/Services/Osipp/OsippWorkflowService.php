<?php

namespace App\Services\Osipp;

use App\Models\Osipp\OsippActivity;
use App\Models\Osipp\OsippDocument;
use App\Models\User;
use App\Services\NotificationUniversalService;
use Illuminate\Validation\ValidationException;

class OsippWorkflowService
{
    public function __construct(
        private readonly OsippAccess $access,
        private readonly NotificationUniversalService $notifications,
    ) {}

    /**
     * @throws ValidationException
     */
    public function transitionActivity(OsippActivity $activity, string $status, User $actor, ?string $notes = null): OsippActivity
    {
        $allowed = [
            OsippActivity::STATUS_DRAFT => [OsippActivity::STATUS_SUBMITTED],
            OsippActivity::STATUS_REVISION => [OsippActivity::STATUS_SUBMITTED],
            OsippActivity::STATUS_SUBMITTED => [
                OsippActivity::STATUS_VERIFIED,
                OsippActivity::STATUS_REVISION,
                OsippActivity::STATUS_REJECTED,
            ],
            OsippActivity::STATUS_VERIFIED => [OsippActivity::STATUS_COMPLETED],
        ];

        if (! in_array($status, $allowed[$activity->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'Status kegiatan tidak dapat diubah dari '.$activity->status.' ke '.$status.'.',
            ]);
        }

        $payload = [
            'status' => $status,
            'updated_by' => $actor->id,
        ];

        if ($status === OsippActivity::STATUS_SUBMITTED) {
            $payload['submitted_at'] = now();
        }

        if (in_array($status, [
            OsippActivity::STATUS_VERIFIED,
            OsippActivity::STATUS_REVISION,
            OsippActivity::STATUS_REJECTED,
        ], true)) {
            $payload['verified_by'] = $actor->id;
            $payload['verified_at'] = now();
            $payload['verification_notes'] = $notes;
        }

        if ($status === OsippActivity::STATUS_COMPLETED) {
            $payload['verification_notes'] = $notes ?: $activity->verification_notes;
        }

        $activity->update($payload);

        if ($status === OsippActivity::STATUS_SUBMITTED) {
            $this->notifyCoordinators(
                $activity->period->school_id,
                $activity->period->academic_year_id,
                $actor,
                'Pengajuan verifikasi kegiatan OSIPP',
                'Kegiatan "'.$activity->title.'" menunggu verifikasi.',
                'osipp.kegiatan.index',
                ['period_id' => $activity->period_id],
                $activity,
            );
        }

        return $activity->fresh();
    }

    /**
     * @throws ValidationException
     */
    public function transitionDocument(OsippDocument $document, string $status, User $actor, ?string $notes = null): OsippDocument
    {
        $allowed = [
            OsippDocument::STATUS_DRAFT => [OsippDocument::STATUS_SUBMITTED],
            OsippDocument::STATUS_REVISION => [OsippDocument::STATUS_SUBMITTED],
            OsippDocument::STATUS_SUBMITTED => [
                OsippDocument::STATUS_VERIFIED,
                OsippDocument::STATUS_REVISION,
                OsippDocument::STATUS_REJECTED,
            ],
        ];

        if (! in_array($status, $allowed[$document->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'Status administrasi tidak dapat diubah dari '.$document->status.' ke '.$status.'.',
            ]);
        }

        $payload = [
            'status' => $status,
            'updated_by' => $actor->id,
        ];

        if ($status === OsippDocument::STATUS_SUBMITTED) {
            $payload['submitted_at'] = now();
        }

        if (in_array($status, [
            OsippDocument::STATUS_VERIFIED,
            OsippDocument::STATUS_REVISION,
            OsippDocument::STATUS_REJECTED,
        ], true)) {
            $payload['verified_by'] = $actor->id;
            $payload['verified_at'] = now();
            $payload['verification_notes'] = $notes;
        }

        $document->update($payload);

        if ($status === OsippDocument::STATUS_SUBMITTED) {
            $this->notifyCoordinators(
                $document->period->school_id,
                $document->period->academic_year_id,
                $actor,
                'Pengajuan verifikasi administrasi OSIPP',
                'Dokumen "'.$document->title.'" menunggu verifikasi.',
                'osipp.administrasi.index',
                ['period_id' => $document->period_id],
                $document,
            );
        }

        return $document->fresh();
    }

    private function notifyCoordinators(
        string $schoolId,
        ?string $academicYearId,
        User $actor,
        string $title,
        string $message,
        string $routeName,
        array $routeParams,
        OsippActivity|OsippDocument $reference,
    ): void {
        $recipients = collect($this->access->coordinatorIds($schoolId, $academicYearId))
            ->reject(fn ($id) => (string) $id === (string) $actor->id);

        if ($recipients->isEmpty()) {
            return;
        }

        $payload = [
            'module' => 'osipp',
            'reference_type' => $reference::class,
            'reference_id' => $reference->id,
            'reference_code' => $reference->title ?? null,
            'type' => 'info',
            'action' => 'osipp_verification',
            'title' => $title,
            'message' => $message,
            'action_text' => 'Buka Workspace OSIPP',
            'priority' => 'medium',
        ];

        foreach ($recipients as $recipientId) {
            $payload['action_url'] = route($routeName, array_merge(['userId' => $recipientId], $routeParams));
            $this->notifications->send($recipientId, $payload);
        }
    }
}
