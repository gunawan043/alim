<?php

namespace App\Services;

/**
 * Implementasi TOTP RFC 6238 sederhana tanpa dependency tambahan.
 * Kompatibel dengan Google Authenticator, Microsoft Authenticator, Authy, dll.
 */
class TotpService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $length = 32): string
    {
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $secret;
    }

    public static function otpauthUri(string $secret, string $email, string $issuer = 'ALIM'): string
    {
        $label = rawurlencode($issuer.':'.$email);

        return 'otpauth://totp/'.$label.'?secret='.rawurlencode($secret)
            .'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30';
    }

    public static function currentCode(string $secret, ?int $timestamp = null): string
    {
        $counter = intdiv($timestamp ?? time(), 30);

        return self::codeForCounter($secret, $counter);
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\D+/', '', trim($code)) ?? '';

        if (strlen($code) !== 6) {
            return false;
        }

        $counter = intdiv(time(), 30);

        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals(self::codeForCounter($secret, $counter + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    private static function codeForCounter(string $secret, int $counter): string
    {
        $key = self::base32Decode($secret);

        if ($key === '') {
            return '';
        }

        $hash = hash_hmac('sha1', pack('N*', 0, $counter), $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($binary % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $secret) ?? '');
        $bits = 0;
        $value = 0;
        $output = '';

        foreach (str_split($secret) as $char) {
            $index = strpos(self::ALPHABET, $char);

            if ($index === false) {
                continue;
            }

            $value = ($value << 5) | $index;
            $bits += 5;

            if ($bits >= 8) {
                $output .= chr(($value >> ($bits - 8)) & 0xFF);
                $bits -= 8;
            }
        }

        return $output;
    }
}
