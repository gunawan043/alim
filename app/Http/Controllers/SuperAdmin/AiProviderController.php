<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AiProviderSetting;
use App\Services\Ai\AiProviderPresets;
use App\Services\Ai\AiProviderService;
use Illuminate\Http\Request;

/**
 * Pengaturan provider AI (multi-platform, OpenAI-compatible).
 * Mendukung provider berbayar maupun gratis/lokal; satu provider default aktif.
 */
class AiProviderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeSuper($request);

        $providers = AiProviderSetting::query()->get()
            ->sortBy(fn (AiProviderSetting $provider) => sprintf(
                '%d-%d-%06d-%s',
                $provider->is_default ? 0 : 1,
                match ($provider->extra['tier'] ?? 'paid') {
                    'free' => 0,
                    'local' => 1,
                    'paid' => 2,
                    default => 3,
                },
                (int) ($provider->extra['sort_order'] ?? 999),
                $provider->name,
            ))
            ->values();

        $keyGroups = [];
        foreach ($providers as $provider) {
            $group = $provider->keyGroup();
            if ($group && filled($provider->api_key)) {
                $keyGroups[$group] = true;
            }
        }

        $presets = AiProviderPresets::all();

        return view('super-admin.ai-providers.index', [
            'userId' => $request->route('userId') ?? auth()->id(),
            'providers' => $providers,
            'keyGroups' => $keyGroups,
            'presets' => $presets,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeSuper($request);

        $data = $this->validated($request);
        $data['created_by'] = $request->user()?->id;

        $provider = AiProviderSetting::create($data);

        if ($request->boolean('is_default') || AiProviderSetting::query()->count() === 1) {
            $provider->makeDefault();
        }

        return back()->with('success', "Provider AI \"{$provider->name}\" ditambahkan.");
    }

    public function update(Request $request, string $userId, string $id)
    {
        $this->authorizeSuper($request);

        $provider = AiProviderSetting::findOrFail($id);
        $data = $this->validated($request, $provider);

        if ($request->boolean('clear_api_key')) {
            $data['api_key'] = null;
        }

        $provider->update($data);

        if ($request->boolean('is_default') && $provider->is_active) {
            $provider->makeDefault();
        } elseif (! $provider->is_active && $provider->is_default) {
            $provider->forceFill(['is_default' => false])->save();
        }

        return back()->with('success', "Provider AI \"{$provider->name}\" diperbarui.");
    }

    public function setDefault(Request $request, string $userId, string $id)
    {
        $this->authorizeSuper($request);

        $provider = AiProviderSetting::findOrFail($id);

        if (! $provider->is_active) {
            return back()->with('error', 'Aktifkan provider terlebih dahulu sebelum menjadikannya default.');
        }

        $provider->makeDefault();

        return back()->with('success', "Provider \"{$provider->name}\" dijadikan default.");
    }

    public function test(Request $request, string $userId, string $id, AiProviderService $ai)
    {
        $this->authorizeSuper($request);

        $provider = AiProviderSetting::findOrFail($id);
        $result = $ai->testConnection($provider);

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['ok']
                ? "Koneksi AI berhasil (HTTP {$result['status']}): ".mb_substr(trim($result['content']), 0, 120)
                : 'Koneksi AI gagal: '.mb_substr((string) $result['error'], 0, 300)
        );
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $this->authorizeSuper($request);

        $provider = AiProviderSetting::findOrFail($id);
        $wasDefault = $provider->is_default;
        $name = $provider->name;
        $provider->delete();

        if ($wasDefault) {
            $next = AiProviderSetting::query()->where('is_active', true)->orderBy('name')->first();
            $next?->makeDefault();
        }

        return back()->with('success', "Provider AI \"{$name}\" dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?AiProviderSetting $provider = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'provider' => 'required|in:'.implode(',', AiProviderPresets::keys()),
            'endpoint' => 'required|url|max:500',
            'api_key' => 'nullable|string|max:2000',
            'model' => 'required|string|max:150',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'clear_api_key' => 'boolean',
        ]);

        unset($data['clear_api_key']);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default');

        if ($provider && blank($data['api_key'] ?? null)) {
            unset($data['api_key']);
        }

        return $data;
    }

    private function authorizeSuper(Request $request): void
    {
        abort_unless(canPermission('super-admin-only'), 403, 'Hanya Super Admin yang dapat mengatur provider AI.');
    }
}
