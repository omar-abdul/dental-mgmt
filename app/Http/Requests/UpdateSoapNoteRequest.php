<?php

namespace App\Http\Requests;

use App\Models\Encounter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSoapNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $encounter = $this->route('encounter');

        return $encounter instanceof Encounter
            && ($this->user()?->can('updateSoap', $encounter) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subjective' => ['nullable', 'string', 'max:65535'],
            'objective' => ['nullable', 'string', 'max:65535'],
            'assessment' => ['nullable', 'string', 'max:65535'],
            'plan' => ['nullable', 'string', 'max:65535'],
        ];
    }
}
