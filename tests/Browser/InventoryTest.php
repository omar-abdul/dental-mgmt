<?php

use App\Enums\InventoryCategory;
use App\Enums\InventoryMovementType;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;

test('nurse can add an inventory item from the dialog and see stock badges', function () {
    $nurse = User::factory()->nurse()->create();

    $this->actingAs($nurse);

    $page = visit(route('inventory.index'));

    $page->assertSee('Inventory')
        ->assertSee('Total items')
        ->assertSee('No inventory items found.')
        ->click('@add-inventory-button')
        ->assertSee('Add inventory item')
        ->fill('name', 'Nitrile gloves')
        ->select('category', InventoryCategory::Ppe->value)
        ->fill('quantity', '12')
        ->fill('unit', 'box')
        ->fill('reorder_level', '4')
        ->fill('unit_cost', '8.50')
        ->fill('expiry_date', now()->addMonths(6)->format('Y-m-d'))
        ->click('@create-inventory-button')
        ->assertSee('Nitrile gloves')
        ->assertSee('PPE')
        ->assertSee('In stock')
        ->assertNoJavaScriptErrors();

    $item = InventoryItem::query()->first();

    expect($item)->not->toBeNull()
        ->and($item->name)->toBe('Nitrile gloves')
        ->and($item->category)->toBe(InventoryCategory::Ppe)
        ->and($item->quantity)->toBe(12)
        ->and($item->unit_cost_cents)->toBe(850);

    expect(InventoryMovement::query()->count())->toBe(1);
});

test('inventory search filters the table', function () {
    $nurse = User::factory()->nurse()->create();
    InventoryItem::factory()->create(['name' => 'Composite resin']);
    InventoryItem::factory()->create(['name' => 'Face masks']);

    $this->actingAs($nurse);

    $page = visit(route('inventory.index'));

    $page->fill('@inventory-search-input', 'Composite')
        ->click('@inventory-search-button')
        ->assertSee('Composite resin')
        ->assertDontSee('Face masks')
        ->assertNoJavaScriptErrors();
});

test('admin receives a purchase order and sees quantity increase in ui and database', function () {
    $admin = User::factory()->admin()->create();
    $supplier = Supplier::factory()->create(['name' => 'Dental Supply Co']);
    $item = InventoryItem::factory()->create([
        'name' => 'Surgical masks',
        'quantity' => 5,
    ]);
    $purchaseOrder = PurchaseOrder::factory()->create([
        'number' => 'PO-2026-00099',
        'supplier_id' => $supplier->id,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 20,
        'unit_cost_cents' => 750,
        'batch_number' => 'MASK-BATCH-1',
        'expiry_date' => now()->addYear()->toDateString(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $this->actingAs($admin);

    $page = visit(route('inventory.purchase-orders.show', $purchaseOrder));

    $page->assertSee('PO-2026-00099')
        ->assertSee('Surgical masks')
        ->click('@receive-purchase-order-button')
        ->assertSee('Received')
        ->assertNoJavaScriptErrors();

    expect($item->fresh()->quantity)->toBe(25);

    $this->assertDatabaseHas('inventory_batches', [
        'inventory_item_id' => $item->id,
        'batch_number' => 'MASK-BATCH-1',
        'quantity' => 20,
    ]);

    $page = visit(route('inventory.index'));

    $page->assertSee('Surgical masks')
        ->assertSee('25')
        ->assertNoJavaScriptErrors();
});

test('admin cannot consume expired batch stock from adjust dialog', function () {
    $admin = User::factory()->admin()->create();
    $item = InventoryItem::factory()->create([
        'name' => 'Expired composite',
        'quantity' => 8,
    ]);
    $batch = InventoryBatch::factory()->expired()->create([
        'inventory_item_id' => $item->id,
        'batch_number' => 'EXP-BATCH-1',
        'quantity' => 8,
    ]);

    $this->actingAs($admin);

    $page = visit(route('inventory.index'));

    $page->assertSee('Expired composite')
        ->click('@adjust-inventory-button')
        ->select('type', InventoryMovementType::Consumption->value)
        ->select('inventory_batch_id', (string) $batch->id)
        ->fill('quantity', '2')
        ->click('@save-adjustment-button')
        ->assertSee('Cannot consume stock from an expired batch')
        ->assertNoJavaScriptErrors();

    expect($item->fresh()->quantity)->toBe(8);
    expect($batch->fresh()->quantity)->toBe(8);
    expect(InventoryMovement::query()->count())->toBe(0);
});
