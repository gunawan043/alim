<?php

use App\Services\Sarpras\UrtAgendaService;

$schoolId = $this->getUserSchoolId($user);
$agenda = app(UrtAgendaService::class)->build($schoolId);

return [
    '_type' => 'agenda-prioritas',
    '_col'  => 'col-xl-12 col-12',
    'label' => 'Agenda & Prioritas',
    'agenda' => $agenda['agenda'],
    'priorities' => $agenda['priorities'],
    'counts' => $agenda['counts'],
];
