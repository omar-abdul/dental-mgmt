<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;

test('admin applies a date range on reports hub and sees matching payment total', function () {
    $frozen = Carbon::parse('2026-01-07 09:00:00', 'Africa/Mogadishu');
    $this->travelTo($frozen);

    $admin = User::factory()->admin()->create();
    $invoice = Invoice::factory()->create();

    Payment::factory()->forInvoice($invoice)->create([
        'amount_cents' => 2500,
        'method' => PaymentMethod::Cash,
        'status' => PaymentStatus::Completed,
        'paid_at' => $frozen->copy()->startOfMonth()->addDays(2)->setTime(10, 0),
    ]);

    Payment::factory()->forInvoice($invoice)->create([
        'amount_cents' => 7500,
        'method' => PaymentMethod::Card,
        'status' => PaymentStatus::Completed,
        'paid_at' => $frozen->copy()->subMonth(),
    ]);

    $expectedTotalCents = (int) Payment::query()
        ->where('status', PaymentStatus::Completed)
        ->where('paid_at', '>=', $frozen->copy()->startOfMonth()->startOfDay())
        ->where('paid_at', '<=', $frozen->copy()->endOfDay())
        ->sum('amount_cents');

    expect($expectedTotalCents)->toBe(2500);

    $this->actingAs($admin);

    $page = visit(route('reports.index', [
        'from' => $frozen->copy()->startOfMonth()->toDateString(),
        'to' => $frozen->toDateString(),
    ]));

    $page->assertSee('Reports')
        ->assertSee('$25.00')
        ->assertNoJavaScriptErrors();

    $page->fill('@report-from-input', '2026-01-01')
        ->fill('@report-to-input', '2026-01-07')
        ->click('@report-filter-submit')
        ->assertSee('$25.00')
        ->assertNoJavaScriptErrors();
});
