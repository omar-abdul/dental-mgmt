<?php

namespace App\Http\Controllers\Settings;

use App\Enums\ClinicRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreStaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewStaff');

        return Inertia::render('settings/Staff', [
            'staff' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'role_label' => $user->role->label(),
                ]),
            'roles' => collect(ClinicRole::cases())->map(fn (ClinicRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ])->values(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('createStaff');

        return Inertia::render('settings/Staff', [
            'staff' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'role_label' => $user->role->label(),
                ]),
            'roles' => collect(ClinicRole::cases())->map(fn (ClinicRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ])->values(),
            'creating' => true,
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        User::query()->create([
            'name' => $request->string('name')->value(),
            'email' => $request->string('email')->value(),
            'role' => $request->enum('role', ClinicRole::class),
            'password' => $request->string('password')->value(),
            'email_verified_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Staff member created.')]);

        return to_route('staff.index');
    }
}
