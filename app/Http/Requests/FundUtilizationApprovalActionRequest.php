<?php

namespace App\Http\Requests;

use App\Support\InputSanitizer;
use Illuminate\Foundation\Http\FormRequest;

class FundUtilizationApprovalActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    protected function prepareForValidation(): void
    {
        $all = $this->all();
        $all = InputSanitizer::sanitizeTextFields($all, ['remarks'], true, true);
        $this->replace($all);
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approve,return'],
            'remarks' => ['required_if:action,return', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'The action (approve or return) is required.',
            'action.in' => 'The action must be either "approve" or "return".',
            'remarks.required_if' => 'Remarks are mandatory when returning a submission for revision.',
            'remarks.max' => 'Remarks must not exceed 1000 characters.',
        ];
    }
}
