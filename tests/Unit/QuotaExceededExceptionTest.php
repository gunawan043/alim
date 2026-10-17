<?php

namespace Tests\Unit;

use App\Domain\Exceptions\QuotaExceededException;
use PHPUnit\Framework\TestCase;

class QuotaExceededExceptionTest extends TestCase
{
    public function test_exception_can_be_constructed(): void
    {
        $exception = new QuotaExceededException('Kuota habis', ['details' => 'test']);
        $this->assertInstanceOf(QuotaExceededException::class, $exception);
        $this->assertSame('Kuota habis', $exception->getMessage());
        $this->assertEquals(['details' => 'test'], $exception->details);
    }

    public function test_exception_has_default_message(): void
    {
        $exception = new QuotaExceededException;
        $this->assertStringContainsString('Kuota', $exception->getMessage());
    }
}
