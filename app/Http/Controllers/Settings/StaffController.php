<?php

namespace App\Http\Controllers\Settings;

use App\Enums\ClinicRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreStaffRequest;
use App\Http\Requests\Settings\UpdateStaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    /**
     * @return list<array<string, mixed>>
     */
    private function staffList(): array
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'is_active'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'is_active' => $user->is_active,
            ])
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return collect(ClinicRole::cases())->map(fn (ClinicRole $role) => [
            'value' => $role->value,
            'label' => $role->label(),
        ])->values()->all();
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewStaff');

        return Inertia::render('settings/Staff', [
            'staff' => $this->staffList(),
            'roles' => $this->roleOptions(),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('createStaff');

        return Inertia::render('settings/Staff', [
            'staff' => $this->staffList(),
            'roles' => $this->roleOptions(),
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
                'is_active' => true,
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

    public function update(UpdateStaffRequest $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user) && ! $request->boolean('is_active')) {
            throw ValidationException::withMessages([
                'is_active' => __('You cannot deactivate your own account.'),
            ]);
        }

        DB::transaction(function () use ($request, $user): void {
            $isActive = $request->boolean('is_active');

            $user->update(['is_active' => $isActive]);

            if ($user->role === ClinicRole::Dentist && $user->dentist !== null) {
                $user->dentist->update(['is_active' => $isActive]);
            }
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $request->boolean('is_active')
                ? __('Staff member activated.')
                : __('Staff member deactivated.'),
        ]);

        return to_route('staff.index');
    }
}
