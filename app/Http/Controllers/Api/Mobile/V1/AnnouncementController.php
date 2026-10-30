<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\AnnouncementAcknowledgement;
use App\Models\DormitoryResident;
use App\Models\HumasAnnouncement;
use App\Models\NotificationUniversal;
use App\Models\StudentClassHistory;
use App\Models\WaliSantri;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Feed pengumuman wali:
 * - Pengumuman sekolah dan broadcast terpusat (HumasAnnouncement) → sesuai
 *   target global/sekolah/asrama/kelas milik anak wali.
 * - Asrama memakai tabel dan target yang sama (dormitory).
 *
 * Wali dengan anak di beberapa sekolah/asrama menerima gabungan yang relevan;
 * gunakan ?student_id= untuk melihat milik satu anak saja.
 */
class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->query('limit', 20), 1), 100);
        $offset = max((int) $request->query('offset', 0), 0);

        $context = $this->context($request);

        if ($context === null) {
            return $this->notFound('Santri tidak ditemukan atau bukan anak Anda.');
        }

        $links = $context['links'];
        $studentIds = $context['student_ids'];
        $schoolIds = $context['school_ids'];
        $dormitoryIds = $context['dormitory_ids'];
        $dormByStudent = $context['dorm_by_student'];

        $humasQuery = $this->humasScopeQuery($context)
            ->with([
                'creator:id,name',
                'images',
                'school:id,name',
                'dormitory:id,name',
                'studyGroup:id,name',
            ])
            ->published();

        $humasTotal = (clone $humasQuery)->count();
        $humas = $humasQuery->orderByDesc('is_pinned')->latest('published_at')
            ->limit($offset + $limit)->get()
            ->map(fn (HumasAnnouncement $announcement) => $this->formatHumas(
                $announcement,
                $links,
                $dormByStudent,
                $context['class_by_student']
            ));

        $merged = $humas;

        $allIds = $merged->pluck('id')->unique()->values();
        $readMap = NotificationUniversal::where('user_id', $request->user()->id)
            ->whereIn('reference_id', $allIds)
            ->get()
            ->mapWithKeys(fn (NotificationUniversal $notification) => [
                $notification->reference_type.'|'.$notification->reference_id => (bool) $notification->is_read,
            ]);
        $ackMap = AnnouncementAcknowledgement::where('user_id', $request->user()->id)
            ->whereIn('reference_id', $allIds)
            ->get()
            ->mapWithKeys(fn (AnnouncementAcknowledgement $ack) => [
                $ack->reference_type.'|'.$ack->reference_id => true,
            ]);

        $items = $merged->slice($offset, $limit)
            ->map(function (array $item) use ($readMap, $ackMap) {
                $key = $item['_reference_type'].'|'.$item['id'];
                $item['is_read'] = (bool) ($readMap[$key] ?? false);
                $item['is_acknowledged'] = (bool) ($ackMap[$key] ?? false);
                unset($item['_sort_at'], $item['_reference_type']);

                return $item;
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'announcements' => $items,
                'total' => $humasTotal,
                'limit' => $limit,
                'offset' => $offset,
                'student_ids' => $studentIds->values()->all(),
            ],
        ]);
    }

    public function show(Request $request, string $userId, string $id): JsonResponse
    {
        $context = $this->context($request);

        if ($context === null) {
            return $this->notFound('Santri tidak ditemukan atau bukan anak Anda.');
        }

        $links = $context['links'];
        $schoolIds = $context['school_ids'];
        $dormitoryIds = $context['dormitory_ids'];
        $dormByStudent = $context['dorm_by_student'];

        $announcement = $this->humasScopeQuery($context)
            ->with([
                'creator:id,name',
                'images',
                'school:id,name',
                'dormitory:id,name',
                'studyGroup:id,name',
            ])
            ->published()
            ->find($id);

        if ($announcement) {
            return response()->json([
                'success' => true,
                'data' => $this->withStatus(
                    $this->withoutSortKey($this->formatHumas(
                        $announcement,
                        $links,
                        $dormByStudent,
                        $context['class_by_student']
                    )),
                    $request->user()->id,
                    HumasAnnouncement::class,
                    $announcement->id
                ),
            ]);
        }

        return $this->notFound('Pengumuman tidak ditemukan.');
    }

    public function markRead(Request $request, string $userId, string $id): JsonResponse
    {
        $reference = $this->accessibleReference($request, $id);

        if ($reference === null) {
            return $this->notFound('Pengumuman tidak ditemukan.');
        }

        NotificationUniversal::where('user_id', $request->user()->id)
            ->where('reference_type', $reference['type'])
            ->where('reference_id', $id)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Pengumuman ditandai sudah dibaca.']);
    }

    public function acknowledge(Request $request, string $userId, string $id): JsonResponse
    {
        $reference = $this->accessibleReference($request, $id);

        if ($reference === null) {
            return $this->notFound('Pengumuman tidak ditemukan.');
        }

        $model = $reference['model'];

        if (! ($model->ack_required ?? false)) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'ACK_NOT_REQUIRED', 'message' => 'Pengumuman ini tidak memerlukan konfirmasi.'],
            ], 422);
        }

        $validated = $request->validate([
            'student_id' => ['nullable', 'uuid', 'exists:students,id'],
        ]);

        $studentId = $validated['student_id'] ?? null;

        if ($studentId) {
            $ownsStudent = WaliSantri::where('user_id', $request->user()->id)
                ->active()
                ->where('student_id', $studentId)
                ->exists();

            if (! $ownsStudent) {
                return $this->notFound('Santri tidak ditemukan atau bukan anak Anda.');
            }
        }

        AnnouncementAcknowledgement::updateOrCreate(
            [
                'reference_type' => $reference['type'],
                'reference_id' => $id,
                'user_id' => $request->user()->id,
                'student_id' => $studentId,
            ],
            ['acknowledged_at' => now()]
        );

        return response()->json(['success' => true, 'message' => 'Konfirmasi berhasil disimpan.']);
    }

    /**
     * @return array{type: string, model: mixed}|null
     */
    private function accessibleReference(Request $request, string $id): ?array
    {
        $context = $this->context($request);

        if ($context === null) {
            return null;
        }

        $announcement = $this->humasScopeQuery($context)
            ->published()
            ->find($id);

        if ($announcement) {
            return ['type' => HumasAnnouncement::class, 'model' => $announcement];
        }

        return null;
    }

    /**
     * Query pengumuman yang relevan untuk seluruh anak milik wali.
     *
     * Target dormitory/class tidak boleh bocor ke semua wali sekolah.
     *
     * @param  array<string, mixed>  $context
     */
    private function humasScopeQuery(array $context): Builder
    {
        return HumasAnnouncement::query()
            ->whereIn('audience', ['all', 'wali'])
            ->where(function (Builder $query) use ($context) {
                $query->whereNull('school_id');

                if ($context['school_ids']->isNotEmpty()) {
                    $query->orWhere(fn (Builder $sub) => $sub
                        ->where('target_type', 'school')
                        ->whereIn('school_id', $context['school_ids']));
                }

                if ($context['dormitory_ids']->isNotEmpty()) {
                    $query->orWhere(fn (Builder $sub) => $sub
                        ->where('target_type', 'dormitory')
                        ->whereIn('dormitory_id', $context['dormitory_ids']));
                }

                if ($context['study_group_ids']->isNotEmpty()) {
                    $query->orWhere(fn (Builder $sub) => $sub
                        ->where('target_type', 'class')
                        ->whereIn('study_group_id', $context['study_group_ids']));
                }
            });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function context(Request $request): ?array
    {
        $links = WaliSantri::with(['student:id,name,school_id', 'student.school:id,name'])
            ->where('user_id', $request->user()->id)
            ->active()
            ->get();

        if ($request->filled('student_id')) {
            $links = $links->where('student_id', $request->string('student_id')->toString())->values();

            if ($links->isEmpty()) {
                return null;
            }
        }

        $studentIds = $links->pluck('student_id')->unique()->values();
        $schoolIds = $links->pluck('school_id')->filter()->unique()->values();

        $dormByStudent = DormitoryResident::whereIn('student_id', $studentIds)
            ->where('is_active', true)
            ->pluck('dormitory_id', 'student_id');

        $dormitoryIds = $dormByStudent->values()->filter()->unique()->values();

        $classByStudent = StudentClassHistory::whereIn('student_id', $studentIds)
            ->where('is_active', true)
            ->pluck('study_group_id', 'student_id');

        $studyGroupIds = $classByStudent->values()->filter()->unique()->values();

        return [
            'links' => $links,
            'student_ids' => $studentIds,
            'school_ids' => $schoolIds,
            'dormitory_ids' => $dormitoryIds,
            'dorm_by_student' => $dormByStudent,
            'study_group_ids' => $studyGroupIds,
            'class_by_student' => $classByStudent,
        ];
    }

    /**
     * @param  Collection<int, WaliSantri>  $links
     * @return array<string, mixed>
     */
    private function formatHumas(
        HumasAnnouncement $announcement,
        Collection $links,
        Collection $dormByStudent,
        Collection $classByStudent
    ): array {
        $eligibleLinks = match ($announcement->target_type) {
            'dormitory' => $links->filter(fn (WaliSantri $link) => (string) $dormByStudent->get($link->student_id) === (string) $announcement->dormitory_id),
            'class' => $links->filter(fn (WaliSantri $link) => (string) $classByStudent->get($link->student_id) === (string) $announcement->study_group_id),
            'school' => $announcement->school_id
                ? $links->where('school_id', $announcement->school_id)
                : $links,
            default => $links,
        };

        $severity = $announcement->severity ?: 'info';
        $images = $announcement->images->map(fn ($image) => [
            'id' => $image->id,
            'url' => $image->url,
            'width' => 1080,
            'height' => 1350,
            'aspect_ratio' => 0.8,
            'sort_order' => (int) $image->sort_order,
        ])->values();

        // Broadcast lama hanya punya banner_path; tetap kirim sebagai satu item
        // agar aplikasi mobile dapat memakai field banners secara konsisten.
        if ($images->isEmpty() && $announcement->banner_path) {
            $images = collect([[
                'id' => $announcement->id,
                'url' => asset('storage/'.$announcement->banner_path),
                'width' => 1080,
                'height' => 1350,
                'aspect_ratio' => 0.8,
                'sort_order' => 0,
            ]]);
        }

        $firstImage = $images->first();

        return [
            'id' => $announcement->id,
            '_reference_type' => HumasAnnouncement::class,
            'source' => $announcement->target_type === 'dormitory' ? 'asrama' : 'sekolah',
            'scope' => match ($announcement->target_type) {
                'dormitory' => 'asrama',
                'class' => 'kelas',
                default => $announcement->school_id === null ? 'global' : 'sekolah',
            },
            'target_type' => $announcement->target_type,
            'recipient_group' => $announcement->recipient_group,
            'channels' => $announcement->channels ?? [],
            'title' => $announcement->title,
            'content' => $announcement->content,
            'excerpt' => $announcement->excerpt,
            'category' => $announcement->category,
            'is_pinned' => (bool) $announcement->is_pinned || in_array($severity, ['urgent', 'emergency'], true),
            'needs_response' => (bool) $announcement->needs_response || (bool) $announcement->ack_required,
            'ack_required' => (bool) $announcement->ack_required,
            'visibility' => $announcement->visibility,
            'visibility_label' => $announcement->visibility ? $announcement->visibility_text : null,
            'severity' => $severity,
            'severity_label' => $this->severityLabel($severity),
            'requires_popup' => (bool) $announcement->ack_required || in_array($severity, ['urgent', 'emergency'], true),
            'school' => $announcement->school_id ? $this->schoolOf($eligibleLinks) : null,
            'dormitory' => $announcement->dormitory
                ? ['id' => $announcement->dormitory->id, 'name' => $announcement->dormitory->name]
                : null,
            'study_group' => $announcement->studyGroup
                ? ['id' => $announcement->studyGroup->id, 'name' => $announcement->studyGroup->name]
                : null,
            'students' => $this->eligibleStudents($eligibleLinks),
            'creator' => $announcement->creator?->name,
            'banner_url' => $firstImage['url'] ?? $announcement->banner_url,
            'banner' => $firstImage ? [
                'width' => 1080,
                'height' => 1350,
                'aspect_ratio' => 0.8,
            ] : null,
            'banners' => $images,
            'attachment_url' => null,
            'published_at' => $announcement->published_at?->toIso8601String(),
            'created_at' => $announcement->created_at?->toIso8601String(),
            'expires_at' => $announcement->expires_at?->toIso8601String(),
            'activity_start_date' => $announcement->activity_start_date?->toDateString(),
            'activity_end_date' => $announcement->activity_end_date?->toDateString(),
            '_sort_at' => $announcement->published_at?->toDateTimeString() ?? $announcement->created_at?->toDateTimeString(),
        ];
    }

    private function severityLabel(?string $severity): string
    {
        return match ($severity) {
            'warning' => 'Peringatan',
            'urgent' => 'Penting',
            'emergency' => 'Darurat',
            default => 'Informasi',
        };
    }

    /**
     * @param  Collection<int, WaliSantri>  $links
     * @return array<int, array<string, mixed>>
     */
    private function eligibleStudents(Collection $links): array
    {
        return $links->map(fn (WaliSantri $link) => [
            'id' => $link->student_id,
            'name' => $link->student?->name,
        ])->values()->all();
    }

    /**
     * @param  Collection<int, WaliSantri>  $links
     * @return array<string, mixed>|null
     */
    private function schoolOf(Collection $links): ?array
    {
        $school = $links->first()?->student?->school;

        return $school ? ['id' => $school->id, 'name' => $school->name] : null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function withStatus(array $item, string $userId, string $referenceType, string $referenceId): array
    {
        $notification = NotificationUniversal::where('user_id', $userId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->first();

        $item['is_read'] = (bool) ($notification?->is_read ?? false);
        $item['is_acknowledged'] = AnnouncementAcknowledgement::where('user_id', $userId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->exists();

        return $item;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function withoutSortKey(array $item): array
    {
        unset($item['_sort_at']);

        return $item;
    }

    private function notFound(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'ANNOUNCEMENT_NOT_FOUND',
                'message' => $message,
            ],
        ], 404);
    }
}
