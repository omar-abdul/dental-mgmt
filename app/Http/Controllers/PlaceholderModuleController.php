<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlaceholderModuleController extends Controller
{
    public function patients(Request $request): Response
    {
        return $this->render($request, 'patients', 'Patients');
    }

    public function appointments(Request $request): Response
    {
        return $this->render($request, 'appointments', 'Appointments');
    }

    public function treatments(Request $request): Response
    {
        return $this->render($request, 'treatments', 'Treatments');
    }

    public function billing(Request $request): Response
    {
        return $this->render($request, 'billing', 'Billing');
    }

    public function inventory(Request $request): Response
    {
        return $this->render($request, 'inventory', 'Inventory');
    }

    private function render(Request $request, string $module, string $title): Response
    {
        $user = $request->user();

        abort_unless($user && $user->role->canViewModule($module), 403);

        return Inertia::render('modules/Placeholder', [
            'title' => $title,
            'module' => $module,
        ]);
    }
}
