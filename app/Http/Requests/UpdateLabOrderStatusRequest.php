<?php

namespace App\Http\Requests;

use App\Enums\LabOrderStatus;
use App\Models\LabOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateLabOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var LabOrder $labOrder */
        $labOrder = $this->route('labOrder');

        return $this->user()?->can('transition', $labOrder) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(LabOrderStatus::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var LabOrder $labOrder */
            $labOrder = $this->route('labOrder');
            $status = $this->enum('status', LabOrderStatus::class);

            if ($status === null) {
                return;
            }

            if (! $labOrder->status->canTransitionTo($status)) {
                $validator->errors()->add(
                    'status',
                    __('Invalid status transition from :from to :to.', [
                        'from' => $labOrder->status->label(),
                        'to' => $status->label(),
                    ]),
                );
            }
        });
    }
}
