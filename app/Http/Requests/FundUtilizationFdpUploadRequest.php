<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FundUtilizationFdpUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'quarter' => ['required', 'in:Q1,Q2,Q3,Q4'],
            'fdp_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
