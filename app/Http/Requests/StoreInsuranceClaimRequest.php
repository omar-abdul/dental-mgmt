<?php

namespace App\Http\Requests;

use App\Enums\InsuranceClaimStatus;
use App\Models\InsuranceClaim;
use App\Models\Invoice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInsuranceClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice instanceof Invoice
            && ($this->user()?->can('create', InsuranceClaim::class) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(InsuranceClaimStatus::class)],
        ];
    }

    /**
     * @return array{provider: string, status: InsuranceClaimStatus}
     */
    public function claimData(): array
    {
        return [
            'provider' => (string) $this->input('provider'),
            'status' => $this->enum('status', InsuranceClaimStatus::class),
        ];
    }
}
