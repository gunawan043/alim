<?php

namespace App\Http\Controllers;

use App\Models\HumasComplaint;
use App\Models\User;
use App\Services\NotificationBroadcastService;
use Illuminate\Http\Request;

class HumasComplaintController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $user = $request->user();
        $isHumas = $user->hasRole('Humas Personalia') || $user->isSystemAdmin() || $user->isSuperAdmin();
        $complaints = HumasComplaint::with(['submitter:id,name', 'handler:id,name'])
            ->when(! $isHumas, fn ($q) => $q->where('submitted_by', $user->id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()->paginate(20)->withQueryString();
        $stats = [
            'total' => HumasComplaint::when(! $isHumas, fn ($q) => $q->where('submitted_by', $user->id))->count(),
            'open' => HumasComplaint::when(! $isHumas, fn ($q) => $q->where('submitted_by', $user->id))->where('status', 'open')->count(),
            'resolved' => HumasComplaint::when(! $isHumas, fn ($q) => $q->where('submitted_by', $user->id))->where('status', 'resolved')->count(),
        ];

        return view('humas.complaints.index', compact('userId', 'complaints', 'stats', 'isHumas'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'category' => 'required|in:umum,akademik,asrama,keuangan,layanan',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:5000',
        ]);
        $user = $request->user();
        $complaint = HumasComplaint::create([
            'school_id' => $request->attributes->get('schoolContextId'),
            'submitted_by' => $user->id,
            'category' => $validated['category'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'open',
        ]);

        $service = app(NotificationBroadcastService::class);
        foreach (User::where('is_active', true)->role('Humas Personalia')->where('id', '!=', $user->id)->pluck('id') as $recipientId) {
            $service->send((string) $recipientId, [
                'module' => 'approval', 'type' => 'warning', 'priority' => 'high',
                'title' => 'Aduan baru dari wali/pegawai',
                'message' => $user->name.' mengirim aduan: '.$validated['subject'],
                'action_url' => route('user.humas.complaints.index', ['userId' => $recipientId]),
                'reference_type' => HumasComplaint::class, 'reference_id' => $complaint->id,
                'action_text' => 'Lihat Aduan',
            ]);
        }

        return back()->with('success', 'Aduan berhasil dikirim ke Humas.');
    }

    public function respond(Request $request, string $userId, string $id)
    {
        $user = $request->user();
        abort_unless($user->hasRole('Humas Personalia') || $user->isSystemAdmin() || $user->isSuperAdmin(), 403);
        $complaint = HumasComplaint::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,rejected',
            'response' => 'required|string|max:3000',
        ]);
        $complaint->update($validated + ['handled_by' => $user->id, 'handled_at' => now()]);

        if ($complaint->submitted_by) {
            app(NotificationBroadcastService::class)->send((string) $complaint->submitted_by, [
                'module' => 'approval', 'type' => 'info', 'priority' => 'medium',
                'title' => 'Aduan diperbarui',
                'message' => 'Aduan "'.$complaint->subject.'" diperbarui: '.str_replace('_', ' ', $validated['status']).'.',
                'action_url' => route('user.humas.complaints.index', ['userId' => $complaint->submitted_by]),
                'reference_type' => HumasComplaint::class, 'reference_id' => $complaint->id,
                'action_text' => 'Lihat Aduan',
            ]);
        }

        return back()->with('success', 'Aduan berhasil diperbarui.');
    }
}
