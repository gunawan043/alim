<?php

namespace App\Http\Controllers;

use App\Models\AppFeedbackAttachment;
use App\Models\AppFeedbackReport;
use App\Models\AppFeedbackUpdate;
use App\Models\GtkWorkUnit;
use App\Models\User;
use App\Services\NotificationBroadcastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AppFeedbackController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $user = $request->user();
        $canManage = $this->canManage($user);

        $scope = AppFeedbackReport::query()
            ->when(! $canManage, fn ($q) => $q->where('submitted_by', $user?->id));

        $filtered = (clone $scope)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->priority))
            ->when($request->filled('division'), fn ($q) => $q->where('division', $request->division))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->q);
                $q->where(function ($qq) use ($term) {
                    $qq->where('title', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('process', 'like', "%{$term}%");
                });
            });

        $reports = $filtered
            ->with(['submitter:id,name', 'school:id,name', 'workUnit:id,name', 'assignee:id,name'])
            ->withCount('attachments')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => (clone $scope)->count(),
            'baru' => (clone $scope)->where('status', 'baru')->count(),
            'diproses' => (clone $scope)->whereIn('status', ['diproses', 'menunggu_informasi'])->count(),
            'selesai' => (clone $scope)->where('status', 'selesai')->count(),
        ];

        $divisions = (clone $scope)
            ->whereNotNull('division')
            ->where('division', '!=', '')
            ->distinct()
            ->orderBy('division')
            ->pluck('division');

        return view('feedback.index', compact('userId', 'reports', 'stats', 'divisions', 'canManage'));
    }

    public function create(Request $request, string $userId)
    {
        return view('feedback.create', compact('userId'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'category' => ['required', Rule::in(array_keys(AppFeedbackReport::CATEGORIES))],
            'priority' => ['required', Rule::in(array_keys(AppFeedbackReport::PRIORITIES))],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'process' => ['nullable', 'string', 'max:10000', 'required_if:category,bug'],
            'expected' => ['nullable', 'string', 'max:5000'],
            'page_url' => ['nullable', 'string', 'max:2048'],
            'evidence' => ['nullable', 'array', 'max:5'],
            'evidence.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf,txt,log,doc,docx,xls,xlsx,zip'],
        ], [
            'process.required_if' => 'Kronologi proses wajib diisi untuk laporan bug agar masalah dapat direproduksi.',
        ]);

        $user = $request->user();
        $employment = $user?->employment;
        $workUnit = $user
            ? GtkWorkUnit::with('workUnit')
                ->where('user_id', $user->id)
                ->orderByDesc('is_primary')
                ->first()
            : null;

        $schoolId = $request->attributes->get('schoolContextId') ?: $employment?->school_id;
        $division = $workUnit?->workUnit?->name
            ?: $employment?->jabatan
            ?: $user?->roles?->pluck('name')->first();

        $report = DB::transaction(function () use ($validated, $user, $schoolId, $workUnit, $division, $employment) {
            $report = AppFeedbackReport::create([
                'school_id' => $schoolId,
                'work_unit_id' => $workUnit?->work_unit_id,
                'submitted_by' => $user?->id,
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'status' => 'baru',
                'title' => $validated['title'],
                'description' => $validated['description'],
                'process' => $validated['process'] ?? null,
                'expected' => $validated['expected'] ?? null,
                'page_url' => $validated['page_url'] ?? null,
                'division' => $division,
                'jabatan' => $employment?->jabatan,
            ]);

            AppFeedbackUpdate::create([
                'app_feedback_report_id' => $report->id,
                'user_id' => $user?->id,
                'type' => 'created',
                'status_to' => 'baru',
                'message' => 'Laporan dibuat dan menunggu penanganan.',
            ]);

            return $report;
        });

        $this->storeEvidence($request, $report, $user);
        $this->notifyManagers($report, $user);

        return redirect()
            ->route('user.feedback.show', ['userId' => $userId, 'id' => $report->id])
            ->with('success', 'Laporan berhasil dikirim. Tim pengelola akan menindaklanjuti.');
    }

    public function show(Request $request, string $userId, string $id)
    {
        $report = AppFeedbackReport::with([
            'school:id,name',
            'workUnit:id,name',
            'submitter:id,name',
            'assignee:id,name',
            'updates.user:id,name',
            'attachments.uploader:id,name',
        ])->findOrFail($id);

        $this->authorizeView($request, $report);

        return view('feedback.show', [
            'userId' => $userId,
            'report' => $report,
            'canManage' => $this->canManage($request->user()),
        ]);
    }

    public function updateStatus(Request $request, string $userId, string $id)
    {
        $report = AppFeedbackReport::findOrFail($id);
        $this->authorizeManage($request, $report);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(AppFeedbackReport::STATUSES))],
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $previousStatus = $report->status;

        DB::transaction(function () use ($report, $validated, $request, $previousStatus) {
            $report->forceFill([
                'status' => $validated['status'],
                'assigned_to' => $report->assigned_to ?: $request->user()?->id,
                'resolved_at' => $validated['status'] === 'selesai' ? now() : null,
            ])->save();

            AppFeedbackUpdate::create([
                'app_feedback_report_id' => $report->id,
                'user_id' => $request->user()?->id,
                'type' => 'status',
                'status_from' => $previousStatus,
                'status_to' => $validated['status'],
                'message' => $validated['note'],
            ]);
        });

        $this->notifyReporter($report, $previousStatus);

        return back()->with('success', 'Status dan keterangan proses berhasil diperbarui.');
    }

    public function reply(Request $request, string $userId, string $id)
    {
        $report = AppFeedbackReport::findOrFail($id);
        $this->authorizeView($request, $report);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();
        $update = AppFeedbackUpdate::create([
            'app_feedback_report_id' => $report->id,
            'user_id' => $user?->id,
            'type' => 'comment',
            'message' => trim($validated['message']),
        ]);

        $this->notifyDiscussion($report, $user);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $update->message,
                'user' => $user?->name,
                'user_id' => $user?->id,
                'created_at' => $update->created_at?->toIso8601String(),
            ]);
        }

        return back()->with('success', 'Pesan diskusi ditambahkan.');
    }

    public function attachment(Request $request, string $userId, string $id, string $attachmentId)
    {
        $report = AppFeedbackReport::findOrFail($id);
        $this->authorizeView($request, $report);

        $attachment = AppFeedbackAttachment::where('app_feedback_report_id', $report->id)
            ->whereKey($attachmentId)
            ->firstOrFail();

        abort_unless(Storage::disk('local')->exists($attachment->path), 404, 'File bukti tidak ditemukan.');

        if ($attachment->isImage()) {
            return Storage::disk('local')->response($attachment->path, $attachment->original_name, [
                'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            ]);
        }

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    private function storeEvidence(Request $request, AppFeedbackReport $report, ?User $user): void
    {
        $files = $request->file('evidence', []);
        if (! is_array($files)) {
            return;
        }

        foreach ($files as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            try {
                $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
                $filename = Str::uuid()->toString().'.'.$extension;
                $path = $file->storeAs('feedback/'.$report->id, $filename, 'local');

                AppFeedbackAttachment::create([
                    'app_feedback_report_id' => $report->id,
                    'uploaded_by' => $user?->id,
                    'path' => $path,
                    'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize() ?: 0,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Gagal menyimpan bukti laporan aplikasi: '.$e->getMessage());
            }
        }
    }

    private function notifyManagers(AppFeedbackReport $report, ?User $reporter): void
    {
        try {
            $managerIds = User::query()
                ->where('is_active', true)
                ->where('id', '!=', $reporter?->id)
                ->where(function ($q) {
                    $q->where('is_system_admin', true)
                        ->orWhereHas('roles', fn ($r) => $r->whereIn('name', ['Super Admin', 'Pimpinan', 'Teknologi Informasi']));
                })
                ->pluck('id');

            $service = app(NotificationBroadcastService::class);
            foreach ($managerIds as $managerId) {
                $service->send((string) $managerId, [
                    'module' => 'system',
                    'type' => 'warning',
                    'priority' => 'high',
                    'title' => 'Laporan aplikasi baru',
                    'message' => ($reporter?->name ?? 'Pengguna').' mengirim '.$report->categoryLabel().': '.$report->title,
                    'action_url' => route('user.feedback.show', ['userId' => $managerId, 'id' => $report->id]),
                    'reference_type' => AppFeedbackReport::class,
                    'reference_id' => $report->id,
                    'action_text' => 'Lihat Laporan',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi laporan aplikasi: '.$e->getMessage());
        }
    }

    private function notifyDiscussion(AppFeedbackReport $report, ?User $sender): void
    {
        $recipientId = null;

        if ((string) $report->submitted_by === (string) $sender?->id) {
            $recipientId = $report->assigned_to;
        } else {
            $recipientId = $report->submitted_by;
        }

        if (! $recipientId) {
            return;
        }

        try {
            app(NotificationBroadcastService::class)->send((string) $recipientId, [
                'module' => 'system',
                'type' => 'info',
                'priority' => 'medium',
                'title' => 'Pesan baru pada laporan aplikasi',
                'message' => ($sender?->name ?? 'Pengguna').' membalas laporan: '.$report->title,
                'action_url' => route('user.ai.index', ['userId' => $recipientId, 'feedback' => $report->id]),
                'reference_type' => AppFeedbackReport::class,
                'reference_id' => $report->id,
                'action_text' => 'Buka Diskusi',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi diskusi laporan: '.$e->getMessage());
        }
    }

    private function notifyReporter(AppFeedbackReport $report, string $previousStatus): void
    {
        if (! $report->submitted_by) {
            return;
        }

        try {
            app(NotificationBroadcastService::class)->send((string) $report->submitted_by, [
                'module' => 'system',
                'type' => 'info',
                'priority' => 'medium',
                'title' => 'Laporan aplikasi diperbarui',
                'message' => $report->title.' — status: '.$report->statusLabel().'.',
                'action_url' => route('user.feedback.show', ['userId' => $report->submitted_by, 'id' => $report->id]),
                'reference_type' => AppFeedbackReport::class,
                'reference_id' => $report->id,
                'action_text' => 'Lihat Laporan',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi pembaruan laporan: '.$e->getMessage());
        }
    }

    private function canManage(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin()) {
            return true;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        try {
            return $user->hasRole('Pimpinan') || $user->hasRole('Teknologi Informasi');
        } catch (\Throwable) {
            return false;
        }
    }

    private function authorizeView(Request $request, AppFeedbackReport $report): void
    {
        $user = $request->user();
        abort_unless(
            $this->canManage($user) || (string) $report->submitted_by === (string) $user?->id,
            403,
            'Anda tidak berwenang melihat laporan ini.'
        );
    }

    private function authorizeManage(Request $request, AppFeedbackReport $report): void
    {
        abort_unless($this->canManage($request->user()), 403, 'Hanya pengelola laporan yang dapat menindaklanjuti laporan ini.');
    }
}
