<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FundUtilizationBatchDocumentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'quarter' => ['required', 'in:Q1,Q2,Q3,Q4'],
            'batch_document_file' => ['required', 'array', 'min:1'],
            'batch_document_file.*' => ['file', 'mimes:pdf', 'max:51200'],
        ];
    }
}
