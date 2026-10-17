<?php

namespace App\Services\Evaluasi;

class ContentHashEngine
{
    public const DELIMITER = '|SOAL_OPT|';

    public function pertanyaanNormalized(string $pertanyaan): string
    {
        $text = strip_tags($pertanyaan);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text) ?? '';
        $text = mb_strtolower(trim($text));

        return $text;
    }

    public function jawabanNormalized(array $correctOptionTexts): string
    {
        $normalized = array_map(function ($text) {
            $text = strip_tags((string) $text);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace('/\s+/u', ' ', $text) ?? '';
            $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text) ?? '';

            return mb_strtolower(trim($text));
        }, $correctOptionTexts);

        sort($normalized);

        return implode(self::DELIMITER, $normalized);
    }

    public function computeHash(string $pertanyaanNormalized, string $jawabanNormalized): string
    {
        return hash('sha256', $pertanyaanNormalized."\n".$jawabanNormalized);
    }

    public function hashFromSoal(string $pertanyaan, array $correctOptionTexts): string
    {
        return $this->computeHash(
            $this->pertanyaanNormalized($pertanyaan),
            $this->jawabanNormalized($correctOptionTexts)
        );
    }

    public function computeShingles(string $pertanyaanNormalized, int $k = 3): array
    {
        $text = preg_replace('/\s+/u', ' ', trim($pertanyaanNormalized)) ?? '';
        if (mb_strlen($text) === 0) {
            return [];
        }

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $charCount = count($chars);
        if ($charCount < $k) {
            $k = max(1, $charCount);
        }

        $shingles = [];
        for ($i = 0; $i <= $charCount - $k; $i++) {
            $shingle = implode('', array_slice($chars, $i, $k));
            $shingles[] = $shingle;
        }

        $shingles = array_values(array_unique($shingles));
        $hashed = array_map(fn ($s) => hash('sha256', $s), $shingles);
        sort($hashed);

        return $hashed;
    }

    public function shinglesFromSoal(string $pertanyaan, int $k = 3): array
    {
        return $this->computeShingles($this->pertanyaanNormalized($pertanyaan), $k);
    }
}
