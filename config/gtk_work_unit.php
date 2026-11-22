<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GTK Work Unit Lifecycle
    |--------------------------------------------------------------------------
    |
    | Batch 1 adds the period history table and lifecycle service. The flag is
    | intentionally disabled by default so existing readers and legacy writers
    | keep their current behavior until the staged reader transition is
    | approved and tested.
    |
    */
    'lifecycle' => [
        'enabled' => env('GTK_WORK_UNIT_LIFECYCLE_ENABLED', false),
    ],
];
