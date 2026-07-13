<?php

namespace App\Http\Controllers;

use App\Exports\GtkExport;
use App\Models\GtkAddress;
use App\Models\GtkContact;
use App\Models\GtkEducation;
use App\Models\GtkEmployment;
use App\Models\GtkFamilyMember;
use App\Models\GtkProfile;
use App\Models\GtkWorkUnit;
use App\Models\JenisGtk;
use App\Models\Province;
use App\Models\School;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class GtkWizardController extends Controller
{
    public function index(Request $request)
    {
        // Return JSON for AJAX search requests
        if ($request->ajax() || $request->wantsJson() || $request->has('format')) {
            $query = User::with(['gtkProfile', 'employment'])
                ->whereHas('employment');

            // Scoped filter: only GTK belonging to the user's school context
            $schoolId = $request->attributes->get('schoolContextId');
            if ($schoolId) {
                $query->whereHas('employment', fn ($q) => $q->where('school_id', $schoolId));
            }

            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhereHas('gtkProfile', fn ($q) => $q->where('nik', 'like', "%{$s}%"))
                        ->orWhereHas('employment', fn ($q) => $q->where('nupy', 'like', "%{$s}%"));
                });
            }

            $gtks = $query->limit(20)->get(['id', 'name', 'email']);

            return response()->json([
                'success' => true,
                'data' => $gtks->map(fn ($gtk) => [
                    'id' => $gtk->id,
                    'name' => $gtk->name,
                    'email' => $gtk->email,
                    'nupy' => $gtk->employment?->nupy,
                    'jenis_gtk' => $gtk->employment?->jenis_gtk,
                    'jabatan' => $gtk->employment?->jabatan,
                    'jenis_kelamin' => $gtk->gtkProfile?->jenis_kelamin,
                    'tempat_lahir' => $gtk->gtkProfile?->tempat_lahir,
                    'tanggal_lahir' => $gtk->gtkProfile?->tanggal_lahir,
                ]),
            ]);
        }

        $query = User::with([
            'gtkProfile',
            'employment',
            'gtkContact',
            'gtkWorkUnits.workUnit',
            'gtkProfile.addresses',
            'educations',
        ])->whereHas('employment');

        $this->applyFilters($query, $request);

        // Scoped filter: only GTK belonging to the user's school context
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->whereHas('employment', fn ($q) => $q->where('school_id', $schoolId));
        }

        $orderBy = $request->get('order_by', 'created_at');
        $orderDir = $request->get('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        $perPage = $request->get('per_page', 10);
        $gtkList = $query->paginate($perPage)->withQueryString();
        $workUnits = WorkUnit::where('is_active', true)->orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();
        $statistics = $this->getStatistics();

        $satuanKerja = $request->filled('satuan_kerja')
            ? WorkUnit::find($request->satuan_kerja)
            : null;

        return view('gtk.index', compact(
            'gtkList', 'workUnits', 'provinces', 'statistics', 'satuanKerja'
        ));
    }

    public function indextendik(Request $request)
    {
        // Return JSON for AJAX search requests
        if ($request->ajax() || $request->wantsJson() || $request->has('format')) {
            $query = User::with(['gtkProfile', 'employment'])
                ->whereHas('employment', function ($q) {
                    // Tendik = BUKAN Tenaga Pendidik Pondok / Guru
                    $q->whereNotIn('jenis_gtk', ['Tenaga Pendidik Pondok', 'Guru']);
                });

            // Scoped filter: only GTK belonging to the user's school context
            $schoolId = $request->attributes->get('schoolContextId');
            if ($schoolId) {
                $query->whereHas('employment', fn ($q) => $q->where('school_id', $schoolId));
            }

            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhereHas('gtkProfile', fn ($q) => $q->where('nik', 'like', "%{$s}%"))
                        ->orWhereHas('employment', fn ($q) => $q->where('nupy', 'like', "%{$s}%"));
                });
            }

            $gtks = $query->limit(20)->get(['id', 'name', 'email']);

            return response()->json([
                'success' => true,
                'data' => $gtks->map(fn ($gtk) => [
                    'id' => $gtk->id,
                    'name' => $gtk->name,
                    'email' => $gtk->email,
                    'nupy' => $gtk->employment?->nupy,
                    'jenis_gtk' => $gtk->employment?->jenis_gtk,
                    'jabatan' => $gtk->employment?->jabatan,
                    'jenis_kelamin' => $gtk->gtkProfile?->jenis_kelamin,
                    'tempat_lahir' => $gtk->gtkProfile?->tempat_lahir,
                    'tanggal_lahir' => $gtk->gtkProfile?->tanggal_lahir,
                ]),
            ]);
        }

        $query = User::with([
            'gtkProfile',
            'employment',
            'gtkContact',
            'gtkWorkUnits.workUnit',
            'gtkProfile.addresses',
            'educations',
        ])->whereHas('employment', function ($q) {
            // Tendik = BUKAN Tenaga Pendidik Pondok / Guru
            $q->whereNotIn('jenis_gtk', ['Tenaga Pendidik Pondok', 'Guru']);
        });

        $this->applyFilters($query, $request);

        // Scoped filter: only GTK belonging to the user's school context
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->whereHas('employment', fn ($q) => $q->where('school_id', $schoolId));
        }

        $orderBy = $request->get('order_by', 'created_at');
        $orderDir = $request->get('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        $perPage = $request->get('per_page', 10);
        $gtkList = $query->paginate($perPage)->withQueryString();
        $workUnits = WorkUnit::where('is_active', true)->orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();
        $statistics = $this->getStatistics();

        $satuanKerja = $request->filled('satuan_kerja')
            ? WorkUnit::find($request->satuan_kerja)
            : null;

        return view('gtk.index', compact(
            'gtkList', 'workUnits', 'provinces', 'statistics', 'satuanKerja'
        ));
    }

    public function indexguru(Request $request)
    {
        // Return JSON for AJAX search requests
        if ($request->ajax() || $request->wantsJson() || $request->has('format')) {
            $query = User::with(['gtkProfile', 'employment'])
                ->whereHas('employment', function ($q) {
                    // Guru = Tenaga Pendidik Pondok / Guru
                    $q->whereIn('jenis_gtk', ['Tenaga Pendidik Pondok', 'Guru']);
                });

            // Scoped filter: only GTK belonging to the user's school context
            $schoolId = $request->attributes->get('schoolContextId');
            if ($schoolId) {
                $query->whereHas('employment', fn ($q) => $q->where('school_id', $schoolId));
            }

            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhereHas('gtkProfile', fn ($q) => $q->where('nik', 'like', "%{$s}%"))
                        ->orWhereHas('employment', fn ($q) => $q->where('nupy', 'like', "%{$s}%"));
                });
            }

            $gtks = $query->limit(20)->get(['id', 'name', 'email']);

            return response()->json([
                'success' => true,
                'data' => $gtks->map(fn ($gtk) => [
                    'id' => $gtk->id,
                    'name' => $gtk->name,
                    'email' => $gtk->email,
                    'nupy' => $gtk->employment?->nupy,
                    'jenis_gtk' => $gtk->employment?->jenis_gtk,
                    'jabatan' => $gtk->employment?->jabatan,
                    'jenis_kelamin' => $gtk->gtkProfile?->jenis_kelamin,
                    'tempat_lahir' => $gtk->gtkProfile?->tempat_lahir,
                    'tanggal_lahir' => $gtk->gtkProfile?->tanggal_lahir,
                ]),
            ]);
        }

        $query = User::with([
            'gtkProfile',
            'employment',
            'gtkContact',
            'gtkWorkUnits.workUnit',
            'gtkProfile.addresses',
            'educations',
        ])->whereHas('employment', function ($q) {
            // Guru = Tenaga Pendidik Pondok / Guru
            $q->whereIn('jenis_gtk', ['Tenaga Pendidik Pondok', 'Guru']);
        });

        $this->applyFilters($query, $request);

        // Scoped filter: only GTK belonging to the user's school context
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->whereHas('employment', fn ($q) => $q->where('school_id', $schoolId));
        }

        $orderBy = $request->get('order_by', 'created_at');
        $orderDir = $request->get('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        $perPage = $request->get('per_page', 10);
        $gtkList = $query->paginate($perPage)->withQueryString();
        $workUnits = WorkUnit::where('is_active', true)->orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();
        $statistics = $this->getStatistics();

        $satuanKerja = $request->filled('satuan_kerja')
            ? WorkUnit::find($request->satuan_kerja)
            : null;

        return view('gtk.index', compact(
            'gtkList', 'workUnits', 'provinces', 'statistics', 'satuanKerja'
        ));
    }

    public function create()
    {
        $provinces = Province::orderBy('name')->get();
        $workUnits = WorkUnit::where('is_active', true)->orderBy('name')->get();
        $jenisGtk = \App\Models\JenisGtk::active()->orderBy('urutan')->orderBy('nama')->get();
        $jabatan = \App\Models\Jabatan::active()->orderBy('urutan')->orderBy('nama')->get();

        return view('gtk.create', compact('provinces', 'workUnits', 'jenisGtk', 'jabatan'));
    }

    public function store(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $this->validateAndStructure($request);

            $user = $this->createUser($data['user']);
            $profile = $this->createGtkProfile($user->id, $data['profile']);

            $this->createAddress($profile->id, $data['addresses']['domisili'], 'domisili');

            if (isset($data['addresses']['ktp'])) {
                $this->createAddress($profile->id, $data['addresses']['ktp'], 'ktp');
            }

            $this->createEducation($user->id, $data['education']);
            $this->createContact($user->id, $data['contact']);
            $this->createEmployment($user->id, $data['employment']);
            $this->assignWorkUnit($user->id, $data['work_unit_id'], $data['employment']['jabatan'] ?? null);

            if (! empty($data['family_members'])) {
                $this->createFamilyMembers($profile->id, $data['family_members']);
            }

            $user->assignRole('GTK');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data GTK berhasil disimpan',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('GtkWizardController@store failed', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.',
            ], 500);
        }
    }

    public function show(string $userId, string $uuid)
    {
        $gtk = User::with([
            'gtkProfile.addresses.province',
            'gtkProfile.addresses.city',
            'gtkProfile.addresses.district',
            'gtkProfile.addresses.village',
            'gtkProfile.familyMembers',
            'employment',
            'educations',
            'gtkContact',
            'gtkWorkUnits.workUnit',
        ])->findOrFail($uuid);

        return view('gtk.profile', compact('gtk', 'userId'));
    }

    public function edit(string $userId, string $uuid)
    {
        $gtk = User::with([
            'gtkProfile.addresses',
            'gtkProfile.familyMembers',
            'employment',
            'educations',
            'gtkContact',
            'gtkWorkUnits',
        ])->findOrFail($uuid);

        $provinces = Province::orderBy('name')->get();
        $workUnits = WorkUnit::where('is_active', true)->orderBy('name')->get();
        $jenisGtk = \App\Models\JenisGtk::active()->orderBy('urutan')->orderBy('nama')->get();
        $jabatan = \App\Models\Jabatan::active()->orderBy('urutan')->orderBy('nama')->get();

        return view('gtk.edit', compact('gtk', 'userId', 'provinces', 'workUnits', 'jenisGtk', 'jabatan'));
    }

    public function update(Request $request, string $userId, string $uuid): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = User::findOrFail($uuid);
            $data = $this->validateAndStructure($request, $user->id);

            $user->update([
                'name' => $data['user']['name'],
                'email' => $data['user']['email'],
                'is_active' => $data['user']['is_active'] ?? true,
            ]);

            if ($user->gtkProfile) {
                $user->gtkProfile->update($data['profile']);
            } else {
                $this->createGtkProfile($user->id, $data['profile']);
                $user->load('gtkProfile');
            }

            $profileId = $user->gtkProfile->id;

            $this->syncAddresses($profileId, $data['addresses'] ?? []);
            $this->syncEducation($user->id, $data['education'] ?? []);
            $this->syncContact($user->id, $data['contact'] ?? []);
            $this->syncEmployment($user->id, $data['employment'] ?? []);
            $this->syncWorkUnit($user->id, $data['work_unit_id'], $data['employment']['jabatan'] ?? null);
            $this->syncFamilyMembers($profileId, $data['family_members'] ?? []);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data GTK berhasil diperbarui',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('GtkWizardController@update failed', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.',
            ], 500);
        }
    }

    public function destroy(string $userId, string $uuid): JsonResponse
    {
        try {
            User::findOrFail($uuid)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data GTK berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            \Log::error('GtkWizardController@destroy failed', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem.',
            ], 500);
        }
    }

    public function datatable(Request $request): JsonResponse
    {
        $query = User::with(['gtkProfile', 'employment', 'gtkWorkUnits.workUnit'])
            ->whereHas('employment');

        // Scoped filter: only GTK belonging to the user's school context
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->whereHas('employment', fn ($q) => $q->where('school_id', $schoolId));
        }

        if (! empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('gtkProfile', fn ($q) => $q->where('nik', 'like', "%{$search}%"))
                    ->orWhereHas('employment', fn ($q) => $q->where('nupy', 'like', "%{$search}%"));
            });
        }

        $columns = ['name', 'email', 'created_at'];
        $colIndex = (int) ($request->input('order.0.column') ?? 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        $orderBy = $columns[$colIndex] ?? 'created_at';
        $query->orderBy($orderBy, $orderDir);

        $total = $query->count();
        $gtks = $query->skip((int) $request->start)->take((int) $request->length)->get();

        return response()->json([
            'draw' => (int) $request->draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $gtks->map(fn ($gtk) => [
                'DT_RowId' => $gtk->id,
                'name' => $gtk->name,
                'email' => $gtk->email,
                'nik' => $gtk->gtkProfile?->masked_nik ?? '-',
                'nupy' => $gtk->employment?->masked_nupy ?? '-',
                'work_unit' => $gtk->gtkWorkUnits->firstWhere('is_primary', true)?->workUnit?->name ?? '-',
                'status' => $gtk->is_active
                    ? '<span class="badge bg-success">Aktif</span>'
                    : '<span class="badge bg-danger">Nonaktif</span>',
                'created_at' => $gtk->created_at->format('d/m/Y'),
                'actions' => view('gtk.partials.actions', compact('gtk'))->render(),
            ]),
        ]);
    }

    public function exportPreview(Request $request)
    {
        $query = $this->buildExportQuery($request);
        $count = $query->count();

        return response()->json([
            'success' => true,
            'count' => $count,
            'message' => "Siap mengekspor {$count} data GTK.",
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'format' => 'nullable|in:excel,xlsx',
            'scope' => 'nullable|in:all,filtered',
            'columns' => 'nullable|array',
        ]);

        $query = $this->buildExportQuery($request);
        $records = $query->get();

        $filename = 'data-gtk-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new GtkExport($records), $filename);
    }

    public function toggleStatus(Request $request, string $userId, string $uuid): JsonResponse
    {
        try {
            $user = User::findOrFail($uuid);
            $user->update(['is_active' => ! $user->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diubah',
                'is_active' => $user->is_active,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem.',
            ], 500);
        }
    }

    public function resetPassword(Request $request, string $userId, string $uuid): JsonResponse
    {
        try {
            $user = User::findOrFail($uuid);
            $nupy = $user->employment?->nupy;

            if (! $nupy) {
                return response()->json([
                    'success' => false,
                    'message' => 'NUPY tidak ditemukan, tidak dapat mereset password.',
                ], 400);
            }

            $password = $this->defaultPassword($nupy);
            $user->update(['password' => Hash::make($password)]);

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil direset ke default ('.$password.')',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem.',
            ], 500);
        }
    }

    public function verifyPassword(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required',
            'gtk_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->gtk_id);

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password yang Anda masukkan salah.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'nik' => $user->gtkProfile?->nik ?? '-',
                'no_kk' => $user->gtkProfile?->no_kk ?? '-',
            ],
        ]);
    }

    public function storeFamilyMember(Request $request, string $userId, string $uuid): JsonResponse
    {
        $request->validate([
            'gtk_profile_id' => 'nullable|exists:gtk_profiles,id',
            'relationship' => 'required|string|max:50',
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'pekerjaan' => 'nullable|string|max:100',
            'pendidikan_terakhir' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            // Get or create GtkProfile for this user
            $gtk = User::findOrFail($uuid);
            $profile = $gtk->gtkProfile;
            if (! $profile) {
                $profile = GtkProfile::create([
                    'id' => Str::uuid(),
                    'user_id' => $gtk->id,
                ]);
            }

            $member = GtkFamilyMember::create([
                'id' => Str::uuid(),
                'gtk_profile_id' => $profile->id,
                'relationship' => $request->relationship,
                'nama' => $request->nama,
                'jenis_kelamin' => $request->jenis_kelamin,
                'pekerjaan' => $request->pekerjaan,
                'pendidikan_terakhir' => $request->pendidikan_terakhir,
                'alamat' => $request->alamat,
                'tanggal_lahir' => $request->tanggal_lahir,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Anggota keluarga berhasil ditambahkan',
                'data' => $member,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('storeFamilyMember failed', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan sistem.'], 500);
        }
    }

    public function editFamilyMember(string $userId, string $uuid, string $id): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => GtkFamilyMember::findOrFail($id),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
        }
    }

    public function updateFamilyMember(Request $request, string $userId, string $uuid, string $id): JsonResponse
    {
        $request->validate([
            'relationship' => 'required|string|max:50',
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'pekerjaan' => 'nullable|string|max:100',
            'pendidikan_terakhir' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $member = GtkFamilyMember::findOrFail($id);
            $member->update($request->only([
                'relationship', 'nama', 'jenis_kelamin',
                'pekerjaan', 'pendidikan_terakhir', 'alamat', 'tanggal_lahir',
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Anggota keluarga berhasil diperbarui',
                'data' => $member,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan sistem.'], 500);
        }
    }

    public function deleteFamilyMember(string $userId, string $uuid, string $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            GtkFamilyMember::findOrFail($id)->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Anggota keluarga berhasil dihapus']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan sistem.'], 500);
        }
    }

    public function storeEducationModal(Request $request, string $userId, string $uuid): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'jenjang_pendidikan' => 'required|string|max:20',
            'nama_satuan_pendidikan' => 'required|string|max:255',
            'jurusan' => 'nullable|string|max:100',
            'fakultas' => 'nullable|string|max:100',
            'tahun_masuk' => 'nullable|integer|min:1900|max:'.date('Y'),
            'tahun_lulus' => 'required|integer|min:1900|max:'.(date('Y') + 10),
            'no_ijazah' => 'nullable|string|max:100',
            'nilai_akhir' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:LULUS,BELUM_LULUS,DROPOUT,PINDAH',
        ]);

        try {
            DB::beginTransaction();

            $education = GtkEducation::create([
                'id' => Str::uuid(),
                'user_id' => $request->user_id,
                'jenjang_pendidikan' => $request->jenjang_pendidikan,
                'nama_satuan_pendidikan' => $request->nama_satuan_pendidikan,
                'jurusan' => $request->jurusan,
                'fakultas' => $request->fakultas,
                'tahun_masuk' => $request->tahun_masuk,
                'tahun_lulus' => $request->tahun_lulus,
                'no_ijazah' => $request->no_ijazah,
                'nilai_akhir' => $request->nilai_akhir,
                'status' => $request->status,
                'is_verified' => false,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Riwayat pendidikan berhasil ditambahkan',
                'data' => $education,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan sistem.'], 500);
        }
    }

    public function editEducation(string $userId, string $uuid, string $id): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => GtkEducation::findOrFail($id),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
        }
    }

    public function updateEducation(Request $request, string $userId, string $uuid, string $id): JsonResponse
    {
        $request->validate([
            'jenjang_pendidikan' => 'required|string|max:20',
            'nama_satuan_pendidikan' => 'required|string|max:255',
            'jurusan' => 'nullable|string|max:100',
            'fakultas' => 'nullable|string|max:100',
            'tahun_masuk' => 'nullable|integer|min:1900|max:'.date('Y'),
            'tahun_lulus' => 'required|integer|min:1900|max:'.(date('Y') + 10),
            'no_ijazah' => 'nullable|string|max:100',
            'nilai_akhir' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:LULUS,BELUM_LULUS,DROPOUT,PINDAH',
        ]);

        try {
            DB::beginTransaction();

            $education = GtkEducation::findOrFail($id);
            $education->update($request->only([
                'jenjang_pendidikan', 'nama_satuan_pendidikan', 'jurusan',
                'fakultas', 'tahun_masuk', 'tahun_lulus',
                'no_ijazah', 'nilai_akhir', 'status',
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Riwayat pendidikan berhasil diperbarui',
                'data' => $education,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan sistem.'], 500);
        }
    }

    public function deleteEducation(string $userId, string $uuid, string $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            GtkEducation::findOrFail($id)->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Riwayat pendidikan berhasil dihapus']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan sistem.'], 500);
        }
    }

    public function verifyEducation(Request $request, string $userId, string $uuid, string $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            GtkEducation::findOrFail($id)->verify(auth()->id());
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Riwayat pendidikan berhasil diverifikasi']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan sistem.'], 500);
        }
    }

    private function applyFilters($query, Request $request): void
    {
        // Scoped filter: only GTK belonging to the user's school context
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->whereHas('employment', fn ($q) => $q->where('school_id', $schoolId));
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhereHas('employment', fn ($q) => $q->where('nupy', 'like', "%{$s}%"))
                    ->orWhereHas('gtkProfile', fn ($q) => $q->where('nik', 'like', "%{$s}%")->orWhere('no_kk', 'like', "%{$s}%"))
                    ->orWhereHas('gtkContact', fn ($q) => $q->where('no_hp', 'like', "%{$s}%")->orWhere('no_whatsapp', 'like', "%{$s}%"))
                    ->orWhereHas('gtkProfile.addresses', fn ($q) => $q->where('jalan', 'like', "%{$s}%")
                        ->orWhere('desa', 'like', "%{$s}%")
                        ->orWhere('kecamatan', 'like', "%{$s}%")
                        ->orWhere('kab_kota', 'like', "%{$s}%")
                        ->orWhere('provinsi', 'like', "%{$s}%")
                    );
            });
        }

        $profileFilters = [
            'jenis_kelamin' => 'jenis_kelamin',
            'golongan_darah' => 'golongan_darah',
            'status_perkawinan' => 'status_perkawinan',
            'agama' => 'agama',
        ];

        foreach ($profileFilters as $param => $column) {
            if ($request->filled($param)) {
                $query->whereHas('gtkProfile', fn ($q) => $q->where($column, $request->$param));
            }
        }

        foreach (['nik', 'no_kk'] as $field) {
            if ($request->filled($field)) {
                $query->whereHas('gtkProfile', fn ($q) => $q->where($field, 'like', "%{$request->$field}%"));
            }
        }

        $empFilters = [
            'status_kepegawaian' => 'status_kepegawaian',
            'jenis_gtk' => 'jenis_gtk',
        ];

        foreach ($empFilters as $param => $column) {
            if ($request->filled($param)) {
                $query->whereHas('employment', fn ($q) => $q->where($column, $request->$param));
            }
        }

        // Tendik: tampilkan semua GTK BUKAN "Tenaga Pendidik Pondok"
        if ($request->filled('jenis_gtk_exclude')) {
            $query->whereHas('employment', fn ($q) => $q->where('jenis_gtk', '!=', $request->jenis_gtk_exclude));
        }

        if ($request->filled('jabatan')) {
            $query->whereHas('employment', fn ($q) => $q->where('jabatan', 'like', "%{$request->jabatan}%"));
        }

        if ($request->filled('nupy')) {
            $query->whereHas('employment', fn ($q) => $q->where('nupy', 'like', "%{$request->nupy}%"));
        }

        if ($request->filled('satuan_kerja')) {
            $query->whereHas('gtkWorkUnits', fn ($q) => $q->where('work_unit_id', $request->satuan_kerja));
        }

        if ($request->filled('jenjang_pendidikan')) {
            $query->whereHas('educations', fn ($q) => $q->where('jenjang_pendidikan', $request->jenjang_pendidikan));
        }

        if ($request->filled('nama_satuan_pendidikan')) {
            $query->whereHas('educations', fn ($q) => $q->where('nama_satuan_pendidikan', 'like', "%{$request->nama_satuan_pendidikan}%"));
        }

        if ($request->filled('jurusan')) {
            $query->whereHas('educations', fn ($q) => $q->where('jurusan', 'like', "%{$request->jurusan}%"));
        }

        if ($request->filled('province_code')) {
            $query->whereHas('gtkProfile.addresses', fn ($q) => $q->where('province_code', $request->province_code));
        }

        if ($request->filled('city_name')) {
            $query->whereHas('gtkProfile.addresses', fn ($q) => $q->where('kab_kota', 'like', "%{$request->city_name}%"));
        }

        if ($request->filled('district_name')) {
            $query->whereHas('gtkProfile.addresses', fn ($q) => $q->where('kecamatan', 'like', "%{$request->district_name}%"));
        }

        if ($request->filled('status_aktif')) {
            $query->where('is_active', $request->status_aktif);
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', "%{$request->email}%");
        }

        if ($request->filled('tmt_from')) {
            $query->whereHas('employment', fn ($q) => $q->whereDate('tmt', '>=', $request->tmt_from));
        }

        if ($request->filled('tmt_to')) {
            $query->whereHas('employment', fn ($q) => $q->whereDate('tmt', '<=', $request->tmt_to));
        }
    }

    private function buildExportQuery(Request $request)
    {
        $query = User::with([
            'gtkProfile',
            'employment',
            'gtkContact',
            'gtkWorkUnits.workUnit',
            'educations',
        ])->whereHas('employment');

        if ($request->get('scope', 'all') === 'filtered') {
            $this->applyFilters($query, $request);
        }

        return $query->orderBy('name');
    }

    private function getStatistics(): array
    {
        // Determine scope: global view vs per-school
        $isGlobalView = canPermission('gtk-wizard-global-view') || canPermission('view_global_school_data');

        $baseQuery = function () use ($isGlobalView) {
            $query = User::whereHas('employment');

            if (! $isGlobalView) {
                // Scope ke school context (sama dengan index() method)
                $schoolId = optional(Auth::user()->employment)->school_id;
                if ($schoolId) {
                    $query->whereHas('employment', fn ($q) => $q->where('school_id', $schoolId));
                }
            }

            return $query;
        };

        $total = $baseQuery()->count();
        $aktif = $baseQuery()->where('is_active', true)->count();
        $nonaktif = $total - $aktif;
        $gtkUserIds = $baseQuery()->pluck('id');

        // Status Kepegawaian
        $statusKepegawaian = GtkEmployment::selectRaw('status_kepegawaian, count(*) as total')
            ->whereIn('user_id', $gtkUserIds)
            ->whereNotNull('status_kepegawaian')
            ->groupBy('status_kepegawaian')
            ->pluck('total', 'status_kepegawaian')
            ->toArray();

        // Jenis GTK
        $jenisGtk = GtkEmployment::selectRaw('jenis_gtk, count(*) as total')
            ->whereIn('user_id', $gtkUserIds)
            ->whereNotNull('jenis_gtk')
            ->groupBy('jenis_gtk')
            ->pluck('total', 'jenis_gtk')
            ->toArray();

        // Gender
        $genderL = GtkProfile::whereIn('user_id', $gtkUserIds)->where('jenis_kelamin', 'L')->count();
        $genderP = GtkProfile::whereIn('user_id', $gtkUserIds)->where('jenis_kelamin', 'P')->count();

        // Distribusi per Satuan Kerja
        $perSatker = DB::table('gtk_work_unit')
            ->selectRaw('work_unit_id, count(*) as total')
            ->whereIn('user_id', $gtkUserIds)
            ->groupBy('work_unit_id')
            ->pluck('total', 'work_unit_id')
            ->toArray();
        $satkerNames = \App\Models\WorkUnit::whereIn('id', array_keys($perSatker))
            ->pluck('name', 'id')->toArray();
        $distSatker = [];
        foreach ($perSatker as $id => $count) {
            $distSatker[$satkerNames[$id] ?? $id] = $count;
        }

        // Pendidikan terakhir (jenjang tertinggi per GTK)
        $jenjangOrder = ['SD', 'SMP', 'SMA', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'];
        $jenjangMap = array_flip($jenjangOrder);
        $pendidikan = GtkEducation::whereIn('user_id', $gtkUserIds)
            ->selectRaw('jenjang_pendidikan, count(distinct user_id) as total')
            ->whereNotNull('jenjang_pendidikan')
            ->groupBy('jenjang_pendidikan')
            ->pluck('total', 'jenjang_pendidikan')
            ->toArray();
        uksort($pendidikan, fn ($a, $b) => ($jenjangMap[$a] ?? 99) <=> ($jenjangMap[$b] ?? 99));

        // Kelamin & Agama
        $agama = GtkProfile::whereIn('user_id', $gtkUserIds)
            ->whereNotNull('agama')
            ->selectRaw('agama, count(*) as total')
            ->groupBy('agama')
            ->pluck('total', 'agama')
            ->toArray();

        // Kompletenes data
        $completeNik = GtkProfile::whereIn('user_id', $gtkUserIds)->whereNotNull('nik')->count();
        $completeAddr = GtkProfile::whereIn('user_id', $gtkUserIds)
            ->whereHas('addresses', fn ($q) => $q->where('type', 'domisili'))->count();
        $completeEdu = \App\Models\User::whereIn('id', $gtkUserIds)->whereHas('educations')->count();
        $completeTmt = GtkEmployment::whereIn('user_id', $gtkUserIds)->whereNotNull('tmt')->count();

        // GTK baru 30 hari terakhir
        $recent = $baseQuery()->where('created_at', '>=', now()->subDays(30))->count();

        // Kontrak / Percobaan hampir habis (TMT + 11 bulan < now < TMT + 13 bulan)
        $kontrakHampirHabis = GtkEmployment::whereIn('user_id', $gtkUserIds)
            ->whereIn('status_kepegawaian', ['KONTRAK', 'Percobaan', 'PTT', 'PTY', 'GTT', 'GTY'])
            ->whereNotNull('tmt')
            ->where('tmt', '>=', now()->subMonths(13))
            ->where('tmt', '<=', now()->subMonths(11))
            ->count();

        return [
            'total' => $total,
            'aktif' => $aktif,
            'nonaktif' => $nonaktif,
            'recent' => $recent,
            'status_kepegawaian' => $statusKepegawaian,
            'jenis_gtk' => $jenisGtk,
            'gender_l' => $genderL,
            'gender_p' => $genderP,
            'dist_satker' => $distSatker,
            'pendidikan' => $pendidikan,
            'agama' => $agama,
            'complete_nik' => $completeNik,
            'complete_addr' => $completeAddr,
            'complete_edu' => $completeEdu,
            'complete_tmt' => $completeTmt,
            'kontrak_habis' => $kontrakHampirHabis,
        ];
    }

    private function validateAndStructure(Request $request, ?string $userId = null): array
    {
        $profileId = $userId
            ? User::find($userId)?->gtkProfile?->id
            : null;

        $employmentId = $userId
            ? User::find($userId)?->employment?->id
            : null;

        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($userId)],

            'nik' => ['required', 'digits:16', Rule::unique('gtk_profiles', 'nik')->ignore($profileId)],
            'no_kk' => 'nullable|digits:16',
            'tempat_lahir' => 'required|string|max:100',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'golongan_darah' => 'nullable|in:A,B,AB,O',
            'agama' => 'nullable|in:islam,kristen,katolik,hindu,buddha,konghucu',
            'status_perkawinan' => 'nullable|in:belum_kawin,kawin,cerai_hidup,cerai_mati',
            'nama_ibu_kandung' => 'nullable|string|max:255',
            'npwp' => 'nullable|string|max:20',

            'kontak.no_hp' => 'required|string|max:20',
            'kontak.no_whatsapp' => 'nullable|string|max:20',
            'kontak.kontak_darurat' => 'nullable|string|max:255',
            'kontak.instagram' => 'nullable|string|max:100',
            'kontak.facebook' => 'nullable|string|max:100',
            'kontak.twitter' => 'nullable|string|max:100',

            'kepegawaian.nupy' => ['required', 'string', 'max:50', Rule::unique('gtk_employments', 'nupy')->ignore($employmentId)],
            'kepegawaian.jenis_gtk' => 'required',
            'kepegawaian.jabatan' => 'required|string|max:150',
            'kepegawaian.status_kepegawaian' => 'required|in:PTT,PTY,Percobaan,Magang,GTT,GTY,KONTRAK',
            'kepegawaian.tmt' => 'required|date',
            'kepegawaian.nomor_sk' => 'required|string|max:100',
            'kepegawaian.tanggal_sk' => 'required|date',
            'kepegawaian.pangkat_golongan' => 'nullable|string|max:50',

            'work_unit_id' => 'required|exists:work_units,id',

            'alamat_domisili.province_code' => 'required|exists:indonesia_provinces,code',
            'alamat_domisili.city_code' => 'required|exists:indonesia_cities,code',
            'alamat_domisili.district_code' => 'required|exists:indonesia_districts,code',
            'alamat_domisili.village_code' => 'required|exists:indonesia_villages,code',
            'alamat_domisili.jalan' => 'required|string',
            'alamat_domisili.rt_rw' => 'required|string|max:10',
            'alamat_domisili.dusun' => 'nullable|string|max:100',
            'alamat_domisili.kode_pos' => 'nullable|string|max:10',

            'alamat_ktp.province_code' => 'nullable|exists:indonesia_provinces,code',
            'alamat_ktp.city_code' => 'nullable|exists:indonesia_cities,code',
            'alamat_ktp.district_code' => 'nullable|exists:indonesia_districts,code',
            'alamat_ktp.village_code' => 'nullable|exists:indonesia_villages,code',
            'alamat_ktp.jalan' => 'nullable|string',
            'alamat_ktp.rt_rw' => 'nullable|string|max:10',
            'alamat_ktp.dusun' => 'nullable|string|max:100',
            'alamat_ktp.kode_pos' => 'nullable|string|max:10',
        ];

        if ($request->has('pendidikan') && is_array($request->pendidikan)) {
            foreach ($request->pendidikan as $key => $edu) {
                $rules["pendidikan.{$key}.jenjang_pendidikan"] = 'required|string|max:20';
                $rules["pendidikan.{$key}.nama_satuan_pendidikan"] = 'required|string|max:255';
                $rules["pendidikan.{$key}.tahun_lulus"] = 'required|integer|min:1900|max:'.(date('Y') + 10);
                $rules["pendidikan.{$key}.jurusan"] = 'nullable|string|max:100';
                $rules["pendidikan.{$key}.fakultas"] = 'nullable|string|max:100';
                $rules["pendidikan.{$key}.tahun_masuk"] = 'nullable|integer|min:1900|max:'.date('Y');
                $rules["pendidikan.{$key}.no_ijazah"] = 'nullable|string|max:100';
                $rules["pendidikan.{$key}.nilai_akhir"] = 'nullable|numeric|min:0|max:100';
                $rules["pendidikan.{$key}.skala_nilai"] = 'nullable|string|max:10';
                $rules["pendidikan.{$key}.status"] = 'nullable|in:LULUS,BELUM_LULUS,DROPOUT,PINDAH';
                $rules["pendidikan.{$key}.nama_kepala_sekolah"] = 'nullable|string|max:255';
                $rules["pendidikan.{$key}.nama_rektor"] = 'nullable|string|max:255';
                $rules["pendidikan.{$key}.keterangan"] = 'nullable|string';
            }
        }

        if ($request->has('anggota_keluarga') && is_array($request->anggota_keluarga)) {
            foreach ($request->anggota_keluarga as $key => $member) {
                $rules["anggota_keluarga.{$key}.relationship"] = 'required|in:suami,istri,anak,ayah,ibu';
                $rules["anggota_keluarga.{$key}.nama"] = 'required|string|max:255';
                $rules["anggota_keluarga.{$key}.jenis_kelamin"] = 'required|in:L,P';
                $rules["anggota_keluarga.{$key}.tempat_lahir"] = 'nullable|string|max:100';
                $rules["anggota_keluarga.{$key}.tanggal_lahir"] = 'nullable|date';
                $rules["anggota_keluarga.{$key}.pekerjaan"] = 'nullable|string|max:100';
                $rules["anggota_keluarga.{$key}.pendidikan_terakhir"] = 'nullable|string|max:100';
                $rules["anggota_keluarga.{$key}.alamat"] = 'nullable|string';
                $rules["anggota_keluarga.{$key}.gtk_id"] = 'nullable|exists:users,id';
            }
        }

        $validated = $request->validate($rules);

        $nupy = $validated['kepegawaian']['nupy'];

        $data = [
            'user' => [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($this->defaultPassword($nupy)),
                'is_active' => $request->boolean('is_active', true),
            ],
            'profile' => [
                'nik' => $validated['nik'],
                'no_kk' => $validated['no_kk'] ?? null,
                'tempat_lahir' => $validated['tempat_lahir'],
                'tanggal_lahir' => $validated['tanggal_lahir'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'golongan_darah' => $validated['golongan_darah'] ?? null,
                'agama' => $validated['agama'] ?? null,
                'status_perkawinan' => $validated['status_perkawinan'] ?? 'belum_kawin',
                'nama_ibu_kandung' => $validated['nama_ibu_kandung'] ?? null,
                'npwp' => $validated['npwp'] ?? null,
            ],
            'addresses' => [
                'domisili' => $this->buildAddressData($validated['alamat_domisili'], 'domisili'),
            ],
            'education' => $validated['pendidikan'] ?? [],
            'contact' => $validated['kontak'] ?? [],
            'employment' => array_merge($validated['kepegawaian'] ?? [], [
                'jenis_gtk_id' => $this->resolveJenisGtkId($validated['kepegawaian']['jenis_gtk'] ?? null),
                'jenis_gtk' => $this->resolveJenisGtkName($validated['kepegawaian']['jenis_gtk'] ?? null),
                'school_id' => $this->resolveSchoolId($validated['work_unit_id'] ?? null),
            ]),
            'work_unit_id' => $validated['work_unit_id'],
            'family_members' => $validated['anggota_keluarga'] ?? [],
        ];

        if (! empty($validated['alamat_ktp']['province_code'])) {
            $data['addresses']['ktp'] = $this->buildAddressData($validated['alamat_ktp'], 'ktp');
        }

        return $data;
    }

    private function createUser(array $userData)
    {
        return User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => $userData['password'],
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function createGtkProfile(string $userId, array $profileData)
    {
        return GtkProfile::create(array_merge($profileData, ['user_id' => $userId]));
    }

    private function createAddress(string $profileId, array $addressData, string $type)
    {
        return GtkAddress::create(array_merge($addressData, [
            'gtk_profile_id' => $profileId,
            'type' => $type,
        ]));
    }

    private function createEducation(string $userId, array $educationData)
    {
        if (empty($educationData)) {
            return;
        }

        foreach ($educationData as $edu) {
            if (empty($edu['jenjang_pendidikan']) || empty($edu['nama_satuan_pendidikan'])) {
                continue;
            }

            GtkEducation::create(array_merge($edu, [
                'user_id' => $userId,
                'is_verified' => true,
                'verified_at' => now(),
                'verified_by' => auth()->id(),
            ]));
        }
    }

    private function createContact(string $userId, array $contactData)
    {
        return GtkContact::create(array_merge($contactData, ['user_id' => $userId]));
    }

    private function createEmployment(string $userId, array $employmentData)
    {
        return GtkEmployment::create(array_merge($employmentData, ['user_id' => $userId]));
    }

    private function assignWorkUnit(string $userId, string $workUnitId, ?string $jabatan = null)
    {
        GtkWorkUnit::where('user_id', $userId)->update(['is_primary' => false]);

        return GtkWorkUnit::create([
            'user_id' => $userId,
            'work_unit_id' => $workUnitId,
            'jabatan' => $jabatan,
            'is_primary' => true,
        ]);
    }

    private function createFamilyMembers(string $profileId, array $members): void
    {
        foreach ($members as $member) {
            if (empty($member['nama'])) {
                continue;
            }

            $data = array_filter([
                'relationship' => $member['relationship'] ?? null,
                'nama' => $member['nama'] ?? null,
                'jenis_kelamin' => $member['jenis_kelamin'] ?? null,
                'tempat_lahir' => $member['tempat_lahir'] ?? null,
                'tanggal_lahir' => $member['tanggal_lahir'] ?? null,
                'pekerjaan' => $member['pekerjaan'] ?? null,
                'pendidikan_terakhir' => $member['pendidikan_terakhir'] ?? null,
                'alamat' => $member['alamat'] ?? null,
                'gtk_profile_id' => $profileId,
            ], fn ($v) => $v !== null);

            if (! empty($member['id'])) {
                $existing = GtkFamilyMember::where('id', $member['id'])
                    ->where('gtk_profile_id', $profileId)
                    ->first();
                if ($existing) {
                    $existing->update($data);

                    continue;
                }
            }

            $data['id'] = Str::uuid();
            GtkFamilyMember::create($data);
        }
    }

    private function syncAddresses(string $profileId, array $addresses): void
    {
        foreach ($addresses as $type => $addressData) {
            $existing = GtkAddress::where('gtk_profile_id', $profileId)
                ->where('type', $type)
                ->first();

            $existing
                ? $existing->update($addressData)
                : $this->createAddress($profileId, $addressData, $type);
        }
    }

    private function syncEducation(string $userId, array $educationData): void
    {
        $keptIds = [];

        foreach ($educationData as $edu) {
            if (empty($edu['jenjang_pendidikan']) || empty($edu['nama_satuan_pendidikan'])) {
                continue;
            }

            $payload = array_filter($edu, function ($key) {
                return ! in_array($key, ['id', '_rowNum', '_errors', '_warnings', '_status']);
            }, ARRAY_FILTER_USE_KEY);

            if (! empty($edu['id'])) {
                $existing = GtkEducation::where('id', $edu['id'])
                    ->where('user_id', $userId)
                    ->first();

                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $created = GtkEducation::create(array_merge($payload, [
                'user_id' => $userId,
                'is_verified' => true,
                'verified_at' => now(),
                'verified_by' => auth()->id(),
            ]));
            $keptIds[] = $created->id;
        }

        GtkEducation::where('user_id', $userId)
            ->whereNotIn('id', $keptIds)
            ->delete();
    }

    private function syncContact(string $userId, array $contactData): void
    {
        if (empty($contactData)) {
            return;
        }

        $contact = GtkContact::where('user_id', $userId)->first();
        $contact
            ? $contact->update($contactData)
            : $this->createContact($userId, $contactData);
    }

    private function syncEmployment(string $userId, array $employmentData): void
    {
        if (empty($employmentData)) {
            return;
        }

        $employment = GtkEmployment::where('user_id', $userId)->first();
        $employment
            ? $employment->update($employmentData)
            : $this->createEmployment($userId, $employmentData);
    }

    private function syncWorkUnit(string $userId, string $workUnitId, ?string $jabatan = null): void
    {
        $workUnit = GtkWorkUnit::where('user_id', $userId)->where('is_primary', true)->first();

        $workUnit
            ? $workUnit->update(['work_unit_id' => $workUnitId, 'jabatan' => $jabatan])
            : $this->assignWorkUnit($userId, $workUnitId, $jabatan);
    }

    private function syncFamilyMembers(string $profileId, array $members): void
    {
        $existingIds = GtkFamilyMember::where('gtk_profile_id', $profileId)
            ->pluck('id')->toArray();

        $incomingIds = array_filter(array_column($members, 'id'));

        $toDelete = array_diff($existingIds, $incomingIds);
        if (! empty($toDelete)) {
            GtkFamilyMember::where('gtk_profile_id', $profileId)
                ->whereIn('id', $toDelete)
                ->delete();
        }

        $this->createFamilyMembers($profileId, $members);
    }

    private function buildAddressData(array $raw, string $type): array
    {
        return [
            'type' => $type,
            'province_code' => $raw['province_code'],
            'city_code' => $raw['city_code'],
            'district_code' => $raw['district_code'],
            'village_code' => $raw['village_code'],
            'jalan' => $raw['jalan'],
            'rt_rw' => $raw['rt_rw'] ?? null,
            'dusun' => $raw['dusun'] ?? null,
            'kode_pos' => $raw['kode_pos'] ?? null,
            'desa' => $this->lookupName(\App\Models\Village::class, $raw['village_code']),
            'kecamatan' => $this->lookupName(\App\Models\District::class, $raw['district_code']),
            'kab_kota' => $this->lookupName(\App\Models\City::class, $raw['city_code']),
            'provinsi' => $this->lookupName(Province::class, $raw['province_code']),
        ];
    }

    private function lookupName(string $model, string $code): ?string
    {
        return $model::where('code', $code)->value('name');
    }

    private function defaultPassword(string $nupy): string
    {
        return '@'.$nupy;
    }

    /**
     * Accept UUID or name string — return the JenisGtk UUID.
     */
    private function resolveJenisGtkId(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            return \App\Models\JenisGtk::find($value)?->id;
        }

        return \App\Models\JenisGtk::where('nama', $value)->value('id');
    }

    /**
     * Accept UUID or name string — return the human-readable name.
     */
    private function resolveJenisGtkName(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            return \App\Models\JenisGtk::find($value)?->nama;
        }

        return $value;
    }

    /**
     * Resolve school_id dari work_unit_id.
     * Relasi: schools.work_unit_id → work_units.id
     */
    private function resolveSchoolId(?string $workUnitId): ?string
    {
        if (! $workUnitId) {
            return null;
        }

        return School::where('work_unit_id', $workUnitId)->value('id');
    }

    public function import()
    {
        $workUnits = WorkUnit::where('is_active', true)->orderBy('name')->get();
        $jenisGtk = JenisGtk::where('is_active', true)->orderBy('urutan')->get();

        return view('gtk.import', compact('workUnits', 'jenisGtk'));
    }

    public function importTemplate(string $workUnitId)
    {
        $workUnit = WorkUnit::findOrFail($workUnitId);

        $headers = [
            'name', 'email', 'nik', 'no_kk', 'tempat_lahir', 'tanggal_lahir',
            'jenis_kelamin', 'golongan_darah', 'agama', 'status_perkawinan', 'npwp',
            'no_hp', 'no_whatsapp',
            'nupy', 'jenis_gtk', 'jabatan', 'status_kepegawaian', 'tmt', 'nomor_sk',
            'tanggal_sk', 'pangkat_golongan',
            'jenjang_pendidikan', 'nama_sekolah', 'jurusan', 'tahun_lulus',
            'alamat_jalan', 'alamat_rt_rw', 'alamat_desa', 'alamat_kecamatan',
            'alamat_kota', 'alamat_provinsi', 'kode_pos',
        ];

        $exampleRow = [
            'Ahmad Fauzi', 'ahmad.fauzi@example.com', '3201234567890123', '3201234567890001',
            'Mataram', '1990-01-15', 'Laki-laki', 'A', 'Islam', 'Menikah', '123456789012345',
            '081234567890', '081234567890',
            'GTK2024001', 'Tenaga Pendidik Pondok', 'Guru', 'Tetap', '2020-01-01',
            'SK/001/2020', '2020-01-01', 'III/a',
            'S1', 'Universitas Indonesia', 'Pendidikan Agama Islam', '2015',
            'Jl. Contoh No. 123', '001/002', 'Kelurahan Contoh', 'Kecamatan Contoh',
            'Kota Contoh', 'Provinsi Contoh', '12345',
        ];

        $data = [$headers, $exampleRow];

        $filename = 'template-import-gtk-'.Str::slug($workUnit->name).'-'.now()->format('Ymd').'.xlsx';

        return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromArray
        {
            private $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function array(): array
            {
                return $this->data;
            }
        }, $filename);
    }

    public function importStore(Request $request): JsonResponse
    {
        $request->validate([
            'work_unit_id' => 'required|exists:work_units,id',
            'rows' => 'required|array|min:1',
            'rows.*' => 'array',
        ]);

        $workUnitId = $request->input('work_unit_id');
        $rows = $request->input('rows');

        $imported = 0;
        $failed = [];

        foreach ($rows as $row) {
            $rowNum = $row['_rowNum'] ?? '?';

            DB::beginTransaction();
            try {
                $required = ['name', 'email', 'nik', 'nupy'];
                foreach ($required as $field) {
                    if (empty($row[$field])) {
                        throw new \InvalidArgumentException("Field '{$field}' wajib diisi.");
                    }
                }

                if (User::where('email', $row['email'])->exists()) {
                    throw new \InvalidArgumentException("Email '{$row['email']}' sudah terdaftar.");
                }
                if (GtkEmployment::where('nupy', $row['nupy'])->exists()) {
                    throw new \InvalidArgumentException("NUPY '{$row['nupy']}' sudah terdaftar.");
                }

                $nupy = $row['nupy'];

                $user = User::create([
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => Hash::make($this->defaultPassword($nupy)),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
                $user->assignRole('gtk');

                $profile = $this->createGtkProfile($user->id, [
                    'nik' => $row['nik'] ?? null,
                    'no_kk' => $row['no_kk'] ?? null,
                    'tempat_lahir' => $row['tempat_lahir'] ?? null,
                    'tanggal_lahir' => $row['tanggal_lahir'] ?? null,
                    'jenis_kelamin' => $row['jenis_kelamin'] ?? null,
                    'golongan_darah' => $row['golongan_darah'] ?? null,
                    'agama' => $row['agama'] ?? null,
                    'status_perkawinan' => $row['status_perkawinan'] ?? null,
                    'npwp' => $row['npwp'] ?? null,
                ]);

                $address = $this->resolveAddressFromNames($row);
                if (! empty($address['province_code'])) {
                    $this->createAddress($profile->id, array_merge($address, ['type' => 'domisili']), 'domisili');
                }

                if (! empty($row['no_hp'])) {
                    $this->createContact($user->id, [
                        'no_hp' => $row['no_hp'],
                        'no_whatsapp' => $row['no_whatsapp'] ?? null,
                    ]);
                }

                $this->createEmployment($user->id, [
                    'nupy' => $nupy,
                    'jenis_gtk' => $row['jenis_gtk'] ?? null,
                    'jabatan' => $row['jabatan'] ?? null,
                    'status_kepegawaian' => $row['status_kepegawaian'] ?? null,
                    'tmt' => $row['tmt'] ?? null,
                    'nomor_sk' => $row['nomor_sk'] ?? null,
                    'tanggal_sk' => $row['tanggal_sk'] ?? null,
                    'pangkat_golongan' => $row['pangkat_golongan'] ?? null,
                ]);

                if (! empty($row['jenjang_pendidikan']) && ! empty($row['nama_sekolah'])) {
                    $this->createEducation($user->id, [[
                        'jenjang_pendidikan' => $row['jenjang_pendidikan'],
                        'nama_satuan_pendidikan' => $row['nama_sekolah'],
                        'jurusan' => $row['jurusan'] ?? null,
                        'tahun_lulus' => $row['tahun_lulus'] ?? null,
                        'status' => 'LULUS',
                    ]]);
                }

                $this->assignWorkUnit($user->id, $workUnitId, $row['jabatan'] ?? null);

                DB::commit();
                $imported++;
            } catch (\Exception $e) {
                DB::rollBack();
                $failed[] = [
                    'row' => $rowNum,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        $failedCount = count($failed);

        return response()->json([
            'success' => $imported > 0,
            'imported' => $imported,
            'failed' => $failed,
            'message' => "Berhasil mengimport {$imported} data GTK".($failedCount > 0 ? ", {$failedCount} gagal." : '.'),
        ]);
    }

    private function resolveAddressFromNames(array $row): array
    {
        $provinceCode = null;
        $cityCode = null;
        $districtCode = null;
        $villageCode = null;

        if (! empty($row['alamat_provinsi'])) {
            $province = Province::where('name', 'LIKE', '%'.$row['alamat_provinsi'].'%')->first();
            $provinceCode = $province?->code;
        }

        if ($provinceCode && ! empty($row['alamat_kota'])) {
            $city = \App\Models\City::where('province_code', $provinceCode)
                ->where('name', 'LIKE', '%'.$row['alamat_kota'].'%')
                ->first();
            $cityCode = $city?->code;
        }

        if ($cityCode && ! empty($row['alamat_kecamatan'])) {
            $district = \App\Models\District::where('city_code', $cityCode)
                ->where('name', 'LIKE', '%'.$row['alamat_kecamatan'].'%')
                ->first();
            $districtCode = $district?->code;
        }

        if ($districtCode && ! empty($row['alamat_desa'])) {
            $village = \App\Models\Village::where('district_code', $districtCode)
                ->where('name', 'LIKE', '%'.$row['alamat_desa'].'%')
                ->first();
            $villageCode = $village?->code;
        }

        return [
            'province_code' => $provinceCode,
            'city_code' => $cityCode,
            'district_code' => $districtCode,
            'village_code' => $villageCode,
            'jalan' => $row['alamat_jalan'] ?? null,
            'rt_rw' => $row['alamat_rt_rw'] ?? null,
            'kode_pos' => $row['kode_pos'] ?? null,
        ];
    }
}
