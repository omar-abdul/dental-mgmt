<?php

use App\Enums\ClinicRole;

test('clinic role enum has expected values', function () {
    expect(ClinicRole::Admin->value)->toBe('admin');
    expect(ClinicRole::Dentist->value)->toBe('dentist');
    expect(ClinicRole::Receptionist->value)->toBe('receptionist');
    expect(ClinicRole::Nurse->value)->toBe('nurse');
    expect(ClinicRole::Accountant->value)->toBe('accountant');
    expect(ClinicRole::Lab->value)->toBe('lab');
});

test('clinic role labels are human readable', function () {
    expect(ClinicRole::Admin->label())->toBe('Admin');
    expect(ClinicRole::Lab->label())->toBe('Lab');
});

test('application uses golden smile branding and clinic timezone', function () {
    expect(config('app.name'))->toContain('Golden Smile');
    expect(config('app.timezone'))->toBe('Africa/Mogadishu');
    expect(config('session.lifetime'))->toBe(30);
});
