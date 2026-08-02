<?php

namespace App\Http\Controllers;

use App\Models\FacilityReferral;
use Illuminate\Http\Request;

class FacilityReferralController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = FacilityReferral::orderBy('facility_name');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($sq) => $sq
                ->where('facility_name', 'like', "%{$q}%")
                ->orWhere('address', 'like', "%{$q}%")
            );
        }

        if ($request->filled('facility_type')) {
            $query->where('facility_type', $request->facility_type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $facilities = $query->paginate(15)->withQueryString();

        return view('health.facility-referrals.index', compact('facilities', 'userId'));
    }

    public function create(Request $request, string $userId)
    {
        return view('health.facility-referrals.create', compact('userId'));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'facility_name' => 'required|string|max:191',
            'facility_type' => 'required|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:191',
            'distance_km' => 'nullable|numeric|min:0',
            'is_available_24h' => 'nullable|boolean',
            'services' => 'nullable|string',
            'operating_hours' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['school_id'] = $schoolId;
        $validated['is_available_24h'] = $request->boolean('is_available_24h');
        $validated['is_active'] = $request->boolean('is_active', true);

        FacilityReferral::create($validated);

        return redirect()
            ->route('user.uks.facility-referrals.index', ['userId' => $userId])
            ->with('success', 'Faskes rujukan berhasil disimpan.');
    }

    public function show(Request $request, string $userId, string $uuid)
    {
        $facility = FacilityReferral::findOrFail($uuid);

        return view('health.facility-referrals.show', compact('facility', 'userId'));
    }

    public function edit(Request $request, string $userId, string $uuid)
    {
        $facility = FacilityReferral::findOrFail($uuid);

        return view('health.facility-referrals.edit', compact('facility', 'userId'));
    }

    public function update(Request $request, string $userId, string $uuid)
    {
        $facility = FacilityReferral::findOrFail($uuid);

        $validated = $request->validate([
            'facility_name' => 'required|string|max:191',
            'facility_type' => 'required|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:191',
            'distance_km' => 'nullable|numeric|min:0',
            'is_available_24h' => 'nullable|boolean',
            'services' => 'nullable|string',
            'operating_hours' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_available_24h'] = $request->boolean('is_available_24h');
        $validated['is_active'] = $request->boolean('is_active', true);

        $facility->update($validated);

        return redirect()
            ->route('user.uks.facility-referrals.show', ['userId' => $userId, 'uuid' => $uuid])
            ->with('success', 'Faskes rujukan berhasil diperbarui.');
    }

    public function destroy(string $userId, string $uuid)
    {
        $facility = FacilityReferral::findOrFail($uuid);
        $facility->delete();

        return redirect()
            ->route('user.uks.facility-referrals.index', ['userId' => $userId])
            ->with('success', 'Faskes rujukan berhasil dihapus.');
    }
}
