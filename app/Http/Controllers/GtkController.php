<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\GtkProfile;
use App\Models\GtkAddress;
use App\Models\GtkContact;
use App\Models\GtkEmployment;
use App\Models\GtkFamilyMember;
use App\Models\School;
use App\Models\WorkUnit;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class GtkController extends Controller
{
    // Index untuk semua GTK
    public function index(Request $request)
    {
        $gtkQuery = User::with(['gtkProfile', 'employment', 'contact', 'workUnits'])
        ->whereHas('employment');

        // Filter berdasarkan satuan kerja
        if ($request->has('satuan_kerja') && $request->satuan_kerja) {
            $gtkQuery->whereHas('workUnits', function ($q) use ($request) {
                $q->where('gtk_work_unit.work_unit_id', $request->satuan_kerja);
            });
        }

        // Filter berdasarkan work unit (jika ada parameter work_unit)
        if ($request->has('work_unit') && $request->work_unit) {
            $gtkQuery->whereHas('workUnits', function ($q) use ($request) {
                $q->where('gtk_work_unit.work_unit_id', $request->work_unit);
            });
        }

        // Search filter
        if ($request->has('search')) {
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

        // Filter berdasarkan status kepegawaian
        if ($request->has('status_kepegawaian') && $request->status_kepegawaian) {
            $gtkQuery->whereHas('employment', function ($q) use ($request) {
                $q->where('status_kepegawaian', $request->status_kepegawaian);
            });
        }

        // Filter berdasarkan jenis GTK (dikomentari di view)
        if ($request->has('jenis_gtk') && $request->jenis_gtk) {
            $gtkQuery->whereHas('employment', function ($q) use ($request) {
                $q->where('jenis_gtk', $request->jenis_gtk);
            });
        }

        // Filter berdasarkan status aktif
        if ($request->has('status_aktif') && $request->status_aktif !== '') {
            $gtkQuery->where('is_active', $request->status_aktif);
        }

        $gtkList = $gtkQuery->orderBy('created_at', 'desc')->paginate(20);
        $workUnits = WorkUnit::active()->get();

        // Statistik dari query yang sama
        $gtkIds = (clone $gtkQuery)->pluck('id');
        $total    = $gtkIds->count();
        $genderL  = GtkProfile::whereIn('user_id', $gtkIds)->where('jenis_kelamin', 'L')->count();
        $genderP  = GtkProfile::whereIn('user_id', $gtkIds)->where('jenis_kelamin', 'P')->count();
        $aktif    = User::whereIn('id', $gtkIds)->where('is_active', true)->count();
        $nonaktif = $total - $aktif;

        $gtkList = $gtkQuery->orderBy('created_at', 'desc')->paginate(20);

        $statistics = [
            'total'    => $total,
            'aktif'    => $aktif,
            'nonaktif' => $nonaktif,
            'gender_l' => $genderL,
            'gender_p' => $genderP,
        ];

        return view('gtk.index', compact('gtkList', 'workUnits', 'statistics'));
    }

    // Index untuk GTK berdasarkan satuan kerja tertentu
    public function indexByWorkUnit(Request $request, $satuanKerja)
    {
        // Cari satuan kerja — findOrFail akan lempar 404 jika tidak ditemukan
        $satuanKerja = WorkUnit::findOrFail($satuanKerja);

        $gtkQuery = User::with(['gtkProfile', 'employment', 'contact', 'workUnits'])
            ->whereHas('employment')
            ->whereHas('workUnits', function ($q) use ($satuanKerja) {
                $q->where('gtk_work_unit.work_unit_id', $satuanKerja->id);
            });

        // Search filter
        if ($request->has('search')) {
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

        // Filter berdasarkan status kepegawaian
        if ($request->has('status_kepegawaian') && $request->status_kepegawaian) {
            $gtkQuery->whereHas('employment', function ($q) use ($request) {
                $q->where('status_kepegawaian', $request->status_kepegawaian);
            });
        }

        // Filter berdasarkan jenis GTK
        if ($request->has('jenis_gtk') && $request->jenis_gtk) {
            $gtkQuery->whereHas('employment', function ($q) use ($request) {
                $q->where('jenis_gtk', $request->jenis_gtk);
            });
        }

        // Filter berdasarkan status aktif
        if ($request->has('status_aktif') && $request->status_aktif !== '') {
            $gtkQuery->where('is_active', $request->status_aktif);
        }

        // Statistik dulu, baru paginate
        $gtkIds = (clone $gtkQuery)->pluck('id');
        $total    = $gtkIds->count();
        $genderL  = GtkProfile::whereIn('user_id', $gtkIds)->where('jenis_kelamin', 'L')->count();
        $genderP  = GtkProfile::whereIn('user_id', $gtkIds)->where('jenis_kelamin', 'P')->count();
        $aktif    = User::whereIn('id', $gtkIds)->where('is_active', true)->count();
        $nonaktif = $total - $aktif;

        $gtkList = $gtkQuery->orderBy('created_at', 'desc')->paginate(20);

        $statistics = [
            'total'    => $total,
            'aktif'    => $aktif,
            'nonaktif' => $nonaktif,
            'gender_l' => $genderL,
            'gender_p' => $genderP,
        ];

        $workUnits = WorkUnit::active()->get();

        return view('gtk.index', compact('gtkList', 'workUnits', 'satuanKerja', 'statistics'));
    }

    public function create()
    {
        $workUnits = WorkUnit::all();
        $provinces = Province::orderBy('name')->get();

        return view('gtk.create', compact('workUnits', 'provinces'));
    }

    public function filter(Request $request)
    {
        $query = User::with(['gtkProfile', 'employment', 'contact', 'workUnits'])
            ->where(function ($q) {
                $q->whereHas('roles', function ($roleQuery) {
                    $roleQuery->whereIn('name', ['gtk', 'Personalia', 'Guru', 'Tenaga Kependidikan']);
                })
                    ->orWhereHas('employment');
            });

        // Filter berdasarkan satuan kerja
        if ($request->has('satuan_kerja') && $request->satuan_kerja) {
            $query->whereHas('workUnits', function ($q) use ($request) {
                $q->where('gtk_work_unit.work_unit_id', $request->satuan_kerja);
            });
        }

        // Filter berdasarkan work unit (jika ada parameter work_unit)
        if ($request->has('work_unit') && $request->work_unit) {
            $query->whereHas('workUnits', function ($q) use ($request) {
                $q->where('gtk_work_unit.work_unit_id', $request->work_unit);
            });
        }

        // Search filter
        if ($request->has('search')) {
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
                    ->orWhereHas('contact', function ($q) use ($search) {
                        $q->where('no_hp', 'like', "%{$search}%");
                    })
                    ->orWhereHas('gtkProfile', function ($q) use ($search) {
                        $q->where('nik', 'like', "%{$search}%");
                    });
            });
        }

        // Filter berdasarkan status kepegawaian
        if ($request->has('status_kepegawaian') && $request->status_kepegawaian) {
            $query->whereHas('employment', function ($q) use ($request) {
                $q->where('status_kepegawaian', $request->status_kepegawaian);
            });
        }

        // Filter berdasarkan jenis GTK
        if ($request->has('jenis_gtk') && $request->jenis_gtk) {
            $query->whereHas('employment', function ($q) use ($request) {
                $q->where('jenis_gtk', $request->jenis_gtk);
            });
        }

        // Filter berdasarkan status aktif
        if ($request->has('status_aktif') && $request->status_aktif !== '') {
            $query->where('users.is_active', $request->status_aktif);
        }

        $gtkList = $query->orderBy('users.created_at', 'desc')->paginate(20);

        // Jika request AJAX, kembalikan JSON
        if ($request->ajax() || $request->wantsJson()) {
            $html = view('gtk.partials.gtk-table-body', compact('gtkList'))->render();
            $pagination = view('gtk.partials.pagination', ['gtkList' => $gtkList])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'pagination' => $pagination,
                'total' => $gtkList->total()
            ]);
        }

        // Jika bukan AJAX, kembalikan view normal
        $workUnits = WorkUnit::active()->get();

        return view('gtk.index', compact('gtkList', 'workUnits'));
    }

    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'gtk_id' => 'required|exists:users,id'
        ]);
        
        // Verify current user's password
        if (!Hash::check($request->password, Auth::user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password tidak valid'
            ], 401);
        }
        
        // Check if user has permission to view this GTK's identity data
        // You can add additional authorization logic here
        
        return response()->json([
            'success' => true,
            'message' => 'Verifikasi berhasil'
        ]);
    }

    // Toggle status aktif/nonaktif GTK
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        // Pastikan user adalah GTK
        if (!$user->hasRole('gtk')) {
            return response()->json([
                'success' => false,
                'message' => 'User bukan GTK'
            ], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => 'GTK berhasil ' . $status,
            'is_active' => $user->is_active
        ]);
    }

    // Hapus GTK
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Pastikan user adalah GTK
        if (!$user->hasRole('gtk')) {
            return response()->json([
                'success' => false,
                'message' => 'User bukan GTK'
            ], 403);
        }

        DB::beginTransaction();

        try {
            // Hapus semua data terkait
            if ($user->profile) {
                // Hapus alamat
                $user->profile->addresses()->delete();

                // Hapus anggota keluarga
                $user->profile->familyMembers()->delete();

                // Hapus profil
                $user->profile()->delete();
            }

            // Hapus kontak
            $user->contact()->delete();

            // Hapus employment
            $user->employment()->delete();

            // Detach work units
            $user->workUnits()->detach();

            // Hapus role
            $user->roles()->detach();

            // Hapus user
            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'GTK berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error deleting GTK: ' . $e->getMessage(), [
                'user_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus GTK: ' . $e->getMessage()
            ], 500);
        }
    }

    // Bulk delete
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        $deletedCount = 0;
        DB::beginTransaction();

        try {
            foreach ($request->ids as $id) {
                $user = User::find($id);

                if ($user && $user->hasRole('gtk')) {
                    // Hapus semua data terkait
                    if ($user->profile) {
                        $user->profile->addresses()->delete();
                        $user->profile->familyMembers()->delete();
                        $user->profile()->delete();
                    }

                    $user->contact()->delete();
                    $user->employment()->delete();
                    $user->workUnits()->detach();
                    $user->roles()->detach();
                    $user->delete();

                    $deletedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil menghapus {$deletedCount} data GTK"
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error bulk deleting GTK: ' . $e->getMessage(), [
                'ids' => $request->ids,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    // Show detail GTK
    public function show($id)
    {
        $user = User::with([
            'profile',
            'profile.addresses',
            'profile.familyMembers',
            'contact',
            'employment',
            'workUnits'
        ])->findOrFail($id);

        // Pastikan user adalah GTK
        if (!$user->hasRole('gtk')) {
            abort(404, 'User bukan GTK');
        }

        $workUnits = WorkUnit::active()->get();

        return view('gtk.profile', compact('user', 'workUnits'));
    }

    // Edit GTK
    public function edit($id)
    {
        $user = User::with([
            'profile',
            'profile.addresses',
            'profile.familyMembers',
            'contact',
            'employment',
            'workUnits'
        ])->findOrFail($id);

        // Pastikan user adalah GTK
        if (!$user->hasRole('gtk')) {
            abort(404, 'User bukan GTK');
        }

        $workUnits = WorkUnit::all();
        $provinces = Province::orderBy('name')->get();

        // Get current addresses
        $domisiliAddress = $user->profile->addresses->where('type', 'domisili')->first();
        $ktpAddress = $user->profile->addresses->where('type', 'ktp')->first();

        return view('gtk.edit', compact('user', 'workUnits', 'provinces', 'domisiliAddress', 'ktpAddress'));
    }

    // Update GTK
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Pastikan user adalah GTK
        if (!$user->hasRole('gtk')) {
            return response()->json([
                'success' => false,
                'message' => 'User bukan GTK'
            ], 403);
        }

        $validator = Validator::make($request->all(), $this->getValidationRules($user->id));

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();

        try {
            // Update User
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => $request->input('is_active', $user->is_active),
            ]);

            // Update Profile
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nik' => $validated['nik'],
                    'no_kk' => $validated['no_kk'] ?? null,
                    'tempat_lahir' => $validated['tempat_lahir'],
                    'tanggal_lahir' => $validated['tanggal_lahir'],
                    'jenis_kelamin' => $validated['jenis_kelamin'],
                    'golongan_darah' => $validated['golongan_darah'],
                    'status_perkawinan' => $validated['status_perkawinan'],
                    'nama_ibu_kandung' => $validated['nama_ibu_kandung'],
                    'npwp' => $validated['npwp'] ?? null,
                ]
            );

            // Update Addresses
            $this->updateOrCreateAddress($user->profile->id, 'domisili', $validated['alamat_domisili']);

            if (isset($validated['alamat_ktp']) && !empty($validated['alamat_ktp']['jalan'])) {
                $this->updateOrCreateAddress($user->profile->id, 'ktp', $validated['alamat_ktp']);
            } else {
                // Hapus alamat KTP jika kosong
                $user->profile->addresses()->where('type', 'ktp')->delete();
            }

            // Update Contact
            $user->contact()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'no_hp' => $validated['kontak']['no_hp'],
                    'no_whatsapp' => $validated['kontak']['no_whatsapp'] ?? null,
                    'kontak_darurat' => $validated['kontak']['kontak_darurat'],
                    'instagram' => $validated['kontak']['instagram'] ?? null,
                    'facebook' => $validated['kontak']['facebook'] ?? null,
                    'twitter' => $validated['kontak']['twitter'] ?? null,
                ]
            );

            // Update Employment
            $user->employment()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nupy' => $validated['kepegawaian']['nupy'],
                    'school_id' => School::where('work_unit_id', $validated['work_unit_id'])->value('id'),
                    'jenis_gtk' => $validated['kepegawaian']['jenis_gtk'],
                    'jabatan' => $validated['kepegawaian']['jabatan'],
                    'status_kepegawaian' => $validated['kepegawaian']['status_kepegawaian'],
                    'tmt' => $validated['kepegawaian']['tmt'],
                    'nomor_sk' => $validated['kepegawaian']['nomor_sk'],
                    'tanggal_sk' => $validated['kepegawaian']['tanggal_sk'],
                    'pangkat_golongan' => $validated['kepegawaian']['pangkat_golongan'] ?? null,
                ]
            );

            // Update Work Unit (single selection)
            $user->workUnits()->sync([$validated['work_unit_id']]);

            // Update Family Members
            if ($user->profile) {
                $user->profile->familyMembers()->delete();

                if (isset($validated['anggota_keluarga']) && is_array($validated['anggota_keluarga'])) {
                    foreach ($validated['anggota_keluarga'] as $anggota) {
                        if (!empty($anggota['nama'])) {
                            GtkFamilyMember::create([
                                'gtk_profile_id' => $user->profile->id,
                                'relationship' => $anggota['relationship'],
                                'nama' => $anggota['nama'],
                                'jenis_kelamin' => $anggota['jenis_kelamin'],
                                'pekerjaan' => $anggota['pekerjaan'],
                                'pendidikan_terakhir' => $anggota['pendidikan_terakhir'],
                                'alamat' => $anggota['alamat'],
                                'tanggal_lahir' => $anggota['tanggal_lahir'],
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data GTK berhasil diperbarui',
                'data' => [
                    'user_id' => $user->id,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error updating GTK data: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data: ' . $e->getMessage()
            ], 500);
        }
    }

    // API untuk wilayah
    public function getCities($provinceCode)
    {
        try {
            $cities = City::where('province_code', $provinceCode)
                ->orderBy('name')
                ->get(['code', 'name']);

            return response()->json([
                'success' => true,
                'data' => $cities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data kota/kabupaten'
            ], 500);
        }
    }

    public function getDistricts($cityCode)
    {
        try {
            $districts = District::where('city_code', $cityCode)
                ->orderBy('name')
                ->get(['code', 'name']);

            return response()->json([
                'success' => true,
                'data' => $districts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data kecamatan'
            ], 500);
        }
    }

    public function getVillages($districtCode)
    {
        try {
            $villages = Village::where('district_code', $districtCode)
                ->orderBy('name')
                ->get(['code', 'name', 'meta']);

            // Decode meta field to get postal code
            $villages = $villages->map(function ($village) {
                $meta = json_decode($village->meta, true);
                $village->postal_code = $meta['pos'] ?? null;
                return $village;
            });

            return response()->json([
                'success' => true,
                'data' => $villages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data desa'
            ], 500);
        }
    }

    public function getPostalCode($villageCode)
    {
        try {
            $village = Village::where('code', $villageCode)->first();

            if (!$village) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa tidak ditemukan'
                ], 404);
            }

            $postalCode = null;
            if ($village->meta) {
                $meta = json_decode($village->meta, true);
                $postalCode = $meta['pos'] ?? null;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'postal_code' => $postalCode
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat kode pos'
            ], 500);
        }
    }

    private function getValidationRules($userId = null)
    {
        $rules = [
            // User Data
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId)
            ],

            // Profile Data
            'nik' => [
                'required',
                'numeric',
                'digits:16',
                Rule::unique('gtk_profiles', 'nik')->ignore($userId, 'user_id')
            ],
            'no_kk' => 'nullable|numeric|digits:16',
            'tempat_lahir' => 'required|string|max:100',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'golongan_darah' => ['required', Rule::in(['A', 'B', 'AB', 'O'])],
            'status_perkawinan' => ['required', Rule::in(['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'])],
            'nama_ibu_kandung' => 'required|string|max:255',
            'npwp' => 'nullable|numeric|digits:15',

            // Address
            'alamat_domisili.provinsi' => 'required|string',
            'alamat_domisili.kab_kota' => 'required|string',
            'alamat_domisili.kecamatan' => 'required|string',
            'alamat_domisili.desa' => 'required|string',
            'alamat_domisili.jalan' => 'required|string',
            'alamat_domisili.rt_rw' => 'required|string',
            'alamat_domisili.dusun' => 'nullable|string',

            'alamat_ktp.provinsi' => 'nullable|string',
            'alamat_ktp.kab_kota' => 'nullable|string',
            'alamat_ktp.kecamatan' => 'nullable|string',
            'alamat_ktp.desa' => 'nullable|string',
            'alamat_ktp.jalan' => 'nullable|string',
            'alamat_ktp.rt_rw' => 'nullable|string',
            'alamat_ktp.dusun' => 'nullable|string',

            // Contact
            'kontak.no_hp' => 'required|string|max:20',
            'kontak.no_whatsapp' => 'nullable|string|max:20',
            'kontak.kontak_darurat' => 'required|string|max:255',
            'kontak.instagram' => 'nullable|string|max:100',
            'kontak.facebook' => 'nullable|string|max:100',
            'kontak.twitter' => 'nullable|string|max:100',

            // Employment
            'kepegawaian.nupy' => [
                'required',
                'string',
                'max:50',
                Rule::unique('gtk_employments', 'nupy')->ignore($userId, 'user_id')
            ],
            'kepegawaian.jenis_gtk' => 'required|string|max:100',
            'kepegawaian.jabatan' => 'required|string|max:100',
            'kepegawaian.status_kepegawaian' => 'required|string|max:50',
            'kepegawaian.tmt' => 'required|date',
            'kepegawaian.nomor_sk' => 'required|string|max:100',
            'kepegawaian.tanggal_sk' => 'required|date',
            'kepegawaian.pangkat_golongan' => 'nullable|string|max:50',
            'work_unit_id' => 'required|exists:work_units,id',

            // Family Members
            'anggota_keluarga.*.relationship' => 'nullable|string|max:50',
            'anggota_keluarga.*.nama' => 'nullable|string|max:255',
            'anggota_keluarga.*.jenis_kelamin' => 'nullable|string|max:1',
            'anggota_keluarga.*.pekerjaan' => 'nullable|string|max:100',
            'anggota_keluarga.*.pendidikan_terakhir' => 'nullable|string|max:100',
            'anggota_keluarga.*.alamat' => 'nullable|string',
            'anggota_keluarga.*.tanggal_lahir' => 'nullable|date',
        ];

        return $rules;
    }

    private function createAddress($profileId, $type, $data)
    {
        // Get postal code from village meta
        $postalCode = null;
        if (!empty($data['desa'])) {
            $village = Village::where('code', $data['desa'])->first();
            if ($village && $village->meta) {
                $meta = json_decode($village->meta, true);
                $postalCode = $meta['pos'] ?? null;
            }
        }

        return GtkAddress::create([
            'gtk_profile_id' => $profileId,
            'type' => $type,
            'jalan' => $data['jalan'],
            'rt_rw' => $data['rt_rw'] ?? null,
            'dusun' => $data['dusun'] ?? null,
            'desa' => $data['desa'],
            'kecamatan' => $data['kecamatan'],
            'kab_kota' => $data['kab_kota'],
            'provinsi' => $data['provinsi'],
            'kode_pos' => $postalCode,
        ]);
    }

    private function updateOrCreateAddress($profileId, $type, $data)
    {
        // Get postal code from village meta
        $postalCode = null;
        if (!empty($data['desa'])) {
            $village = Village::where('code', $data['desa'])->first();
            if ($village && $village->meta) {
                $meta = json_decode($village->meta, true);
                $postalCode = $meta['pos'] ?? null;
            }
        }

        return GtkAddress::updateOrCreate(
            [
                'gtk_profile_id' => $profileId,
                'type' => $type
            ],
            [
                'jalan' => $data['jalan'],
                'rt_rw' => $data['rt_rw'] ?? null,
                'dusun' => $data['dusun'] ?? null,
                'desa' => $data['desa'],
                'kecamatan' => $data['kecamatan'],
                'kab_kota' => $data['kab_kota'],
                'provinsi' => $data['provinsi'],
                'kode_pos' => $postalCode,
            ]
        );
    }
}
