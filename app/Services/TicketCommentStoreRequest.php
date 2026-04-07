<?php

namespace App\Services;

use App\Support\InputSanitizer;
use Illuminate\Foundation\Http\FormRequest;

class TicketCommentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    protected function prepareForValidation(): void
    {
        $all = $this->all();
        $all = InputSanitizer::sanitizeTextFields($all, ['comment'], true);
        $this->replace($all);
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'max:5000'],
        ];
    }
}
