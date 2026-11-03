<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Dormitory;
use App\Models\Security\SecurityGuestLog;
use App\Models\Security\SecurityPost;
use App\Models\Student;
use App\Services\Security\SecurityCodeService;
use App\Support\SecurityAccess;
use Illuminate\Http\Request;

class SecurityGuestController extends Controller
{
    public function __construct(private readonly SecurityCodeService $codes) {}

    public function index(Request $request, string $userId)
    {
        $query = SecurityGuestLog::query()->with(['post', 'recorder', 'dormitory']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('check_in_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('check_in_at', '<=', $to);
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('visitor_name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhere('id_number', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('institution', 'like', '%'.$search.'%');
            });
        }

        $guests = $query->latest('check_in_at')->paginate(20)->withQueryString();

        $stats = [
            'inside' => SecurityGuestLog::query()->inside()->count(),
            'today' => SecurityGuestLog::query()->whereDate('check_in_at', today())->count(),
            'out_today' => SecurityGuestLog::query()->whereDate('check_out_at', today())->count(),
            'total' => SecurityGuestLog::query()->whereDate('check_in_at', today())->count(),
        ];

        $posts = SecurityPost::query()->where('is_active', true)->orderBy('name')->get();
        $dormitories = Dormitory::query()->where('is_active', true)->orderBy('name')->get();
        $students = Student::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'nisn']);

        return view('security.guests.index', compact(
            'userId', 'guests', 'stats', 'posts', 'dormitories', 'students'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $data = $this->validated($request);

        $postId = $data['post_id'] ?? SecurityAccess::postIdFor($request->user())
            ?? SecurityPost::query()->where('is_active', true)->value('id');

        $guest = SecurityGuestLog::create([
            'post_id' => $postId,
            'dormitory_id' => $data['dormitory_id'] ?? null,
            'student_id' => $data['student_id'] ?? null,
            'code' => $this->codes->guest(),
            'visitor_name' => $data['visitor_name'],
            'id_type' => $data['id_type'] ?? 'ktp',
            'id_number' => $data['id_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'origin' => $data['origin'] ?? null,
            'institution' => $data['institution'] ?? null,
            'purpose' => $data['purpose'],
            'person_to_meet' => $data['person_to_meet'] ?? null,
            'relationship' => $data['relationship'] ?? null,
            'visitor_count' => $data['visitor_count'] ?? 1,
            'vehicle_plate' => $data['vehicle_plate'] ?? null,
            'card_number' => $data['card_number'] ?? null,
            'check_in_at' => now(),
            'status' => SecurityGuestLog::STATUS_INSIDE,
            'notes' => $data['notes'] ?? null,
            'recorded_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('user.keamanan.guests.index', ['userId' => $userId])
            ->with('success', 'Tamu '.$guest->visitor_name.' tercatat masuk ('.$guest->code.').');
    }

    public function show(Request $request, string $userId, string $guest)
    {
        $guest = SecurityGuestLog::query()
            ->with(['post', 'recorder', 'dormitory', 'student'])
            ->findOrFail($guest);

        return view('security.guests.show', compact('userId', 'guest'));
    }

    public function checkout(Request $request, string $userId, string $guest)
    {
        $guest = SecurityGuestLog::query()->findOrFail($guest);

        if (! $guest->isInside()) {
            return back()->with('error', 'Tamu ini sudah tercatat keluar.');
        }

        $guest->update([
            'check_out_at' => now(),
            'status' => SecurityGuestLog::STATUS_OUT,
            'checked_out_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('user.keamanan.guests.index', ['userId' => $userId])
            ->with('success', 'Tamu '.$guest->visitor_name.' tercatat keluar.');
    }

    public function card(Request $request, string $userId, string $guest)
    {
        $guest = SecurityGuestLog::query()->with(['post'])->findOrFail($guest);

        if (! $guest->badge_printed_at) {
            $guest->forceFill(['badge_printed_at' => now()])->save();
        }

        return view('security.guests.card', compact('userId', 'guest'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'visitor_name' => ['required', 'string', 'max:150'],
            'id_type' => ['nullable', 'in:ktp,sim,kartu_pelajar,paspor,lainnya'],
            'id_number' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'origin' => ['nullable', 'string', 'max:150'],
            'institution' => ['nullable', 'string', 'max:150'],
            'purpose' => ['required', 'string', 'max:191'],
            'person_to_meet' => ['nullable', 'string', 'max:150'],
            'relationship' => ['nullable', 'string', 'max:60'],
            'visitor_count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
            'card_number' => ['nullable', 'string', 'max:30'],
            'post_id' => ['nullable', 'uuid', 'exists:security_posts,id'],
            'dormitory_id' => ['nullable', 'uuid', 'exists:dormitories,id'],
            'student_id' => ['nullable', 'uuid', 'exists:students,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'visitor_name.required' => 'Nama tamu wajib diisi.',
            'purpose.required' => 'Keperluan kunjungan wajib diisi.',
        ]);

        return $data;
    }
}
