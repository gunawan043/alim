<?php

namespace App\Http\Controllers;

use App\Models\HumasAnnouncement;
use App\Models\HumasAnnouncementImage;
use App\Models\User;
use App\Services\Broadcast\AnnouncementNotifier;
use App\Services\Humas\AnnouncementCalendarService;
use App\Services\Media\BannerImageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HumasAnnouncementController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $this->authorizeManage($request);

        $announcements = $this->baseQuery($request)
            ->with(['creator:id,name', 'images'])
            ->when($request->filled('audience'), fn ($q) => $q->where('audience', $request->audience))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()->paginate(20)->withQueryString();

        $statsQuery = $this->baseQuery($request);

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'published' => (clone $statsQuery)->where('status', 'published')->count(),
            'draft' => (clone $statsQuery)->where('status', 'draft')->count(),
        ];

        return view('humas.announcements.index', compact('userId', 'announcements', 'stats'));
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeManage($request);

        $validated = $this->validateAnnouncement($request);
        $schoolId = $this->isGlobalPublisher($request->user())
            ? null
            : $this->authorSchoolId($request);

        if (! $this->isGlobalPublisher($request->user()) && ! $schoolId) {
            return back()->with('error', 'Satuan pendidikan untuk akun Anda belum ditentukan.');
        }

        $recipientGroup = match ($validated['audience']) {
            'wali' => 'wali',
            'gtk' => 'gtk',
            default => 'both',
        };

        $data = $validated;
        unset($data['banners'], $data['banner']);
        $files = $this->uploadedBanners($request);

        if (count($files) > 5) {
            return back()->withInput()->withErrors(['banners' => 'Maksimal 5 gambar carousel.']);
        }

        DB::transaction(function () use ($request, $data, $files, $schoolId, $recipientGroup, $validated) {
            $announcement = HumasAnnouncement::create($data + [
                'school_id' => $schoolId,
                'target_type' => $schoolId ? 'school' : 'global',
                'recipient_group' => $recipientGroup,
                'channels' => ['inapp', 'push'],
                'severity' => 'info',
                'slug' => Str::slug($validated['title']).'-'.Str::lower(Str::random(5)),
                'created_by' => $request->user()->id,
                'status' => 'draft',
            ]);

            $this->storeBanners($announcement, $files);
        });

        return back()->with('success', 'Pengumuman disimpan sebagai draft.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        $this->authorizeManage($request);

        $announcement = HumasAnnouncement::findOrFail($id);
        $this->assertCanManage($request, $announcement);

        $data = $this->validateAnnouncement($request);
        unset($data['banners'], $data['banner']);

        $removeIds = array_filter((array) $request->input('remove_images', []));
        $newFiles = $this->uploadedBanners($request);
        $remaining = $announcement->images()->whereNotIn('id', $removeIds)->count();

        if ($remaining + count($newFiles) > 5) {
            return back()->withInput()->withErrors(['banners' => 'Maksimal 5 gambar carousel.']);
        }

        DB::transaction(function () use ($announcement, $data, $removeIds, $newFiles) {
            $announcement->update($data);

            // Simpan gambar baru lebih dulu; jika ada upload yang gagal,
            // gambar lama belum terhapus sehingga data tetap konsisten.
            $this->storeBanners($announcement, $newFiles);

            foreach ($announcement->images()->whereIn('id', $removeIds)->get() as $image) {
                app(BannerImageService::class)->delete($image->path);
                $image->delete();
            }

            $first = $announcement->images()->orderBy('sort_order')->first();
            $announcement->update(['banner_path' => $first?->path]);
        });

        app(AnnouncementCalendarService::class)->sync($announcement->fresh(), $request->user());

        return back()->with('success', 'Pengumuman diperbarui.');
    }

    public function publish(Request $request, string $userId, string $id)
    {
        $this->authorizeManage($request);

        $announcement = HumasAnnouncement::findOrFail($id);
        $this->assertCanManage($request, $announcement);

        $announcement->update(['status' => 'published', 'published_at' => now()]);
        $this->notifyAudience($announcement, $request->user());
        app(AnnouncementCalendarService::class)->sync($announcement->fresh(), $request->user());

        return back()->with('success', 'Pengumuman dipublikasikan dan notifikasi dikirim.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $this->authorizeManage($request);

        $announcement = HumasAnnouncement::findOrFail($id);
        $this->assertCanManage($request, $announcement);
        $announcement->delete();

        return back()->with('success', 'Pengumuman dihapus.');
    }

    public function feed(Request $request, string $userId)
    {
        $announcements = $this->feedQuery($request)
            ->orderByDesc('is_pinned')->latest('published_at')->paginate(12);

        return view('humas.announcements.feed', compact('userId', 'announcements'));
    }

    public function show(Request $request, string $userId, string $id)
    {
        $announcement = $this->feedQuery($request)->findOrFail($id);

        return view('humas.announcements.show', compact('userId', 'announcement'));
    }

    /**
     * Dukung field lama `banner` dan field carousel `banners[]`.
     *
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

    private function authorizeManage(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user->isSystemAdmin()
            || $user->isSuperAdmin()
            || $user->hasRole('Humas Personalia')
            || $user->hasRole('Satuan Pendidikan'),
            403,
            'Anda tidak memiliki akses untuk mengelola pengumuman.'
        );
    }

    /**
     * Personalia/Super Admin = global; sekolah = hanya sekolahnya.
     */
    private function isGlobalPublisher(?User $user): bool
    {
        return (bool) ($user?->isSystemAdmin()
            || $user?->isSuperAdmin()
            || $user?->hasRole('Humas Personalia'));
    }

    private function authorSchoolId(Request $request): ?string
    {
        $schoolId = $request->attributes->get('schoolContextId');

        if (is_string($schoolId) && $schoolId !== '') {
            return $schoolId;
        }

        return $request->user()?->employment?->school_id;
    }

    private function baseQuery(Request $request): Builder
    {
        $query = HumasAnnouncement::query();

        if (! $this->isGlobalPublisher($request->user())) {
            $schoolId = $this->authorSchoolId($request);

            if (! $schoolId) {
                abort(403, 'Satuan pendidikan untuk akun Anda belum ditentukan.');
            }

            $query->where('school_id', $schoolId);
        }

        return $query;
    }

    private function feedQuery(Request $request): Builder
    {
        $query = HumasAnnouncement::with('images')->published()->whereIn('audience', ['all', 'gtk']);

        if (! $this->isGlobalPublisher($request->user())) {
            $schoolId = $this->authorSchoolId($request);

            if ($schoolId) {
                $query->where(fn ($sub) => $sub->whereNull('school_id')->orWhere('school_id', $schoolId));
            } else {
                $query->whereNull('school_id');
            }
        }

        return $query;
    }

    private function assertCanManage(Request $request, HumasAnnouncement $announcement): void
    {
        if ($this->isGlobalPublisher($request->user())) {
            return;
        }

        $schoolId = $this->authorSchoolId($request);

        abort_unless(
            $schoolId !== null && (string) $announcement->school_id === (string) $schoolId,
            403,
            'Anda hanya dapat mengelola pengumuman sekolah Anda.'
        );
    }

    private function notifyAudience(HumasAnnouncement $announcement, User $publisher): void
    {
        app(AnnouncementNotifier::class)->notify($announcement, $publisher);
    }
}
