<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return view('recruitment.settings.index');
    }

    public function update(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Settings updated']);
    }

    public function updateStages(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Stages updated']);
    }

    public function updateEmailTemplates(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Email templates updated']);
    }
}
