<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInsuranceClaimRequest;
use App\Models\InsuranceClaim;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class InsuranceClaimController extends Controller
{
    public function store(StoreInsuranceClaimRequest $request, Invoice $invoice): RedirectResponse
    {
        Gate::authorize('create', InsuranceClaim::class);

        $user = $request->user();

        abort_unless($user !== null, 403);

        $data = $request->claimData();

        InsuranceClaim::query()->create([
            'invoice_id' => $invoice->id,
            'provider' => $data['provider'],
            'status' => $data['status'],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Insurance claim recorded.')]);

        return to_route('billing.show', $invoice);
    }
}
