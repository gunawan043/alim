<?php

namespace App\Support;

/**
 * Maps a User's effective roles to a deduplicated set of Sanctum abilities.
 *
 * ADR-018: abilities are domain-based (e.g. "attendance.read"), never
 * application-based. The mapping itself lives in config/sanctum.php so
 * future applications (Guru Mobile, Musyrif Mobile, Admin Mobile, External
 * APIs) can adopt the same registry without code changes.
 *
 * Authorization Z-layer checks (Policies, Form Requests, Gates) remain
 * authoritative for access decisions; abilities only constrain which
 * endpoints a token may invoke and give the API gateway a fast filter.
 */
final class AbilityRegistry
{
    /**
     * @param  iterable<string>  $roles  Role identifiers (lowercased, trimmed).
     * @return list<string> Sorted, de-duplicated ability list.
     */
    public static function forRoles(iterable $roles): array
    {
        $merged = [];

        $roleMap = config('sanctum.abilities.roles', []);

        foreach ($roles as $role) {
            $key = strtolower(trim((string) $role));

            if ($key === '' || ! array_key_exists($key, $roleMap)) {
                continue;
            }

            foreach ($roleMap[$key] as $ability) {
                $merged[] = (string) $ability;
            }
        }

        if ($merged === []) {
            $merged = config('sanctum.abilities.default', []);
        }

        $expanded = self::expandWildcards($merged);

        return self::filterToCatalog($expanded);
    }

    /**
     * Expand "*" and "<domain>.*" into the corresponding concrete abilities.
     *
     * @param  list<string>  $abilities
     * @return list<string>
     */
    private static function expandWildcards(array $abilities): array
    {
        $catalog = config('sanctum.abilities.catalog', []);

        if (in_array('*', $abilities, true)) {
            return array_values(array_merge($catalog, $abilities));
        }

        $expanded = [];

        foreach ($abilities as $ability) {
            $expanded[] = $ability;

            if (str_ends_with($ability, '.*')) {
                $domain = substr($ability, 0, -2);

                foreach ($catalog as $concrete) {
                    if (str_starts_with($concrete, $domain.'.')) {
                        $expanded[] = $concrete;
                    }
                }
            }
        }

        return $expanded;
    }

    /**
     * Filter the expanded list down to abilities that exist in the
     * catalogue. Defensive: catches legacy / stale role definitions
     * before they leak into a freshly minted token.
     */
    private static function filterToCatalog(array $abilities): array
    {
        $catalog = config('sanctum.abilities.catalog', []);

        $filtered = array_values(array_unique(array_filter(
            $abilities,
            static fn (string $a) => in_array($a, $catalog, true) || $a === '*',
        )));

        sort($filtered);

        return $filtered;
    }
}
