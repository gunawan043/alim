<?php

namespace App\Http\Controllers;

use App\Exports\GtkExport;
use App\Exports\GtkImportTemplateExport;
use App\Helpers\JabatanRoleMapper;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\City;
use App\Models\District;
use App\Models\GtkAddress;
use App\Models\GtkContact;
use App\Models\GtkCrossUnitAssignment;
use App\Models\GtkEducation;
use App\Models\GtkEmployment;
use App\Models\GtkFamilyMember;
use App\Models\GtkHealthData;
use App\Models\GtkProfile;
use App\Models\GtkWorkUnit;
use App\Models\JenisGtk;
use App\Models\Province;
use App\Models\School;
use App\Models\StructuralAssignment;
use App\Models\StructuralPosition;
use App\Models\User;
use App\Models\Village;
use App\Models\WorkUnit;
use App\Services\GtkFamilyService;
use App\Services\SchoolGroupService;
use App\Support\GtkAccess;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
                $query->whereIn('users.id', SchoolGroupService::userIdsForSchool($schoolId));
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
        ]);

        // Scoped filter: GTK internal sekolah + penugasan resmi dari pondok.
        $schoolId = $request->attributes->get('schoolContextId');
        $crossUnitUserIds = collect();
        if ($schoolId) {
            $crossUnitUserIds = GtkCrossUnitAssignment::where('school_id', $schoolId)
                ->where('status', 'active')
                ->pluck('user_id');
            $request->attributes->set('crossUnitUserIds', $crossUnitUserIds);
        }

        $this->applyFilters($query, $request);

        if ($schoolId) {
            $query->where(function ($q) use ($crossUnitUserIds) {
                $q->whereHas('employment')
                    ->orWhereIn('users.id', $crossUnitUserIds);
            });
        } else {
            $query->whereHas('employment');
        }

        $orderBy = $request->get('order_by', 'created_at');
        $orderDir = $request->get('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        $perPage = $request->get('per_page', 10);
        $gtkList = $query->paginate($perPage)->withQueryString();
        $workUnits = WorkUnit::where('is_active', true)->orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();
        $statistics = $this->getStatistics($schoolId);

        $satuanKerja = $request->filled('satuan_kerja')
            ? WorkUnit::find($request->satuan_kerja)
            : null;

        $jenisGtk = JenisGtk::active()->orderBy('urutan')->get();

        return view('gtk.index', compact(
            'gtkList', 'workUnits', 'provinces', 'statistics', 'satuanKerja', 'jenisGtk', 'crossUnitUserIds'
        ));
    }

    public function indextendik(Request $request)
    {
        // Return JSON for AJAX search requests
        if ($request->ajax() || $request->wantsJson() || $request->has('format')) {
            $query = User::with(['gtkProfile', 'employment'])
                ->whereHas('employment', function ($q) {
                    // Tendik = BUKAN pendidik/guru
                    $q->where(function ($qq) {
                        $qq->where('jenis_gtk', 'not like', '%guru%')
                            ->where('jenis_gtk', 'not like', '%pendidik%')
                            ->where(fn ($q3) => $q3->whereNull('jabatan')->orWhere('jabatan', 'not like', '%guru%'));
                    });
                });

            // Scoped filter: only GTK belonging to the user's school context
            $schoolId = $request->attributes->get('schoolContextId');
            if ($schoolId) {
                $query->whereIn('users.id', SchoolGroupService::userIdsForSchool($schoolId));
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
            // Tendik = BUKAN pendidik/guru
            $q->where(function ($qq) {
                $qq->where('jenis_gtk', 'not like', '%guru%')
                    ->where('jenis_gtk', 'not like', '%pendidik%')
                    ->where(fn ($q3) => $q3->whereNull('jabatan')->orWhere('jabatan', 'not like', '%guru%'));
            });
        });

        $this->applyFilters($query, $request);

        // Scoped filter: only GTK belonging to the user's school context
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $crossUnitUserIds = collect($request->attributes->get('crossUnitUserIds', []));
            $query->whereIn('users.id', collect(SchoolGroupService::userIdsForSchool($schoolId))->merge($crossUnitUserIds)->unique());
        }

        $orderBy = $request->get('order_by', 'created_at');
        $orderDir = $request->get('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        $perPage = $request->get('per_page', 10);
        $gtkList = $query->paginate($perPage)->withQueryString();
        $workUnits = WorkUnit::where('is_active', true)->orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();
        $statistics = $this->getStatistics($schoolId);

        $satuanKerja = $request->filled('satuan_kerja')
            ? WorkUnit::find($request->satuan_kerja)
            : null;

        $jenisGtk = JenisGtk::active()->orderBy('urutan')->get();

        return view('gtk.index', compact(
            'gtkList', 'workUnits', 'provinces', 'statistics', 'satuanKerja', 'jenisGtk'
        ));
    }

    public function indexguru(Request $request)
    {
        // Return JSON for AJAX search requests
        if ($request->ajax() || $request->wantsJson() || $request->has('format')) {
            $query = User::with(['gtkProfile', 'employment'])
                ->whereHas('employment', function ($q) {
                    // Guru = pendidik/guru (nilai jenis_gtk nyata: "Pendidik / Guru", dsb.)
                    $q->where(function ($qq) {
                        $qq->where('jenis_gtk', 'like', '%guru%')
                            ->orWhere('jenis_gtk', 'like', '%pendidik%')
                            ->orWhere('jabatan', 'like', '%guru%');
                    });
                });

            // Scoped filter: only GTK belonging to the user's school context
            $schoolId = $request->attributes->get('schoolContextId');
            if ($schoolId) {
                $query->whereIn('users.id', SchoolGroupService::userIdsForSchool($schoolId));
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
            // Guru = pendidik/guru (nilai jenis_gtk nyata: "Pendidik / Guru", dsb.)
            $q->where(function ($qq) {
                $qq->where('jenis_gtk', 'like', '%guru%')
                    ->orWhere('jenis_gtk', 'like', '%pendidik%')
                    ->orWhere('jabatan', 'like', '%guru%');
            });
        });

        $this->applyFilters($query, $request);

        // Scoped filter: only GTK belonging to the user's school context
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->whereIn('users.id', SchoolGroupService::userIdsForSchool($schoolId));
        }

        $orderBy = $request->get('order_by', 'created_at');
        $orderDir = $request->get('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        $perPage = $request->get('per_page', 10);
        $gtkList = $query->paginate($perPage)->withQueryString();
        $workUnits = WorkUnit::where('is_active', true)->orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();
        $statistics = $this->getStatistics($schoolId);

        $satuanKerja = $request->filled('satuan_kerja')
            ? WorkUnit::find($request->satuan_kerja)
            : null;

        $jenisGtk = JenisGtk::active()->orderBy('urutan')->get();

        return view('gtk.index', compact(
            'gtkList', 'workUnits', 'provinces', 'statistics', 'satuanKerja', 'jenisGtk'
        ));
    }

    public function create()
    {
        $this->authorizeGtkMutation();

        $provinces = Province::orderBy('name')->get();
        $workUnits = WorkUnit::where('is_active', true)->orderBy('name')->get();
        $jenisGtk = JenisGtk::active()->orderBy('urutan')->orderBy('nama')->get();
        $jabatan = StructuralPosition::active()->orderBy('urutan')->orderBy('name')->get();

        return view('gtk.create', compact('provinces', 'workUnits', 'jenisGtk', 'jabatan'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeGtkMutation();

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

            // Assign Spatie role from jabatan — strict 14-role constraint
            $role = JabatanRoleMapper::resolve($data['employment']['jabatan'] ?? '');
            if ($user->hasRole($role)) {
                $user->syncRoles([$role]);
            } else {
                $user->assignRole($role);
            }

            if (! empty($data['family_members'])) {
                $this->createFamilyMembers($profile->id, $data['family_members']);
            }

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

        } catch (ValidationException $e) {
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

    public function show(Request $request, string $userId, string $uuid)
    {
        $gtk = User::with([
            'gtkProfile.addresses.province',
            'gtkProfile.addresses.city',
            'gtkProfile.addresses.district',
            'gtkProfile.addresses.village',
            'gtkProfile.familyMembers.linkedGtkProfile',
            'employment',
            'educations',
            'gtkContact',
            'gtkWorkUnits.workUnit',
            'gtkHealthData',
        ])->findOrFail($uuid);

        abort_unless(
            GtkAccess::canAccess($request->user(), $gtk, $request->attributes->get('schoolContextId')),
            403,
            'Anda tidak berwenang melihat GTK dari satuan pendidikan lain.'
        );

        return view('gtk.profile', compact('gtk', 'userId'));
    }

    public function edit(Request $request, string $userId, string $uuid)
    {
        $gtk = User::with([
            'gtkProfile.addresses',
            'gtkProfile.familyMembers.linkedGtkProfile',
            'employment',
            'educations',
            'gtkContact',
            'gtkWorkUnits',
        ])->findOrFail($uuid);

        abort_unless(
            GtkAccess::canAccess($request->user(), $gtk, $request->attributes->get('schoolContextId')),
            403,
            'Anda tidak berwenang mengedit GTK dari satuan pendidikan lain.'
        );

        $provinces = Province::orderBy('name')->get();
        $workUnits = WorkUnit::where('is_active', true)->orderBy('name')->get();
        $jenisGtk = JenisGtk::active()->orderBy('urutan')->orderBy('nama')->get();
        $jabatan = StructuralPosition::active()->orderBy('urutan')->orderBy('name')->get();

        return view('gtk.edit', compact('gtk', 'userId', 'provinces', 'workUnits', 'jenisGtk', 'jabatan'));
    }

    public function update(Request $request, string $userId, string $uuid): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = User::findOrFail($uuid);
            abort_unless(
                GtkAccess::canAccess($request->user(), $user, $request->attributes->get('schoolContextId')),
                403,
                'Anda tidak berwenang mengubah GTK dari satuan pendidikan lain.'
            );
            $data = $this->validateAndStructure($request, $user->id);

            if (! GtkAccess::isManager($request->user())) {
                // Self-service: jangan izinkan perubahan data kepegawaian,
                // satuan kerja, atau status aktif. Mencegah eskalasi hak
                // akses melalui jabatan/role yang tersinkron otomatis.
                $data['employment'] = null;
                $data['work_unit_id'] = null;
                $data['user']['is_active'] = $user->is_active;
            }

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

            if (! empty($data['work_unit_id'])) {
                $this->syncWorkUnit($user->id, $data['work_unit_id'], $data['employment']['jabatan'] ?? null);
            }

            $this->syncFamilyMembers($profileId, $data['family_members'] ?? []);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data GTK berhasil diperbarui',
            ]);

        } catch (ValidationException $e) {
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

    public function destroy(Request $request, string $userId, string $uuid): JsonResponse
    {
        try {
            $user = User::findOrFail($uuid);
            abort_unless(
                GtkAccess::canAccess($request->user(), $user, $request->attributes->get('schoolContextId')),
                403,
                'Anda tidak berwenang menghapus GTK dari satuan pendidikan lain.'
            );
            $user->delete();

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

    /**
     * Store or update GTK health data.
     */
    public function storeHealthData(Request $request, string $userId, string $uuid): JsonResponse
    {
        try {
            $user = User::findOrFail($uuid);

            $data = $request->validate([
                'golongan_darah' => ['nullable', 'string', 'max:2'],
                'tekanan_darah' => ['nullable', 'string', 'max:10'],
                'tinggi_badan' => ['nullable', 'numeric', 'min:0', 'max:300'],
                'berat_badan' => ['nullable', 'numeric', 'min:0', 'max:500'],
                'lingkar_kepala' => ['nullable', 'string', 'max:10'],
                'riwayat_penyakit' => ['nullable', 'string'],
                'alergi' => ['nullable', 'string'],
                'p3k' => ['nullable', 'string'],
                'keluhan_yang_dialami' => ['nullable', 'string'],
            ]);

            GtkHealthData::updateOrCreate(
                ['user_id' => $user->id],
                $data
            );

            return response()->json([
                'success' => true,
                'message' => 'Data kesehatan berhasil disimpan',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('GtkWizardController@storeHealthData failed', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.',
            ], 500);
        }
    }

    /**
     * Update GTK health data (alias for storeHealthData with PUT semantics).
     */
    public function updateHealthData(Request $request, string $userId, string $uuid): JsonResponse
    {
        return $this->storeHealthData($request, $userId, $uuid);
    }

    public function datatable(Request $request): JsonResponse
    {
        $query = User::with(['gtkProfile', 'employment', 'gtkWorkUnits.workUnit']);

        // Scoped filter: GTK internal sekolah + penugasan resmi dari pondok.
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $crossUnitUserIds = GtkCrossUnitAssignment::where('school_id', $schoolId)
                ->where('status', 'active')
                ->pluck('user_id');

            $query->where(function ($q) use ($crossUnitUserIds) {
                $q->whereHas('employment')->orWhereIn('users.id', $crossUnitUserIds);
            })->whereIn('users.id', collect(SchoolGroupService::userIdsForSchool($schoolId))->merge($crossUnitUserIds)->unique());
        } else {
            $query->whereHas('employment');
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
            $actor = $request->user();
            $user = User::findOrFail($uuid);

            abort_unless(
                GtkAccess::canResetPassword($actor, $user, $request->attributes->get('schoolContextId')),
                403,
                'Anda tidak berwenang mereset password GTK ini.'
            );

            $nupy = $user->employment?->nupy;

            if (! $nupy) {
                return response()->json([
                    'success' => false,
                    'message' => 'NUPY tidak ditemukan, tidak dapat mereset password.',
                ], 400);
            }

            $password = $this->defaultPassword($nupy);

            DB::transaction(function () use ($user, $password, $actor) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'must_change_password' => true,
                    'remember_token' => Str::random(60),
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                    'locked_at' => null,
                ])->save();

                if (DB::getSchemaBuilder()->hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }

                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }

                AuditLog::create([
                    'user_id' => $actor->id,
                    'action' => 'GTK_PASSWORD_RESET',
                    'table_name' => 'users',
                    'record_id' => $user->id,
                    'record_type' => 'user',
                    'ip_address' => request()->ip(),
                    'user_agent' => substr((string) request()->userAgent(), 0, 255),
                ]);
            });

            return response()->json([
                'success' => true,
                'password' => $password,
                'message' => 'Password berhasil direset ke default ('.$password.'). Pengguna wajib mengganti password saat login berikutnya.',
            ]);
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('GtkWizardController@resetPassword failed', [
                'message' => $e->getMessage(),
                'target' => $uuid ?? null,
            ]);

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
            'gtk_id' => 'nullable|exists:users,id',
            'relationship' => 'required|string|max:50',
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'pekerjaan' => 'nullable|string|max:100',
            'pendidikan_terakhir' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
            'tempat_lahir' => 'nullable|string|max:100',
            'nama_satuan_pendidikan' => 'nullable|string|max:255',
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

            $member = app(GtkFamilyService::class)->add($profile, $request->only([
                'gtk_id', 'relationship', 'nama', 'jenis_kelamin', 'tempat_lahir',
                'tanggal_lahir', 'pekerjaan', 'pendidikan_terakhir', 'alamat', 'nama_satuan_pendidikan',
            ]));

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
            'gtk_id' => 'nullable|exists:users,id',
            'relationship' => 'required|string|max:50',
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'pekerjaan' => 'nullable|string|max:100',
            'pendidikan_terakhir' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
            'tempat_lahir' => 'nullable|string|max:100',
            'nama_satuan_pendidikan' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $gtk = User::findOrFail($uuid);
            $profile = $gtk->gtkProfile;
            $member = app(GtkFamilyService::class)->updateMember($profile, $id, $request->only([
                'gtk_id', 'relationship', 'nama', 'jenis_kelamin', 'tempat_lahir',
                'tanggal_lahir', 'pekerjaan', 'pendidikan_terakhir', 'alamat', 'nama_satuan_pendidikan',
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
            $gtk = User::findOrFail($uuid);
            app(GtkFamilyService::class)->deleteMember($gtk->gtkProfile, $id);
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
            $crossUnitUserIds = collect($request->attributes->get('crossUnitUserIds', []));
            $query->whereIn('users.id', collect(SchoolGroupService::userIdsForSchool($schoolId))->merge($crossUnitUserIds)->unique());
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

    private function getStatistics(?string $schoolId = null): array
    {
        // Determine scope: global view vs per-school
        $isGlobalView = canPermission('gtk-wizard-global-view') || canPermission('view_global_school_data');

        $baseQuery = function () use ($isGlobalView, $schoolId) {
            $query = User::whereHas('employment');

            if (! $isGlobalView) {
                // Scope ke school context (work unit / kepegawaian)
                $scopedSchoolId = $schoolId ?: optional(SchoolGroupService::getUserSchool(Auth::user()))->id;
                if ($scopedSchoolId) {
                    $query->whereIn('users.id', SchoolGroupService::userIdsForSchool($scopedSchoolId));
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
        $satkerNames = WorkUnit::whereIn('id', array_keys($perSatker))
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
        $completeEdu = User::whereIn('id', $gtkUserIds)->whereHas('educations')->count();
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
            'name_ar' => 'nullable|string|max:255',
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
            'kepegawaian.jenis_gtk' => ['required', Rule::exists('jenis_gtk', 'id')],
            'kepegawaian.jabatan' => [
                'required',
                'string',
                'max:150',
                Rule::exists('positions', 'id'),
            ],
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
                'name_ar' => $validated['name_ar'] ?? null,
                'name_ar_status' => filled($validated['name_ar'] ?? null) ? 'approved' : null,
                'name_ar_reviewed_at' => filled($validated['name_ar'] ?? null) ? now() : null,
                'name_ar_reviewed_by' => filled($validated['name_ar'] ?? null) ? $request->user()?->id : null,
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
                'jenis_gtk_id' => $this->resolveJenisGtkId(
                    $validated['kepegawaian']['jenis_gtk'] ?? null,
                    $validated['kepegawaian']['jabatan'] ?? null
                ),
                'jenis_gtk' => $this->resolveJenisGtkName(
                    $validated['kepegawaian']['jenis_gtk'] ?? null,
                    $this->resolveJenisGtkId(
                        $validated['kepegawaian']['jenis_gtk'] ?? null,
                        $validated['kepegawaian']['jabatan'] ?? null
                    )
                ),
                'jabatan_id' => $this->resolveJabatanId(
                    $validated['kepegawaian']['jabatan'] ?? null,
                    $this->resolveJenisGtkId(
                        $validated['kepegawaian']['jenis_gtk'] ?? null,
                        $validated['kepegawaian']['jabatan'] ?? null
                    )
                ),
                'jabatan' => $this->resolveJabatanName($validated['kepegawaian']['jabatan'] ?? null),
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

    private function assignWorkUnit(string $userId, string $workUnitId, ?string $jabatan = null): void
    {
        GtkWorkUnit::where('user_id', $userId)->update(['is_primary' => false]);

        // updateOrCreate supaya import ulang tidak menumpuk baris penempatan.
        GtkWorkUnit::updateOrCreate(
            ['user_id' => $userId, 'work_unit_id' => $workUnitId],
            [
                'jabatan' => $jabatan,
                'position_id' => $this->positionIdForName($jabatan),
                'is_primary' => true,
            ]
        );

        // Create StructuralAssignment so domain-based permissions work correctly.
        // Use the work unit's domain (not the position's seeder domain) so GTK placed
        // in a Satuan Pendidikan work unit get the correct domain regardless of
        // whether their position is "Staf Tata Usaha" (seeder: Keuangan) or "Guru".
        $workUnit = WorkUnit::with('domain')->find($workUnitId);
        if (! $workUnit || ! $workUnit->domain_id) {
            return;
        }

        // Find a structural position whose domain matches the work unit's domain.
        // We prefer positions that share the same job title name, but fall back to
        // any active position in the matching domain.
        $positionId = StructuralPosition::where('domain_id', $workUnit->domain_id)
            ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$jabatan ?? ''])
            ->where('is_active', true)
            ->value('id');

        if (! $positionId) {
            $positionId = StructuralPosition::where('domain_id', $workUnit->domain_id)
                ->where('is_active', true)
                ->orderBy('urutan')
                ->value('id');
        }

        if (! $positionId) {
            return;
        }

        $schoolId = School::where('work_unit_id', $workUnitId)->value('id');
        if (! $schoolId) {
            return;
        }

        $academicYearId = AcademicYear::where('is_active', true)
            ->orderBy('start_date', 'desc')
            ->value('id');

        if (! $academicYearId) {
            return;
        }

        StructuralAssignment::updateOrCreate(
            [
                'user_id' => $userId,
                'position_id' => $positionId,
                'school_id' => $schoolId,
                'academic_year_id' => $academicYearId,
            ],
            [
                'status' => 'active',
                'start_date' => now()->toDateString(),
                'notes' => "Assigned via GTK wizard ({$userId})",
            ]
        );
    }

    /**
     * Refresh or create the StructuralAssignment for a user so that
     * activeDomainIds() resolves against the work-unit's domain, not
     * the seeder-snapshot position domain.
     */
    private function refreshStructuralAssignment(string $userId, string $workUnitId, ?string $jabatan = null): void
    {
        $workUnit = WorkUnit::with('domain')->find($workUnitId);
        if (! $workUnit || ! $workUnit->domain_id) {
            return;
        }

        // Find a structural position whose domain matches the work unit's domain.
        $positionId = StructuralPosition::where('domain_id', $workUnit->domain_id)
            ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$jabatan ?? ''])
            ->where('is_active', true)
            ->value('id');

        if (! $positionId) {
            $positionId = StructuralPosition::where('domain_id', $workUnit->domain_id)
                ->where('is_active', true)
                ->orderBy('urutan')
                ->value('id');
        }

        if (! $positionId) {
            return;
        }

        $schoolId = School::where('work_unit_id', $workUnitId)->value('id');
        if (! $schoolId) {
            return;
        }

        $academicYearId = AcademicYear::where('is_active', true)
            ->orderBy('start_date', 'desc')
            ->value('id');

        if (! $academicYearId) {
            return;
        }

        StructuralAssignment::updateOrCreate(
            [
                'user_id' => $userId,
                'position_id' => $positionId,
                'school_id' => $schoolId,
                'academic_year_id' => $academicYearId,
            ],
            [
                'status' => 'active',
                'start_date' => now()->toDateString(),
                'notes' => "Assigned via GTK wizard ({$userId})",
            ]
        );
    }

    private function createFamilyMembers(string $profileId, array $members): void
    {
        $profile = GtkProfile::findOrFail($profileId);
        app(GtkFamilyService::class)->sync($profile, $members);
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
            ? $workUnit->update([
                'work_unit_id' => $workUnitId,
                'jabatan' => $jabatan,
                'position_id' => $this->positionIdForName($jabatan),
            ])
            : GtkWorkUnit::create([
                'user_id' => $userId,
                'work_unit_id' => $workUnitId,
                'jabatan' => $jabatan,
                'position_id' => $this->positionIdForName($jabatan),
                'is_primary' => true,
            ]);

        // Refresh StructuralAssignment so domain-based permissions stay in sync.
        $this->refreshStructuralAssignment($userId, $workUnitId, $jabatan);
    }

    private function syncFamilyMembers(string $profileId, array $members): void
    {
        $profile = GtkProfile::findOrFail($profileId);
        app(GtkFamilyService::class)->sync($profile, $members);
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
            'desa' => $this->lookupName(Village::class, $raw['village_code']),
            'kecamatan' => $this->lookupName(District::class, $raw['district_code']),
            'kab_kota' => $this->lookupName(City::class, $raw['city_code']),
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

    protected function resolveJenisGtkId(?string $jenisGtk, ?string $jabatan = null): ?string
    {
        // ── Step 0: Kosong → null ────────────────────────────────────────
        if (! $jenisGtk) {
            return $this->resolveJenisGtkIdFromJabatan($jabatan);
        }

        // ── Step 1: UUID detection ───────────────────────────────────────
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $jenisGtk)) {
            return JenisGtk::where('id', $jenisGtk)->value('id');
        }

        $normalize = fn ($s) => preg_replace('/[^a-z0-9]/', '', strtolower((string) $s));
        $target = $normalize($jenisGtk);
        $sqlExpr = "REPLACE(REPLACE(REPLACE(REPLACE(LOWER(nama), ' ', ''), '/', ''), '-', ''), '.', '')";

        // ── Step 2: Exact match ──────────────────────────────────────────
        $id = JenisGtk::whereRaw("{$sqlExpr} = ?", [$target])->value('id');
        if ($id) {
            return $id;
        }

        // ── Step 3: LIKE match ───────────────────────────────────────────
        $id = JenisGtk::whereRaw("{$sqlExpr} LIKE ?", ['%'.$target.'%'])->value('id');
        if ($id) {
            return $id;
        }

        // ── Step 4: Fallback — cari dari JABATAN ─────────────────────────
        return $this->resolveJenisGtkIdFromJabatan($jabatan);
    }

    protected function resolveJenisGtkIdFromJabatan(?string $jabatan): ?string
    {
        if (! $jabatan) {
            return null;
        }

        $normalize = fn ($s) => preg_replace('/[^a-z0-9]/', '', strtolower((string) $s));
        $target = $normalize($jabatan);

        $sqlExpr = "REPLACE(REPLACE(REPLACE(REPLACE(LOWER(name), ' ', ''), '/', ''), '-', ''), '.', '')";

        // Cari StructuralPosition dengan nama jabatan yang match
        $sp = StructuralPosition::whereRaw("{$sqlExpr} = ?", [$target])->first();
        if ($sp && $sp->jenis_gtk_id) {
            return $sp->jenis_gtk_id;
        }

        // Fallback: LIKE match
        $sp = StructuralPosition::whereRaw("{$sqlExpr} LIKE ?", ['%'.$target.'%'])->first();
        if ($sp && $sp->jenis_gtk_id) {
            return $sp->jenis_gtk_id;
        }

        return null;
    }

    /**
     * Return human-readable nama jenis GTK.
     * Kalau input null atau tidak ketemu, fallback ke lookup by ID.
     */
    private function resolveJenisGtkName(?string $value, ?string $resolvedId = null): ?string
    {
        // Kalau value kosong, ambil dari resolvedId
        if (! $value) {
            return $resolvedId ? JenisGtk::find($resolvedId)?->nama : null;
        }

        // Kalau value UUID, cari namanya
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            return JenisGtk::find($value)?->nama;
        }

        // Cek apakah jenis GTK dengan nama ini ADA di DB
        $normalize = fn ($s) => preg_replace('/[^a-z0-9]/', '', strtolower((string) $s));
        $target = $normalize($value);
        $sqlExpr = "REPLACE(REPLACE(REPLACE(REPLACE(LOWER(nama), ' ', ''), '/', ''), '-', ''), '.', '')";

        $found = JenisGtk::whereRaw("{$sqlExpr} = ?", [$target])->first();
        if ($found) {
            return $found->nama;
        }

        // Kalau tidak ketemu tapi ada resolvedId → ambil dari resolvedId
        // (artinya value dari Excel salah, tapi sudah di-resolve dari jabatan)
        if ($resolvedId) {
            return JenisGtk::find($resolvedId)?->nama ?? $value;
        }

        return $value;
    }

    /**
     * Accept UUID or name — return StructuralPosition UUID.
     * Case-insensitive, whitespace-insensitive, punctuation-insensitive.
     * If jenisGtkId given: prefer match within that jenis, fallback to global.
     */
    protected function resolveJabatanId(?string $jabatanName, ?string $jenisGtkId = null): ?string
    {
        $match = $this->positionMatch($jabatanName, $jenisGtkId);

        // Hanya kecocokan yang yakin yang dipakai; yang ragu harus dikonfirmasi
        // lewat halaman import supaya tidak salah menempatkan jabatan.
        return $match['status'] === 'auto' ? $match['match']['id'] : null;
    }

    /**
     * Accept UUID or name — return human-readable name.
     * Kalau teks bebas tidak persis sama, pakai nama dari id hasil pencocokan
     * supaya kolom teks tetap konsisten dengan foreign key-nya.
     */
    private function resolveJabatanName(?string $value, ?string $resolvedId = null): ?string
    {
        if (! $value) {
            return $resolvedId ? StructuralPosition::find($resolvedId)?->name : null;
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            return StructuralPosition::find($value)?->name;
        }

        // Teks bebas dinormalkan ke nama jabatan master agar tidak menyimpang.
        $name = StructuralPosition::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($value))])
            ->value('name');

        if ($name) {
            return $name;
        }

        return $resolvedId ? (StructuralPosition::find($resolvedId)?->name ?? $value) : $value;
    }

    /**
     * Id jabatan master untuk sebuah nama jabatan.
     */
    private function positionIdForName(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        return StructuralPosition::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($name))])
            ->value('id');
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
        $this->authorizeGtkMutation();

        $workUnits = WorkUnit::where('is_active', true)->orderBy('name')->get();
        $jenisGtk = JenisGtk::where('is_active', true)->orderBy('urutan')->get();

        return view('gtk.import', compact('workUnits', 'jenisGtk'));
    }

    public function importTemplate(string $userId, ?string $workUnitId = null)
    {
        $this->authorizeGtkMutation();

        // Satuan kerja tidak wajib: template bisa dipakai untuk beberapa unit
        // sekaligus karena kolom "satuan_kerja" dibaca per baris.
        $workUnit = $workUnitId ? WorkUnit::find($workUnitId) : null;

        $slug = $workUnit ? Str::slug($workUnit->name) : 'semua-satuan-kerja';
        $filename = 'template-import-gtk-'.$slug.'-'.now()->format('Ymd').'.xlsx';

        return Excel::download(
            new GtkImportTemplateExport($workUnit?->name ?? 'Semua Satuan Kerja'),
            $filename
        );
    }

    /**
     * Cocokkan nilai teks dari file Excel sebelum diimport.
     *
     * Halaman preview memakai hasil ini untuk menandai baris yang sudah yakin
     * dan baris yang perlu dikonfirmasi, sehingga tidak ada tebakan diam-diam.
     */
    public function resolveImportValues(Request $request, string $userId): JsonResponse
    {
        $this->authorizeGtkMutation();

        $validated = $request->validate([
            'satuan_kerja' => 'array',
            'satuan_kerja.*' => 'string|max:150',
            'jabatan' => 'array',
            'jabatan.*' => 'string|max:150',
            'jenis_gtk' => 'nullable|string|max:150',
        ]);

        $jenisGtkId = $this->resolveJenisGtkId($validated['jenis_gtk'] ?? null);

        $workUnits = [];
        foreach (array_unique($validated['satuan_kerja'] ?? []) as $value) {
            $workUnits[$value] = $this->workUnitMatch($value);
        }

        $positions = [];
        foreach (array_unique($validated['jabatan'] ?? []) as $value) {
            $positions[$value] = $this->positionMatch($value, $jenisGtkId);
        }

        return response()->json([
            'success' => true,
            'satuan_kerja' => $workUnits,
            'jabatan' => $positions,
        ]);
    }

    public function importStore(Request $request, string $userId): JsonResponse
    {
        $this->authorizeGtkMutation();

        try {
            $validated = $request->validate([
                'work_unit_id' => 'nullable|exists:work_units,id',
                'rows' => 'required|array|min:1',
                'rows.*' => 'array',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: '.json_encode($e->errors()),
                'failed' => [],
            ], 422);
        }

        // Pilihan manual hanya jadi cadangan; kolom "satuan_kerja" per baris menang.
        $workUnitId = $request->input('work_unit_id') ?: null;
        $rows = $request->input('rows');

        $imported = 0;
        $updated = 0;
        $failed = [];

        foreach ($rows as $index => $row) {
            $rowNum = $row['_rowNum'] ?? ($index + 2);

            $norm = $this->normalizeImportRow($row);
            if ($norm['error']) {
                $failed[] = ['row' => $rowNum, 'reason' => $norm['error']];

                continue;
            }
            $row = $norm['row'];

            DB::beginTransaction();
            try {
                // Wajib hanya nama & NUPY (kunci pencocokan). NIK, No HP, dan
                // jabatan opsional; email dibuat otomatis bila kosong.
                $required = ['name', 'nupy'];
                $missing = [];
                foreach ($required as $field) {
                    if (empty($row[$field])) {
                        $missing[] = $field;
                    }
                }
                if (! empty($missing)) {
                    throw new \InvalidArgumentException('Field wajib kosong: '.implode(', ', $missing));
                }

                $nupy = $row['nupy'];

                if (empty($row['email'])) {
                    $row['email'] = $this->generateImportEmail($nupy);
                }

                // Satuan kerja: pilihan di preview menang, lalu kolom Excel, lalu
                // pilihan di langkah 1. Kandidat teratas dipakai bila ragu.
                $rowWorkUnitId = $this->resolveImportWorkUnit($row, $workUnitId);

                // NUPY yang sudah terdaftar diperbarui, bukan ditolak.
                // Data wajib (nama, email, NIK, NUPY, No HP) tidak pernah ditimpa.
                $existing = GtkEmployment::where('nupy', $nupy)->first();

                if ($existing) {
                    $this->updateFromImportRow($existing, $row, $rowWorkUnitId);
                    DB::commit();
                    $updated++;

                    continue;
                }

                if (User::where('email', $row['email'])->exists()) {
                    throw new \InvalidArgumentException("Email '{$row['email']}' sudah terdaftar pada GTK lain (NUPY berbeda).");
                }
                if (! empty($row['nik']) && GtkProfile::where('nik', $row['nik'])->exists()) {
                    throw new \InvalidArgumentException("NIK '{$row['nik']}' sudah terdaftar pada GTK lain (NUPY berbeda).");
                }

                $user = User::create([
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => Hash::make($this->defaultPassword($nupy)),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);

                $profile = $this->createGtkProfile($user->id, [
                    'nik' => $row['nik'] ?? null,
                    'no_kk' => $row['no_kk'] ?? null,
                    'tempat_lahir' => $row['tempat_lahir'] ?? null,
                    'tanggal_lahir' => $row['tanggal_lahir'] ?? null,
                    'jenis_kelamin' => $row['jenis_kelamin'] ?? null,
                    'golongan_darah' => $row['golongan_darah'] ?? null,
                    'agama' => $row['agama'] ?? null,
                    'status_perkawinan' => $row['status_perkawinan'] ?? 'belum_kawin',
                    'npwp' => $row['npwp'] ?? null,
                ]);

                $importedFamily = $this->familyMembersFromImportRow($row, $profile);
                app(GtkFamilyService::class)->sync($profile, $importedFamily);

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

                $jenisGtk = $row['jenis_gtk'] ?? null;
                $jabatan = $row['jabatan'] ?? null;

                // ── Resolve jenis GTK — dengan fallback ke jabatan ─────────────────
                // Kalau jenis GTK dari Excel tidak ketemu, cari dari jabatan
                $jenisGtkId = $this->resolveJenisGtkId($jenisGtk, $jabatan);

                // ── Resolve jabatan (setelah jenis GTK benar) ───────────────────────
                $jabatanId = $this->resolveImportJabatan($row, $jenisGtkId);

                // ── Validasi ────────────────────────────────────────────────────────
                if (! $jenisGtkId) {
                    // Kalau benar-benar tidak ketemu (bahkan setelah fallback)
                    if ($jenisGtk) {
                        throw new \InvalidArgumentException(
                            "Jenis GTK '{$jenisGtk}' tidak ditemukan, dan juga tidak bisa di-resolve "
                            ."dari jabatan '{$jabatan}'. Cek data master `jenis_gtk` & `structural_positions`."
                        );
                    }
                    throw new \InvalidArgumentException(
                        "Jenis GTK kosong dan jabatan '{$jabatan}' juga tidak bisa di-resolve ke jenis GTK manapun."
                    );
                }

                if ($jenisGtk && ! $jenisGtkId) {
                    throw new \InvalidArgumentException(
                        "Jenis GTK '{$jenisGtk}' tidak ditemukan di master data. "
                        .'Cek: apakah nama jenis GTK di Excel sudah sesuai dengan tabel `jenis_gtk`?'
                    );
                }

                $this->createEmployment($user->id, [
                    'nupy' => $nupy,
                    'jenis_gtk' => $this->resolveJenisGtkName($jenisGtk),
                    'jenis_gtk_id' => $jenisGtkId,
                    'jabatan' => $this->resolveJabatanName($jabatan),
                    'jabatan_id' => $jabatanId,
                    'status_kepegawaian' => $row['status_kepegawaian'] ?? null,
                    'tmt' => $row['tmt'] ?? null,
                    'nomor_sk' => $row['nomor_sk'] ?? null,
                    'tanggal_sk' => $row['tanggal_sk'] ?? null,
                    'pangkat_golongan' => $row['pangkat_golongan'] ?? null,
                    'satuan_kerja' => WorkUnit::where('id', $rowWorkUnitId)->value('name'),
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

                $this->assignWorkUnit($user->id, $rowWorkUnitId, $jabatan);

                $roleName = $this->roleNameForJabatan($jabatan, $jabatanId);

                if ($roleName) {
                    $user->syncRoles([$roleName]);
                } elseif (! empty($jabatan)) {
                    // Jabatan opsional: hanya jabatan yang diisi tapi tanpa role
                    // yang dianggap masalah.
                    throw new \InvalidArgumentException(
                        "Role untuk jabatan '{$jabatan}' tidak ditemukan. "
                        ."Pastikan jabatan '{$jabatan}' sudah terdaftar di StructuralPosition dengan `role_id` yang benar."
                        .$this->suggestPositions($jabatan, $jenisGtkId)
                    );
                }

                DB::commit();
                $imported++;
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Import GTK gagal baris '.$rowNum, [
                    'message' => $e->getMessage(),
                    'row_data' => $row,
                    'trace' => $e->getTraceAsString(),
                ]);
                $failed[] = [
                    'row' => $rowNum,
                    'reason' => $this->parseDatabaseError($e),
                    'data' => $row,
                ];
            }
        }

        return response()->json([
            'success' => ($imported + $updated) > 0,
            'imported' => $imported,
            'updated' => $updated,
            'failed' => $failed,
            'message' => $this->buildImportMessage($imported, $updated, count($failed)),
        ]);
    }

    private function parseDatabaseError(\Exception $e): string
    {
        $msg = $e->getMessage();

        // Extract MySQL SQLSTATE and column info from truncation/hydra exceptions.
        if (preg_match('/SQLSTATE\\[(\\w+)\\].*?Data truncated for column \'(?<col>\w+)\'/', $msg, $m)) {
            return "Kolom '{$m['col']}' tidak sesuai format (enum/value).";
        }
        if (preg_match('/SQLSTATE.*?Duplicate entry \'(?<val>[^\']+)\' for key/', $msg, $m)) {
            $label = str_starts_with($m['val'], 'email_') ? 'Email'
                : (str_starts_with($m['val'], 'nupy_') ? 'NUPY'
                : 'NIK');

            return "{$label} '{$m['val']}' sudah terdaftar.";
        }
        if (preg_match('/Column \'(?<col>\w+)\' cannot be null/', $msg, $m)) {
            return "Kolom '{$m['col']}' wajib diisi.";
        }

        // Fallback: strip SQL boilerplate, keep human-readable part.
        $clean = preg_replace('/^SQLSTATE\[[\w]+]: \w+: \d+ /', '', $msg);
        $clean = preg_replace('/ \(Connection: mysql\)$/', '', $clean);
        $clean = preg_replace('/ \([\w\s\.]+\)$/', '', $clean);

        return $clean ?: $msg;
    }

    private function buildImportMessage(int $created, int $updated, int $failed): string
    {
        if ($created === 0 && $updated === 0 && $failed === 0) {
            return 'Tidak ada data untuk diimport.';
        }
        if ($created === 0 && $updated === 0) {
            return 'Gagal mengimport semua data. Periksa pesan error pada setiap baris.';
        }

        $parts = [];
        if ($created > 0) {
            $parts[] = "{$created} GTK baru";
        }
        if ($updated > 0) {
            $parts[] = "{$updated} GTK diperbarui";
        }

        $summary = implode(' dan ', $parts);

        return $failed === 0
            ? "Berhasil: {$summary}."
            : "Berhasil: {$summary}, {$failed} gagal. Periksa detail error di bawah.";
    }

    /**
     * Cocokkan teks satuan kerja dari Excel ke work_units.id.
     * Menerima UUID, kode, atau nama — tidak peka huruf besar/kecil & tanda baca.
     */
    /**
     * Ambang keyakinan pencocokan: skor >= AUTO dipakai langsung,
     * skor di antara AMBIGUOUS dan AUTO wajib dikonfirmasi manusia.
     */
    private const MATCH_AUTO = 90;

    private const MATCH_AMBIGUOUS = 70;

    /**
     * Istilah yang dianggap sama saat mencocokkan satuan kerja.
     * "Pengasuhan" adalah nama lain asrama di lingkungan pesantren.
     */
    private const MATCH_SYNONYMS = [
        'pengasuhan' => 'asrama',
        'pengasuh' => 'asrama',
        'pengasramaan' => 'asrama',
        'dormitori' => 'asrama',
        'dormitory' => 'asrama',
        'boarding' => 'asrama',
    ];

    private function normalizeMatchKey(?string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $value))) ?? '';
    }

    /**
     * Samakan istilah sejenis sebelum dibandingkan, mis. "Pengasuhan SMP Putra"
     * dibaca "Asrama SMP Putra" agar cocok dengan unit asrama yang ada.
     */
    private function canonicalizeMatchValue(?string $value): string
    {
        $text = strtolower(trim((string) $value));

        foreach (self::MATCH_SYNONYMS as $from => $to) {
            $text = preg_replace('/\b'.preg_quote($from, '/').'\b/', $to, $text) ?? $text;
        }

        return $text;
    }

    /**
     * Skor kemiripan 0–100 antara teks bebas dan satu kandidat master.
     * Sengaja tidak pernah memberi 100 untuk selain kesamaan persis.
     */
    private function similarityScore(?string $input, ?string $candidate): int
    {
        $left = $this->canonicalizeMatchValue($input);
        $right = $this->canonicalizeMatchValue($candidate);

        $a = $this->normalizeMatchKey($left);
        $b = $this->normalizeMatchKey($right);

        if ($a === '' || $b === '') {
            return 0;
        }

        if ($a === $b) {
            return 100;
        }

        // Kesamaan kata: "Guru Umum" vs "Guru Mata Pelajaran Umum".
        $tokensA = array_values(array_unique(array_filter(preg_split('/[^a-z0-9]+/', $left) ?: [])));
        $tokensB = array_values(array_unique(array_filter(preg_split('/[^a-z0-9]+/', $right) ?: [])));

        // Semua kata input ada di nama kandidat, mis. "Pengasuhan MA Plus" vs
        // "Asrama MA Plus Abu Hurairah Mataram". Butuh minimal dua kata supaya
        // satu kata umum seperti "Guru" atau "Asrama" tetap minta konfirmasi.
        if (count($tokensA) >= 2 && $tokensB !== [] && array_diff($tokensA, $tokensB) === []) {
            return self::MATCH_AUTO;
        }

        // Satu nama memuat yang lain, mis. "MTs Contoh Kota" vs "MTs Contoh".
        if (str_contains($a, $b) || str_contains($b, $a)) {
            $shorter = min(strlen($a), strlen($b));
            $longer = max(strlen($a), strlen($b));

            return ($longer > 0 && $shorter / $longer >= 0.6) ? 92 : 80;
        }

        if ($tokensA !== [] && $tokensB !== []) {
            $common = count(array_intersect($tokensA, $tokensB));
            $union = count(array_unique(array_merge($tokensA, $tokensB)));
            $jaccard = $union > 0 ? $common / $union : 0;

            if ($jaccard >= 0.6) {
                return (int) round(70 + 20 * $jaccard);
            }
        }

        similar_text($a, $b, $percent);

        // Dikali 0,9 supaya kemiripan huruf saja tidak pernah lolos sebagai yakin.
        return (int) round($percent * 0.9);
    }

    /**
     * Ubah daftar kandidat ber-skor menjadi keputusan: auto / ambiguous / unknown.
     */
    private function classifyMatch(array $scored): array
    {
        $scored = array_values(array_filter($scored, fn ($candidate) => ($candidate['score'] ?? 0) >= 40));

        if ($scored === []) {
            return ['status' => 'unknown', 'score' => 0, 'match' => null, 'candidates' => []];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        $top = $scored[0];
        $topCount = count(array_filter($scored, fn ($candidate) => $candidate['score'] === $top['score']));

        $status = match (true) {
            $topCount > 1 => 'ambiguous',
            $top['score'] >= self::MATCH_AUTO => 'auto',
            $top['score'] >= self::MATCH_AMBIGUOUS => 'ambiguous',
            default => 'unknown',
        };

        return [
            'status' => $status,
            'score' => $top['score'],
            'match' => $status === 'auto' ? ['id' => $top['id'], 'label' => $top['label']] : null,
            'candidates' => array_slice($scored, 0, 5),
        ];
    }

    /**
     * Cocokkan teks satuan kerja ke work_units (nama atau kode).
     */
    private function workUnitMatch(?string $value): array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return ['status' => 'empty', 'score' => 0, 'match' => null, 'candidates' => []];
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            $unit = WorkUnit::find($value);

            return $unit
                ? ['status' => 'auto', 'score' => 100, 'match' => ['id' => $unit->id, 'label' => $unit->name], 'candidates' => []]
                : ['status' => 'unknown', 'score' => 0, 'match' => null, 'candidates' => []];
        }

        $scored = WorkUnit::query()
            ->get(['id', 'name', 'code'])
            ->map(fn (WorkUnit $unit) => [
                'id' => $unit->id,
                'label' => $unit->name,
                'code' => $unit->code,
                'score' => max(
                    $this->similarityScore($value, $unit->name),
                    $this->similarityScore($value, $unit->code),
                ),
            ])
            ->all();

        return $this->classifyMatch($scored);
    }

    /**
     * Cocokkan teks jabatan ke structural_positions.
     */
    private function positionMatch(?string $value, ?string $jenisGtkId = null): array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return ['status' => 'empty', 'score' => 0, 'match' => null, 'candidates' => []];
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            $position = StructuralPosition::find($value);

            return $position
                ? ['status' => 'auto', 'score' => 100, 'match' => ['id' => $position->id, 'label' => $position->name], 'candidates' => []]
                : ['status' => 'unknown', 'score' => 0, 'match' => null, 'candidates' => []];
        }

        $positions = StructuralPosition::query()
            ->where('is_active', true)
            ->when($jenisGtkId, fn ($q) => $q->where('jenis_gtk_id', $jenisGtkId))
            ->get(['id', 'name']);

        // Kalau filter jenis GTK terlalu sempit, ulangi tanpa filter.
        if ($positions->isEmpty() && $jenisGtkId) {
            $positions = StructuralPosition::query()->where('is_active', true)->get(['id', 'name']);
        }

        $scored = $positions
            ->map(fn (StructuralPosition $position) => [
                'id' => $position->id,
                'label' => $position->name,
                'score' => $this->similarityScore($value, $position->name),
            ])
            ->all();

        return $this->classifyMatch($scored);
    }

    private function resolveWorkUnitId(?string $value): ?string
    {
        $match = $this->workUnitMatch($value);

        return $match['status'] === 'auto' ? $match['match']['id'] : null;
    }

    /**
     * Satuan kerja untuk satu baris import.
     * Urutan: konfirmasi pengguna > kolom Excel > pilihan manual.
     */
    private function resolveImportWorkUnit(array $row, ?string $fallbackId): string
    {
        $override = trim((string) ($row['work_unit_id'] ?? ''));

        if ($override !== '') {
            $id = WorkUnit::where('id', $override)->value('id');

            if (! $id) {
                throw new \InvalidArgumentException('Satuan kerja hasil konfirmasi tidak ditemukan di master.');
            }

            return $id;
        }

        $value = trim((string) ($row['satuan_kerja'] ?? ''));
        $match = $this->workUnitMatch($value);

        if ($match['status'] === 'empty') {
            if ($fallbackId) {
                return $fallbackId;
            }

            throw new \InvalidArgumentException(
                'Satuan kerja kosong. Isi kolom "satuan_kerja" di Excel atau pilih satuan kerja di langkah 1.'
            );
        }

        if ($match['status'] === 'auto') {
            return $match['match']['id'];
        }

        // Ragu atau tidak dikenali: pakai kandidat teratas supaya baris tetap
        // terisi, lalu pengguna bisa mengubahnya di preview.
        if (! empty($match['candidates'][0]['id'])) {
            return $match['candidates'][0]['id'];
        }

        throw new \InvalidArgumentException(
            $this->matchFailureMessage('Satuan kerja', $value, $match, ' Samakan dengan nama/kode di sheet "Referensi: Satuan Kerja".')
        );
    }

    /**
     * Jabatan untuk satu baris import. Ragu = ditolak dengan daftar kandidat.
     */
    private function resolveImportJabatan(array $row, ?string $jenisGtkId): ?string
    {
        $override = trim((string) ($row['jabatan_id'] ?? ''));

        if ($override !== '') {
            $id = StructuralPosition::where('id', $override)->value('id');

            if (! $id) {
                throw new \InvalidArgumentException('Jabatan hasil konfirmasi tidak ditemukan di master.');
            }

            return $id;
        }

        $value = trim((string) ($row['jabatan'] ?? ''));

        if ($value === '') {
            return null;
        }

        $match = $this->positionMatch($value, $jenisGtkId);

        if ($match['status'] === 'auto') {
            return $match['match']['id'];
        }

        // Jabatan opsional tapi dianjurkan: ambil kandidat teratas bila ada,
        // biarkan kosong bila sama sekali tidak ada yang mirip.
        return $match['candidates'][0]['id'] ?? null;
    }

    private function matchFailureMessage(string $label, string $value, array $match, string $hint = ''): string
    {
        $candidates = collect($match['candidates'])
            ->take(3)
            ->map(fn ($candidate) => "'{$candidate['label']}' ({$candidate['score']}%)")
            ->implode(', ');

        $problem = $match['status'] === 'ambiguous'
            ? 'ambigu, perlu dikonfirmasi di preview.'
            : 'tidak dikenali.';

        return "{$label} '{$value}' {$problem}{$hint} Kandidat terdekat: ".($candidates ?: '-').'.';
    }

    /**
     * Nama role yang menempel pada sebuah jabatan.
     */
    private function roleNameForJabatan(?string $jabatan, ?string $jabatanId): ?string
    {
        if (! empty($jabatanId)) {
            $position = StructuralPosition::with('role')->find($jabatanId);
            if ($position?->role) {
                return $position->role->name;
            }
        }

        if (empty($jabatan)) {
            return null;
        }

        $normalize = fn ($s) => preg_replace('/[^a-z0-9]/', '', strtolower((string) $s));
        $target = $normalize($jabatan);

        $position = StructuralPosition::with('role')
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(LOWER(name), ' ', ''), '/', ''), '-', ''), '.', '') = ?",
                [$target]
            )
            ->first();

        return $position?->role?->name;
    }

    /**
     * Saran nama jabatan terdekat supaya pesan error memberi jalan keluar.
     */
    private function suggestPositions(?string $jabatan, ?string $jenisGtkId = null, int $limit = 3): string
    {
        $normalize = fn ($s) => preg_replace('/[^a-z0-9]/', '', strtolower((string) $s));
        $target = $normalize($jabatan ?? '');

        if ($target === '') {
            return '';
        }

        $names = StructuralPosition::query()
            ->when($jenisGtkId, fn ($q) => $q->where('jenis_gtk_id', $jenisGtkId))
            ->where('is_active', true)
            ->pluck('name')
            ->map(function ($name) use ($normalize, $target) {
                similar_text($normalize($name), $target, $percent);

                return ['name' => $name, 'score' => $percent];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('name')
            ->all();

        return $names === [] ? '' : ' Mungkin maksud Anda: '.implode(', ', $names).'.';
    }

    /**
     * Perbarui GTK yang sudah ada, dicocokkan lewat NUPY.
     *
     * Data wajib tidak pernah ditimpa: nama, email, NIK, NUPY, dan No HP.
     * Kolom lain hanya diisi bila sel di Excel tidak kosong, supaya file
     * parsial tidak menghapus data yang sudah benar.
     */
    private function updateFromImportRow(GtkEmployment $employment, array $row, string $workUnitId): void
    {
        $user = $employment->user;

        if (! $user) {
            throw new \InvalidArgumentException("NUPY '{$employment->nupy}' terdaftar tetapi akun user-nya tidak ditemukan.");
        }

        $profile = $user->gtkProfile;
        $jenisGtk = $row['jenis_gtk'] ?? null;
        $jabatan = $row['jabatan'] ?? null;

        $jenisGtkId = $this->resolveJenisGtkId($jenisGtk, $jabatan) ?? $employment->jenis_gtk_id;
        $jabatanId = $jabatan
            ? ($this->resolveImportJabatan($row, $jenisGtkId) ?? $employment->jabatan_id)
            : $employment->jabatan_id;

        if ($jabatan && ! $jabatanId) {
            throw new \InvalidArgumentException(
                "Jabatan '{$jabatan}' tidak ditemukan di master jabatan."
                .$this->suggestPositions($jabatan, $jenisGtkId)
            );
        }

        $previousJabatanId = $employment->jabatan_id;

        $employment->update($this->onlyFilled([
            'status_kepegawaian' => $row['status_kepegawaian'] ?? null,
            'tmt' => $row['tmt'] ?? null,
            'nomor_sk' => $row['nomor_sk'] ?? null,
            'tanggal_sk' => $row['tanggal_sk'] ?? null,
            'pangkat_golongan' => $row['pangkat_golongan'] ?? null,
            'jenis_gtk' => $this->resolveJenisGtkName($jenisGtk, $jenisGtkId),
            'jenis_gtk_id' => $jenisGtkId,
            'jabatan' => $this->resolveJabatanName($jabatan, $jabatanId),
            'jabatan_id' => $jabatanId,
            'satuan_kerja' => WorkUnit::where('id', $workUnitId)->value('name'),
        ]));

        if ($profile) {
            $profileData = $this->onlyFilled([
                'no_kk' => $row['no_kk'] ?? null,
                'tempat_lahir' => $row['tempat_lahir'] ?? null,
                'tanggal_lahir' => $row['tanggal_lahir'] ?? null,
                'jenis_kelamin' => $row['jenis_kelamin'] ?? null,
                'golongan_darah' => $row['golongan_darah'] ?? null,
                'agama' => $row['agama'] ?? null,
                'status_perkawinan' => $row['status_perkawinan'] ?? null,
                'npwp' => $row['npwp'] ?? null,
            ]);

            if ($profileData !== []) {
                $profile->update($profileData);
            }
        }

        // no_hp termasuk data wajib, jadi hanya WhatsApp yang disegarkan.
        if (! empty($row['no_whatsapp'])) {
            GtkContact::updateOrCreate(
                ['user_id' => $user->id],
                ['no_whatsapp' => $row['no_whatsapp']]
            );
        }

        $address = $this->resolveAddressFromNames($row);
        if ($profile && ! empty($address['province_code'])) {
            $existingAddress = GtkAddress::where('gtk_profile_id', $profile->id)
                ->where('type', 'domisili')
                ->first();

            if ($existingAddress) {
                $existingAddress->update($address);
            } else {
                $this->createAddress($profile->id, array_merge($address, ['type' => 'domisili']), 'domisili');
            }
        }

        if (! empty($row['jenjang_pendidikan']) && ! empty($row['nama_sekolah'])) {
            GtkEducation::updateOrCreate(
                ['user_id' => $user->id, 'jenjang_pendidikan' => $row['jenjang_pendidikan']],
                [
                    'nama_satuan_pendidikan' => $row['nama_sekolah'],
                    'jurusan' => $row['jurusan'] ?? null,
                    'tahun_lulus' => $row['tahun_lulus'] ?? null,
                    'status' => 'LULUS',
                ]
            );
        }

        if ($profile) {
            $family = $this->familyMembersFromImportRow($row, $profile);
            if ($family !== []) {
                app(GtkFamilyService::class)->sync($profile, $family);
            }
        }

        $this->assignWorkUnit($user->id, $workUnitId, $jabatan ?? $employment->jabatan);

        // Role hanya disinkronkan kalau jabatannya benar-benar berubah,
        // supaya role tambahan yang diberikan manual tidak terhapus.
        if ($jabatanId && $jabatanId !== $previousJabatanId) {
            $roleName = $this->roleNameForJabatan($jabatan, $jabatanId);
            if ($roleName) {
                $user->syncRoles([$roleName]);
            }
        }
    }

    private function onlyFilled(array $data): array
    {
        return array_filter($data, fn ($value) => $value !== null && $value !== '');
    }

    /**
     * Email internal untuk GTK yang tidak punya email. Dipakai sebagai username
     * login; password awalnya tetap diturunkan dari NUPY.
     */
    private function generateImportEmail(string $nupy): string
    {
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($nupy)), '-');
        $slug = $slug !== '' ? $slug : 'gtk';

        $email = $slug.'@gtk.local';
        $counter = 1;

        while (User::where('email', $email)->exists()) {
            $email = $slug.'-'.(++$counter).'@gtk.local';
        }

        return $email;
    }

    private function familyMembersFromImportRow(array $row, GtkProfile $profile): array
    {
        $members = [];
        $spouseName = trim((string) ($row['nama_pasangan'] ?? ''));
        $spouseNupy = trim((string) ($row['nupy_pasangan'] ?? ''));
        $linkedProfile = $spouseNupy !== ''
            ? GtkEmployment::where('nupy', $spouseNupy)->first()?->user?->gtkProfile
            : null;

        // NUPY pasangan tidak wajib diisi: kalau namanya cocok dengan GTK lain,
        // tautan dan NUPY-nya diisi otomatis.
        if (! $linkedProfile && $spouseName !== '') {
            $linkedProfile = $this->findGtkProfileByName($spouseName, $profile->user_id);
        }

        if ($spouseName !== '' || $linkedProfile) {
            $members[] = [
                'relationship' => $profile->jenis_kelamin === 'L' ? 'istri' : 'suami',
                'nama' => $linkedProfile?->user?->name ?: $spouseName,
                'jenis_kelamin' => $linkedProfile?->jenis_kelamin ?: ($profile->jenis_kelamin === 'L' ? 'P' : 'L'),
                'tanggal_lahir' => $linkedProfile?->tanggal_lahir?->toDateString(),
                'gtk_id' => $linkedProfile?->user_id,
                'spouse_nupy' => $spouseNupy ?: $linkedProfile?->latestEmployment?->nupy,
            ];
        }

        foreach ([1, 2, 3] as $index) {
            $name = trim((string) ($row['nama_anak_'.$index] ?? ''));
            if ($name === '') {
                continue;
            }
            $members[] = [
                'relationship' => 'anak',
                'nama' => $name,
                'jenis_kelamin' => strtoupper((string) ($row['jenis_kelamin_anak_'.$index] ?? '')) ?: null,
                'tanggal_lahir' => $row['tanggal_lahir_anak_'.$index] ?? null,
            ];
        }

        return $members;
    }

    /**
     * Cari GTK lain dengan nama yang sama (tanpa membedakan huruf besar/kecil).
     * Hanya ditautkan bila kandidatnya tunggal, supaya nama kembar tidak salah pasang.
     */
    private function findGtkProfileByName(string $name, ?string $exceptUserId): ?GtkProfile
    {
        $needle = preg_replace('/\s+/', ' ', mb_strtolower(trim($name))) ?? '';

        if ($needle === '') {
            return null;
        }

        $candidates = User::query()
            ->whereRaw('LOWER(name) = ?', [$needle])
            ->when($exceptUserId, fn ($query) => $query->where('id', '!=', $exceptUserId))
            ->limit(2)
            ->get();

        return $candidates->count() === 1 ? $candidates->first()->gtkProfile : null;
    }

    private function normalizeImportRow(array $row): array
    {
        // Numeric ID fields from Excel may come as scientific notation floats — coerce to clean digit strings.
        // Use number_format() to avoid PHP float precision loss on large integers (> 15 digits).
        foreach (['nik', 'no_kk', 'nupy', 'npwp'] as $field) {
            if (isset($row[$field]) && $row[$field] !== null && $row[$field] !== '') {
                $row[$field] = preg_replace('/[^\d]/', '', number_format((float) $row[$field], 0, '.', ''));
            }
        }

        // Normalize jenis_kelamin to uppercase single letter.
        if (! empty($row['jenis_kelamin'])) {
            $row['jenis_kelamin'] = strtoupper(trim($row['jenis_kelamin']));
            if ($row['jenis_kelamin'] === 'LAKI-LAKI') {
                $row['jenis_kelamin'] = 'L';
            } elseif ($row['jenis_kelamin'] === 'PEREMPUAN') {
                $row['jenis_kelamin'] = 'P';
            } elseif (! in_array($row['jenis_kelamin'], ['L', 'P'])) {
                $row['jenis_kelamin'] = null;
            }
        }

        // Normalize status_perkawinan to DB enum values.
        if (! empty($row['status_perkawinan'])) {
            $s = strtolower(trim($row['status_perkawinan']));
            $map = [
                'kawin' => 'kawin',
                'menikah' => 'kawin',
                'belum kawin' => 'belum_kawin',
                'belum menikah' => 'belum_kawin',
                'cerai hidup' => 'cerai_hidup',
                'cerai mati' => 'cerai_mati',
            ];
            $row['status_perkawinan'] = $map[$s] ?? null;
        }

        // Normalize status_kepegawaian to DB enum values.
        if (! empty($row['status_kepegawaian'])) {
            $s = trim($row['status_kepegawaian']);
            $known = ['ptt', 'pty', 'percobaan', 'magang', 'gtt', 'gty', 'kontrak', 'tetap'];
            $sLower = strtolower($s);
            if ($sLower === 'tetap') {
                $sLower = 'pty';
            }
            if (in_array($sLower, $known)) {
                $row['status_kepegawaian'] = strtoupper($sLower);
            } else {
                $row['status_kepegawaian'] = null;
            }
        }

        // Ensure no_hp is a clean digit string.
        if (! empty($row['no_hp'])) {
            $row['no_hp'] = preg_replace('/[^\d]/', '', (string) $row['no_hp']);
        }
        if (! empty($row['no_whatsapp'])) {
            $row['no_whatsapp'] = preg_replace('/[^\d]/', '', (string) $row['no_whatsapp']);
        }

        // Normalize date fields that may arrive as Excel serial numbers or non-standard strings.
        foreach (['tanggal_lahir', 'tmt', 'tanggal_sk', 'tanggal_lahir_anak_1', 'tanggal_lahir_anak_2', 'tanggal_lahir_anak_3'] as $dateField) {
            if (isset($row[$dateField]) && $row[$dateField] !== null && $row[$dateField] !== '') {
                $val = $row[$dateField];
                if (is_object($val) && method_exists($val, 'format')) {
                    $row[$dateField] = $val->format('Y-m-d');
                } elseif (is_numeric($val)) {
                    $row[$dateField] = Carbon::instance(Date::excelToDateTimeObject((float) $val))->format('Y-m-d');
                } else {
                    $decoded = Carbon::parse((string) $val);
                    $row[$dateField] = $decoded->format('Y-m-d');
                }
            }
        }

        // Normalize tahun_lulus to integer.
        if (isset($row['tahun_lulus']) && $row['tahun_lulus'] !== null && $row['tahun_lulus'] !== '') {
            $row['tahun_lulus'] = (int) preg_replace('/[^\d]/', '', (string) $row['tahun_lulus']);
        }

        return ['row' => $row, 'error' => null];
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
            $city = City::where('province_code', $provinceCode)
                ->where('name', 'LIKE', '%'.$row['alamat_kota'].'%')
                ->first();
            $cityCode = $city?->code;
        }

        if ($cityCode && ! empty($row['alamat_kecamatan'])) {
            $district = District::where('city_code', $cityCode)
                ->where('name', 'LIKE', '%'.$row['alamat_kecamatan'].'%')
                ->first();
            $districtCode = $district?->code;
        }

        if ($districtCode && ! empty($row['alamat_desa'])) {
            $village = Village::where('district_code', $districtCode)
                ->where('name', 'LIKE', '%'.$row['alamat_desa'].'%')
                ->first();
            $villageCode = $village?->code;
        }

        // Kolom teks ikut diisi karena gtk_addresses mewajibkannya (NOT NULL);
        // kode wilayah tetap disimpan supaya relasi wilayah tetap presisi.
        return [
            'province_code' => $provinceCode,
            'city_code' => $cityCode,
            'district_code' => $districtCode,
            'village_code' => $villageCode,
            'jalan' => $row['alamat_jalan'] ?? null,
            'rt_rw' => $row['alamat_rt_rw'] ?? null,
            'desa' => $row['alamat_desa'] ?? null,
            'kecamatan' => $row['alamat_kecamatan'] ?? null,
            'kab_kota' => $row['alamat_kota'] ?? null,
            'provinsi' => $row['alamat_provinsi'] ?? null,
            'kode_pos' => $row['kode_pos'] ?? null,
        ];
    }

    /**
     * Show GTK list filtered by a specific work unit (satuan kerja).
     * Migrated from legacy GtkController@indexByWorkUnit.
     */
    public function indexByWorkUnit(Request $request, string $satuanKerja)
    {
        $workUnit = WorkUnit::findOrFail($satuanKerja);

        $gtkQuery = User::with(['gtkProfile', 'employment', 'contact', 'workUnits'])
            ->whereHas('employment')
            ->whereHas('workUnits', function ($q) use ($workUnit) {
                $q->where('gtk_work_unit.work_unit_id', $workUnit->id);
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $gtkQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('employment', function ($q) use ($search) {
                        $q->where('nupy', 'like', "%{$search}%")
                            ->orWhere('jenis_gtk', 'like', "%{$search}%")
                            ->orWhere('jabatan', 'like', "%{$search}%");
                    })
                    ->orWhereHas('contact', function ($q) use ($search) {
                        $q->where('no_hp', 'like', "%{$search}%");
                    })
                    ->orWhereHas('gtkProfile', function ($q) use ($search) {
                        $q->where('nik', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status_kepegawaian')) {
            $gtkQuery->whereHas('employment', fn ($q) => $q->where('status_kepegawaian', $request->status_kepegawaian));
        }

        if ($request->filled('jenis_gtk')) {
            $gtkQuery->whereHas('employment', fn ($q) => $q->where('jenis_gtk', $request->jenis_gtk));
        }

        if ($request->has('status_aktif') && $request->status_aktif !== '') {
            $gtkQuery->where('is_active', $request->status_aktif);
        }

        $gtkIds = (clone $gtkQuery)->pluck('id');
        $total = $gtkIds->count();
        $genderL = GtkProfile::whereIn('user_id', $gtkIds)->where('jenis_kelamin', 'L')->count();
        $genderP = GtkProfile::whereIn('user_id', $gtkIds)->where('jenis_kelamin', 'P')->count();
        $aktif = User::whereIn('id', $gtkIds)->where('is_active', true)->count();
        $nonaktif = $total - $aktif;

        $gtkList = $gtkQuery->orderBy('created_at', 'desc')->paginate(20);

        $statistics = [
            'total' => $total,
            'aktif' => $aktif,
            'nonaktif' => $nonaktif,
            'gender_l' => $genderL,
            'gender_p' => $genderP,
        ];

        $workUnits = WorkUnit::active()->get();

        $jenisGtk = JenisGtk::active()->orderBy('urutan')->get();

        return view('gtk.index', compact('gtkList', 'workUnits', 'satuanKerja', 'statistics', 'jenisGtk'));
    }

    /**
     * Filtered list view with broader role coverage (used by Wadir 1 / Personalia).
     * Migrated from legacy GtkController@filter.
     */
    public function filter(Request $request)
    {
        $query = User::with(['gtkProfile', 'employment', 'contact', 'workUnits'])
            ->where(function ($q) {
                $q->whereHas('roles', function ($roleQuery) {
                    $roleQuery->whereIn('name', ['gtk', 'Personalia', 'Guru', 'Tenaga Kependidikan']);
                })
                    ->orWhereHas('employment');
            });

        if ($request->filled('satuan_kerja')) {
            $query->whereHas('workUnits', fn ($q) => $q->where('gtk_work_unit.work_unit_id', $request->satuan_kerja));
        }

        if ($request->filled('work_unit')) {
            $query->whereHas('workUnits', fn ($q) => $q->where('gtk_work_unit.work_unit_id', $request->work_unit));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhereHas('employment', function ($q) use ($search) {
                        $q->where('nupy', 'like', "%{$search}%")
                            ->orWhere('jenis_gtk', 'like', "%{$search}%")
                            ->orWhere('jabatan', 'like', "%{$search}%")
                            ->orWhere('status_kepegawaian', 'like', "%{$search}%");
                    })
                    ->orWhereHas('contact', fn ($q) => $q->where('no_hp', 'like', "%{$search}%"))
                    ->orWhereHas('gtkProfile', fn ($q) => $q->where('nik', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status_kepegawaian')) {
            $query->whereHas('employment', fn ($q) => $q->where('status_kepegawaian', $request->status_kepegawaian));
        }

        if ($request->filled('jenis_gtk')) {
            $query->whereHas('employment', fn ($q) => $q->where('jenis_gtk', $request->jenis_gtk));
        }

        if ($request->has('status_aktif') && $request->status_aktif !== '') {
            $query->where('users.is_active', $request->status_aktif);
        }

        $gtkList = $query->orderBy('users.created_at', 'desc')->paginate(20);

        if ($request->ajax() || $request->wantsJson()) {
            $html = view('gtk.partials.gtk-table-body', compact('gtkList'))->render();
            $pagination = view('gtk.partials.pagination', ['gtkList' => $gtkList])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'pagination' => $pagination,
                'total' => $gtkList->total(),
            ]);
        }

        $workUnits = WorkUnit::active()->get();

        $jenisGtk = JenisGtk::active()->orderBy('urutan')->get();

        return view('gtk.index', compact('gtkList', 'workUnits', 'jenisGtk'));
    }

    public function massUpdate(Request $request): JsonResponse
    {
        if (! GtkAccess::canMassUpdate(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Personalia dan Super Admin yang dapat melakukan Update Massal GTK.',
            ], 403);
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:users,id',
            'status_kepegawaian' => 'nullable|in:PTT,PTY,GTT,GTY,KONTRAK,Percobaan,Magang',
            'jenis_gtk_id' => 'nullable|exists:jenis_gtk,id',
            'jabatan_id' => 'nullable|exists:structural_positions,id',
            'tmt' => 'nullable|date',
            'nomor_sk' => 'nullable|string|max:255',
            'tanggal_sk' => 'nullable|date',
            'pangkat_golongan' => 'nullable|string|max:50',
            'work_unit_id' => 'nullable|exists:work_units,id',
        ]);

        $userId = auth()->id();
        DB::beginTransaction();
        try {
            $updated = 0;
            foreach ($validated['ids'] as $id) {
                $user = User::where('id', $id)->where('is_active', true)->first();
                if (! $user) {
                    continue;
                }

                // Scope check — only allow update if user's school matches context
                $schoolId = $request->attributes->get('schoolContextId');
                if ($schoolId) {
                    $schoolWorkUnitId = School::find($schoolId)?->work_unit_id;
                    $hasSchoolScope = (string) $user->employment?->school_id === (string) $schoolId
                        || ($schoolWorkUnitId && $user->gtkWorkUnits()->where('work_unit_id', $schoolWorkUnitId)->exists());
                    if (! $hasSchoolScope) {
                        continue;
                    }
                }

                DB::transaction(function () use ($user, $validated) {
                    // Employment fields
                    $employmentUpdates = [];
                    if ($validated['status_kepegawaian'] !== null) {
                        $employmentUpdates['status_kepegawaian'] = $validated['status_kepegawaian'];
                    }
                    if ($validated['jenis_gtk_id'] !== null) {
                        $employmentUpdates['jenis_gtk_id'] = $validated['jenis_gtk_id'];
                        $employmentUpdates['jenis_gtk'] = JenisGtk::find($validated['jenis_gtk_id'])?->nama;
                    }
                    if ($validated['jabatan_id'] !== null) {
                        $employmentUpdates['jabatan_id'] = $validated['jabatan_id'];
                        $employmentUpdates['jabatan'] = StructuralPosition::find($validated['jabatan_id'])?->name;
                    }
                    if ($validated['tmt'] !== null) {
                        $employmentUpdates['tmt'] = $validated['tmt'];
                    }
                    if ($validated['nomor_sk'] !== null) {
                        $employmentUpdates['nomor_sk'] = $validated['nomor_sk'];
                        $employmentUpdates['decree_number'] = $validated['nomor_sk'];
                    }
                    if ($validated['tanggal_sk'] !== null) {
                        $employmentUpdates['tanggal_sk'] = $validated['tanggal_sk'];
                        $employmentUpdates['decree_date'] = $validated['tanggal_sk'];
                    }
                    if ($validated['pangkat_golongan'] !== null) {
                        $employmentUpdates['pangkat_golongan'] = $validated['pangkat_golongan'];
                    }
                    if (! empty($employmentUpdates)) {
                        $user->employment?->update($employmentUpdates);
                    }

                    // Work unit change
                    if ($validated['work_unit_id'] !== null) {
                        $wu = WorkUnit::find($validated['work_unit_id']);
                        $existing = GtkWorkUnit::where('user_id', $user->id)->first();
                        $position = $validated['jabatan_id'] ? StructuralPosition::find($validated['jabatan_id']) : null;
                        if ($existing) {
                            $existing->update([
                                'work_unit_id' => $validated['work_unit_id'],
                                'jabatan' => $position?->name ?? $existing->jabatan,
                                'position_id' => $position?->id ?? $existing->position_id,
                            ]);
                        } else {
                            GtkWorkUnit::create([
                                'user_id' => $user->id,
                                'work_unit_id' => $validated['work_unit_id'],
                                'is_primary' => true,
                                'jabatan' => $position?->name,
                                'position_id' => $position?->id,
                            ]);
                        }
                    }

                    $updated++;
                });
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil memperbarui {$updated} GTK",
                'updated' => $updated,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('massUpdate failed', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem.',
            ], 500);
        }
    }

    private function authorizeGtkMutation(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && (
                (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
                || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
                || $user->hasRole('Super Admin')
                || $user->hasRole('Humas Personalia')
            ),
            403,
            'Import dan penambahan GTK hanya dapat dilakukan oleh Humas Personalia atau Super Admin.'
        );
    }

    public function massal(Request $request)
    {
        abort_unless(
            GtkAccess::canMassUpdate(auth()->user()),
            403,
            'Hanya Personalia dan Super Admin yang dapat mengakses Update Massal GTK.'
        );

        $query = User::with(['gtkProfile', 'employment', 'gtkContact', 'gtkWorkUnits.workUnit', 'gtkProfile.addresses', 'educations'])
            ->whereHas('employment');

        $this->applyFilters($query, $request);

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->whereIn('users.id', SchoolGroupService::userIdsForSchool($schoolId));
        }

        $orderBy = $request->get('order_by', 'created_at');
        $orderDir = $request->get('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        $perPage = $request->get('per_page', 20);
        $gtkList = $query->paginate($perPage)->withQueryString();

        $workUnits = WorkUnit::active()->get();
        $jenisGtk = JenisGtk::active()->orderBy('urutan')->get();
        $jabatan = StructuralPosition::active()->orderBy('urutan')->orderBy('name')->get();
        $userId = auth()->id();
        $currentUser = auth()->user();
        $canEditWorkUnit = $currentUser->hasRole('Super Admin') || $currentUser->hasRole('Humas Personalia');

        return view('gtk.massal.index', compact('gtkList', 'workUnits', 'jenisGtk', 'jabatan', 'userId', 'canEditWorkUnit'));
    }
}
