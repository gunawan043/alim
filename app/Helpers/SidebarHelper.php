<?php

if (! function_exists('isActiveAsr')) {
    function isActiveAsr(?string $routeName, string $pattern): bool
    {
        if (! $routeName) {
            return false;
        }

        return str_starts_with($routeName, $pattern);
    }
}
