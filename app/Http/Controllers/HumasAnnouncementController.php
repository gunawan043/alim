<?php

namespace App\Http\Controllers;

use App\Models\HumasAnnouncement;
use App\Models\User;
use App\Models\WaliSantri;
use App\Services\NotificationBroadcastService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HumasAnnouncementController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $this->authorizeHumas($request);
        $announcements = HumasAnnouncement::with('creator:id,name')
            ->when($request->filled('audience'), fn ($q) => $q->where('audience', $request->audience))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()->paginate(20)->withQueryString();
        $stats = [
            'total' => HumasAnnouncement::count(),
            'published' => HumasAnnouncement::where('status', 'published')->count(),
            'draft' => HumasAnnouncement::where('status', 'draft')->count(),
        ];

        return view('humas.announcements.index', compact('userId', 'announcements', 'stats'));
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeHumas($request);
        $validated = $this->validateAnnouncement($request);
        $announcement = HumasAnnouncement::create($validated + [
            'school_id' => $request->attributes->get('schoolContextId'),
            'slug' => Str::slug($validated['title']).'-'.Str::lower(Str::random(5)),
            'created_by' => $request->user()->id,
            'status' => 'draft',
        ]);

        return back()->with('success', 'Pengumuman disimpan sebagai draft.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        $this->authorizeHumas($request);
        $announcement = HumasAnnouncement::findOrFail($id);
        $validated = $this->validateAnnouncement($request);
        $announcement->update($validated);

        return back()->with('success', 'Pengumuman diperbarui.');
    }

    public function publish(Request $request, string $userId, string $id)
    {
        $this->authorizeHumas($request);
        $announcement = HumasAnnouncement::findOrFail($id);
        $announcement->update(['status' => 'published', 'published_at' => now()]);
        $this->notifyAudience($announcement, $request->user());

        return back()->with('success', 'Pengumuman dipublikasikan dan notifikasi dikirim.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $this->authorizeHumas($request);
        HumasAnnouncement::findOrFail($id)->delete();

        return back()->with('success', 'Pengumuman dihapus.');
    }

    public function feed(Request $request, string $userId)
    {
        $announcements = HumasAnnouncement::published()
            ->whereIn('audience', ['all', 'gtk'])
            ->orderByDesc('is_pinned')->latest('published_at')->paginate(12);

        return view('humas.announcements.feed', compact('userId', 'announcements'));
    }

    public function show(Request $request, string $userId, string $id)
    {
        $announcement = HumasAnnouncement::published()
            ->whereIn('audience', ['all', 'gtk'])->findOrFail($id);

        return view('humas.announcements.show', compact('userId', 'announcement'));
    }

    private function validateAnnouncement(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:200',
            'category' => 'required|string|max:50',
            'audience' => 'required|in:all,wali,gtk',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'is_pinned' => 'nullable|boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);
    }

    private function authorizeHumas(Request $request): void
    {
        $user = $request->user();
        abort_unless($user->hasRole('Humas Personalia') || $user->isSystemAdmin() || $user->isSuperAdmin(), 403);
    }

    private function notifyAudience(HumasAnnouncement $announcement, User $publisher): void
    {
        $service = app(NotificationBroadcastService::class);
        $gtkIds = collect();
        $waliIds = collect();

        if (in_array($announcement->audience, ['all', 'gtk'], true)) {
            $gtkIds = User::where('is_active', true)->whereHas('employment')->pluck('id');
        }
        if (in_array($announcement->audience, ['all', 'wali'], true)) {
            $waliIds = WaliSantri::where('is_active', true)->whereNotNull('user_id')->pluck('user_id');
        }

        foreach ($gtkIds->merge($waliIds)->unique()->reject(fn ($id) => (string) $id === (string) $publisher->id) as $recipientId) {
            $token = WaliSantri::where('user_id', $recipientId)->where('is_active', true)->value('access_token');
            $url = $token
                ? route('portal.announcements.show', ['token' => $token, 'id' => $announcement->id])
                : route('user.announcements.show', ['userId' => $recipientId, 'id' => $announcement->id]);

            $service->send((string) $recipientId, [
                'module' => 'system', 'type' => 'info', 'priority' => $announcement->is_pinned ? 'high' : 'medium',
                'title' => 'Pengumuman baru: '.$announcement->title,
                'message' => $announcement->excerpt ?: Str::limit(strip_tags($announcement->content), 140),
                'action_url' => $url, 'reference_type' => HumasAnnouncement::class,
                'reference_id' => $announcement->id, 'action_text' => 'Baca Pengumuman',
            ]);
        }
    }
}
