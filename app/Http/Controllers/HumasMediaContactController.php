<?php

namespace App\Http\Controllers;

use App\Models\HumasMediaContact;
use Illuminate\Http\Request;

class HumasMediaContactController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $this->authorizeHumas($request);
        $contacts = HumasMediaContact::with('creator:id,name')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s->where('name', 'like', '%'.$request->search.'%')->orWhere('institution', 'like', '%'.$request->search.'%')))
            ->latest()->paginate(20)->withQueryString();
        $stats = [
            'total' => HumasMediaContact::count(),
            'media' => HumasMediaContact::where('type', 'media')->count(),
            'instansi' => HumasMediaContact::where('type', 'instansi')->count(),
        ];

        return view('humas.media-contacts.index', compact('userId', 'contacts', 'stats'));
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeHumas($request);
        $validated = $this->validateContact($request);
        HumasMediaContact::create($validated + [
            'school_id' => $request->attributes->get('schoolContextId'),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Kontak media/instansi ditambahkan.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        $this->authorizeHumas($request);
        $contact = HumasMediaContact::findOrFail($id);
        $contact->update($this->validateContact($request));

        return back()->with('success', 'Kontak diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $this->authorizeHumas($request);
        HumasMediaContact::findOrFail($id)->delete();

        return back()->with('success', 'Kontak dihapus.');
    }

    private function validateContact(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'institution' => 'nullable|string|max:150',
            'type' => 'required|in:media,instansi',
            'contact_person' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
        ]);
    }

    private function authorizeHumas(Request $request): void
    {
        $user = $request->user();
        abort_unless($user->hasRole('Humas Personalia') || $user->isSystemAdmin() || $user->isSuperAdmin(), 403);
    }
}
