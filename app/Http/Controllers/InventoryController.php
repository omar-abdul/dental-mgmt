<?php

namespace App\Http\Controllers;

use App\Enums\InventoryCategory;
use App\Enums\InventoryMovementType;
use App\Http\Requests\AdjustInventoryItemRequest;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', InventoryItem::class);

        $search = trim((string) $request->query('search', ''));

        $itemsQuery = InventoryItem::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name');

        $allItems = InventoryItem::query()->get(['quantity', 'reorder_level', 'unit_cost_cents']);

        $lowStockCount = $allItems->filter(
            fn (InventoryItem $item): bool => $item->quantity > 0 && $item->quantity <= $item->reorder_level,
        )->count();

        $stockValueCents = $allItems->sum(
            fn (InventoryItem $item): int => $item->quantity * $item->unit_cost_cents,
        );

        $items = $itemsQuery
            ->paginate(15)
            ->withQueryString()
            ->through(fn (InventoryItem $item) => $this->inventoryListItem($item));

        return Inertia::render('inventory/Index', [
            'items' => $items,
            'search' => $search,
            'stats' => [
                'total_items' => $allItems->count(),
                'low_stock' => $lowStockCount,
                'out_of_stock' => $allItems->where('quantity', 0)->count(),
                'stock_value_cents' => $stockValueCents,
                'stock_value_formatted' => $this->formatCents($stockValueCents),
            ],
            'categories' => $this->categoryOptions(),
            'movementTypes' => $this->movementTypeOptions(),
            'canCreate' => $request->user()?->can('create', InventoryItem::class) ?? false,
            'canAdjust' => $request->user()?->can('create', InventoryItem::class) ?? false,
        ]);
    }

    public function store(StoreInventoryItemRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $userId = $request->user()?->id;
            $quantity = $request->integer('quantity');

            $item = InventoryItem::query()->create([
                'name' => $request->string('name')->trim()->value(),
                'category' => $request->enum('category', InventoryCategory::class),
                'quantity' => $quantity,
                'unit' => $request->string('unit')->trim()->value(),
                'reorder_level' => $request->integer('reorder_level'),
                'unit_cost_cents' => $this->dollarsToCents($request->float('unit_cost')),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            if ($quantity > 0) {
                InventoryMovement::query()->create([
                    'inventory_item_id' => $item->id,
                    'delta' => $quantity,
                    'type' => InventoryMovementType::AdjustmentIn,
                    'user_id' => $userId,
                    'reason' => __('Initial stock'),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Inventory item created.')]);

        return to_route('inventory.index');
    }

    public function adjust(AdjustInventoryItemRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        Gate::authorize('adjust', $inventoryItem);

        DB::transaction(function () use ($request, $inventoryItem): void {
            $userId = $request->user()?->id;
            $quantity = $request->integer('quantity');
            $type = $request->enum('type', InventoryMovementType::class);

            $delta = match ($type) {
                InventoryMovementType::AdjustmentIn => $quantity,
                InventoryMovementType::AdjustmentOut,
                InventoryMovementType::Consumption => -$quantity,
            };

            $newQuantity = $inventoryItem->quantity + $delta;

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity' => __('Insufficient stock for this adjustment.'),
                ]);
            }

            $inventoryItem->update([
                'quantity' => $newQuantity,
                'updated_by' => $userId,
            ]);

            InventoryMovement::query()->create([
                'inventory_item_id' => $inventoryItem->id,
                'delta' => $delta,
                'type' => $type,
                'user_id' => $userId,
                'reason' => $request->string('reason')->toString() ?: null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stock adjusted.')]);

        return to_route('inventory.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function inventoryListItem(InventoryItem $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'category' => $item->category->value,
            'category_label' => $item->category->label(),
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'reorder_level' => $item->reorder_level,
            'unit_cost_cents' => $item->unit_cost_cents,
            'unit_cost_formatted' => $this->formatCents($item->unit_cost_cents),
            'stock_status' => $item->stockStatus()->value,
            'stock_status_label' => $item->stockStatus()->label(),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function categoryOptions(): array
    {
        return collect(InventoryCategory::cases())
            ->map(fn (InventoryCategory $category) => [
                'value' => $category->value,
                'label' => $category->label(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function movementTypeOptions(): array
    {
        return collect(InventoryMovementType::cases())
            ->map(fn (InventoryMovementType $type) => [
                'value' => $type->value,
                'label' => match ($type) {
                    InventoryMovementType::AdjustmentIn => 'Stock in',
                    InventoryMovementType::AdjustmentOut => 'Stock out',
                    InventoryMovementType::Consumption => 'Consumption',
                },
            ])
            ->values()
            ->all();
    }

    private function dollarsToCents(float $dollars): int
    {
        return (int) round($dollars * 100);
    }

    private function formatCents(int $cents): string
    {
        return '$'.number_format($cents / 100, 2);
    }
}
