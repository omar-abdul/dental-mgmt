<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseOrderStatus;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\InventoryStockService;
use App\Services\PurchaseOrderNumberGenerator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', PurchaseOrder::class);

        $search = trim((string) $request->query('search', ''));

        $orders = PurchaseOrder::query()
            ->with('supplier')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (PurchaseOrder $order) => $this->listItem($order));

        return Inertia::render('inventory/purchase-orders/Index', [
            'orders' => $orders,
            'search' => $search,
            'canCreate' => $request->user()?->can('create', PurchaseOrder::class) ?? false,
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', PurchaseOrder::class);

        return Inertia::render('inventory/purchase-orders/Create', [
            'suppliers' => $this->supplierOptions(),
            'inventoryItems' => $this->inventoryItemOptions(),
        ]);
    }

    public function store(
        StorePurchaseOrderRequest $request,
        PurchaseOrderNumberGenerator $numberGenerator,
    ): RedirectResponse {
        $userId = $request->user()?->id;
        $validated = $request->validated();
        $maxAttempts = 3;
        $order = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $order = DB::transaction(function () use ($validated, $numberGenerator, $userId): PurchaseOrder {
                    $purchaseOrder = PurchaseOrder::query()->create([
                        'number' => $numberGenerator->generate(),
                        'supplier_id' => $validated['supplier_id'],
                        'status' => PurchaseOrderStatus::Pending,
                        'notes' => filled($validated['notes'] ?? null) ? $validated['notes'] : null,
                        'ordered_at' => now(),
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);

                    foreach ($validated['items'] as $item) {
                        $purchaseOrder->items()->create([
                            'inventory_item_id' => $item['inventory_item_id'],
                            'quantity_ordered' => $item['quantity_ordered'],
                            'unit_cost_cents' => (int) round((float) $item['unit_cost'] * 100),
                            'batch_number' => filled($item['batch_number'] ?? null) ? $item['batch_number'] : null,
                            'expiry_date' => $item['expiry_date'] ?? null,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);
                    }

                    return $purchaseOrder;
                });

                break;
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === $maxAttempts) {
                    throw $exception;
                }
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Purchase order created.')]);

        return to_route('inventory.purchase-orders.show', $order);
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        Gate::authorize('view', $purchaseOrder);

        $purchaseOrder->load(['supplier', 'items.inventoryItem']);

        return Inertia::render('inventory/purchase-orders/Show', [
            'order' => $this->detail($purchaseOrder),
            'canReceive' => $request->user()?->can('receive', $purchaseOrder) ?? false,
        ]);
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder, InventoryStockService $stockService): RedirectResponse
    {
        Gate::authorize('receive', $purchaseOrder);

        $stockService->receivePurchaseOrder($purchaseOrder, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Purchase order received.')]);

        return to_route('inventory.purchase-orders.show', $purchaseOrder);
    }

    /**
     * @return array<string, mixed>
     */
    private function listItem(PurchaseOrder $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'supplier_name' => $order->supplier->name,
            'ordered_at_formatted' => $order->ordered_at?->format('M j, Y'),
            'received_at_formatted' => $order->received_at?->format('M j, Y'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(PurchaseOrder $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'notes' => $order->notes,
            'ordered_at_formatted' => $order->ordered_at?->format('M j, Y g:i A'),
            'received_at_formatted' => $order->received_at?->format('M j, Y g:i A'),
            'supplier' => [
                'id' => $order->supplier->id,
                'name' => $order->supplier->name,
            ],
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'inventory_item_name' => $item->inventoryItem->name,
                'quantity_ordered' => $item->quantity_ordered,
                'quantity_received' => $item->quantity_received,
                'unit_cost_formatted' => '$'.number_format($item->unit_cost_cents / 100, 2),
                'batch_number' => $item->batch_number,
                'expiry_date_formatted' => $item->expiry_date?->format('M j, Y'),
            ])->values()->all(),
        ];
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function supplierOptions(): array
    {
        return Supplier::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Supplier $supplier) => [
                'id' => $supplier->id,
                'label' => $supplier->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, label: string, unit: string}>
     */
    private function inventoryItemOptions(): array
    {
        return InventoryItem::query()
            ->orderBy('name')
            ->get(['id', 'name', 'unit'])
            ->map(fn (InventoryItem $item) => [
                'id' => $item->id,
                'label' => $item->name,
                'unit' => $item->unit,
            ])
            ->values()
            ->all();
    }
}
