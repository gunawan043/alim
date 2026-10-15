<?php

declare(strict_types=1);

namespace App\Services\Ljk\Omr;

/**
 * Kodek data strip mesin (32 bit).
 *
 * Susunan byte:
 *   byte0, byte1 : nomor urut peserta (16 bit)
 *   byte2        : checksum = byte0 XOR byte1 XOR byte3
 *   byte3        : versi (4 bit atas) + nomor halaman (4 bit bawah)
 *
 * Strip dicetak sebagai 32 sel; sel gelap = bit 1, urutan MSB lebih dulu.
 * Tujuan: identitas lembar + halaman yang dapat dibaca mesin tanpa pustaka
 * QR eksternal dan tanpa mengekspos data sensitif.
 */
final class StripCodec
{
    public const VERSION = 1;

    public const COUNT = 32;

    /**
     * @return array<int, int> bit per sel (0/1)
     */
    public static function encodeBits(int $participantSeq, int $page, int $count = self::COUNT): array
    {
        $seq = $participantSeq & 0xFFFF;
        $bytes = [($seq >> 8) & 0xFF, $seq & 0xFF, 0x00];
        $bytes[3] = ((self::VERSION & 0x0F) << 4) | ($page & 0x0F);
        $bytes[2] = $bytes[0] ^ $bytes[1] ^ $bytes[3];

        $bits = [];
        for ($b = 0; $b < 4; $b++) {
            for ($bit = 7; $bit >= 0; $bit--) {
                $bits[] = ($bytes[$b] >> $bit) & 1;
            }
        }

        $bits = array_slice($bits, 0, $count);

        while (count($bits) < $count) {
            $bits[] = 0;
        }

        return $bits;
    }

    /**
     * @param  array<int, int>  $bits
     * @return array{version:int, participant_seq:int, page:int, checksum_valid:bool}|null
     */
    public static function decodeBits(array $bits): ?array
    {
        if (count($bits) < 32) {
            return null;
        }

        $bytes = [0, 0, 0, 0];
        for ($b = 0; $b < 4; $b++) {
            $value = 0;
            for ($bit = 0; $bit < 8; $bit++) {
                $value = ($value << 1) | ((int) ($bits[$b * 8 + $bit] ?? 0) & 1);
            }
            $bytes[$b] = $value;
        }

        $checksum = $bytes[0] ^ $bytes[1] ^ $bytes[2];
        $allZero = ($bytes[0] | $bytes[1] | $bytes[2] | $bytes[3]) === 0;

        return [
            'version' => ($bytes[3] >> 4) & 0x0F,
            'participant_seq' => (($bytes[0] << 8) | $bytes[1]) & 0xFFFF,
            'page' => $bytes[3] & 0x0F,
            'checksum_valid' => ! $allZero && $checksum === $bytes[3],
        ];
    }
}
