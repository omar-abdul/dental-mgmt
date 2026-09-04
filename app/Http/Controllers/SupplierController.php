<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Supplier::class);

        $search = trim((string) $request->query('search', ''));

        $suppliers = Supplier::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Supplier $supplier) => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'contact_name' => $supplier->contact_name,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
            ]);

        return Inertia::render('inventory/suppliers/Index', [
            'suppliers' => $suppliers,
            'search' => $search,
            'canCreate' => $request->user()?->can('create', Supplier::class) ?? false,
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $userId = $request->user()?->id;

        Supplier::query()->create([
            'name' => $request->string('name')->trim()->value(),
            'contact_name' => $request->string('contact_name')->trim()->value() ?: null,
            'phone' => $request->string('phone')->trim()->value() ?: null,
            'email' => $request->string('email')->trim()->value() ?: null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier created.')]);

        return to_route('inventory.suppliers.index');
    }
}
