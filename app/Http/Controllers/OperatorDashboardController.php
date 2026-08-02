<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OperatorDashboardController extends Controller
{
    public function index(Request $request)
    {
        // Delegate to WakaController dashboard logic
        $wakaController = new \App\Http\Controllers\WakaController;

        return $wakaController->dashboard($request);
    }
}
