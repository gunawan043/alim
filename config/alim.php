<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Batas maksimal mahrom per santri
    |--------------------------------------------------------------------------
    | Dipakai controller & view (config('alim.max_mahrom')).
    */
    'max_mahrom' => (int) env('ALIM_MAX_MAHROM', 4),
];
