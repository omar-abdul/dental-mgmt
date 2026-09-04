<?php

use App\Enums\ClinicRole;
use App\Enums\InventoryCategory;
use App\Enums\InventoryMovementType;
use App\Enums\InventoryStockStatus;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
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
        'expiry_date' => now()->addMonths(6)->toDateString(),
    ], $overrides);
}

function assertItemBatchQuantityInvariant(InventoryItem $item): void
{
    $item->refresh();

    $batchSum = (int) InventoryBatch::query()
        ->where('inventory_item_id', $item->id)
        ->sum('quantity');

    expect($item->quantity)->toBe($batchSum);
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

    $this->assertDatabaseHas('inventory_batches', [
        'inventory_item_id' => $item->id,
        'quantity' => 10,
    ]);

    assertItemBatchQuantityInvariant($item);
});

test('admin stock adjustment in updates quantity and creates movement', function () {
    $admin = User::factory()->admin()->create();
    $item = InventoryItem::factory()->create([
        'quantity' => 5,
        'reorder_level' => 3,
    ]);
    InventoryBatch::factory()->create([
        'inventory_item_id' => $item->id,
        'quantity' => 5,
    ]);

    $this->actingAs($admin)
        ->post(route('inventory.adjust', $item), [
            'type' => InventoryMovementType::AdjustmentIn->value,
            'quantity' => 4,
            'expiry_date' => now()->addMonths(3)->toDateString(),
            'reason' => 'Restock',
        ])
        ->assertRedirect(route('inventory.index'));

    $item->refresh();

    expect($item->quantity)->toBe(9);

    $this->assertDatabaseHas('inventory_movements', [
        'inventory_item_id' => $item->id,
        'delta' => 4,
        'type' => InventoryMovementType::AdjustmentIn->value,
        'user_id' => $admin->id,
        'reason' => 'Restock',
    ]);

    assertItemBatchQuantityInvariant($item);
});

test('nurse cannot adjust stock without admin authorization', function () {
    $nurse = User::factory()->nurse()->create();
    $item = InventoryItem::factory()->create([
        'quantity' => 5,
    ]);

    $this->actingAs($nurse)
        ->post(route('inventory.adjust', $item), [
            'type' => InventoryMovementType::AdjustmentIn->value,
            'quantity' => 4,
        ])
        ->assertForbidden();

    expect($item->fresh()->quantity)->toBe(5);
});

test('admin stock adjustment out updates quantity and creates negative delta movement', function () {
    $admin = User::factory()->admin()->create();
    $item = InventoryItem::factory()->create([
        'quantity' => 8,
    ]);
    InventoryBatch::factory()->create([
        'inventory_item_id' => $item->id,
        'quantity' => 8,
        'expiry_date' => now()->addMonths(6)->toDateString(),
    ]);

    $this->actingAs($admin)
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
        'user_id' => $admin->id,
    ]);

    assertItemBatchQuantityInvariant($item);
});

test('receptionist cannot adjust stock without admin authorization', function () {
    $receptionist = User::factory()->receptionist()->create();
    $item = InventoryItem::factory()->create([
        'quantity' => 8,
    ]);

    $this->actingAs($receptionist)
        ->post(route('inventory.adjust', $item), [
            'type' => InventoryMovementType::AdjustmentOut->value,
            'quantity' => 3,
        ])
        ->assertForbidden();

    expect($item->fresh()->quantity)->toBe(8);
});

test('adjustment that would make quantity negative returns 422', function () {
    $admin = User::factory()->admin()->create();
    $item = InventoryItem::factory()->create([
        'quantity' => 2,
    ]);
    $batch = InventoryBatch::factory()->create([
        'inventory_item_id' => $item->id,
        'quantity' => 2,
    ]);

    $this->actingAs($admin)
        ->from(route('inventory.index'))
        ->post(route('inventory.adjust', $item), [
            'type' => InventoryMovementType::Consumption->value,
            'quantity' => 5,
            'inventory_batch_id' => $batch->id,
        ])
        ->assertSessionHasErrors('quantity');

    expect($item->fresh()->quantity)->toBe(2);
    expect($batch->fresh()->quantity)->toBe(2);
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
            ->where('stats.expiring_soon', 0)
            ->where('items.data.0.stock_status', fn ($status) => in_array($status, ['out', 'low', 'in_stock'], true)));
});

test('consuming expired batch stock returns 422 and leaves quantities unchanged', function () {
    $admin = User::factory()->admin()->create();
    $item = InventoryItem::factory()->create([
        'quantity' => 10,
    ]);
    $batch = InventoryBatch::factory()->expired()->create([
        'inventory_item_id' => $item->id,
        'quantity' => 10,
    ]);

    $this->actingAs($admin)
        ->from(route('inventory.index'))
        ->post(route('inventory.adjust', $item), [
            'type' => InventoryMovementType::Consumption->value,
            'quantity' => 2,
            'inventory_batch_id' => $batch->id,
        ])
        ->assertSessionHasErrors('inventory_batch_id');

    expect($item->fresh()->quantity)->toBe(10);
    expect($batch->fresh()->quantity)->toBe(10);
    expect(InventoryMovement::query()->where('inventory_item_id', $item->id)->count())->toBe(0);

    assertItemBatchQuantityInvariant($item);
});

test('batch expiring today is still consumable through the clinic day', function () {
    $admin = User::factory()->admin()->create();
    $item = InventoryItem::factory()->create([
        'quantity' => 6,
    ]);
    $batch = InventoryBatch::factory()->create([
        'inventory_item_id' => $item->id,
        'quantity' => 6,
        'expiry_date' => now()->toDateString(),
    ]);

    expect($batch->isExpired())->toBeFalse();

    $this->actingAs($admin)
        ->post(route('inventory.adjust', $item), [
            'type' => InventoryMovementType::Consumption->value,
            'quantity' => 2,
            'inventory_batch_id' => $batch->id,
        ])
        ->assertRedirect(route('inventory.index'));

    expect($item->fresh()->quantity)->toBe(4);
    expect($batch->fresh()->quantity)->toBe(4);

    assertItemBatchQuantityInvariant($item);
});

test('receiving a purchase order increases item quantity and creates purchase movement', function () {
    $nurse = User::factory()->nurse()->create();
    $item = InventoryItem::factory()->create([
        'quantity' => 0,
        'unit_cost_cents' => 500,
    ]);
    $purchaseOrder = PurchaseOrder::factory()->create([
        'created_by' => $nurse->id,
        'updated_by' => $nurse->id,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 12,
        'unit_cost_cents' => 900,
        'batch_number' => 'BATCH-PO-001',
        'expiry_date' => now()->addMonths(6)->toDateString(),
        'created_by' => $nurse->id,
        'updated_by' => $nurse->id,
    ]);

    $this->actingAs($nurse)
        ->post(route('inventory.purchase-orders.receive', $purchaseOrder))
        ->assertRedirect(route('inventory.purchase-orders.show', $purchaseOrder));

    $item->refresh();
    $purchaseOrder->refresh();

    expect($item->quantity)->toBe(12);
    expect($item->unit_cost_cents)->toBe(900);
    expect($purchaseOrder->status->value)->toBe('received');
    expect($purchaseOrder->received_at)->not->toBeNull();

    $this->assertDatabaseHas('inventory_batches', [
        'inventory_item_id' => $item->id,
        'batch_number' => 'BATCH-PO-001',
        'quantity' => 12,
    ]);

    $this->assertDatabaseHas('inventory_movements', [
        'inventory_item_id' => $item->id,
        'purchase_order_id' => $purchaseOrder->id,
        'delta' => 12,
        'type' => InventoryMovementType::Purchase->value,
        'user_id' => $nurse->id,
    ]);

    assertItemBatchQuantityInvariant($item);
});

test('receiving an already received purchase order returns 403 and leaves quantities unchanged', function () {
    $nurse = User::factory()->nurse()->create();
    $item = InventoryItem::factory()->create([
        'quantity' => 12,
    ]);
    $purchaseOrder = PurchaseOrder::factory()->received()->create([
        'created_by' => $nurse->id,
        'updated_by' => $nurse->id,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 12,
        'quantity_received' => 12,
        'expiry_date' => now()->addMonths(6)->toDateString(),
        'created_by' => $nurse->id,
        'updated_by' => $nurse->id,
    ]);
    InventoryBatch::factory()->create([
        'inventory_item_id' => $item->id,
        'quantity' => 12,
    ]);

    $this->actingAs($nurse)
        ->post(route('inventory.purchase-orders.receive', $purchaseOrder))
        ->assertForbidden();

    expect($item->fresh()->quantity)->toBe(12);
    assertItemBatchQuantityInvariant($item);
});

test('receiving a purchase order without line expiry is rejected', function () {
    $nurse = User::factory()->nurse()->create();
    $item = InventoryItem::factory()->create([
        'quantity' => 3,
    ]);
    $purchaseOrder = PurchaseOrder::factory()->create([
        'created_by' => $nurse->id,
        'updated_by' => $nurse->id,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 12,
        'expiry_date' => null,
        'created_by' => $nurse->id,
        'updated_by' => $nurse->id,
    ]);

    $this->actingAs($nurse)
        ->from(route('inventory.purchase-orders.show', $purchaseOrder))
        ->post(route('inventory.purchase-orders.receive', $purchaseOrder))
        ->assertSessionHasErrors('purchase_order');

    expect($item->fresh()->quantity)->toBe(3);
    expect($purchaseOrder->fresh()->status->value)->toBe('pending');
});

test('item quantity matches batch totals after receive and consumption', function () {
    $admin = User::factory()->admin()->create();
    $item = InventoryItem::factory()->create([
        'quantity' => 0,
    ]);
    $purchaseOrder = PurchaseOrder::factory()->create([
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 20,
        'batch_number' => 'INV-MIX-001',
        'expiry_date' => now()->addMonths(6)->toDateString(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->post(route('inventory.purchase-orders.receive', $purchaseOrder))
        ->assertRedirect(route('inventory.purchase-orders.show', $purchaseOrder));

    assertItemBatchQuantityInvariant($item);

    $batch = InventoryBatch::query()
        ->where('inventory_item_id', $item->id)
        ->where('batch_number', 'INV-MIX-001')
        ->firstOrFail();

    $this->actingAs($admin)
        ->post(route('inventory.adjust', $item), [
            'type' => InventoryMovementType::Consumption->value,
            'quantity' => 5,
            'inventory_batch_id' => $batch->id,
        ])
        ->assertRedirect(route('inventory.index'));

    assertItemBatchQuantityInvariant($item);
    expect($item->fresh()->quantity)->toBe(15);
});

test('inventory index exposes expiry alerts for batches nearing expiry', function () {
    $admin = User::factory()->admin()->create();
    $item = InventoryItem::factory()->create(['name' => 'Local anesthetic']);

    InventoryBatch::factory()->expiringSoon()->create([
        'inventory_item_id' => $item->id,
        'batch_number' => 'BATCH-ALERT',
        'quantity' => 4,
    ]);

    $this->actingAs($admin)
        ->get(route('inventory.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('inventory/Index')
            ->where('stats.expiring_soon', 1)
            ->has('expiryAlerts', 1)
            ->where('expiryAlerts.0.item_name', 'Local anesthetic')
            ->where('expiryAlerts.0.batch_number', 'BATCH-ALERT'));
});
