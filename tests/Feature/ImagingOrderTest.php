<?php

use App\Enums\ClinicRole;
use App\Enums\ImagingOrderStatus;
use App\Enums\ImagingOrderType;
use App\Models\Dentist;
use App\Models\ImageFile;
use App\Models\ImagingOrder;
use App\Models\ImagingResult;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-09-04 10:00:00', 'Africa/Mogadishu'));
    Storage::fake('local');
});

afterEach(function () {
    Carbon::setTestNow();
});

function validImagingOrderPayload(array $overrides = []): array
{
    $patient = Patient::factory()->create();
    $dentist = Dentist::factory()->create();

    return array_merge([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'encounter_id' => null,
        'type' => ImagingOrderType::Bitewing->value,
        'notes' => 'Lower left quadrant',
        'status' => ImagingOrderStatus::Ordered->value,
    ], $overrides);
}

test('authorized roles can create imaging orders with IMG number format', function (ClinicRole $role) {
    $user = User::factory()->role($role)->create();
    $payload = validImagingOrderPayload();

    $this->actingAs($user)
        ->post(route('imaging.store'), $payload)
        ->assertRedirect();

    $order = ImagingOrder::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->number)->toMatch('/^IMG-\d{4}-\d{5}$/')
        ->and($order->type)->toBe(ImagingOrderType::Bitewing)
        ->and($order->status)->toBe(ImagingOrderStatus::Ordered)
        ->and($order->notes)->toBe('Lower left quadrant')
        ->and($order->created_by)->toBe($user->id);
})->with([
    'admin' => ClinicRole::Admin,
    'dentist' => ClinicRole::Dentist,
]);

test('dentist can create imaging order with optional file and result metadata', function () {
    $dentistUser = User::factory()->dentist()->create();
    $payload = validImagingOrderPayload([
        'result_findings' => 'No periapical pathology.',
        'result_impression' => 'Within normal limits.',
    ]);

    $this->actingAs($dentistUser)
        ->post(route('imaging.store'), [
            ...$payload,
            'file' => UploadedFile::fake()->create('bitewing.jpg', 50, 'image/jpeg'),
        ])
        ->assertRedirect();

    $order = ImagingOrder::query()->with(['result', 'files'])->first();

    expect($order)->not->toBeNull()
        ->and($order->result)->not->toBeNull()
        ->and($order->result->findings)->toBe('No periapical pathology.')
        ->and($order->result->impression)->toBe('Within normal limits.')
        ->and($order->files)->toHaveCount(1);

    $file = $order->files->first();

    expect($file->original_name)->toBe('bitewing.jpg')
        ->and($file->mime_type)->toBe('image/jpeg')
        ->and(Storage::disk('local')->exists($file->path))->toBeTrue();
});

test('receptionist cannot access imaging write routes', function () {
    $receptionist = User::factory()->receptionist()->create();
    $order = ImagingOrder::factory()->create();

    $this->actingAs($receptionist)
        ->get(route('imaging.index'))
        ->assertForbidden();

    $this->actingAs($receptionist)
        ->get(route('imaging.create'))
        ->assertForbidden();

    $this->actingAs($receptionist)
        ->post(route('imaging.store'), validImagingOrderPayload())
        ->assertForbidden();

    $this->actingAs($receptionist)
        ->get(route('imaging.show', $order))
        ->assertForbidden();
});

test('nurse can view imaging module but not create orders', function () {
    $nurse = User::factory()->nurse()->create();
    $order = ImagingOrder::factory()->create();

    $this->actingAs($nurse)
        ->get(route('imaging.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('imaging/Index')
            ->where('canCreate', false));

    $this->actingAs($nurse)
        ->get(route('imaging.show', $order))
        ->assertOk();

    $this->actingAs($nurse)
        ->get(route('imaging.create'))
        ->assertForbidden();

    $this->actingAs($nurse)
        ->post(route('imaging.store'), validImagingOrderPayload())
        ->assertForbidden();
});

test('imaging module appears in allowed modules for authorized roles only', function (ClinicRole $role, bool $canView) {
    expect($role->canViewModule('imaging'))->toBe($canView);
})->with([
    'admin' => [ClinicRole::Admin, true],
    'dentist' => [ClinicRole::Dentist, true],
    'nurse' => [ClinicRole::Nurse, true],
    'receptionist' => [ClinicRole::Receptionist, false],
    'accountant' => [ClinicRole::Accountant, false],
    'lab' => [ClinicRole::Lab, false],
]);

test('creating imaging order without result metadata or file skips related records', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('imaging.store'), validImagingOrderPayload())
        ->assertRedirect();

    expect(ImagingOrder::query()->count())->toBe(1)
        ->and(ImagingResult::query()->count())->toBe(0)
        ->and(ImageFile::query()->count())->toBe(0);
});
