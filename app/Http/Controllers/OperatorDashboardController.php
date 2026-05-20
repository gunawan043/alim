<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\AcademicYear;
use App\Models\StudentClassHistory;
use App\Models\School;
use App\Models\User;
use App\Models\StudentMutationIn;
use App\Models\StudentMutationOut;
use App\Models\StudentAchievement;
use App\Models\Violation;
use Illuminate\Http\Request;

class OperatorDashboardController extends Controller
{
    public function index(Request $request)
    {
        // Delegate to WakaController dashboard logic
        $wakaController = new \App\Http\Controllers\WakaController();
        return $wakaController->dashboard($request);
    }
}