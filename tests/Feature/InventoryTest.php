<?php

use App\Enums\ClinicRole;
use App\Enums\InventoryCategory;
use App\Enums\InventoryMovementType;
use App\Enums\InventoryStockStatus;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\User;

function validInventoryItemPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Composite Resin A2',
        'category' => InventoryCategory::DentalMaterials->value,
        'quantity' => 10,
        'unit' => 'box',
        'reorder_level' => 5,
        'unit_cost' => '12.50',
    ], $overrides);
}

test('inventory item stock status is derived from quantity and reorder level', function () {
    $outItem = InventoryItem::factory()->create([
        'quantity' => 0,
        'reorder_level' => 5,
    ]);

    $lowItem = InventoryItem::factory()->create([
        'quantity' => 3,
        'reorder_level' => 5,
    ]);

    $inStockItem = InventoryItem::factory()->create([
        'quantity' => 20,
        'reorder_level' => 5,
    ]);

    expect($outItem->stockStatus())->toBe(InventoryStockStatus::Out);
    expect($lowItem->stockStatus())->toBe(InventoryStockStatus::Low);
    expect($inStockItem->stockStatus())->toBe(InventoryStockStatus::InStock);
});

test('nurse can create an inventory item and initial stock movement is recorded', function () {
    $nurse = User::factory()->nurse()->create();

    $this->actingAs($nurse)
        ->post(route('inventory.store'), validInventoryItemPayload())
        ->assertRedirect(route('inventory.index'));

    $item = InventoryItem::query()->where('name', 'Composite Resin A2')->first();

    expect($item)->not->toBeNull();
    expect($item->quantity)->toBe(10);
    expect($item->unit_cost_cents)->toBe(1250);
    expect($item->created_by)->toBe($nurse->id);

    $this->assertDatabaseHas('inventory_movements', [
        'inventory_item_id' => $item->id,
        'delta' => 10,
        'type' => InventoryMovementType::AdjustmentIn->value,
        'user_id' => $nurse->id,
    ]);
});

test('stock adjustment in updates quantity and creates movement', function () {
    $nurse = User::factory()->nurse()->create();
    $item = InventoryItem::factory()->create([
        'quantity' => 5,
        'reorder_level' => 3,
    ]);

    $this->actingAs($nurse)
        ->post(route('inventory.adjust', $item), [
            'type' => InventoryMovementType::AdjustmentIn->value,
            'quantity' => 4,
            'reason' => 'Restock',
        ])
        ->assertRedirect(route('inventory.index'));

    $item->refresh();

    expect($item->quantity)->toBe(9);

    $this->assertDatabaseHas('inventory_movements', [
        'inventory_item_id' => $item->id,
        'delta' => 4,
        'type' => InventoryMovementType::AdjustmentIn->value,
        'user_id' => $nurse->id,
        'reason' => 'Restock',
    ]);
});

test('stock adjustment out updates quantity and creates negative delta movement', function () {
    $receptionist = User::factory()->receptionist()->create();
    $item = InventoryItem::factory()->create([
        'quantity' => 8,
    ]);

    $this->actingAs($receptionist)
        ->post(route('inventory.adjust', $item), [
            'type' => InventoryMovementType::AdjustmentOut->value,
            'quantity' => 3,
        ])
        ->assertRedirect(route('inventory.index'));

    $item->refresh();

    expect($item->quantity)->toBe(5);

    $this->assertDatabaseHas('inventory_movements', [
        'inventory_item_id' => $item->id,
        'delta' => -3,
        'type' => InventoryMovementType::AdjustmentOut->value,
        'user_id' => $receptionist->id,
    ]);
});

test('adjustment that would make quantity negative returns 422', function () {
    $nurse = User::factory()->nurse()->create();
    $item = InventoryItem::factory()->create([
        'quantity' => 2,
    ]);

    $this->actingAs($nurse)
        ->from(route('inventory.index'))
        ->post(route('inventory.adjust', $item), [
            'type' => InventoryMovementType::Consumption->value,
            'quantity' => 5,
        ])
        ->assertSessionHasErrors('quantity');

    expect($item->fresh()->quantity)->toBe(2);
    expect(InventoryMovement::query()->where('inventory_item_id', $item->id)->count())->toBe(0);
});

test('inventory index search matches item name', function () {
    $receptionist = User::factory()->receptionist()->create();

    $item = InventoryItem::factory()->create([
        'name' => 'Surgical Gloves Medium',
    ]);

    InventoryItem::factory()->create([
        'name' => 'Face Mask Box',
    ]);

    $this->actingAs($receptionist)
        ->get(route('inventory.index', ['search' => 'Surgical Gloves']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('inventory/Index')
            ->has('items.data', 1)
            ->where('items.data.0.id', $item->id));
});

test('accountant and lab cannot view inventory index', function (ClinicRole $role) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)
        ->get(route('inventory.index'))
        ->assertForbidden();
})->with([
    'accountant' => ClinicRole::Accountant,
    'lab' => ClinicRole::Lab,
]);

test('dentist can view inventory but cannot create or adjust', function () {
    $dentist = User::factory()->dentist()->create();
    $item = InventoryItem::factory()->create();

    $this->actingAs($dentist)
        ->get(route('inventory.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('inventory/Index')
            ->where('canCreate', false)
            ->where('canAdjust', false));

    $this->actingAs($dentist)
        ->post(route('inventory.store'), validInventoryItemPayload([
            'name' => 'Dentist Blocked Item',
        ]))
        ->assertForbidden();

    $this->actingAs($dentist)
        ->post(route('inventory.adjust', $item), [
            'type' => InventoryMovementType::AdjustmentIn->value,
            'quantity' => 1,
        ])
        ->assertForbidden();
});

test('guest is redirected to login when visiting inventory index', function () {
    $this->get(route('inventory.index'))
        ->assertRedirect(route('login'));
});

test('inventory index exposes summary cards and derived stock badges', function () {
    $admin = User::factory()->admin()->create();

    InventoryItem::factory()->create([
        'name' => 'Out Item',
        'quantity' => 0,
        'reorder_level' => 5,
        'unit_cost_cents' => 1000,
    ]);

    InventoryItem::factory()->create([
        'name' => 'Low Item',
        'quantity' => 3,
        'reorder_level' => 5,
        'unit_cost_cents' => 2000,
    ]);

    InventoryItem::factory()->create([
        'name' => 'In Stock Item',
        'quantity' => 10,
        'reorder_level' => 5,
        'unit_cost_cents' => 500,
    ]);

    $this->actingAs($admin)
        ->get(route('inventory.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('inventory/Index')
            ->where('stats.total_items', 3)
            ->where('stats.low_stock', 1)
            ->where('stats.out_of_stock', 1)
            ->where('stats.stock_value_cents', 11000)
            ->where('stats.stock_value_formatted', '$110.00')
            ->where('items.data.0.stock_status', fn ($status) => in_array($status, ['out', 'low', 'in_stock'], true)));
});
