<?php

namespace App\Http\Requests;

use App\Models\Encounter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSoapNoteAmendmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $encounter = $this->route('encounter');

        return $encounter instanceof Encounter
            && ($this->user()?->can('amend', $encounter) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:65535'],
        ];
    }
}
