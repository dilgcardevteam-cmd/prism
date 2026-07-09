<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FundUtilizationIndividualDocumentBulkUploadRequest extends FormRequest
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
            'project_codes.*' => ['string'],
            'mov_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'secretary_dbm' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'secretary_dilg' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'speaker_house' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'president_senate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'house_committee' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'senate_committee' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'fdp_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'posting_link' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $writtenNoticeFields = [
                'secretary_dbm',
                'secretary_dilg',
                'speaker_house',
                'president_senate',
                'house_committee',
                'senate_committee',
            ];

            $hasWrittenNoticeFile = false;
            foreach ($writtenNoticeFields as $field) {
                if ($this->hasFile($field)) {
                    $hasWrittenNoticeFile = true;
                    break;
                }
            }

            $hasPostingLink = trim((string) $this->input('posting_link', '')) !== '';

            if (
                !$this->hasFile('mov_file')
                && !$hasWrittenNoticeFile
                && !$this->hasFile('fdp_file')
                && !$hasPostingLink
            ) {
                $validator->errors()->add('individual_documents', 'Please upload or provide at least one individual document.');
            }
        });
    }
}
