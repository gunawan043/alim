<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OperatorDashboardController extends Controller
{
    public function index(Request $request)
    {
        // Delegate to WakaController dashboard logic
        $wakaController = new WakaController;

        return $wakaController->dashboard($request);
    }
}
