<?php

namespace App\Domain\Types;

final class DecisionOutcome
{
    public const ALLOW = 'allow';

    public const DENY = 'deny';

    public const REQUIRE_APPROVAL = 'require_approval';

    public const REQUIRE_OVERRIDE = 'require_override';
}
