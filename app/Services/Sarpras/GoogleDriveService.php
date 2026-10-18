<?php

namespace App\Services\Sarpras;

use App\Models\SystemSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    public function settings(): array
    {
        $json = null;
        $encrypted = SystemSetting::get('google_drive_service_account_json');
        if ($encrypted) {
            try {
                $json = json_decode(Crypt::decryptString($encrypted), true);
            } catch (\Throwable $e) {
                Log::warning('Google Drive service account tidak dapat didekripsi: '.$e->getMessage());
            }
        }

        return [
            'enabled' => (string) SystemSetting::get('google_drive_enabled', '0') === '1',
            'folder_id' => SystemSetting::get('google_drive_folder_id'),
            'shared_drive_id' => SystemSetting::get('google_drive_shared_drive_id'),
            'folder_url' => SystemSetting::get('google_drive_folder_url'),
            'service_account' => $json,
            'service_account_email' => $json['client_email'] ?? SystemSetting::get('google_drive_service_account_email'),
            'project_id' => $json['project_id'] ?? SystemSetting::get('google_drive_project_id'),
        ];
    }

    public function isConfigured(): bool
    {
        $settings = $this->settings();

        return $settings['enabled']
            && ! empty($settings['folder_id'])
            && ! empty($settings['service_account']['client_email'])
            && ! empty($settings['service_account']['private_key']);
    }

    /**
     * @return array{path:?string,url:?string,provider:string}|null
     */
    public function upload(UploadedFile $file, string $folder = 'sarpras'): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $settings = $this->settings();
            $token = $this->accessToken($settings['service_account']);
            if (! $token) {
                return null;
            }

            $name = trim($folder, '/').'/'.now()->format('Ymd-His').'-'.preg_replace('/[^A-Za-z0-9._-]/', '-', $file->getClientOriginalName());
            $metadata = [
                'name' => $name,
                'parents' => [$settings['folder_id']],
            ];

            $response = Http::withToken($token)
                ->attach('metadata', json_encode($metadata), 'metadata.json', ['Content-Type' => 'application/json; charset=UTF-8'])
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName(), ['Content-Type' => $file->getMimeType() ?: 'application/octet-stream'])
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&supportsAllDrives=true&fields=id,webViewLink');

            if (! $response->successful()) {
                Log::warning('Google Drive upload gagal: '.$response->body());
                return null;
            }

            $id = $response->json('id');
            $url = $response->json('webViewLink') ?: ($id ? 'https://drive.google.com/file/d/'.$id.'/view' : null);

            if ($id) {
                Http::withToken($token)->post("https://www.googleapis.com/drive/v3/files/{$id}/permissions?supportsAllDrives=true", [
                    'role' => 'reader',
                    'type' => 'anyone',
                ]);
            }

            return ['path' => null, 'url' => $url, 'provider' => 'google_drive'];
        } catch (\Throwable $e) {
            Log::warning('Google Drive upload exception: '.$e->getMessage());
            return null;
        }
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'Pengaturan Google Drive belum lengkap/aktif.'];
        }

        try {
            $settings = $this->settings();
            $token = $this->accessToken($settings['service_account']);
            if (! $token) {
                return ['ok' => false, 'message' => 'Gagal memperoleh access token dari Google.'];
            }

            $response = Http::withToken($token)->get('https://www.googleapis.com/drive/v3/files/'.$settings['folder_id'].'?supportsAllDrives=true&fields=id,name');
            if (! $response->successful()) {
                return ['ok' => false, 'message' => 'Folder tidak dapat diakses: '.$response->body()];
            }

            return ['ok' => true, 'message' => 'Koneksi Google Drive berhasil. Folder: '.($response->json('name') ?? $settings['folder_id'])];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Koneksi gagal: '.$e->getMessage()];
        }
    }

    private function accessToken(array $serviceAccount): ?string
    {
        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $input = $this->base64Url(json_encode($header)).'.'.$this->base64Url(json_encode($claims));
        $signature = '';
        if (! openssl_sign($input, $signature, $serviceAccount['private_key'], OPENSSL_ALGO_SHA256)) {
            return null;
        }

        $jwt = $input.'.'.$this->base64Url($signature);
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            Log::warning('Google Drive token gagal: '.$response->body());
            return null;
        }

        return $response->json('access_token');
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
