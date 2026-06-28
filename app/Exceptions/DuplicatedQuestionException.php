<?php

namespace App\Exceptions;

use App\Models\Soal;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class DuplicatedQuestionException extends Exception
{
    public function __construct(
        public readonly Soal $existingSoal,
        public readonly string $existingBankName,
        string $message = ''
    ) {
        parent::__construct(
            $message ?: sprintf(
                'Soal ini identik dengan soal yang sudah ada di bank "%s". Silakan edit atau gunakan soal yang sudah ada.',
                $existingBankName
            ),
            Response::HTTP_CONFLICT
        );
    }

    public static function for(Soal $existing): self
    {
        return new self(
            existingSoal: $existing,
            existingBankName: $existing->bankSoal?->nama ?? '(tanpa nama bank)',
        );
    }
}
