<?php

return [
    // Path lokal/ter-mount untuk salinan offsite (mis. /mnt/nas/alim-backups).
    'offsite_path' => env('BACKUP_OFFSITE_PATH'),
    'keep' => (int) env('BACKUP_KEEP', 14),
];
