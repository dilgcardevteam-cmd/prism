<?php

namespace App\Services;

use App\Support\InputSanitizer;
use Illuminate\Foundation\Http\FormRequest;

class TicketEscalateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    protected function prepareForValidation(): void
    {
        $all = $this->all();
        $all = InputSanitizer::sanitizeTextFields($all, ['escalation_reason', 'comment'], true, true);
        $this->replace($all);
    }

    public function rules(): array
    {
        return [
            'escalation_reason' => ['required', 'string', 'max:5000'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
