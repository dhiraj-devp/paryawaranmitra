<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardSummary;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardSummary $summary): View
    {
        return view('dashboard.index', $summary->forUser($request->user()));
    }
}
