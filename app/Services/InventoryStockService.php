<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Enums\PurchaseOrderStatus;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryStockService
{
    public function consumeFromBatch(
        InventoryItem $item,
        InventoryBatch $batch,
        int $quantity,
        User $user,
        ?string $reason = null,
    ): void {
        DB::transaction(function () use ($item, $batch, $quantity, $user, $reason): void {
            $lockedBatch = InventoryBatch::query()
                ->lockForUpdate()
                ->findOrFail($batch->id);

            $this->assertBatchBelongsToItem($item, $lockedBatch);
            $this->assertBatchConsumable($lockedBatch, $quantity);

            $lockedBatch->decrement('quantity', $quantity);
            $lockedBatch->update(['updated_by' => $user->id]);

            $lockedItem = InventoryItem::query()
                ->lockForUpdate()
                ->findOrFail($item->id);

            $lockedItem->decrement('quantity', $quantity);
            $lockedItem->update(['updated_by' => $user->id]);

            InventoryMovement::query()->create([
                'inventory_item_id' => $item->id,
                'inventory_batch_id' => $lockedBatch->id,
                'delta' => -$quantity,
                'type' => InventoryMovementType::Consumption,
                'user_id' => $user->id,
                'reason' => $reason,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });
    }

    public function adjustStockIn(
        InventoryItem $item,
        int $quantity,
        User $user,
        Carbon $expiryDate,
        ?string $batchNumber = null,
        ?string $reason = null,
    ): void {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => __('Quantity must be at least 1.'),
            ]);
        }

        DB::transaction(function () use ($item, $quantity, $user, $expiryDate, $batchNumber, $reason): void {
            $batchNumber = $batchNumber ?: sprintf('INIT-%d', $item->id);

            $batch = InventoryBatch::query()->firstOrCreate(
                [
                    'inventory_item_id' => $item->id,
                    'batch_number' => $batchNumber,
                ],
                [
                    'quantity' => 0,
                    'expiry_date' => $expiryDate->toDateString(),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ],
            );

            if ($batch->wasRecentlyCreated === false) {
                $batch->update([
                    'expiry_date' => $expiryDate->toDateString(),
                    'updated_by' => $user->id,
                ]);
            }

            $batch->increment('quantity', $quantity);
            $batch->update(['updated_by' => $user->id]);

            $lockedItem = InventoryItem::query()
                ->lockForUpdate()
                ->findOrFail($item->id);

            $lockedItem->increment('quantity', $quantity);
            $lockedItem->update(['updated_by' => $user->id]);

            InventoryMovement::query()->create([
                'inventory_item_id' => $item->id,
                'inventory_batch_id' => $batch->id,
                'delta' => $quantity,
                'type' => InventoryMovementType::AdjustmentIn,
                'user_id' => $user->id,
                'reason' => $reason,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });
    }

    public function adjustStockOut(
        InventoryItem $item,
        int $quantity,
        User $user,
        ?int $batchId = null,
        ?string $reason = null,
    ): void {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => __('Quantity must be at least 1.'),
            ]);
        }

        DB::transaction(function () use ($item, $quantity, $user, $batchId, $reason): void {
            $lockedItem = InventoryItem::query()
                ->lockForUpdate()
                ->findOrFail($item->id);

            if ($lockedItem->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => __('Insufficient stock for this adjustment.'),
                ]);
            }

            $remaining = $quantity;

            if ($batchId !== null) {
                $lockedBatch = InventoryBatch::query()
                    ->lockForUpdate()
                    ->findOrFail($batchId);

                $this->assertBatchBelongsToItem($item, $lockedBatch);
                $this->assertBatchConsumable($lockedBatch, $quantity);

                $this->decrementBatchAndRecordMovement(
                    $lockedItem,
                    $lockedBatch,
                    $quantity,
                    $user,
                    InventoryMovementType::AdjustmentOut,
                    $reason,
                );

                $lockedItem->decrement('quantity', $quantity);
                $lockedItem->update(['updated_by' => $user->id]);

                return;
            }

            $batches = InventoryBatch::query()
                ->where('inventory_item_id', $item->id)
                ->where('quantity', '>', 0)
                ->orderBy('expiry_date')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $lockedBatch) {
                if ($remaining <= 0) {
                    break;
                }

                if ($lockedBatch->isExpired()) {
                    continue;
                }

                $take = min($remaining, $lockedBatch->quantity);

                $this->decrementBatchAndRecordMovement(
                    $lockedItem,
                    $lockedBatch,
                    $take,
                    $user,
                    InventoryMovementType::AdjustmentOut,
                    $reason,
                );

                $remaining -= $take;
            }

            if ($remaining > 0) {
                throw ValidationException::withMessages([
                    'quantity' => __('Insufficient non-expired stock for this adjustment.'),
                ]);
            }

            $lockedItem->decrement('quantity', $quantity);
            $lockedItem->update(['updated_by' => $user->id]);
        });
    }

    public function receivePurchaseOrder(PurchaseOrder $purchaseOrder, User $user): void
    {
        DB::transaction(function () use ($purchaseOrder, $user): void {
            $lockedOrder = PurchaseOrder::query()
                ->lockForUpdate()
                ->with(['items.inventoryItem'])
                ->findOrFail($purchaseOrder->id);

            if (! $lockedOrder->status->isReceivable()) {
                throw ValidationException::withMessages([
                    'purchase_order' => __('This purchase order cannot be received.'),
                ]);
            }

            foreach ($lockedOrder->items as $lineItem) {
                if ($lineItem->expiry_date === null) {
                    throw ValidationException::withMessages([
                        'purchase_order' => __('All line items must have an expiry date before receiving.'),
                    ]);
                }

                $this->receiveLineItem($lockedOrder, $lineItem, $user);
            }

            $lockedOrder->update([
                'status' => PurchaseOrderStatus::Received,
                'received_at' => now(),
                'updated_by' => $user->id,
            ]);
        });
    }

    private function receiveLineItem(
        PurchaseOrder $purchaseOrder,
        PurchaseOrderItem $lineItem,
        User $user,
    ): void {
        $quantity = $lineItem->quantity_ordered;
        $item = $lineItem->inventoryItem;

        if ($quantity <= 0) {
            return;
        }

        $batchNumber = $lineItem->batch_number ?: sprintf('PO-%s', $purchaseOrder->number);
        $expiryDate = $lineItem->expiry_date->toDateString();

        $batch = InventoryBatch::query()->firstOrCreate(
            [
                'inventory_item_id' => $item->id,
                'batch_number' => $batchNumber,
            ],
            [
                'quantity' => 0,
                'expiry_date' => $expiryDate,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
        );

        if ($batch->wasRecentlyCreated === false) {
            $batch->update([
                'expiry_date' => $expiryDate,
                'updated_by' => $user->id,
            ]);
        }

        $batch->increment('quantity', $quantity);
        $batch->update(['updated_by' => $user->id]);

        $item->increment('quantity', $quantity);
        $item->update([
            'unit_cost_cents' => $lineItem->unit_cost_cents,
            'updated_by' => $user->id,
        ]);

        $lineItem->update([
            'quantity_received' => $quantity,
            'updated_by' => $user->id,
        ]);

        InventoryMovement::query()->create([
            'inventory_item_id' => $item->id,
            'inventory_batch_id' => $batch->id,
            'purchase_order_id' => $purchaseOrder->id,
            'delta' => $quantity,
            'type' => InventoryMovementType::Purchase,
            'user_id' => $user->id,
            'reason' => __('Received purchase order :number', ['number' => $purchaseOrder->number]),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function decrementBatchAndRecordMovement(
        InventoryItem $item,
        InventoryBatch $batch,
        int $quantity,
        User $user,
        InventoryMovementType $type,
        ?string $reason,
    ): void {
        $batch->decrement('quantity', $quantity);
        $batch->update(['updated_by' => $user->id]);

        InventoryMovement::query()->create([
            'inventory_item_id' => $item->id,
            'inventory_batch_id' => $batch->id,
            'delta' => -$quantity,
            'type' => $type,
            'user_id' => $user->id,
            'reason' => $reason,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function assertBatchBelongsToItem(InventoryItem $item, InventoryBatch $batch): void
    {
        if ($batch->inventory_item_id !== $item->id) {
            throw ValidationException::withMessages([
                'inventory_batch_id' => __('The selected batch does not belong to this item.'),
            ]);
        }
    }

    private function assertBatchConsumable(InventoryBatch $batch, int $quantity): void
    {
        if ($batch->isExpired()) {
            throw ValidationException::withMessages([
                'inventory_batch_id' => __('Cannot consume stock from an expired batch.'),
            ]);
        }

        if ($batch->quantity < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => __('Insufficient stock in the selected batch.'),
            ]);
        }
    }
}
