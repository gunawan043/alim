<?php

use Illuminate\Support\Facades\DB;

$gender = $this->getUksGender($user);

$query = DB::table('uks_beds');
if ($gender !== null) {
    $query->where('gender', $gender);
}

$tersedia = (clone $query)->where('status', 'tersedia')->count();
$terpakai = (clone $query)->where('status', 'dipakai')->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $tersedia,
    'label' => 'Bed Tersedia',
    'icon'  => 'ri-hotel-bed-2-line',
    'color' => $tersedia > 0 ? 'success' : 'danger',
    'sub'   => $terpakai . ' bed sedang dipakai',
];
