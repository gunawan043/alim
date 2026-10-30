<?php

namespace App\Http\Controllers;

use App\Models\AnnouncementAcknowledgement;
use App\Models\Dormitory;
use App\Models\DormitoryActivityTemplate;
use App\Models\HumasAnnouncement;
use App\Models\HumasAnnouncementImage;
use App\Models\NotificationUniversal;
use App\Services\Broadcast\AnnouncementNotifier;
use App\Services\Dormitory\DormitoryAudit;
use App\Services\Humas\AnnouncementCalendarService;
use App\Services\Media\BannerImageService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Komunikasi asrama memakai tabel yang sama dengan Humas:
 * humas_announcements + humas_announcement_images.
 *
 * Halaman tetap berada di menu asrama, tetapi pola, tabel, dan desain
 * menggunakan view Humas/Broadcast yang sama.
 */
class DormitoryPostController extends Controller
{
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $query = HumasAnnouncement::with(['creator:id,name', 'images'])
            ->where('target_type', 'dormitory')
            ->where('dormitory_id', $asramaUuid)
            ->where('category', '!=', 'broadcast');

        if ($request->filled('audience')) {
            $query->where('audience', $request->string('audience'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $announcements = $query->latest()->paginate(20)->withQueryString();

        $statsQuery = HumasAnnouncement::query()
            ->where('target_type', 'dormitory')
            ->where('dormitory_id', $asramaUuid)
            ->where('category', '!=', 'broadcast');

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'published' => (clone $statsQuery)->where('status', 'published')->count(),
            'draft' => (clone $statsQuery)->where('status', 'draft')->count(),
        ];

        return view('humas.announcements.index', [
            'userId' => $userId,
            'announcements' => $announcements,
            'stats' => $stats,
            'dormitory' => $dormitory,
            'announcementRoutes' => [
                'store' => 'user.asrama.posts.store',
                'update' => 'user.asrama.posts.update',
                'publish' => 'user.asrama.posts.publish',
                'destroy' => 'user.asrama.posts.destroy',
            ],
            'announcementRouteParams' => ['userId' => $userId, 'asramaUuid' => $asramaUuid],
            'announcementRouteId' => 'postUuid',
            'lockedAudience' => 'wali',
            'pageTitle' => 'Informasi Asrama',
            'pageHeading' => 'Informasi Asrama',
            'breadcrumbParent' => 'Asrama',
            'breadcrumbMiddle' => $dormitory->name,
        ]);
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $request->session()->flash('open_create', true);

        return $this->index($request, $userId, $asramaUuid);
    }

    public function store(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $validated = $this->validateAnnouncement($request);
        $files = $this->uploadedBanners($request);

        if (count($files) > 5) {
            return back()->withInput()->withErrors(['banners' => 'Maksimal 5 gambar carousel.']);
        }

        $data = $validated;
        unset($data['banners'], $data['banner']);

        DB::transaction(function () use ($request, $data, $files, $dormitory, $asramaUuid) {
            $announcement = HumasAnnouncement::create($data + [
                'school_id' => $dormitory->school_id,
                'target_type' => 'dormitory',
                'dormitory_id' => $asramaUuid,
                'recipient_group' => $this->recipientGroup($data['audience']),
                'channels' => ['inapp', 'push'],
                'severity' => 'info',
                'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(5)),
                'created_by' => $request->user()->id,
                'status' => 'draft',
            ]);

            $this->storeBanners($announcement, $files);
        });

        return redirect()
            ->route('user.asrama.posts.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Informasi asrama disimpan sebagai draft.');
    }

    public function show(Request $request, string $userId, string $asramaUuid, string $postUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $announcement = HumasAnnouncement::with(['creator:id,name', 'images'])
            ->where('target_type', 'dormitory')
            ->where('dormitory_id', $asramaUuid)
            ->where('category', '!=', 'broadcast')
            ->findOrFail($postUuid);

        return view('humas.announcements.show', [
            'userId' => $userId,
            'announcement' => $announcement,
            'dormitory' => $dormitory,
            'announcementBackRoute' => 'user.asrama.posts.index',
            'announcementBackParams' => ['userId' => $userId, 'asramaUuid' => $asramaUuid],
        ]);
    }

    public function edit(Request $request, string $userId, string $asramaUuid, string $postUuid)
    {
        $announcement = HumasAnnouncement::where('target_type', 'dormitory')
            ->where('dormitory_id', $asramaUuid)
            ->where('category', '!=', 'broadcast')
            ->findOrFail($postUuid);

        $request->session()->flash('open_edit', $announcement->id);

        return $this->index($request, $userId, $asramaUuid);
    }

    public function update(Request $request, string $userId, string $asramaUuid, string $postUuid)
    {
        $announcement = HumasAnnouncement::where('target_type', 'dormitory')
            ->where('dormitory_id', $asramaUuid)
            ->where('category', '!=', 'broadcast')
            ->findOrFail($postUuid);

        $validated = $this->validateAnnouncement($request);
        $data = $validated;
        unset($data['banners'], $data['banner']);

        $removeIds = array_filter((array) $request->input('remove_images', []));
        $newFiles = $this->uploadedBanners($request);
        $remaining = $announcement->images()->whereNotIn('id', $removeIds)->count();

        if ($remaining + count($newFiles) > 5) {
            return back()->withInput()->withErrors(['banners' => 'Maksimal 5 gambar carousel.']);
        }

        DB::transaction(function () use ($announcement, $data, $removeIds, $newFiles) {
            $data['audience'] = 'wali';
            $data['recipient_group'] = 'wali';
            $announcement->update($data);

            $this->storeBanners($announcement, $newFiles);

            foreach ($announcement->images()->whereIn('id', $removeIds)->get() as $image) {
                app(BannerImageService::class)->delete($image->path);
                $image->delete();
            }

            $first = $announcement->images()->orderBy('sort_order')->first();
            $announcement->update(['banner_path' => $first?->path]);
        });

        app(AnnouncementCalendarService::class)->sync($announcement->fresh(), $request->user());

        return redirect()
            ->route('user.asrama.posts.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Informasi asrama diperbarui.');
    }

    public function publish(Request $request, string $userId, string $asramaUuid, string $postUuid)
    {
        $announcement = HumasAnnouncement::where('target_type', 'dormitory')
            ->where('dormitory_id', $asramaUuid)
            ->where('category', '!=', 'broadcast')
            ->findOrFail($postUuid);

        $announcement->update(['status' => 'published', 'published_at' => now()]);
        app(AnnouncementNotifier::class)->notify($announcement, $request->user());
        app(AnnouncementCalendarService::class)->sync($announcement->fresh(), $request->user());

        return back()->with('success', 'Informasi asrama dipublikasikan.');
    }

    public function destroy(Request $request, string $userId, string $asramaUuid, string $postUuid)
    {
        $announcement = HumasAnnouncement::where('target_type', 'dormitory')
            ->where('dormitory_id', $asramaUuid)
            ->where('category', '!=', 'broadcast')
            ->findOrFail($postUuid);

        $announcement->delete();

        return redirect()
            ->route('user.asrama.posts.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Informasi asrama dihapus.');
    }

    public function broadcastIndex(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $broadcasts = HumasAnnouncement::with(['creator:id,name', 'images'])
            ->where('category', 'broadcast')
            ->where('target_type', 'dormitory')
            ->where('dormitory_id', $asramaUuid)
            ->latest('published_at')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('broadcasts.index', [
            'userId' => $userId,
            'broadcasts' => $broadcasts,
            'schools' => collect(),
            'dormitories' => collect(),
            'studyGroups' => collect(),
            'isGlobal' => false,
            'schoolId' => $dormitory->school_id,
            'lockedRecipient' => 'wali',
            'lockedTarget' => [
                'type' => 'dormitory',
                'id' => $dormitory->id,
                'label' => 'Asrama: '.$dormitory->name,
            ],
            'broadcastRoutes' => [
                'store' => 'user.asrama.broadcasts.store',
                'show' => 'user.asrama.broadcasts.show',
                'destroy' => 'user.asrama.broadcasts.destroy',
            ],
            'broadcastRouteParams' => ['userId' => $userId, 'asramaUuid' => $asramaUuid],
            'broadcastRouteId' => 'broadcastUuid',
            'pageTitle' => 'Broadcast Asrama',
            'broadcastPageTitle' => 'Broadcast Asrama',
            'broadcastBreadcrumb' => 'Asrama '.$dormitory->name,
        ]);
    }

    public function broadcastStore(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'content' => 'required|string',
            'recipient_group' => 'nullable|in:wali,gtk,both',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:inapp,push,wa',
            'severity' => 'required|in:info,warning,urgent,emergency',
            'ack_required' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'activity_start_date' => 'nullable|date|required_with:activity_end_date',
            'activity_end_date' => 'nullable|date|required_with:activity_start_date|after_or_equal:activity_start_date',
        ]);

        $channels = array_values(array_unique(array_merge(['inapp'], $validated['channels'])));
        $bannerPath = $request->hasFile('banner')
            ? app(BannerImageService::class)->store($request->file('banner'))
            : null;

        $broadcast = HumasAnnouncement::create([
            'school_id' => $dormitory->school_id,
            'target_type' => 'dormitory',
            'dormitory_id' => $dormitory->id,
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']).'-'.Str::lower(Str::random(5)),
            'category' => 'broadcast',
            'severity' => $validated['severity'],
            'channels' => $channels,
            'ack_required' => $request->boolean('ack_required'),
            'recipient_group' => 'wali',
            'audience' => 'wali',
            'content' => $validated['content'],
            'excerpt' => Str::limit(strip_tags($validated['content']), 180),
            'banner_path' => $bannerPath,
            'status' => 'published',
            'published_at' => now(),
            'expires_at' => $validated['expires_at'] ?? null,
            'activity_start_date' => $validated['activity_start_date'] ?? null,
            'activity_end_date' => $validated['activity_end_date'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $sent = app(AnnouncementNotifier::class)->notify($broadcast, $request->user());
        app(AnnouncementCalendarService::class)->sync($broadcast, $request->user());
        DormitoryAudit::log('DORMITORY_BROADCAST_SENT', 'humas_announcements', $broadcast->id);

        return redirect()
            ->route('user.asrama.broadcasts.show', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid,
                'broadcastUuid' => $broadcast->id,
            ])
            ->with('success', "Broadcast terkirim ke {$sent} penerima.");
    }

    public function broadcastShow(Request $request, string $userId, string $asramaUuid, string $broadcastUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $broadcast = HumasAnnouncement::with(['creator:id,name', 'images'])
            ->where('category', 'broadcast')
            ->where('target_type', 'dormitory')
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($broadcastUuid);

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

        return view('broadcasts.show', [
            'userId' => $userId,
            'broadcast' => $broadcast,
            'dormitory' => $dormitory,
            'stats' => $stats,
            'recipients' => $recipients,
            'broadcastIndexRoute' => 'user.asrama.broadcasts.index',
            'broadcastIndexParams' => ['userId' => $userId, 'asramaUuid' => $asramaUuid],
        ]);
    }

    public function broadcastDestroy(Request $request, string $userId, string $asramaUuid, string $broadcastUuid)
    {
        $broadcast = HumasAnnouncement::where('category', 'broadcast')
            ->where('target_type', 'dormitory')
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($broadcastUuid);

        if ((string) $broadcast->created_by !== (string) $request->user()->id && ! $request->user()->isSystemAdmin()) {
            abort(403);
        }

        $broadcast->delete();

        return redirect()
            ->route('user.asrama.broadcasts.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Broadcast berhasil dihapus.');
    }

    private function validateAnnouncement(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:200',
            'category' => 'required|string|max:50',
            'audience' => 'required|in:all,wali,gtk',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'banners' => 'nullable|array|max:5',
            'banners.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'is_pinned' => 'nullable|boolean',
            'expires_at' => 'nullable|date|after:now',
            'activity_start_date' => 'nullable|date|required_with:activity_end_date',
            'activity_end_date' => 'nullable|date|required_with:activity_start_date|after_or_equal:activity_start_date',
        ]);
    }

    private function recipientGroup(string $audience): string
    {
        return match ($audience) {
            'wali' => 'wali',
            'gtk' => 'gtk',
            default => 'both',
        };
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function uploadedBanners(Request $request): array
    {
        $files = array_values((array) $request->file('banners', []));

        if ($request->hasFile('banner')) {
            $files[] = $request->file('banner');
        }

        return $files;
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeBanners(HumasAnnouncement $announcement, array $files): void
    {
        $sortOrder = (int) $announcement->images()->max('sort_order');
        $stored = [];

        try {
            foreach (array_values($files) as $index => $file) {
                $path = app(BannerImageService::class)->store($file, 'announcements/banners', 'banners.'.$index);
                $stored[] = $path;

                HumasAnnouncementImage::create([
                    'humas_announcement_id' => $announcement->id,
                    'path' => $path,
                    'sort_order' => $sortOrder + $index + 1,
                ]);

                if (! $announcement->banner_path) {
                    $announcement->update(['banner_path' => $path]);
                }
            }
        } catch (\Throwable $exception) {
            foreach ($stored as $path) {
                app(BannerImageService::class)->delete($path);
            }

            throw $exception;
        }
    }

    public function templateIndex(Request $request, string $userId, string $asramaUuid)
    {
        return redirect()->route('user.asrama.kegiatan.schedules', [
            'userId' => $userId, 'asramaUuid' => $asramaUuid,
        ])->with('info', 'Template kegiatan kini dikelola sebagai Kegiatan Rutin (harian / malam tertentu).');
    }

    public function templateStore(Request $request, string $userId, string $asramaUuid)
    {
        $data = $request->validate([
            'session' => 'required|in:subuh,pagi,siang,sore,isya,malam',
            'activity_items' => 'required|array|min:1',
            'notes' => 'nullable|string',
        ]);

        DormitoryActivityTemplate::updateOrCreate(
            ['dormitory_id' => $asramaUuid, 'session' => $data['session']],
            ['activity_items' => $data['activity_items'], 'notes' => $data['notes'] ?? null, 'is_active' => true]
        );

        return back()->with('success', 'Templat aktivitas berhasil disimpan.');
    }

    public function templateToggle(Request $request, string $userId, string $asramaUuid, string $session)
    {
        $template = DormitoryActivityTemplate::where('dormitory_id', $asramaUuid)
            ->where('session', $session)
            ->first();

        if ($template) {
            $template->update(['is_active' => ! $template->is_active]);
        }

        return back();
    }

    public function activityLogIndex(Request $request, string $userId, string $asramaUuid)
    {
        return redirect()->route('user.asrama.kegiatan.index', [
            'userId' => $userId, 'asramaUuid' => $asramaUuid,
        ])->with('info', 'Log kegiatan kini terintegrasi dengan Presensi Kegiatan Asrama.');
    }
}
