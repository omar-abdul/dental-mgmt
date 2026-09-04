<?php

namespace App\Http\Requests;

use App\Enums\ToothStatus;
use App\Enums\ToothSurface;
use App\Models\Patient;
use App\Policies\ChartPolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOdontogramRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    public const FDI_TEETH = [
        '18', '17', '16', '15', '14', '13', '12', '11',
        '21', '22', '23', '24', '25', '26', '27', '28',
        '38', '37', '36', '35', '34', '33', '32', '31',
        '41', '42', '43', '44', '45', '46', '47', '48',
    ];

    public function authorize(): bool
    {
        $patient = $this->route('patient');

        return $patient instanceof Patient
            && (new ChartPolicy)->updateOdontogram($this->user(), $patient);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $patient = $this->route('patient');
        $patientId = $patient instanceof Patient ? $patient->id : null;

        return [
            'tooth_fdi' => ['required', 'string', Rule::in(self::FDI_TEETH)],
            'status' => ['required', Rule::enum(ToothStatus::class)],
            'surfaces' => ['nullable', 'array'],
            'surfaces.*' => [Rule::enum(ToothSurface::class)],
            'notes' => ['nullable', 'string', 'max:65535'],
            'encounter_id' => [
                'nullable',
                'integer',
                Rule::exists('encounters', 'id')->where(
                    fn ($query) => $patientId !== null ? $query->where('patient_id', $patientId) : $query
                ),
            ],
        ];
    }
}
