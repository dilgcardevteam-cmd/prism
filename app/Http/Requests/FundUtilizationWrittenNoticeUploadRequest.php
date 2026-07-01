<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FundUtilizationWrittenNoticeUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'quarter' => ['required', 'in:Q1,Q2,Q3,Q4'],
            'secretary_dbm' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'secretary_dilg' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'speaker_house' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'president_senate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'house_committee' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'senate_committee' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $fields = [
                'secretary_dbm',
                'secretary_dilg',
                'speaker_house',
                'president_senate',
                'house_committee',
                'senate_committee',
            ];

            foreach ($fields as $field) {
                if ($this->hasFile($field)) {
                    return;
                }
            }

            $validator->errors()->add('written_notice', 'Please upload at least one written notice document.');
        });
    }
}
