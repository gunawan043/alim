<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dormitory\StorePostRequest;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryActivityLog;
use App\Models\DormitoryActivityTemplate;
use App\Models\DormitoryEmergencyBroadcast;
use App\Models\DormitoryPost;
use App\Services\DormitoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DormitoryPostController extends Controller
{
    protected DormitoryService $service;

    public function __construct(DormitoryService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $query = DormitoryPost::with(['creator'])
            ->where('dormitory_id', $asramaUuid)
            ->where('is_active', true);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($sq) => $sq
                ->where('title', 'like', "%{$q}%")
                ->orWhere('content', 'like', "%{$q}%")
            );
        }

        $posts = $query->orderByDesc('is_pinned')->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('dormitory.posts.index', compact('dormitory', 'posts', 'userId'));
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        return view('dormitory.posts.create', compact('dormitory', 'userId'));
    }

    public function store(StorePostRequest $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $data = $request->validated();

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('dormitory/posts', 'public');
        }

        $data['dormitory_id'] = $asramaUuid;
        $data['created_by'] = auth()->id();
        $data['needs_response'] = $request->boolean('needs_response');
        $data['is_pinned'] = $request->boolean('is_pinned');

        DB::transaction(function () use ($data) {
            DormitoryPost::create($data);
        });

        return redirect()->route('user.asrama.posts.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Informasi berhasil diposting.');
    }

    public function show(Request $request, string $userId, string $asramaUuid, string $postUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $post = DormitoryPost::with(['creator', 'responses.student'])
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($postUuid);

        return view('dormitory.posts.show', compact('dormitory', 'post', 'userId'));
    }

    public function edit(Request $request, string $userId, string $asramaUuid, string $postUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $post = DormitoryPost::where('dormitory_id', $asramaUuid)->findOrFail($postUuid);

        return view('dormitory.posts.edit', compact('dormitory', 'post', 'userId'));
    }

    public function update(Request $request, string $userId, string $asramaUuid, string $postUuid)
    {
        $post = DormitoryPost::where('dormitory_id', $asramaUuid)->findOrFail($postUuid);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|in:pengumuman,undangan,laporan,darurat',
            'visibility' => 'required|in:wali,pengurus,umum',
            'needs_response' => 'boolean',
            'is_pinned' => 'boolean',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
        ]);

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('dormitory/posts', 'public');
        }

        $data['needs_response'] = $request->boolean('needs_response');
        $data['is_pinned'] = $request->boolean('is_pinned');
        $post->update($data);

        return redirect()->route('user.asrama.posts.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid, 'postUuid' => $postUuid])
            ->with('success', 'Informasi berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $asramaUuid, string $postUuid)
    {
        $post = DormitoryPost::where('dormitory_id', $asramaUuid)->findOrFail($postUuid);
        $post->delete();

        return redirect()->route('user.asrama.posts.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Informasi berhasil dihapus.');
    }

    // ── ACTIVITY TEMPLATE MANAGEMENT ──────────────────────────────

    public function templateIndex(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $templates = DormitoryActivityTemplate::where('dormitory_id', $asramaUuid)->get();

        return view('dormitory.templates.index', compact('dormitory', 'templates', 'userId'));
    }

    public function templateStore(Request $request, string $userId, string $asramaUuid)
    {
        $data = $request->validate([
            'session' => 'required|in:subuh,pagi,siang,sore,isya,malam',
            'activity_items' => 'required|array|min:1',
            'notes' => 'nullable|string',
        ]);

        $data['dormitory_id'] = $asramaUuid;

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

    // ── ACTIVITY LOG ─────────────────────────────────────────────

    public function activityLogIndex(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $date = $request->filled('date') ? $request->date : now()->toDateString();
        $session = $request->filled('session') ? $request->session : 'malam';

        $logs = DormitoryActivityLog::with(['resident.student', 'recordedBy'])
            ->where('dormitory_id', $asramaUuid)
            ->where('activity_date', $date)
            ->where('session', $session)
            ->orderBy('resident_id')
            ->get();

        return view('dormitory.activities.index', compact(
            'dormitory', 'logs', 'userId', 'activeYear', 'date', 'session'
        ));
    }

    // ── EMERGENCY BROADCAST ──────────────────────────────────────

    public function broadcastShow(Request $request, string $userId, string $asramaUuid, string $broadcastUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $broadcast = DormitoryEmergencyBroadcast::where('dormitory_id', $asramaUuid)->findOrFail($broadcastUuid);

        return view('dormitory.broadcasts.show', compact('dormitory', 'broadcast', 'userId'));
    }

    public function broadcastIndex(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $broadcasts = DormitoryEmergencyBroadcast::with(['creator'])
            ->where('dormitory_id', $asramaUuid)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('dormitory.broadcasts.index', compact('dormitory', 'broadcasts', 'userId'));
    }

    public function broadcastStore(Request $request, string $userId, string $asramaUuid)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'severity' => 'required|in:info,warning,urgent,emergency',
            'broadcast_via' => 'required|in:whatsapp,inapp,all',
            'ack_required' => 'boolean',
            'expires_at' => 'nullable|date',
        ]);

        $data['dormitory_id'] = $asramaUuid;
        $data['created_by'] = auth()->id();
        $data['ack_required'] = $request->boolean('ack_required');

        $broadcast = DormitoryEmergencyBroadcast::create($data);

        // Broadcast via service (akan kirim notifikasi saat sistem wali tersedia)
        $this->service->broadcastToDormitoryWalis($dormitory, $data);

        return back()->with('success', 'Broadcast darurat berhasil dikirim.');
    }

    public function broadcastDestroy(Request $request, string $userId, string $asramaUuid, string $broadcastUuid)
    {
        $broadcast = DormitoryEmergencyBroadcast::where('dormitory_id', $asramaUuid)->findOrFail($broadcastUuid);

        if ($broadcast->created_by !== auth()->id()) {
            abort(403);
        }

        $broadcast->delete();

        return redirect()->route('user.asrama.broadcasts.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Broadcast berhasil dihapus.');
    }
}
