<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageNotificationTemplates') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
        ];
    }
}
