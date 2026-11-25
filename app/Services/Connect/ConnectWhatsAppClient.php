<?php

namespace App\Services\Connect;

use App\Support\ConnectApiConfig;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Klien HTTP Core → ALIM Connect untuk pesan WhatsApp.
 *
 * Ruang lingkupnya sengaja dibatasi pada yang dibutuhkan uji koneksi:
 * membaca status sesi dan mengirim satu pesan teks. Pengiriman massal,
 * media, dan Status/Story menyusul di siklus migrasi provider — bukan di sini.
 *
 * Semua metode mengembalikan susunan hasil yang seragam sehingga pemanggil
 * (controller, dan nanti `WhatsAppGatewayService`) tidak perlu tahu HTTP:
 *
 *   ok        bool    true bila Connect menerima permintaan
 *   status    int     HTTP status (0 bila tidak ada respons)
 *   message   string  keterangan singkat berbahasa Indonesia
 *   data      array   isi body respons (bila JSON)
 *   raw       string  potongan body mentah, maksimal 300 karakter
 */
class ConnectWhatsAppClient
{
    public const TIMEOUT_SECONDS = 15;

    /** Status sesi Connect yang berarti pesan boleh dikirim sekarang. */
    public const READY = 'ready';

    /**
     * Ambil status satu sesi: GET /api/sessions/{sessionId}
     *
     * @return array{ok: bool, status: int, message: string, data: array, raw: string}
     */
    public function sessionStatus(): array
    {
        $base = ConnectApiConfig::baseUrl();
        $sessionId = ConnectApiConfig::sessionId();

        if ($base === '' || $sessionId === '') {
            return $this->failure(0, 'Alamat Connect atau Session ID belum lengkap.');
        }

        return $this->call(
            fn () => $this->request()->get($base.'/api/sessions/'.rawurlencode($sessionId))
        );
    }

    /**
     * Kirim satu pesan teks: POST /api/sessions/{sessionId}/messages/send-text
     *
     * @return array{ok: bool, status: int, message: string, data: array, raw: string}
     */
    public function sendText(string $phone, string $text): array
    {
        $base = ConnectApiConfig::baseUrl();
        $sessionId = ConnectApiConfig::sessionId();
        $jid = ConnectApiConfig::jid($phone);

        if ($base === '' || $sessionId === '') {
            return $this->failure(0, 'Alamat Connect atau Session ID belum lengkap.');
        }

        if ($jid === null) {
            return $this->failure(0, 'Nomor tujuan tidak valid.');
        }

        return $this->call(
            fn () => $this->request()->post(
                $base.'/api/sessions/'.rawurlencode($sessionId).'/messages/send-text',
                ['chatId' => $jid, 'text' => $text]
            ),
            successStatuses: [200, 201, 202]
        );
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            ConnectApiConfig::KEY_HEADER => ConnectApiConfig::apiKey(),
            'Accept' => 'application/json',
        ])
            ->timeout(self::TIMEOUT_SECONDS)
            ->connectTimeout(10);
    }

    /**
     * @param  callable(): \Illuminate\Http\Client\Response  $send
     * @param  list<int>  $successStatuses
     * @return array{ok: bool, status: int, message: string, data: array, raw: string}
     */
    private function call(callable $send, array $successStatuses = [200]): array
    {
        try {
            $response = $send();
        } catch (ConnectionException $e) {
            return $this->failure(
                0,
                'Connect tidak bisa dihubungi. Periksa Base URL dan apakah layanan Connect sedang hidup.',
                mb_substr($e->getMessage(), 0, 300)
            );
        } catch (\Throwable $e) {
            return $this->failure(0, 'Permintaan ke Connect gagal.', mb_substr($e->getMessage(), 0, 300));
        }

        $body = (string) $response->body();
        $json = $response->json();
        $data = is_array($json) ? $json : [];
        $http = $response->status();

        if (in_array($http, $successStatuses, true)) {
            return [
                'ok' => true,
                'status' => $http,
                'message' => 'Connect menerima permintaan.',
                'data' => $data,
                'raw' => mb_substr($body, 0, 300),
            ];
        }

        return [
            'ok' => false,
            'status' => $http,
            'message' => $this->explain($http, $data),
            'data' => $data,
            'raw' => mb_substr($body, 0, 300),
        ];
    }

    /**
     * Terjemahkan status HTTP menjadi langkah perbaikan, bukan sekadar kode.
     * Pesan ini yang tampil ke operator, jadi harus menyebut apa yang harus
     * diperiksa — bukan membocorkan detail internal Connect.
     *
     * @param  array<string, mixed>  $data
     */
    private function explain(int $http, array $data): string
    {
        $detail = $this->detail($data);

        return match ($http) {
            400 => 'Connect menolak permintaan: sesi belum aktif atau isian tidak valid.'
                .' Buka halaman Connect dan pastikan sesi WhatsApp sudah terhubung (status ready).'.($detail !== '' ? ' Detail: '.$detail : ''),
            401 => 'API key ditolak Connect. Periksa kembali API key ber-role OPERATOR di halaman pengaturan.',
            403 => 'API key tidak berwenang untuk aksi ini. Gunakan key dengan role OPERATOR.',
            404 => 'Session ID atau alamat API tidak ditemukan. Periksa Session ID di halaman pengaturan.',
            409 => 'Mesin WhatsApp Connect belum siap menerima perintah. Tunggu sebentar lalu coba lagi.'.($detail !== '' ? ' Detail: '.$detail : ''),
            413 => 'Pesan atau media melebihi batas ukuran Connect.',
            422 => 'Isian ditolak Connect.'.($detail !== '' ? ' Detail: '.$detail : ''),
            429 => 'Terlalu banyak permintaan dalam waktu singkat. Tunggu satu menit lalu coba lagi.',
            503 => 'Layanan Connect sedang tidak tersedia. Coba lagi beberapa saat lagi.',
            default => 'Connect mengembalikan status HTTP '.$http.'.'.($detail !== '' ? ' Detail: '.$detail : ''),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function detail(array $data): string
    {
        foreach (['message', 'error', 'detail'] as $key) {
            if (isset($data[$key]) && is_string($data[$key]) && trim($data[$key]) !== '') {
                return mb_substr(trim($data[$key]), 0, 200);
            }
        }

        return '';
    }

    /**
     * @return array{ok: bool, status: int, message: string, data: array, raw: string}
     */
    private function failure(int $status, string $message, string $raw = ''): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'message' => $message,
            'data' => [],
            'raw' => $raw,
        ];
    }
}
