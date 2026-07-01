<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FundUtilizationBatchDocumentBulkUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'quarter' => ['required', 'in:Q1,Q2,Q3,Q4'],
            'project_codes' => ['required', 'array', 'min:1'],
            'project_codes.*' => ['required', 'string'],
            'batch_document_files' => ['required', 'array', 'min:1'],
            'batch_document_files.*' => ['file', 'mimes:pdf', 'max:51200'],
        ];
    }
}
