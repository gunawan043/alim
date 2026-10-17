<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\SidebarAccess;
use Illuminate\View\View;

class SidebarAccessComposer
{
    public function compose(View $view): void
    {
        $view->with('sidebarAccesses', SidebarAccess::listAll());
    }
}
