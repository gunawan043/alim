<?php

namespace App\Http\Controllers;

use App\Models\HumasSatisfactionResponse;
use App\Models\HumasSatisfactionSurvey;
use App\Models\User;
use App\Models\WaliSantri;
use App\Services\NotificationBroadcastService;
use Illuminate\Http\Request;

class HumasSatisfactionController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $this->authorizeHumas($request);
        $surveys = HumasSatisfactionSurvey::withCount('responses')->latest()->paginate(20);
        $stats = [
            'total' => HumasSatisfactionSurvey::count(),
            'published' => HumasSatisfactionSurvey::where('status', 'published')->count(),
            'responses' => HumasSatisfactionResponse::count(),
            'average' => round(HumasSatisfactionResponse::avg('rating') ?? 0, 2),
        ];

        return view('humas.satisfaction.index', compact('userId', 'surveys', 'stats'));
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeHumas($request);
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'audience' => 'required|in:all,wali,gtk,santri',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);
        HumasSatisfactionSurvey::create($validated + [
            'school_id' => $request->attributes->get('schoolContextId'),
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Survei disimpan sebagai draft.');
    }

    public function publish(Request $request, string $userId, string $id)
    {
        $this->authorizeHumas($request);
        $survey = HumasSatisfactionSurvey::findOrFail($id);
        $survey->update(['status' => 'published', 'starts_at' => $survey->starts_at ?? now()]);
        $this->notifyAudience($survey, $request->user());

        return back()->with('success', 'Survei dipublikasikan.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $this->authorizeHumas($request);
        HumasSatisfactionSurvey::findOrFail($id)->delete();

        return back()->with('success', 'Survei dihapus.');
    }

    public function feed(Request $request, string $userId)
    {
        $surveys = HumasSatisfactionSurvey::active()
            ->whereIn('audience', ['all', 'gtk'])
            ->with(['responses' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->latest()->get();

        return view('humas.satisfaction.feed', compact('userId', 'surveys'));
    }

    public function submit(Request $request, string $userId, string $id)
    {
        $survey = HumasSatisfactionSurvey::active()->findOrFail($id);
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:2000',
        ]);
        HumasSatisfactionResponse::updateOrCreate(
            ['humas_satisfaction_survey_id' => $survey->id, 'user_id' => $request->user()->id],
            ['rating' => $validated['rating'], 'feedback' => $validated['feedback'] ?? null, 'submitted_at' => now()]
        );

        return back()->with('success', 'Terima kasih, penilaian Anda sudah tersimpan.');
    }

    private function authorizeHumas(Request $request): void
    {
        $user = $request->user();
        abort_unless($user->hasRole('Humas Personalia') || $user->isSystemAdmin() || $user->isSuperAdmin(), 403);
    }

    private function notifyAudience(HumasSatisfactionSurvey $survey, User $publisher): void
    {
        $service = app(NotificationBroadcastService::class);

        if (in_array($survey->audience, ['all', 'gtk'], true)) {
            $gtkIds = User::where('is_active', true)->whereHas('employment')->pluck('id');

            foreach ($gtkIds->reject(fn ($id) => (string) $id === (string) $publisher->id) as $recipientId) {
                $service->send((string) $recipientId, [
                    'module' => 'system',
                    'type' => 'info',
                    'priority' => 'medium',
                    'title' => 'Survei kepuasan baru',
                    'message' => $survey->title.'. Mohon luangkan waktu untuk menilai layanan kami.',
                    'action_url' => route('user.satisfaction.index', ['userId' => $recipientId]),
                    'reference_type' => HumasSatisfactionSurvey::class,
                    'reference_id' => $survey->id,
                    'action_text' => 'Isi Survei',
                ]);
            }
        }

        if (in_array($survey->audience, ['all', 'santri'], true)) {
            $studentUserIds = User::where('is_active', true)
                ->whereHas('studentProfile')
                ->pluck('id');

            foreach ($studentUserIds as $recipientId) {
                if ((string) $recipientId === (string) $publisher->id) {
                    continue;
                }

                $service->send((string) $recipientId, [
                    'module' => 'system',
                    'type' => 'info',
                    'priority' => 'medium',
                    'title' => 'Survei kepuasan baru',
                    'message' => $survey->title.'. Mohon luangkan waktu untuk menilai layanan kami.',
                    'reference_type' => HumasSatisfactionSurvey::class,
                    'reference_id' => $survey->id,
                    'action_text' => 'Isi Survei',
                    'data' => [
                        'survey_id' => $survey->id,
                        'school_id' => $survey->school_id,
                        'audience' => 'santri',
                        'scope' => $survey->school_id ? 'school' : 'global',
                    ],
                ]);
            }
        }

        if (! in_array($survey->audience, ['all', 'wali'], true)) {
            return;
        }

        $links = WaliSantri::query()
            ->where('status', WaliSantri::STATUS_ACTIVE)
            ->whereNotNull('user_id')
            ->when($survey->school_id, fn ($query) => $query->where('school_id', $survey->school_id))
            ->get()
            ->unique('user_id');

        foreach ($links as $link) {
            $token = $link->access_token;
            $service->send((string) $link->user_id, [
                'module' => 'system',
                'type' => 'info',
                'priority' => 'medium',
                'title' => 'Survei kepuasan baru',
                'message' => $survey->title.'. Mohon luangkan waktu untuk menilai layanan kami.',
                'action_url' => $token
                    ? route('portal.satisfaction.index', ['token' => $token])
                    : route('user.satisfaction.index', ['userId' => $link->user_id]),
                'reference_type' => HumasSatisfactionSurvey::class,
                'reference_id' => $survey->id,
                'action_text' => 'Isi Survei',
                'data' => [
                    'survey_id' => $survey->id,
                    'school_id' => $survey->school_id,
                    'scope' => $survey->school_id ? 'school' : 'global',
                ],
            ]);
        }
    }
}
