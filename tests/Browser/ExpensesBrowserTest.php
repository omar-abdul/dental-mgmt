<?php

use App\Models\DailyCashClosing;
use App\Models\Expense;
use App\Models\User;

test('accountant can record an expense and daily cash close from the UI', function () {
    $accountant = User::factory()->accountant()->create();

    $this->actingAs($accountant);

    $page = visit(route('expenses.index'));

    $page->assertSee('Expenses')
        ->assertSee('No expenses recorded yet.')
        ->click('@toggle-expense-form-button')
        ->assertSee('Record expense')
        ->fill('@expense-description-input', 'Stationery purchase')
        ->fill('@expense-amount-input', '25.00')
        ->click('@submit-expense-button')
        ->assertSee('Stationery purchase')
        ->assertSee('$25.00')
        ->click('@toggle-cash-close-form-button')
        ->assertSee('Daily cash close')
        ->fill('@cash-close-counted-input', '0.00')
        ->click('@submit-cash-close-button')
        ->assertSee("Today's cash close")
        ->assertNoJavaScriptErrors();

    $expense = Expense::query()->first();
    $closing = DailyCashClosing::query()->first();

    expect($expense)->not->toBeNull()
        ->and($expense->description)->toBe('Stationery purchase')
        ->and($expense->amount_cents)->toBe(2500)
        ->and($expense->recorded_by)->toBe($accountant->id)
        ->and($closing)->not->toBeNull()
        ->and($closing->counted_cash_cents)->toBe(0)
        ->and($closing->closed_by)->toBe($accountant->id);
});

test('dentist cannot access expenses module in sidebar or URL', function () {
    $dentist = User::factory()->dentist()->create();

    $this->actingAs($dentist);

    visit(route('dashboard'))
        ->assertSee('Billing')
        ->assertDontSee('Expenses')
        ->assertNoJavaScriptErrors();

    $this->get(route('expenses.index'))
        ->assertForbidden();
});
