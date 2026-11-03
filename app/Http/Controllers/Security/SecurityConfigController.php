<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Security\SecurityContact;
use App\Models\Security\SecurityIncidentType;
use App\Models\Security\SecurityPost;
use App\Models\Security\SecurityShift;
use App\Models\Security\SecurityZone;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * SOP & Konfigurasi Satuan Keamanan.
 *
 * Satu halaman bertab: Pos Jaga, Shift, Zona Area, Jenis Insiden,
 * Kontak Darurat, dan SOP (tautan ke peraturan asrama yang sudah ada).
 */
class SecurityConfigController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $tab = (string) $request->query('tab', 'pos');

        $posts = SecurityPost::query()->with('zone')->orderBy('name')->get();
        $shifts = SecurityShift::query()->orderBy('start_time')->get();
        $zones = SecurityZone::query()->withCount('posts')->orderBy('name')->get();
        $types = SecurityIncidentType::query()->withCount('incidents')->orderBy('name')->get();
        $contacts = SecurityContact::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('security.config.index', compact(
            'userId', 'tab', 'posts', 'shifts', 'zones', 'types', 'contacts'
        ));
    }

    // ── POS JAGA ────────────────────────────────────────────────────────

    public function storePost(Request $request, string $userId)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:security_posts,code'],
            'name' => ['required', 'string', 'max:100'],
            'zone_id' => ['nullable', 'uuid', 'exists:security_zones,id'],
            'location' => ['nullable', 'string', 'max:191'],
            'phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        SecurityPost::create($data + ['is_active' => true]);

        return redirect()
            ->route('user.keamanan.config.index', ['userId' => $userId, 'tab' => 'pos'])
            ->with('success', 'Pos jaga ditambahkan.');
    }

    public function destroyPost(Request $request, string $userId, string $post)
    {
        SecurityPost::query()->findOrFail($post)->delete();

        return redirect()
            ->route('user.keamanan.config.index', ['userId' => $userId, 'tab' => 'pos'])
            ->with('success', 'Pos jaga dihapus.');
    }

    // ── SHIFT ───────────────────────────────────────────────────────────

    public function storeShift(Request $request, string $userId)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:security_shifts,code'],
            'name' => ['required', 'string', 'max:60'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        SecurityShift::create($data + ['is_active' => true]);

        return redirect()
            ->route('user.keamanan.config.index', ['userId' => $userId, 'tab' => 'shift'])
            ->with('success', 'Shift ditambahkan.');
    }

    public function destroyShift(Request $request, string $userId, string $shift)
    {
        SecurityShift::query()->findOrFail($shift)->delete();

        return redirect()
            ->route('user.keamanan.config.index', ['userId' => $userId, 'tab' => 'shift'])
            ->with('success', 'Shift dihapus.');
    }

    // ── ZONA ────────────────────────────────────────────────────────────

    public function storeZone(Request $request, string $userId)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:security_zones,code'],
            'name' => ['required', 'string', 'max:100'],
            'risk_level' => ['required', 'in:rendah,sedang,tinggi'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        SecurityZone::create($data + ['is_active' => true]);

        return redirect()
            ->route('user.keamanan.config.index', ['userId' => $userId, 'tab' => 'zona'])
            ->with('success', 'Zona area ditambahkan.');
    }

    public function destroyZone(Request $request, string $userId, string $zone)
    {
        SecurityZone::query()->findOrFail($zone)->delete();

        return redirect()
            ->route('user.keamanan.config.index', ['userId' => $userId, 'tab' => 'zona'])
            ->with('success', 'Zona area dihapus.');
    }

    // ── JENIS INSIDEN ───────────────────────────────────────────────────

    public function storeIncidentType(Request $request, string $userId)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('security_incident_types', 'code')],
            'name' => ['required', 'string', 'max:100'],
            'default_severity' => ['required', 'in:ringan,sedang,berat,kritis'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        SecurityIncidentType::create($data + ['is_active' => true]);

        return redirect()
            ->route('user.keamanan.config.index', ['userId' => $userId, 'tab' => 'jenis'])
            ->with('success', 'Jenis insiden ditambahkan.');
    }

    public function destroyIncidentType(Request $request, string $userId, string $type)
    {
        SecurityIncidentType::query()->findOrFail($type)->delete();

        return redirect()
            ->route('user.keamanan.config.index', ['userId' => $userId, 'tab' => 'jenis'])
            ->with('success', 'Jenis insiden dihapus.');
    }

    // ── KONTAK DARURAT ──────────────────────────────────────────────────

    public function storeContact(Request $request, string $userId)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'category' => ['required', 'in:internal,eksternal,lainnya'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        SecurityContact::create($data + ['is_active' => true]);

        return redirect()
            ->route('user.keamanan.config.index', ['userId' => $userId, 'tab' => 'kontak'])
            ->with('success', 'Kontak darurat ditambahkan.');
    }

    public function destroyContact(Request $request, string $userId, string $contact)
    {
        SecurityContact::query()->findOrFail($contact)->delete();

        return redirect()
            ->route('user.keamanan.config.index', ['userId' => $userId, 'tab' => 'kontak'])
            ->with('success', 'Kontak darurat dihapus.');
    }
}
