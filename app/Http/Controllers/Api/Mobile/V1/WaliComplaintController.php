<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\HumasComplaint;
use App\Models\User;
use App\Models\WaliSantri;
use App\Services\NotificationBroadcastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Kritik & saran (aduan) dari wali santri.
 *
 * Wali hanya dapat melihat aduan miliknya sendiri. Humas menerima notifikasi
 * dan membalas melalui panel Humas.
 */
class WaliComplaintController extends Controller
{
    private const CATEGORIES = ['umum', 'akademik', 'asrama', 'keuangan', 'layanan'];

    public function index(Request $request): JsonResponse
    {
        $query = HumasComplaint::with(['handler:id,name'])
            ->where('submitted_by', $request->user()->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->latest();

        $perPage = min(max($request->integer('per_page', 20), 1), 100);
        $paginator = $query->paginate($perPage)->through(fn (HumasComplaint $complaint) => $this->format($complaint));

        return response()->json([
            'success' => true,
            'data' => [
                'complaints' => $paginator->items(),
                'meta' => [
                    'page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        ]);
    }

    public function show(Request $request, string $userId, string $id): JsonResponse
    {
        $complaint = HumasComplaint::with(['handler:id,name'])
            ->where('submitted_by', $request->user()->id)
            ->find($id);

        if (! $complaint) {
            return $this->notFound();
        }

        return response()->json([
            'success' => true,
            'data' => $this->format($complaint, full: true),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['nullable', 'uuid', 'exists:students,id'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $link = WaliSantri::where('user_id', $request->user()->id)
            ->active()
            ->when($validated['student_id'] ?? null, fn ($q) => $q->where('student_id', $validated['student_id']))
            ->first();

        if (! $link) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'STUDENT_NOT_FOUND',
                    'message' => 'Santri tidak ditemukan atau bukan anak Anda.',
                ],
            ], 404);
        }

        $complaint = HumasComplaint::create([
            'school_id' => $link->school_id,
            'submitted_by' => $request->user()->id,
            'category' => $validated['category'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'open',
        ]);

        $service = app(NotificationBroadcastService::class);
        $humasIds = User::where('is_active', true)
            ->where(function ($query) {
                $query->whereHas('roles', fn ($roles) => $roles->where('name', 'Humas Personalia'))
                    ->orWhere('is_system_admin', true);
            })
            ->pluck('id');

        foreach ($humasIds as $recipientId) {
            if ((string) $recipientId === (string) $request->user()->id) {
                continue;
            }

            $service->send((string) $recipientId, [
                'module' => 'approval',
                'type' => 'warning',
                'priority' => 'high',
                'title' => 'Aduan baru dari wali santri',
                'message' => $request->user()->name.' mengirim aduan: '.$complaint->subject,
                'action_url' => route('user.humas.complaints.index', ['userId' => $recipientId]),
                'reference_type' => HumasComplaint::class,
                'reference_id' => $complaint->id,
                'action_text' => 'Lihat Aduan',
                'data' => [
                    'complaint_id' => $complaint->id,
                    'student_id' => $link->student_id,
                    'school_id' => $link->school_id,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kritik/saran berhasil dikirim ke Humas.',
            'data' => $this->format($complaint),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function format(HumasComplaint $complaint, bool $full = false): array
    {
        $data = [
            'id' => $complaint->id,
            'category' => $complaint->category,
            'subject' => $complaint->subject,
            'status' => $complaint->status,
            'created_at' => $complaint->created_at?->toIso8601String(),
            'updated_at' => $complaint->updated_at?->toIso8601String(),
        ];

        if ($full) {
            $data['message'] = $complaint->message;
            $data['response'] = $complaint->response;
            $data['handled_by'] = $complaint->handler?->name;
            $data['handled_at'] = $complaint->handled_at?->toIso8601String();
        }

        return $data;
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'COMPLAINT_NOT_FOUND',
                'message' => 'Aduan tidak ditemukan.',
            ],
        ], 404);
    }
}
