<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\GtkProfile;
use App\Models\GtkContact;
use App\Models\GtkAddress;
use App\Models\GtkFamilyMember;
use App\Models\GtkEmployment;
use App\Models\GtkEducation;
use App\Models\WorkUnit;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(string $userId, string $uuid)
    {
        try {
            $gtk = User::with([
                'gtkProfile',
                'gtkProfile.addresses',
                'gtkProfile.familyMembers',
                'gtkContact',
                'gtkEmployment',
                'gtkEducations',
                'workUnits.workUnit',
            ])->where('id', $uuid)->firstOrFail();

            $completionPercentage = $this->calculateCompletionPercentage($gtk);

            return view('gtk.profile', compact('gtk', 'userId', 'completionPercentage'));
        } catch (\Exception $e) {
            Log::error('Error fetching GTK profile: ' . $e->getMessage());
            return redirect()->route('user.gtk.index', ['userId' => $userId])
                ->with('error', 'Data GTK tidak ditemukan.');
        }
    }

    public function edit(string $userId, string $uuid)
    {
        try {
            $gtk = User::with([
                'gtkProfile',
                'gtkProfile.addresses',
                'gtkProfile.familyMembers',
                'gtkContact',
                'gtkEmployment',
                'gtkEducations',
                'workUnits.workUnit',
            ])->where('id', $uuid)->firstOrFail();

            $workUnits = WorkUnit::all();
            $provinces = Province::orderBy('name')->get();

            $completionPercentage = $this->calculateCompletionPercentage($gtk);

            return view('gtk.edit', compact('gtk', 'userId', 'workUnits', 'provinces', 'completionPercentage'));
        } catch (\Exception $e) {
            Log::error('Error fetching GTK for edit: ' . $e->getMessage());
            return redirect()->route('user.gtk.index', ['userId' => $userId])
                ->with('error', 'Data GTK tidak ditemukan.');
        }
    }

    public function getCities($provinceUuid)
    {
        try {
            $cities = City::where('province_uuid', $provinceUuid)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
            
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

    public function getDistricts($cityUuid)
    {
        try {
            $districts = District::where('city_uuid', $cityUuid)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
            
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

    public function getVillages($districtUuid)
    {
        try {
            $villages = Village::where('district_uuid', $districtUuid)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'meta']);
            
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

    public function update(Request $request, string $userId, string $uuid)
    {
        try {
            $user = User::where('id', $uuid)->firstOrFail();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'User tidak ditemukan.')
                ->withInput();
        }

        $validator = Validator::make($request->all(), $this->getValidationRules($user));

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $validated = $validator->validated();

            // Update User
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            // Update atau Create Profile
            $profileData = [
                'nik' => $validated['nik'],
                'no_kk' => $validated['no_kk'] ?? null,
                'tempat_lahir' => $validated['tempat_lahir'],
                'tanggal_lahir' => $validated['tanggal_lahir'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'golongan_darah' => $validated['golongan_darah'] ?? null,
                'status_perkawinan' => $validated['status_perkawinan'] ?? null,
                'nama_ibu_kandung' => $validated['nama_ibu_kandung'] ?? null,
                'npwp' => $validated['npwp'] ?? null,
            ];

            if ($user->gtkProfile) {
                $user->gtkProfile->update($profileData);
                $profile = $user->gtkProfile;
            } else {
                $profileData['id'] = Str::id();
                $profileData['user_id_uuid'] = $user->id;
                $profile = GtkProfile::create($profileData);
            }

            // Update atau Create Contact
            $contactData = [
                'no_hp' => $validated['kontak']['no_hp'],
                'no_whatsapp' => $validated['kontak']['no_whatsapp'] ?? null,
                'kontak_darurat' => $validated['kontak']['kontak_darurat'],
                'instagram' => $validated['kontak']['instagram'] ?? null,
                'facebook' => $validated['kontak']['facebook'] ?? null,
                'twitter' => $validated['kontak']['twitter'] ?? null,
            ];

            if ($user->gtkContact) {
                $user->gtkContact->update($contactData);
            } else {
                $contactData['id'] = Str::id();
                $contactData['user_id'] = $user->id;
                GtkContact::create($contactData);
            }

            // Update atau Create Employment
            if (isset($validated['employment'])) {
                $employmentData = [
                    'nupy' => $validated['employment']['nupy'] ?? null,
                    'jenis_gtk' => $validated['employment']['jenis_gtk'] ?? null,
                    'jabatan' => $validated['employment']['jabatan'] ?? null,
                    'status_kepegawaian' => $validated['employment']['status_kepegawaian'] ?? null,
                    'tmt' => $validated['employment']['tmt'] ?? null,
                    'nomor_sk' => $validated['employment']['nomor_sk'] ?? null,
                    'tanggal_sk' => $validated['employment']['tanggal_sk'] ?? null,
                ];

                if ($user->gtkEmployment) {
                    $user->gtkEmployment->update($employmentData);
                } else {
                    $employmentData['id'] = Str::id();
                    $employmentData['user_id'] = $user->id;
                    GtkEmployment::create($employmentData);
                }
            }

            // Update Work Units via pivot table
            if (isset($validated['work_units']) && is_array($validated['work_units'])) {
                // Hapus relasi lama
                DB::table('gtk_work_unit')->where('user_id', $user->id)->delete();
                
                // Tambah relasi baru
                foreach ($validated['work_units'] as $index => $workUnitId) {
                    $workUnit = WorkUnit::where('id', $workUnitId)->first();
                    if ($workUnit) {
                        DB::table('gtk_work_unit')->insert([
                            'id' => Str::id(),
                            'user_id' => $user->id,
                            'work_unit_id' => $workUnit->id,
                            'jabatan' => $validated['employment']['jabatan'] ?? null,
                            'is_primary' => $index === 0, // Work unit pertama sebagai primary
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            // Update atau Create Addresses
            if ($profile) {
                // Domisili Address
                if (isset($validated['alamat_domisili'])) {
                    $domisiliData = $this->prepareAddressData($validated['alamat_domisili'], 'domisili');
                    $domisiliData['gtk_profile_id_uuid'] = $profile->id;
                    
                    GtkAddress::updateOrCreate(
                        [
                            'gtk_profile_id_uuid' => $profile->id,
                            'type' => 'domisili'
                        ],
                        $domisiliData
                    );
                }

                // KTP Address
                if (isset($validated['alamat_ktp']) && !empty($validated['alamat_ktp']['jalan'])) {
                    $ktpData = $this->prepareAddressData($validated['alamat_ktp'], 'ktp');
                    $ktpData['gtk_profile_id_uuid'] = $profile->id;
                    
                    GtkAddress::updateOrCreate(
                        [
                            'gtk_profile_id_uuid' => $profile->id,
                            'type' => 'ktp'
                        ],
                        $ktpData
                    );
                } else {
                    GtkAddress::where('gtk_profile_id_uuid', $profile->id)
                        ->where('type', 'ktp')
                        ->delete();
                }
            }

            // Update Family Members
            if ($profile && isset($validated['anggota_keluarga']) && is_array($validated['anggota_keluarga'])) {
                $profile->familyMembers()->delete();

                foreach ($validated['anggota_keluarga'] as $anggota) {
                    if (!empty($anggota['nama'])) {
                        GtkFamilyMember::create([
                            'id' => Str::id(),
                            'gtk_profile_id_uuid' => $profile->id,
                            'relationship' => $anggota['relationship'],
                            'nama' => $anggota['nama'],
                            'jenis_kelamin' => $anggota['jenis_kelamin'],
                            'pekerjaan' => $anggota['pekerjaan'] ?? null,
                            'pendidikan_terakhir' => $anggota['pendidikan_terakhir'] ?? null,
                            'alamat' => $anggota['alamat'] ?? null,
                            'tanggal_lahir' => $anggota['tanggal_lahir'] ?? null,
                        ]);
                    }
                }
            }

            // ** UPDATE ATAU CREATE PENDIDIKAN **
            if (isset($validated['pendidikan']) && is_array($validated['pendidikan'])) {
                // Hapus data pendidikan lama
                GtkEducation::where('user_id', $user->id)->delete();
                
                // Tambah data pendidikan baru
                foreach ($validated['pendidikan'] as $pendidikan) {
                    if (!empty($pendidikan['jenjang_pendidikan']) && !empty($pendidikan['nama_satuan_pendidikan'])) {
                        $educationData = [
                            'id' => Str::uuid(),
                            'user_id' => $user->id,
                            'jenjang_pendidikan' => $pendidikan['jenjang_pendidikan'],
                            'nama_satuan_pendidikan' => $pendidikan['nama_satuan_pendidikan'],
                            'jurusan' => $pendidikan['jurusan'] ?? null,
                            'fakultas' => $pendidikan['fakultas'] ?? null,
                            'tahun_masuk' => $pendidikan['tahun_masuk'] ?? null,
                            'tahun_lulus' => $pendidikan['tahun_lulus'] ?? null,
                            'no_ijazah' => $pendidikan['no_ijazah'] ?? null,
                            'nilai_akhir' => $pendidikan['nilai_akhir'] ?? null,
                            'status' => $pendidikan['status'] ?? 'LULUS',
                            'is_verified' => true,
                            'verified_at' => now(),
                            'verified_by' => auth()->id(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        
                        GtkEducation::create($educationData);
                    }
                }
            }

            // Update password
            if ($request->filled('current_password') && $request->filled('new_password')) {
                if (Hash::check($request->current_password, $user->password)) {
                    $user->update([
                        'password' => Hash::make($request->new_password)
                    ]);
                } else {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', 'Password saat ini tidak sesuai')
                        ->withInput();
                }
            }

            DB::commit();

            $successMessage = 'Profile berhasil diperbarui';

            return redirect()->route('user.gtk.show', ['userId' => $userId, 'uuid' => $user->id])
                ->with('success', $successMessage);
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating profile: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    private function getValidationRules($user)
    {
        $rules = [
            // User data
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id, 'id')
            ],
            
            // Profile data
            'nik' => 'required|string|max:20',
            'no_kk' => 'nullable|string|max:20',
            'tempat_lahir' => 'required|string|max:100',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'golongan_darah' => 'nullable|in:A,B,AB,O',
            'status_perkawinan' => 'nullable|string|max:50',
            'nama_ibu_kandung' => 'nullable|string|max:255',
            'npwp' => 'nullable|string|max:20',

            // Contact data
            'kontak.no_hp' => 'required|string|max:20',
            'kontak.no_whatsapp' => 'nullable|string|max:20',
            'kontak.kontak_darurat' => 'required|string|max:20',
            'kontak.instagram' => 'nullable|string|max:100',
            'kontak.facebook' => 'nullable|string|max:100',
            'kontak.twitter' => 'nullable|string|max:100',

            // Employment data
            'employment.nupy' => 'nullable|string|max:50',
            'employment.jenis_gtk' => 'nullable|string|max:100',
            'employment.jabatan' => 'nullable|string|max:100',
            'employment.status_kepegawaian' => 'nullable|string|max:50',
            'employment.tmt' => 'nullable|date',
            'employment.nomor_sk' => 'nullable|string|max:100',
            'employment.tanggal_sk' => 'nullable|date',
            
            // Work Units - multiple
            'work_units' => 'nullable|array',
            'work_units.*' => 'nullable|exists:work_units,id',

            // Alamat Domisili
            'alamat_domisili.jalan' => 'required|string|max:255',
            'alamat_domisili.rt_rw' => 'nullable|string|max:20',
            'alamat_domisili.dusun' => 'nullable|string|max:100',
            'alamat_domisili.desa' => 'nullable|string|max:100',
            'alamat_domisili.kecamatan' => 'nullable|string|max:100',
            'alamat_domisili.kab_kota' => 'nullable|string|max:100',
            'alamat_domisili.provinsi' => 'nullable|string|max:100',
            'alamat_domisili.kode_pos' => 'nullable|string|max:10',

            // Alamat KTP
            'alamat_ktp.jalan' => 'nullable|string|max:255',
            'alamat_ktp.rt_rw' => 'nullable|string|max:20',
            'alamat_ktp.dusun' => 'nullable|string|max:100',
            'alamat_ktp.desa' => 'nullable|string|max:100',
            'alamat_ktp.kecamatan' => 'nullable|string|max:100',
            'alamat_ktp.kab_kota' => 'nullable|string|max:100',
            'alamat_ktp.provinsi' => 'nullable|string|max:100',
            'alamat_ktp.kode_pos' => 'nullable|string|max:10',

            // Anggota Keluarga
            'anggota_keluarga' => 'nullable|array',
            'anggota_keluarga.*.relationship' => 'required_with:anggota_keluarga.*.nama|string|max:50',
            'anggota_keluarga.*.nama' => 'required_with:anggota_keluarga.*.relationship|string|max:255',
            'anggota_keluarga.*.jenis_kelamin' => 'required_with:anggota_keluarga.*.nama|in:L,P',
            'anggota_keluarga.*.pekerjaan' => 'nullable|string|max:100',
            'anggota_keluarga.*.pendidikan_terakhir' => 'nullable|string|max:100',
            'anggota_keluarga.*.tanggal_lahir' => 'nullable|date',
            'anggota_keluarga.*.alamat' => 'nullable|string',

            // ** PENDIDIKAN **
            'pendidikan' => 'nullable|array',
            'pendidikan.*.jenjang_pendidikan' => 'required_with:pendidikan.*.nama_satuan_pendidikan|string|max:20',
            'pendidikan.*.nama_satuan_pendidikan' => 'required_with:pendidikan.*.jenjang_pendidikan|string|max:255',
            'pendidikan.*.jurusan' => 'nullable|string|max:100',
            'pendidikan.*.fakultas' => 'nullable|string|max:100',
            'pendidikan.*.tahun_masuk' => 'nullable|integer|min:1900|max:' . date('Y'),
            'pendidikan.*.tahun_lulus' => 'nullable|integer|min:1900|max:' . date('Y'),
            'pendidikan.*.no_ijazah' => 'nullable|string|max:100',
            'pendidikan.*.nilai_akhir' => 'nullable|numeric|min:0|max:100',
            'pendidikan.*.status' => 'nullable|in:LULUS,BELUM_LULUS,DROPOUT,PINDAH',

            // Password change
            'current_password' => 'nullable|required_with:new_password',
            'new_password' => 'nullable|min:8|confirmed',
        ];

        return $rules;
    }

    private function prepareAddressData($addressData, $type)
    {
        $data = [
            'id' => Str::id(),
            'type' => $type,
            'jalan' => $addressData['jalan'],
            'rt_rw' => $addressData['rt_rw'] ?? null,
            'dusun' => $addressData['dusun'] ?? null,
            'kode_pos' => $addressData['kode_pos'] ?? null,
            'desa' => $addressData['desa'] ?? null,
            'kecamatan' => $addressData['kecamatan'] ?? null,
            'kab_kota' => $addressData['kab_kota'] ?? null,
            'provinsi' => $addressData['provinsi'] ?? null,
        ];

        return $data;
    }

    private function calculateCompletionPercentage($user)
    {
        $totalFields = 0;
        $completedFields = 0;

        // User fields
        $userFields = ['name', 'email'];
        foreach ($userFields as $field) {
            $totalFields++;
            if (!empty($user->$field)) $completedFields++;
        }

        // Profile fields
        if ($user->gtkProfile) {
            $profileFields = ['nik', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'no_kk'];
            foreach ($profileFields as $field) {
                $totalFields++;
                if (!empty($user->gtkProfile->$field)) $completedFields++;
            }
        } else {
            $totalFields += 5;
        }

        // Contact fields
        if ($user->gtkContact) {
            $contactFields = ['no_hp', 'kontak_darurat'];
            foreach ($contactFields as $field) {
                $totalFields++;
                if (!empty($user->gtkContact->$field)) $completedFields++;
            }
        } else {
            $totalFields += 2;
        }

        // Domisili address
        if ($user->gtkProfile) {
            $domisiliAddress = $user->gtkProfile->addresses->where('type', 'domisili')->first() ?? null;
            if ($domisiliAddress) {
                $addressFields = ['jalan', 'desa', 'kecamatan', 'kab_kota', 'provinsi'];
                foreach ($addressFields as $field) {
                    $totalFields++;
                    if (!empty($domisiliAddress->$field)) $completedFields++;
                }
            } else {
                $totalFields += 5;
            }
        }

        // Family members
        if ($user->gtkProfile && $user->gtkProfile->familyMembers->count() > 0) {
            $totalFields += 1;
            $completedFields += 1;
        } else {
            $totalFields += 1;
        }

        // Employment
        if ($user->gtkEmployment) {
            $employmentFields = ['nupy', 'jenis_gtk', 'jabatan', 'status_kepegawaian'];
            foreach ($employmentFields as $field) {
                $totalFields++;
                if (!empty($user->gtkEmployment->$field)) $completedFields++;
            }
        } else {
            $totalFields += 4;
        }

        // Work Units
        if ($user->workUnits && $user->workUnits->count() > 0) {
            $totalFields += 1;
            $completedFields += 1;
        } else {
            $totalFields += 1;
        }

        // ** PENDIDIKAN **
        if ($user->gtkEducations && $user->gtkEducations->count() > 0) {
            $totalFields += 1;
            $completedFields += 1;
        } else {
            $totalFields += 1;
        }

        return $totalFields > 0 ? round(($completedFields / $totalFields) * 100) : 0;
    }

    public function myProfile()
    {
        $user = Auth::user();
        return $this->show($user->id, $user->id);
    }

    public function editMyProfile()
    {
        $user = Auth::user();
        return $this->edit($user->id, $user->id);
    }

    public function updateMyProfile(Request $request)
    {
        return $this->update($request);
    }

    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'gtk_uuid' => 'required|string'
        ]);

        try {
            $user = Auth::user();
            
            if (Hash::check($request->password, $user->password)) {
                $gtk = User::with('gtkProfile')->where('id', $request->gtk_uuid)->first();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Verifikasi berhasil',
                    'data' => [
                        'nik' => $gtk->gtkProfile?->nik,
                        'no_kk' => $gtk->gtkProfile?->no_kk
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Password yang Anda masukkan salah'
                ], 401);
            }
        } catch (\Exception $e) {
            Log::error('Error verifying password: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server'
            ], 500);
        }
    }

    public function uploadPhoto(Request $request, string $userId, ?string $uuid = null)
    {
        $user = $uuid ? User::where('id', $uuid)->firstOrFail() : Auth::user();

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            if ($request->hasFile('photo')) {
                if ($user->profile_photo_path) {
                    \Storage::delete($user->profile_photo_path);
                }

                $path = $request->file('photo')->store('profile-photos', 'public');
                
                $user->update([
                    'profile_photo_path' => $path
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Foto profil berhasil diunggah',
                    'photo_url' => asset('storage/' . $path)
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Tidak ada file yang diunggah'
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Error uploading profile photo: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah foto'
            ], 500);
        }
    }

    public function deletePhoto(?Request $request = null, ?string $userId = null, ?string $uuid = null)
    {
        if ($uuid) {
            $user = User::where('id', $uuid)->firstOrFail();
        } elseif ($userId) {
            $user = User::where('id', $userId)->firstOrFail();
        } else {
            $user = Auth::user();
        }

        try {
            if ($user->profile_photo_path) {
                \Storage::delete($user->profile_photo_path);
                $user->update(['profile_photo_path' => null]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Foto profil berhasil dihapus'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Tidak ada foto profil'
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Error deleting profile photo: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus foto'
            ], 500);
        }
    }

    public function downloadCv(string $userId, string $uuid)
    {
        try {
            $gtk = User::with([
                'gtkProfile',
                'gtkProfile.addresses',
                'gtkProfile.familyMembers',
                'gtkContact',
                'gtkEmployment',
                'workUnits.workUnit',
                'educations',
                'competencies',
                'trainings',
                'careerPaths',
                'additionalTasks',
            ])->where('id', $uuid)->firstOrFail();

            $domisiliAddress = $gtk->gtkProfile?->addresses->where('type', 'domisili')->first();
            $ktpAddress      = $gtk->gtkProfile?->addresses->where('type', 'ktp')->first();

            $avatarPath = $gtk->avatar
                ? public_path('images/' . $gtk->avatar)
                : public_path('build/images/users/avatar-1.jpg');

            $ext = strtolower(pathinfo($gtk->avatar ?? 'avatar.jpg', PATHINFO_EXTENSION));
            $mimeType = $ext === 'png' ? 'image/png' : 'image/jpeg';

            $avatarBase64 = file_exists($avatarPath)
                ? 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($avatarPath))
                : null;

            $html = view('pdf.gtk-cv', [
                'gtk'              => $gtk,
                'avatarBase64'     => $avatarBase64,
                'domisiliAddress'  => $domisiliAddress,
                'ktpAddress'       => $ktpAddress,
            ])->render();

            $options = (new Options)->set('isRemoteEnabled', false);
            $dompdf  = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $output   = $dompdf->output();
            $filename = 'CV_' . Str::slug($gtk->name) . '_' . now()->format('Ymd') . '.pdf';

            return response($output, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Length'      => strlen($output),
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating CV PDF: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            $msg = $e->getMessage();
            return response(
                "<html><body style=\"font-family:Arial;padding:40px;text-align:center\">" .
                "<h2 style=\"color:#c0392b\">Gagal Membuat CV</h2>" .
                "<p style=\"color:#555\">{$msg}</p>" .
                "<a href=\"javascript:history.back()\" style=\"color:#3498db\">« Kembali</a>" .
                "</body></html>",
                500,
                ['Content-Type' => 'text/html; charset=utf-8']
            );
        }
    }
}