<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardMetrics;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardMetrics $metrics): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Dashboard', $metrics->forUser($user));
    }
}
