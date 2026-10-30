<?php

namespace App\Services;

use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Indeks menu untuk pencarian global (topbar).
 *
 * Sumber kebenaran = sidebar yang benar-benar dirender untuk user tersebut.
 * Dengan begitu hasil pencarian otomatis mengikuti role, jabatan, dan tugas
 * tambahan — tidak ada menu yang muncul kalau user tidak punya aksesnya,
 * dan menu baru di sidebar langsung bisa dicari tanpa mendaftarkannya manual.
 */
class MenuSearchIndex
{
    private const CACHE_MINUTES = 5;

    /**
     * @return list<array{label: string, group: string, url: string}>
     */
    public static function forUser(User $user): array
    {
        return Cache::remember(
            'menu-search:'.$user->id,
            now()->addMinutes(self::CACHE_MINUTES),
            fn () => self::build()
        );
    }

    public static function forget(User $user): void
    {
        Cache::forget('menu-search:'.$user->id);
    }

    /**
     * @return list<array{label: string, group: string, url: string}>
     */
    private static function build(): array
    {
        try {
            $html = view('layouts.sidebar')->render();
        } catch (\Throwable $e) {
            Log::warning('MenuSearchIndex: gagal merender sidebar', ['error' => $e->getMessage()]);

            return [];
        }

        return self::parse($html);
    }

    /**
     * @return list<array{label: string, group: string, url: string}>
     */
    private static function parse(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);
        $list = $xpath->query('//ul[@id="navbar-nav"]')->item(0);

        if (! $list instanceof DOMElement) {
            return [];
        }

        $items = [];
        $group = 'Menu';

        foreach ($list->childNodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $classes = ' '.$node->getAttribute('class').' ';

            if (str_contains($classes, ' menu-title ')) {
                $group = self::cleanText($node);
                continue;
            }

            $anchors = $xpath->query('.//a', $node);
            if ($anchors->length === 0) {
                continue;
            }

            // Label menu induk dipakai sebagai grup untuk submenu di dalamnya.
            $parentLabel = self::cleanText($anchors->item(0));
            $first = true;

            foreach ($anchors as $anchor) {
                $href = trim($anchor->getAttribute('href'));

                if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
                    continue;
                }

                $label = self::cleanText($anchor);
                if ($label === '') {
                    continue;
                }

                $items[$href] = [
                    'label' => $label,
                    'group' => $first ? $group : $parentLabel,
                    'url' => $href,
                ];

                $first = false;
            }
        }

        return array_values($items);
    }

    private static function cleanText(\DOMNode $node): string
    {
        $clone = $node->cloneNode(true);

        if ($clone instanceof DOMElement) {
            $xpath = new DOMXPath($clone->ownerDocument);
            foreach ($xpath->query('.//*[contains(@class,"badge")]', $clone) as $badge) {
                $badge->parentNode?->removeChild($badge);
            }
        }

        return trim((string) preg_replace('/\s+/u', ' ', $clone->textContent));
    }
}
