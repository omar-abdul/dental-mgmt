<?php

namespace App\Http\Controllers;

use App\Models\Encounter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ChartController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Encounter::class);

        $encounters = Encounter::query()
            ->with(['patient', 'dentist', 'soapNote'])
            ->orderByDesc('visited_at')
            ->paginate(15)
            ->through(fn (Encounter $encounter) => [
                'id' => $encounter->id,
                'number' => $encounter->number,
                'visited_at_formatted' => $encounter->visited_at->format('M j, Y g:i A'),
                'patient_name' => "{$encounter->patient->first_name} {$encounter->patient->last_name}",
                'patient_number' => $encounter->patient->patient_number,
                'dentist_name' => $encounter->dentist->display_name,
                'is_signed' => $encounter->soapNote?->isSigned() ?? false,
            ]);

        return Inertia::render('chart/Index', [
            'encounters' => $encounters,
        ]);
    }
}
