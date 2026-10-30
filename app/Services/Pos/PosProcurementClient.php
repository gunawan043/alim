<?php

namespace App\Services\Pos;

use App\Support\PosProcurementConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien API pengadaan POS. ALIM hanya membaca katalog, mengajukan permintaan,
 * dan memantau status; pemrosesan stok/SIPLah tetap di POS.
 */
class PosProcurementClient
{
    public function products(array $query = []): array
    {
        return $this->get('products', $query);
    }

    public function requests(array $query = []): array
    {
        return $this->get('requests', $query);
    }

    public function request(string $number): array
    {
        return (array) ($this->get('requests/'.rawurlencode($number))['data'] ?? []);
    }

    public function createRequest(array $payload): array
    {
        $response = $this->http()->post($this->url('requests'), $payload);
        $json = $response->json();

        if (! $response->successful() || ! is_array($json) || ! ($json['success'] ?? false)) {
            throw new RuntimeException($this->errorMessage($response->status(), $json));
        }

        return (array) ($json['data'] ?? []);
    }

    public function ping(): array
    {
        return $this->requests(['per_page' => 1]);
    }

    public function bumpPaymentMethods(): array
    {
        return $this->get('bump-orders/payment-methods')['data'] ?? [];
    }

    public function bumpOrders(array $query = []): array
    {
        return $this->get('bump-orders', $query);
    }

    public function createBumpOrder(array $payload): array
    {
        $response = $this->http()->post($this->url('bump-orders'), $payload);
        $json = $response->json();
        if (! $response->successful() || ! is_array($json) || ! ($json['success'] ?? false)) {
            throw new RuntimeException($this->errorMessage($response->status(), $json));
        }

        return (array) ($json['data'] ?? []);
    }

    public function bumpOrder(string $number): array
    {
        return (array) ($this->get('bump-orders/'.rawurlencode($number))['data'] ?? []);
    }

    public function cancelBumpOrder(string $number, string $reason): array
    {
        $response = $this->http()->post($this->url('bump-orders/'.rawurlencode($number).'/cancel'), ['reason' => $reason]);
        $json = $response->json();
        if (! $response->successful() || ! is_array($json) || ! ($json['success'] ?? false)) {
            throw new RuntimeException($this->errorMessage($response->status(), $json));
        }

        return (array) ($json['data'] ?? []);
    }

    public function siplahProposals(array $query = []): array
    {
        return $this->get('siplah/proposals', $query);
    }

    public function showSiplahProposal(string $number): array
    {
        return (array) ($this->get('siplah/proposals/'.rawurlencode($number))['data'] ?? []);
    }

    public function createSiplahProposal(array $payload, ?UploadedFile $attachment = null): array
    {
        $request = $this->http();

        if ($attachment) {
            $request = $request->attach('attachment', file_get_contents($attachment->getRealPath()), $attachment->getClientOriginalName());
        }

        $response = $request->post($this->url('siplah/proposals'), $payload);
        $json = $response->json();

        if (! $response->successful() || ! is_array($json) || ! ($json['success'] ?? false)) {
            throw new RuntimeException($this->errorMessage($response->status(), $json));
        }

        return (array) ($json['data'] ?? []);
    }

    public function updateSiplahProposal(string $number, array $payload, ?UploadedFile $attachment = null): array
    {
        $request = $this->http();
        if ($attachment) {
            $request = $request->attach('attachment', file_get_contents($attachment->getRealPath()), $attachment->getClientOriginalName());
        }

        $response = $request->put($this->url('siplah/proposals/'.rawurlencode($number)), $payload);
        $json = $response->json();

        if (! $response->successful() || ! is_array($json) || ! ($json['success'] ?? false)) {
            throw new RuntimeException($this->errorMessage($response->status(), $json));
        }

        return (array) ($json['data'] ?? []);
    }

    public function actionSiplahProposal(string $number, string $action, array $payload = []): array
    {
        $response = $this->http()->post($this->url('siplah/proposals/'.rawurlencode($number).'/'.$action), $payload);
        $json = $response->json();

        if (! $response->successful() || ! is_array($json) || ! ($json['success'] ?? false)) {
            throw new RuntimeException($this->errorMessage($response->status(), $json));
        }

        return (array) ($json['data'] ?? []);
    }

    private function get(string $path, array $query = []): array
    {
        $response = $this->http()->get($this->url($path), $query);
        $json = $response->json();

        if (! $response->successful() || ! is_array($json) || ! ($json['success'] ?? false)) {
            throw new RuntimeException($this->errorMessage($response->status(), $json));
        }

        return [
            'data' => (array) ($json['data'] ?? []),
            'meta' => (array) ($json['meta'] ?? []),
        ];
    }

    private function http(): PendingRequest
    {
        if (! PosProcurementConfig::isConfigured()) {
            throw new RuntimeException('Koneksi API pengadaan POS belum dikonfigurasi.');
        }

        return Http::withHeaders([
            PosProcurementConfig::header() => (string) PosProcurementConfig::token(),
        ])
            ->acceptJson()
            ->timeout(PosProcurementConfig::timeout())
            ->withOptions(['verify' => PosProcurementConfig::verifySsl()]);
    }

    private function url(string $path): string
    {
        $url = PosProcurementConfig::endpoint($path);

        if ($url === '') {
            throw new RuntimeException('Base URL API pengadaan POS belum diatur.');
        }

        return $url;
    }

    private function errorMessage(int $status, ?array $json): string
    {
        $message = is_array($json) ? (string) ($json['message'] ?? '') : '';

        return match (true) {
            $status === 401 => 'Token API pengadaan POS tidak valid.',
            $status === 403 => 'Akses API pengadaan POS ditolak.',
            $status === 404 => 'Endpoint/permintaan pengadaan tidak ditemukan di POS.',
            $status === 422 => $message !== '' ? $message : 'Data pengadaan ditolak POS.',
            $status === 429 => 'Terlalu banyak permintaan ke API pengadaan POS.',
            $status >= 500 => 'Server POS mengalami kesalahan.',
            default => $message !== '' ? $message : 'API pengadaan POS mengembalikan error.',
        };
    }
}
