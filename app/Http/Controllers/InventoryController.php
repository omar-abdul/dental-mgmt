<?php

namespace App\Http\Controllers;

use App\Enums\InventoryCategory;
use App\Enums\InventoryMovementType;
use App\Http\Requests\AdjustInventoryItemRequest;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Services\InventoryStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $expiryAlerts = InventoryBatch::query()
            ->with('inventoryItem')
            ->where('quantity', '>', 0)
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->orderBy('expiry_date')
            ->limit(10)
            ->get()
            ->map(fn (InventoryBatch $batch) => [
                'id' => $batch->id,
                'inventory_item_id' => $batch->inventory_item_id,
                'item_name' => $batch->inventoryItem->name,
                'batch_number' => $batch->batch_number,
                'quantity' => $batch->quantity,
                'expiry_date' => $batch->expiry_date->toDateString(),
                'expiry_date_formatted' => $batch->expiry_date->format('M j, Y'),
                'is_expired' => $batch->isExpired(),
            ])
            ->values()
            ->all();

        $expiringSoonCount = InventoryBatch::query()
            ->where('quantity', '>', 0)
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->count();

        $items = $itemsQuery
            ->with(['batches' => fn ($query) => $query
                ->where('quantity', '>', 0)
                ->whereDate('expiry_date', '>=', today())
                ->orderBy('expiry_date')])
            ->paginate(15)
            ->withQueryString()
            ->through(fn (InventoryItem $item) => $this->inventoryListItem($item, $request));

        return Inertia::render('inventory/Index', [
            'items' => $items,
            'search' => $search,
            'stats' => [
                'total_items' => $allItems->count(),
                'low_stock' => $lowStockCount,
                'out_of_stock' => $allItems->where('quantity', 0)->count(),
                'stock_value_cents' => $stockValueCents,
                'stock_value_formatted' => $this->formatCents($stockValueCents),
                'expiring_soon' => $expiringSoonCount,
            ],
            'expiryAlerts' => $expiryAlerts,
            'categories' => $this->categoryOptions(),
            'movementTypes' => $this->movementTypeOptions(),
            'canCreate' => $request->user()?->can('create', InventoryItem::class) ?? false,
            'canAdjust' => $request->user()?->can('adjustStock', InventoryItem::class) ?? false,
        ]);
    }

    public function store(StoreInventoryItemRequest $request, InventoryStockService $stockService): RedirectResponse
    {
        $user = $request->user();
        $userId = $user?->id;
        $quantity = $request->integer('quantity');

        $item = InventoryItem::query()->create([
            'name' => $request->string('name')->trim()->value(),
            'category' => $request->enum('category', InventoryCategory::class),
            'quantity' => 0,
            'unit' => $request->string('unit')->trim()->value(),
            'reorder_level' => $request->integer('reorder_level'),
            'unit_cost_cents' => $this->dollarsToCents($request->float('unit_cost')),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        if ($quantity > 0) {
            $stockService->adjustStockIn(
                $item,
                $quantity,
                $user,
                Carbon::parse($request->string('expiry_date')->value()),
                $request->string('batch_number')->toString() ?: null,
                __('Initial stock'),
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Inventory item created.')]);

        return to_route('inventory.index');
    }

    public function adjust(
        AdjustInventoryItemRequest $request,
        InventoryItem $inventoryItem,
        InventoryStockService $stockService,
    ): RedirectResponse {
        Gate::authorize('adjust', $inventoryItem);

        $type = $request->enum('type', InventoryMovementType::class);

        if ($type === InventoryMovementType::Consumption) {
            $batch = InventoryBatch::query()->findOrFail($request->integer('inventory_batch_id'));
            $stockService->consumeFromBatch(
                $inventoryItem,
                $batch,
                $request->integer('quantity'),
                $request->user(),
                $request->string('reason')->toString() ?: null,
            );
        } elseif ($type === InventoryMovementType::AdjustmentIn) {
            $stockService->adjustStockIn(
                $inventoryItem,
                $request->integer('quantity'),
                $request->user(),
                Carbon::parse($request->string('expiry_date')->value()),
                $request->string('batch_number')->toString() ?: null,
                $request->string('reason')->toString() ?: null,
            );
        } elseif ($type === InventoryMovementType::AdjustmentOut) {
            $stockService->adjustStockOut(
                $inventoryItem,
                $request->integer('quantity'),
                $request->user(),
                $request->integer('inventory_batch_id') ?: null,
                $request->string('reason')->toString() ?: null,
            );
        } else {
            throw ValidationException::withMessages([
                'type' => __('Invalid adjustment type.'),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stock adjusted.')]);

        return to_route('inventory.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function inventoryListItem(InventoryItem $item, Request $request): array
    {
        $data = [
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

        if ($request->user()?->can('adjust', $item) ?? false) {
            $data['batches'] = $item->batches->map(fn (InventoryBatch $batch) => [
                'id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'quantity' => $batch->quantity,
                'expiry_date' => $batch->expiry_date->toDateString(),
                'expiry_date_formatted' => $batch->expiry_date->format('M j, Y'),
                'is_expired' => $batch->isExpired(),
            ])->values()->all();
        }

        return $data;
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
        return collect([
            InventoryMovementType::AdjustmentIn,
            InventoryMovementType::AdjustmentOut,
            InventoryMovementType::Consumption,
        ])
            ->map(fn (InventoryMovementType $type) => [
                'value' => $type->value,
                'label' => match ($type) {
                    InventoryMovementType::AdjustmentIn => 'Stock in',
                    InventoryMovementType::AdjustmentOut => 'Stock out',
                    InventoryMovementType::Consumption => 'Consumption',
                    InventoryMovementType::Purchase => 'Purchase',
                    InventoryMovementType::Expired => 'Expired',
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
