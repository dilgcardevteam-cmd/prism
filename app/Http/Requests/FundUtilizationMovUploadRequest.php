<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FundUtilizationMovUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'quarter' => ['required', 'in:Q1,Q2,Q3,Q4'],
            'mov_file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
