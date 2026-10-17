<?php

declare(strict_types=1);

namespace App\Authorization\ValueObjects;

use App\Authorization\Registry\PermissionRegistry;

final readonly class PermissionName
{
    private function __construct(public string $value) {}

    public static function from(string $name): self
    {
        PermissionRegistry::validate($name);

        return new self($name);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
