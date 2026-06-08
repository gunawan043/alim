<?php

namespace App\Services;

use App\Models\RecruitmentDocument;
use App\Models\RecruitmentProfile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service untuk mengambil dokumen pelamar dari recruitment.abuhurairah.id
 *
 * Dokumen dan foto pelamar disimpan di sistem recruitment (external),
 * service ini bertugas mengambil referensi URL dan metadata-nya untuk ALIM.
 */
class RecruitmentDocumentService
{
    /**
     * Base URL recruitment app (from config)
     */
    protected static function getBaseUrl(): string
    {
        return config('services.recruitment_api.url', env('RECRUITMENT_API_URL', 'https://recruitment.abuhurairah.id'));
    }

    /**
     * API Token untuk authentication
     */
    protected static function getApiToken(): ?string
    {
        return config('services.recruitment_api.token', env('RECRUITMENT_API_TOKEN'));
    }

    /**
     * Ambil semua dokumen untuk profile tertentu dari API
     *
     * @param int $profileId RecruitmentProfile ID
     * @return array Array of document metadata
     */
    public static function getDocuments(int $profileId): array
    {
        try {
            $baseUrl = self::getBaseUrl();
            $token = self::getApiToken();

            if (!$token) {
                Log::warning('RecruitmentDocumentService: No API token configured');
                return [];
            }

            $response = Http::withToken($token)
                ->timeout(10)
                ->get("{$baseUrl}/api/profiles/{$profileId}/documents");

            if ($response->successful()) {
                $data = $response->json('data', []);
                Log::info('RecruitmentDocumentService: Successfully fetched documents', [
                    'profile_id' => $profileId,
                    'count' => count($data),
                ]);
                return $data;
            }

            Log::error('RecruitmentDocumentService: Failed to fetch documents', [
                'profile_id' => $profileId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [];
        } catch (Exception $e) {
            Log::error('RecruitmentDocumentService: Exception while fetching documents', [
                'profile_id' => $profileId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Ambil URL foto profil dari API
     *
     * @param int $profileId RecruitmentProfile ID
     * @return string|null Full URL ke foto atau null jika tidak ada
     */
    public static function getPhotoUrl(int $profileId): ?string
    {
        try {
            $baseUrl = self::getBaseUrl();
            $token = self::getApiToken();

            if (!$token) {
                return null;
            }

            $response = Http::withToken($token)
                ->timeout(10)
                ->get("{$baseUrl}/api/profiles/{$profileId}/photo");

            if ($response->successful()) {
                return $response->json('url');
            }

            return null;
        } catch (Exception $e) {
            Log::error('RecruitmentDocumentService: Exception while fetching photo', [
                'profile_id' => $profileId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Ambil URL dokumen spesifik berdasarkan jenis dokumen
     *
     * @param int $profileId RecruitmentProfile ID
     * @param string $jenis Jenis dokumen (cv, ktp, ijazah, dll)
     * @return string|null Full URL ke dokumen atau null jika tidak ada
     */
    public static function getDocumentUrl(int $profileId, string $jenis): ?string
    {
        try {
            $baseUrl = self::getBaseUrl();
            $token = self::getApiToken();

            if (!$token) {
                return null;
            }

            $response = Http::withToken($token)
                ->timeout(10)
                ->get("{$baseUrl}/api/profiles/{$profileId}/documents/{$jenis}");

            if ($response->successful()) {
                return $response->json('url');
            }

            return null;
        } catch (Exception $e) {
            Log::error('RecruitmentDocumentService: Exception while fetching document URL', [
                'profile_id' => $profileId,
                'jenis' => $jenis,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Sync dokumen dari API ke database lokal
     * Menyimpan metadata dan external URL ke tabel recruitment_documents
     *
     * @param int $profileId RecruitmentProfile ID
     * @return int Jumlah dokumen yang disync
     */
    public static function syncToLocal(int $profileId): int
    {
        try {
            $documents = self::getDocuments($profileId);
            $synced = 0;

            foreach ($documents as $doc) {
                // Update atau insert dokumen
                RecruitmentDocument::updateOrCreate(
                    [
                        'recruitment_profile_id' => $profileId,
                        'external_id' => $doc['id'] ?? null,
                    ],
                    [
                        'jenis_dokumen' => $doc['jenis'] ?? 'lainnya',
                        'nama_dokumen' => $doc['nama'] ?? 'Dokumen',
                        'file_path' => $doc['path'] ?? null, // Path untuk display
                        'external_url' => $doc['url'] ?? null,
                        'file_size' => $doc['ukuran'] ?? null,
                        'file_extension' => $doc['ekstensi'] ?? null,
                        'synced_at' => now(),
                        'is_primary' => $doc['is_primary'] ?? false,
                    ]
                );
                $synced++;
            }

            Log::info('RecruitmentDocumentService: Sync completed', [
                'profile_id' => $profileId,
                'synced' => $synced,
            ]);

            return $synced;
        } catch (Exception $e) {
            Log::error('RecruitmentDocumentService: Exception during sync', [
                'profile_id' => $profileId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Sync dokumen untuk profile object — convenience method untuk controller
     *
     * @param RecruitmentProfile $profile
     * @return array ['success' => bool, 'message' => string, 'synced' => int]
     */
    public function syncDocumentsForProfile(RecruitmentProfile $profile): array
    {
        if (! $profile->external_id) {
            return [
                'success' => false,
                'message' => 'Kandidat tidak memiliki external_id untuk sync ke recruitment.abuhurairah.id.',
            ];
        }

        if (! self::isConfigured()) {
            return [
                'success' => false,
                'message' => 'API recruitment tidak terkonfigurasi. Pastikan RECRUITMENT_API_URL dan RECRUITMENT_API_TOKEN diset.',
            ];
        }

        $synced = self::syncToLocal($profile->id);

        return [
            'success' => true,
            'message' => "Berhasil sync {$synced} dokumen dari recruitment.abuhurairah.id.",
            'synced' => $synced,
        ];
    }

    /**
     * Sync foto profil dari API ke database lokal
     *
     * @param int $profileId RecruitmentProfile ID
     * @return bool True jika berhasil sync
     */
    public static function syncPhotoToLocal(int $profileId): bool
    {
        try {
            $url = self::getPhotoUrl($profileId);

            if (!$url) {
                return false;
            }

            // Update profile dengan URL foto external
            $profile = RecruitmentProfile::find($profileId);

            if (!$profile) {
                return false;
            }

            // Simpan URL di field yang sesuai (misalnya foto_url_external)
            // Jika belum ada field, bisa disimpan di JSON field atau relation
            $profile->foto_url_external = $url;
            $profile->save();

            Log::info('RecruitmentDocumentService: Photo sync completed', [
                'profile_id' => $profileId,
                'url' => $url,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('RecruitmentDocumentService: Exception during photo sync', [
                'profile_id' => $profileId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cek apakah service siap digunakan (config ada)
     *
     * @return bool
     */
    public static function isConfigured(): bool
    {
        return !empty(self::getApiToken()) && !empty(self::getBaseUrl());
    }

    /**
     * Ambil data nilai/application dari recruitment.abuhurairah.id
     *
     * @param int $applicationId Local application ID
     * @return array Data nilai dari recruitment (skor_administrasi, nilai_tes, dll)
     */
    public static function getApplicationNilai(int $applicationId): array
    {
        try {
            $baseUrl = self::getBaseUrl();
            $token = self::getApiToken();

            if (!$token) {
                return [];
            }

            $response = Http::withToken($token)
                ->timeout(10)
                ->get("{$baseUrl}/api/applications/{$applicationId}/nilai");

            if ($response->successful()) {
                return $response->json('data', []);
            }

            Log::warning('RecruitmentDocumentService: Failed to fetch application nilai', [
                'application_id' => $applicationId,
                'status' => $response->status(),
            ]);
            return [];
        } catch (Exception $e) {
            Log::error('RecruitmentDocumentService: Exception while fetching nilai', [
                'application_id' => $applicationId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Push/Update nilai ke recruitment.abuhurairah.id
     *
     * @param int $applicationId Local application ID
     * @param array $nilaiData Data nilai yang akan di-push
     * @return bool True jika berhasil
     */
    public static function pushNilaiToRecruitment(int $applicationId, array $nilaiData): bool
    {
        try {
            $baseUrl = self::getBaseUrl();
            $token = self::getApiToken();

            if (!$token) {
                Log::warning('RecruitmentDocumentService: No token, cannot push nilai');
                return false;
            }

            $response = Http::withToken($token)
                ->timeout(15)
                ->put("{$baseUrl}/api/applications/{$applicationId}/nilai", $nilaiData);

            if ($response->successful()) {
                Log::info('RecruitmentDocumentService: Successfully pushed nilai', [
                    'application_id' => $applicationId,
                ]);
                return true;
            }

            Log::error('RecruitmentDocumentService: Failed to push nilai', [
                'application_id' => $applicationId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (Exception $e) {
            Log::error('RecruitmentDocumentService: Exception while pushing nilai', [
                'application_id' => $applicationId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Sync application profile ke recruitment.abuhurairah.id
     * Membuat data application di recruitment jika belum ada
     *
     * @param int $profileId Local profile ID
     * @param array $applicationData Data application
     * @return array|null Response dari API
     */
    public static function syncApplicationToRecruitment(int $profileId, array $applicationData): ?array
    {
        try {
            $baseUrl = self::getBaseUrl();
            $token = self::getApiToken();

            if (!$token) {
                return null;
            }

            $response = Http::withToken($token)
                ->timeout(15)
                ->post("{$baseUrl}/api/profiles/{$profileId}/applications", $applicationData);

            if ($response->successful()) {
                return $response->json('data');
            }

            Log::error('RecruitmentDocumentService: Failed to sync application', [
                'profile_id' => $profileId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (Exception $e) {
            Log::error('RecruitmentDocumentService: Exception while syncing application', [
                'profile_id' => $profileId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
