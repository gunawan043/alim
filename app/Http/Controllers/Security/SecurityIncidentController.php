<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Security\SecurityIncident;
use App\Models\Security\SecurityIncidentAction;
use App\Models\Security\SecurityIncidentType;
use App\Models\Security\SecurityPost;
use App\Models\Security\SecurityZone;
use App\Services\Security\SecurityCodeService;
use App\Support\SecurityAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecurityIncidentController extends Controller
{
    public function __construct(private readonly SecurityCodeService $codes) {}

    public function index(Request $request, string $userId)
    {
        $query = SecurityIncident::query()->with(['type', 'post', 'zone', 'reporter', 'assignee']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($severity = $request->query('severity')) {
            $query->where('severity', $severity);
        }

        if ($typeId = $request->query('type_id')) {
            $query->where('incident_type_id', $typeId);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('occurred_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('occurred_at', '<=', $to);
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhere('location', 'like', '%'.$search.'%');
            });
        }

        $incidents = $query->latest('occurred_at')->paginate(20)->withQueryString();

        $stats = [
            'open' => SecurityIncident::query()->open()->count(),
            'new' => SecurityIncident::query()->where('status', SecurityIncident::STATUS_NEW)->count(),
            'investigating' => SecurityIncident::query()->where('status', SecurityIncident::STATUS_INVESTIGATING)->count(),
            'closed_month' => SecurityIncident::query()
                ->where('status', SecurityIncident::STATUS_CLOSED)
                ->whereMonth('closed_at', now()->month)
                ->whereYear('closed_at', now()->year)
                ->count(),
        ];

        $types = SecurityIncidentType::query()->where('is_active', true)->orderBy('name')->get();

        return view('security.incidents.index', compact('userId', 'incidents', 'stats', 'types'));
    }

    public function create(Request $request, string $userId)
    {
        $types = SecurityIncidentType::query()->where('is_active', true)->orderBy('name')->get();
        $posts = SecurityPost::query()->where('is_active', true)->orderBy('name')->get();
        $zones = SecurityZone::query()->where('is_active', true)->orderBy('name')->get();

        return view('security.incidents.create', compact('userId', 'types', 'posts', 'zones'));
    }

    public function store(Request $request, string $userId)
    {
        $data = $request->validate([
            'incident_type_id' => ['nullable', 'uuid', 'exists:security_incident_types,id'],
            'post_id' => ['nullable', 'uuid', 'exists:security_posts,id'],
            'zone_id' => ['nullable', 'uuid', 'exists:security_zones,id'],
            'title' => ['required', 'string', 'max:191'],
            'description' => ['required', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:191'],
            'occurred_at' => ['required', 'date'],
            'severity' => ['required', 'in:ringan,sedang,berat,kritis'],
        ], [
            'title.required' => 'Judul insiden wajib diisi.',
            'description.required' => 'Uraian kejadian wajib diisi.',
            'occurred_at.required' => 'Waktu kejadian wajib diisi.',
        ]);

        $incident = SecurityIncident::create([
            'incident_type_id' => $data['incident_type_id'] ?? null,
            'post_id' => $data['post_id'] ?? SecurityAccess::postIdFor($request->user()),
            'zone_id' => $data['zone_id'] ?? null,
            'reported_by' => $request->user()->id,
            'code' => $this->codes->incident(),
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => $data['location'] ?? null,
            'occurred_at' => $data['occurred_at'],
            'severity' => $data['severity'],
            'status' => SecurityIncident::STATUS_NEW,
        ]);

        SecurityIncidentAction::create([
            'incident_id' => $incident->id,
            'actor_id' => $request->user()->id,
            'action' => 'Laporan dibuat',
            'notes' => 'Insiden dicatat oleh '.$request->user()->name.'.',
        ]);

        return redirect()
            ->route('user.keamanan.incidents.show', ['userId' => $userId, 'incident' => $incident->id])
            ->with('success', 'Insiden '.$incident->code.' tercatat.');
    }

    public function show(Request $request, string $userId, string $incident)
    {
        $incident = SecurityIncident::query()
            ->with([
                'type', 'post', 'zone', 'reporter', 'assignee', 'closer',
                'actions.actor',
            ])
            ->findOrFail($incident);

        $canManage = SecurityAccess::canManage($request->user());

        return view('security.incidents.show', compact('userId', 'incident', 'canManage'));
    }

    public function action(Request $request, string $userId, string $incident)
    {
        $incident = SecurityIncident::query()->findOrFail($incident);

        $data = $request->validate([
            'action' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'in:baru,diproses,investigasi'],
        ], [
            'action.required' => 'Tindakan wajib diisi.',
        ]);

        if ($incident->isClosed()) {
            return back()->with('error', 'Insiden sudah ditutup.');
        }

        DB::transaction(function () use ($data, $incident, $request) {
            SecurityIncidentAction::create([
                'incident_id' => $incident->id,
                'actor_id' => $request->user()->id,
                'action' => $data['action'],
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['status'])) {
                $incident->status = $data['status'];
            }

            if (! $incident->assigned_to) {
                $incident->assigned_to = $request->user()->id;
            }

            $incident->save();
        });

        return back()->with('success', 'Tindak lanjut dicatat.');
    }

    public function close(Request $request, string $userId, string $incident)
    {
        $incident = SecurityIncident::query()->findOrFail($incident);

        if (! SecurityAccess::canManage($request->user())) {
            abort(403, 'Hanya Kepala/Koordinator Satuan Keamanan yang dapat menutup insiden.');
        }

        $data = $request->validate([
            'resolution' => ['required', 'string', 'max:2000'],
        ], [
            'resolution.required' => 'Uraian penyelesaian wajib diisi.',
        ]);

        DB::transaction(function () use ($data, $incident, $request) {
            $incident->update([
                'status' => SecurityIncident::STATUS_CLOSED,
                'resolution' => $data['resolution'],
                'closed_at' => now(),
                'closed_by' => $request->user()->id,
            ]);

            SecurityIncidentAction::create([
                'incident_id' => $incident->id,
                'actor_id' => $request->user()->id,
                'action' => 'Insiden ditutup',
                'notes' => $data['resolution'],
            ]);
        });

        return back()->with('success', 'Insiden ditutup.');
    }
}
