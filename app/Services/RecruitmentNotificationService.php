<?php

// app/Services/RecruitmentNotificationService.php

namespace App\Services;

use App\Models\RecruitmentApplication;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk mengirim notification ke applicant melalui recruitment app
 *
 * Admin app menggunakan service ini untuk queue notifications
 * yang akan diproses oleh recruitment app secara async
 */
class RecruitmentNotificationService
{
    /**
     * Base URL recruitment app (from config)
     */
    protected static function getBaseUrl()
    {
        return config('services.recruitment_api.url', env('RECRUITMENT_API_URL', 'http://localhost:8000'));
    }

    /**
     * API Token untuk authentication
     */
    protected static function getApiToken()
    {
        return config('services.recruitment_api.token', env('RECRUITMENT_API_TOKEN'));
    }

    /**
     * Send single notification via API
     */
    public static function send(string $type, int $userId, array $payload = []): ?array
    {
        try {
            $url = self::getBaseUrl().'/api/notifications/send';

            $response = Http::withToken(self::getApiToken())
                ->post($url, [
                    'type' => $type,
                    'user_id' => $userId,
                    'payload' => $payload,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('RecruitmentNotificationService: Failed to send', [
                'type' => $type,
                'user_id' => $userId,
                'response' => $response->body(),
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('RecruitmentNotificationService: Exception', [
                'type' => $type,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send bulk notifications via API
     */
    public static function sendBulk(string $type, array $userIds, array $payload = []): ?array
    {
        try {
            $url = self::getBaseUrl().'/api/notifications/send-bulk';

            $response = Http::withToken(self::getApiToken())
                ->post($url, [
                    'type' => $type,
                    'user_ids' => $userIds,
                    'payload' => $payload,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (Exception $e) {
            Log::error('RecruitmentNotificationService: Bulk send failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ============================================================
    // CONVENIENCE METHODS
    // ============================================================

    /**
     * Notify applicant when their application status changes
     */
    public static function notifyApplicationStatusChanged(
        RecruitmentApplication $application,
        string $oldStatus,
        string $newStatus,
        ?string $catatan = null
    ): ?array {
        $userId = $application->recruitmentProfile->user_id ?? null;

        if (! $userId) {
            Log::warning('RecruitmentNotificationService: No user_id for application', [
                'application_id' => $application->id,
            ]);

            return null;
        }

        return self::send('application_status_changed', $userId, [
            'application_id' => $application->id,
            'job_id' => $application->recruitment_job_id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'catatan' => $catatan,
        ]);
    }

    /**
     * Notify applicant when interview is scheduled
     */
    public static function notifyInterviewScheduled(
        RecruitmentApplication $application,
        array $stageData
    ): ?array {
        $userId = $application->recruitmentProfile->user_id ?? null;

        if (! $userId) {
            return null;
        }

        return self::send('interview_scheduled', $userId, [
            'application_id' => $application->id,
            'stage' => $stageData,
        ]);
    }

    /**
     * Notify applicant when interview is rescheduled
     */
    public static function notifyInterviewRescheduled(
        RecruitmentApplication $application,
        array $stageData
    ): ?array {
        $userId = $application->recruitmentProfile->user_id ?? null;

        if (! $userId) {
            return null;
        }

        return self::send('interview_rescheduled', $userId, [
            'application_id' => $application->id,
            'stage' => $stageData,
        ]);
    }

    /**
     * Notify applicant when stage result is published
     */
    public static function notifyStageResult(
        RecruitmentApplication $application,
        string $result,
        ?array $stageData = null,
        ?string $feedback = null
    ): ?array {
        $userId = $application->recruitmentProfile->user_id ?? null;

        if (! $userId) {
            return null;
        }

        return self::send('stage_result', $userId, [
            'application_id' => $application->id,
            'result' => $result,
            'stage' => $stageData,
            'feedback' => $feedback,
        ]);
    }

    /**
     * Notify applicant when profile is verified
     */
    public static function notifyProfileVerified(User $user): ?array
    {
        return self::send('profile_verified', $user->id, []);
    }

    /**
     * Notify applicant when profile is rejected
     */
    public static function notifyProfileRejected(User $user, ?string $reason = null): ?array
    {
        return self::send('profile_rejected', $user->id, [
            'reason' => $reason,
        ]);
    }

    /**
     * Notify applicant when they are accepted
     */
    public static function notifyApplicationAccepted(RecruitmentApplication $application): ?array
    {
        $userId = $application->recruitmentProfile->user_id ?? null;

        if (! $userId) {
            return null;
        }

        return self::send('application_accepted', $userId, [
            'application_id' => $application->id,
        ]);
    }

    /**
     * Notify applicant when they are rejected
     */
    public static function notifyApplicationRejected(
        RecruitmentApplication $application,
        ?string $reason = null
    ): ?array {
        $userId = $application->recruitmentProfile->user_id ?? null;

        if (! $userId) {
            return null;
        }

        return self::send('application_rejected', $userId, [
            'application_id' => $application->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Bulk notify accepted applications
     */
    public static function bulkNotifyAccepted(array $applicationIds): ?array
    {
        $userIds = RecruitmentApplication::whereIn('id', $applicationIds)
            ->with('recruitmentProfile')
            ->get()
            ->pluck('recruitmentProfile.user_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($userIds)) {
            return null;
        }

        return self::sendBulk('application_accepted', $userIds, []);
    }

    /**
     * Bulk notify rejected applications
     */
    public static function bulkNotifyRejected(array $applicationIds, ?string $reason = null): ?array
    {
        $userIds = RecruitmentApplication::whereIn('id', $applicationIds)
            ->with('recruitmentProfile')
            ->get()
            ->pluck('recruitmentProfile.user_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($userIds)) {
            return null;
        }

        return self::sendBulk('application_rejected', $userIds, [
            'reason' => $reason,
        ]);
    }
}
