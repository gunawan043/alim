<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\SidebarAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SidebarAccessController extends Controller
{
    /**
     * Menu default yang otomatis diregistrasi saat tabel masih kosong,
     * supaya halaman hak akses langsung bisa dipakai.
     */
    public const DEFAULT_MENUS = [
        // Modul lintas-role
        'dashboard'         => 'Dashboard Role',
        'kesiswaan'         => 'Menu Kesiswaan',
        'ekstrakurikuler'   => 'Menu Ekstrakurikuler',
        'sarpras'           => 'Laboratorium & Sarpras',
        'gtk'               => 'Menu GTK & Kepegawaian',
        'akademik'          => 'Menu Akademik / KBM',
        'keuangan'          => 'Menu Keuangan (role)',
        'laporan'           => 'Menu Laporan (role)',
        'approval'          => 'Menu Approval Center',

        // Modul inti per role
        'asrama'            => 'Menu Asrama',
        'uks'               => 'Menu UKS',
        'tahfidz'           => 'Menu Tahfidz',
        'bahasa'            => 'Menu Bahasa',
        'perpustakaan'      => 'Menu Perpustakaan',
        'keamanan'          => 'Menu Satuan Keamanan',
        'humas'             => 'Menu Humas & Personalia',
        'rumah-tangga'      => 'Menu Unit Rumah Tangga',
        'teknologi-informasi' => 'Menu Teknologi Informasi',
        'gizi'              => 'Menu Pelayanan Gizi',

        // Section pelengkap (laporan/referensi/administrasi) per role
        'asrama-pelengkap'            => 'Asrama — Laporan & Administrasi',
        'uks-pelengkap'               => 'UKS — Laporan & Referensi',
        'tahfidz-pelengkap'           => 'Tahfidz — Laporan & Administrasi',
        'bahasa-pelengkap'            => 'Bahasa — Laporan & Referensi',
        'perpustakaan-pelengkap'      => 'Perpustakaan — Laporan & Administrasi',
        'keamanan-pelengkap'          => 'Keamanan — Laporan & Administrasi',
        'humas-pelengkap'             => 'Humas Personalia — Laporan & Administrasi',
        'rumah-tangga-pelengkap'      => 'URT — Laporan & Administrasi',
        'teknologi-informasi-pelengkap' => 'TI — Referensi & Administrasi',
        'gizi-pelengkap'              => 'Gizi — Laporan & Logistik',
        'keuangan-pelengkap'          => 'Keuangan — Laporan & Referensi',

        // Menu tugas tambahan
        'tugas-wali-kelas'            => 'Tugas: Wali Kelas',
        'tugas-wali-kamar'            => 'Tugas: Wali Kamar',
        'tugas-staf-asrama'           => 'Tugas: Staf Asrama',
        'tugas-staf-perizinan'        => 'Tugas: Staf Perizinan',
        'tugas-admin-uks-putra'       => 'Tugas: Admin UKS Putra',
        'tugas-admin-uks-putri'       => 'Tugas: Admin UKS Putri',
        'tugas-koordinator-guru-umum' => 'Tugas: Koordinator Guru Umum',
        'tugas-koordinator-guru-agama' => 'Tugas: Koordinator Guru Agama',
        'tugas-koordinator-guru-hadits' => 'Tugas: Koordinator Guru Hadits',
        'tugas-koordinator-guru-bahasa-arab' => 'Tugas: Koordinator Guru Bahasa Arab',
        'tugas-koordinator-guru-tahfidz' => 'Tugas: Koordinator Guru Tahfidz',
        'tugas-koordinator-ekskul'    => 'Tugas: Koordinator Ekskul',
        'tugas-koordinator-kesiswaan' => 'Tugas: Koordinator Kesiswaan',
        'tugas-koordinator-kurikulum' => 'Tugas: Koordinator Kurikulum',
        'tugas-koordinator-lab'       => 'Tugas: Koordinator Laboratorium',
        'tugas-koordinator-sarpras'   => 'Tugas: Koordinator Sarpras',
        'tugas-pembina-ekskul'        => 'Tugas: Pembina Ekskul',
        'tugas-tim-kesiswaan'         => 'Tugas: Tim Kesiswaan',
    ];

    public function index(Request $request): View
    {
        // Registrasi menu default (idempotent) — key baru otomatis ditambahkan.
        foreach (self::DEFAULT_MENUS as $key => $label) {
            SidebarAccess::firstOrCreate(
                ['menu_key' => $key],
                ['display_name' => $label, 'allowed_roles' => []]
            );
        }

        $accesses = SidebarAccess::listAll();
        $roles = Role::where('guard_name', 'web')
            ->whereNotIn('name', ['Super Admin', 'System Admin'])
            ->orderBy('name')
            ->get();

        $userId = $request->route('userId');

        return view('super-admin.sidebar-access.index', compact('accesses', 'roles', 'userId'));
    }

    public function update(Request $request, string $userId, string $menuKey): RedirectResponse
    {
        $validated = $request->validate([
            'allowed_roles' => 'nullable|array',
            'allowed_roles.*' => 'exists:roles,name',
        ]);

        $access = SidebarAccess::where('menu_key', $menuKey)->firstOrFail();
        $access->assignRoles($validated['allowed_roles'] ?? []);

        return redirect()
            ->route('user.sa.sidebar-access.index', ['userId' => $userId])
            ->with('success', 'Hak akses menu "' . $access->display_name . '" berhasil diperbarui.');
    }

    public function store(Request $request, string $userId): RedirectResponse
    {
        $validated = $request->validate([
            'menu_key' => 'required|string|max:255|unique:sidebar_accesses,menu_key',
            'display_name' => 'required|string|max:255',
            'allowed_roles' => 'nullable|array',
            'allowed_roles.*' => 'exists:roles,name',
        ]);

        SidebarAccess::create([
            'menu_key' => $validated['menu_key'],
            'display_name' => $validated['display_name'],
            'allowed_roles' => $validated['allowed_roles'] ?? [],
        ]);

        return redirect()
            ->route('user.sa.sidebar-access.index', ['userId' => $userId])
            ->with('success', 'Menu "' . $validated['display_name'] . '" berhasil ditambahkan.');
    }

    public function destroy(string $userId, string $menuKey): RedirectResponse
    {
        $access = SidebarAccess::where('menu_key', $menuKey)->firstOrFail();
        $access->delete();

        return redirect()
            ->route('user.sa.sidebar-access.index', ['userId' => $userId])
            ->with('success', 'Menu "' . $access->display_name . '" berhasil dihapus.');
    }
}
