<?php

namespace App\Services;

use App\Support\InputSanitizer;
use Illuminate\Foundation\Http\FormRequest;

class TicketResolveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    protected function prepareForValidation(): void
    {
        $all = $this->all();
        $all = InputSanitizer::sanitizeTextFields($all, ['resolution_note'], true, true);
        $this->replace($all);
    }

    public function rules(): array
    {
        return [
            'resolution_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
