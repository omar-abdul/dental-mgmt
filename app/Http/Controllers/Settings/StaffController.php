<?php

namespace App\Http\Controllers\Settings;

use App\Enums\ClinicRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreStaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewStaff');

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
        Gate::authorize('createStaff');

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
        $role = $request->enum('role', ClinicRole::class);
        $actorId = $request->user()?->id;

        DB::transaction(function () use ($request, $role, $actorId): void {
            $staff = User::query()->create([
                'name' => $request->string('name')->value(),
                'email' => $request->string('email')->value(),
                'role' => $role,
                'password' => $request->string('password')->value(),
                'email_verified_at' => now(),
            ]);

            if ($role === ClinicRole::Dentist) {
                $staff->dentist()->create([
                    'display_name' => $staff->name,
                    'is_active' => true,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Staff member created.')]);

        return to_route('staff.index');
    }
}
