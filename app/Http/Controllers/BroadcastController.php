<?php

namespace App\Http\Controllers;

use App\Models\AnnouncementAcknowledgement;
use App\Models\Dormitory;
use App\Models\HumasAnnouncement;
use App\Models\NotificationUniversal;
use App\Models\School;
use App\Models\StudyGroup;
use App\Services\Broadcast\AnnouncementNotifier;
use App\Services\Humas\AnnouncementCalendarService;
use App\Services\Media\BannerImageService;
use App\Support\BroadcastAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Broadcast terpusat untuk Personalia/Super Admin dan Sekolah.
 *
 * Target: global, sekolah, asrama, atau kelas. Kanal: in-app, push, WhatsApp.
 * Status baca diambil dari notifications_universal; status ack dari
 * announcement_acknowledgements.
 */
class BroadcastController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $this->authorizeManage($request);

        $isGlobal = BroadcastAccess::isGlobal($request->user());
        $schoolId = $this->authorSchoolId($request);

        if (! $isGlobal && ! $schoolId) {
            abort(403, 'Satuan pendidikan untuk akun Anda belum ditentukan.');
        }

        $broadcasts = HumasAnnouncement::with(['creator:id,name', 'images', 'school:id,name', 'dormitory:id,name', 'studyGroup:id,name'])
            ->where('category', 'broadcast')
            ->whereIn('target_type', ['global', 'school', 'dormitory', 'class'])
            ->when(! $isGlobal, fn ($query) => $query->where('school_id', $schoolId))
            ->latest('published_at')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $schools = $isGlobal
            ? School::active()->orderBy('name')->get(['id', 'name'])
            : School::whereKey($schoolId)->get(['id', 'name']);
        $dormitories = Dormitory::query()
            ->when(! $isGlobal, fn ($query) => $query->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'school_id']);
        $studyGroups = StudyGroup::query()
            ->when(! $isGlobal, fn ($query) => $query->where('school_id', $schoolId))
            ->where('is_active', true)
            ->with('gradeLevel:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'school_id', 'grade_level_id']);

        return view('broadcasts.index', compact(
            'userId', 'broadcasts', 'schools', 'dormitories', 'studyGroups', 'isGlobal', 'schoolId'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'content' => 'required|string',
            'target_type' => ['required', Rule::in(['global', 'school', 'dormitory', 'class'])],
            'target_id' => 'nullable|uuid',
            'recipient_group' => ['required', Rule::in(['wali', 'gtk', 'both'])],
            'channels' => 'required|array|min:1',
            'channels.*' => [Rule::in(['inapp', 'push', 'wa'])],
            'severity' => ['required', Rule::in(['info', 'warning', 'urgent', 'emergency'])],
            'ack_required' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'activity_start_date' => 'nullable|date|required_with:activity_end_date',
            'activity_end_date' => 'nullable|date|required_with:activity_start_date|after_or_equal:activity_start_date',
        ]);

        $user = $request->user();
        $isGlobal = BroadcastAccess::isGlobal($user);
        $ownSchoolId = $this->authorSchoolId($request);

        if (! $isGlobal && ! $ownSchoolId) {
            return back()->withInput()->with('error', 'Satuan pendidikan untuk akun Anda belum ditentukan.');
        }

        $schoolId = null;
        $dormitoryId = null;
        $studyGroupId = null;

        switch ($validated['target_type']) {
            case 'global':
                if (! $isGlobal) {
                    return back()->withInput()->with('error', 'Hanya Personalia/Super Admin yang dapat mengirim broadcast global.');
                }
                break;

            case 'school':
                $school = School::find($validated['target_id'] ?? null);

                if (! $school) {
                    return back()->withInput()->with('error', 'Sekolah target tidak ditemukan.');
                }

                if (! $isGlobal && $school->id !== $ownSchoolId) {
                    return back()->withInput()->with('error', 'Anda hanya dapat mengirim broadcast untuk sekolah Anda.');
                }

                $schoolId = $school->id;
                break;

            case 'dormitory':
                $dormitory = Dormitory::find($validated['target_id'] ?? null);

                if (! $dormitory) {
                    return back()->withInput()->with('error', 'Asrama target tidak ditemukan.');
                }

                if (! $isGlobal && $dormitory->school_id !== $ownSchoolId) {
                    return back()->withInput()->with('error', 'Anda hanya dapat mengirim broadcast untuk asrama di sekolah Anda.');
                }

                $schoolId = $dormitory->school_id;
                $dormitoryId = $dormitory->id;
                break;

            case 'class':
                $studyGroup = StudyGroup::find($validated['target_id'] ?? null);

                if (! $studyGroup) {
                    return back()->withInput()->with('error', 'Kelas target tidak ditemukan.');
                }

                if (! $isGlobal && $studyGroup->school_id !== $ownSchoolId) {
                    return back()->withInput()->with('error', 'Anda hanya dapat mengirim broadcast untuk kelas di sekolah Anda.');
                }

                $schoolId = $studyGroup->school_id;
                $studyGroupId = $studyGroup->id;
                break;
        }

        $channels = array_values(array_unique(array_merge(['inapp'], $validated['channels'])));
        $audience = match ($validated['recipient_group']) {
            'wali' => 'wali',
            'gtk' => 'gtk',
            default => 'all',
        };

        $bannerPath = $request->hasFile('banner')
            ? app(BannerImageService::class)->store($request->file('banner'))
            : null;

        $broadcast = HumasAnnouncement::create([
            'school_id' => $schoolId,
            'banner_path' => $bannerPath,
            'target_type' => $validated['target_type'],
            'dormitory_id' => $dormitoryId,
            'study_group_id' => $studyGroupId,
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']).'-'.Str::lower(Str::random(5)),
            'category' => 'broadcast',
            'severity' => $validated['severity'],
            'channels' => $channels,
            'ack_required' => $request->boolean('ack_required'),
            'recipient_group' => $validated['recipient_group'],
            'audience' => $audience,
            'content' => $validated['content'],
            'excerpt' => Str::limit(strip_tags($validated['content']), 180),
            'status' => 'published',
            'published_at' => now(),
            'expires_at' => $validated['expires_at'] ?? null,
            'activity_start_date' => $validated['activity_start_date'] ?? null,
            'activity_end_date' => $validated['activity_end_date'] ?? null,
            'created_by' => $user->id,
        ]);

        $sent = app(AnnouncementNotifier::class)->notify($broadcast, $user);
        app(AnnouncementCalendarService::class)->sync($broadcast, $user);

        return redirect()->route('user.broadcasts.show', ['userId' => $userId, 'id' => $broadcast->id])
            ->with('success', "Broadcast terkirim ke {$sent} penerima.");
    }

    public function show(Request $request, string $userId, string $id)
    {
        $this->authorizeManage($request);

        $broadcast = $this->findBroadcast($request, $id);

        if (! $broadcast) {
            abort(404);
        }

        $notifications = NotificationUniversal::with('user:id,name')
            ->where('reference_type', HumasAnnouncement::class)
            ->where('reference_id', $broadcast->id);

        $stats = [
            'recipients' => (clone $notifications)->count(),
            'read' => (clone $notifications)->where('is_read', true)->count(),
            'acknowledged' => AnnouncementAcknowledgement::where('reference_type', HumasAnnouncement::class)
                ->where('reference_id', $broadcast->id)
                ->count(),
        ];

        $recipients = $notifications->latest()->paginate(50);

        return view('broadcasts.show', compact('userId', 'broadcast', 'stats', 'recipients'));
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless(
            BroadcastAccess::canManage($request->user()),
            403,
            'Hanya Personalia/Super Admin atau Sekolah yang dapat mengelola broadcast.'
        );
    }

    private function authorSchoolId(Request $request): ?string
    {
        $schoolId = $request->attributes->get('schoolContextId');

        if (is_string($schoolId) && $schoolId !== '') {
            return $schoolId;
        }

        return $request->user()?->employment?->school_id;
    }

    private function findBroadcast(Request $request, string $id): ?HumasAnnouncement
    {
        $query = HumasAnnouncement::query()->where('category', 'broadcast');

        if (! BroadcastAccess::isGlobal($request->user())) {
            $schoolId = $this->authorSchoolId($request);

            if (! $schoolId) {
                return null;
            }

            $query->where('school_id', $schoolId);
        }

        return $query->find($id);
    }
}
