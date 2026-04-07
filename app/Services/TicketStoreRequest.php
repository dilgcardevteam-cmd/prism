<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Support\InputSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isLguUser();
    }

    protected function prepareForValidation(): void
    {
        $all = $this->all();

        $all = InputSanitizer::sanitizeTextFields($all, [
            'title',
            'contact_information',
            'subcategory',
        ], false, true);

        $all = InputSanitizer::sanitizeTextFields($all, [
            'description',
        ], true);

        $selectedCategory = $this->resolveSelectedCategory($all['category_id'] ?? null);

        if (!$selectedCategory?->isOthers()) {
            $all['subcategory'] = null;
        }

        $this->replace($all);
    }

    public function rules(): array
    {
        $requiresSpecify = $this->selectedCategory()?->isOthers() ?? false;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category_id' => ['required', 'integer', Rule::exists('ticket_categories', 'id')->where('is_active', true)],
            'subcategory' => [Rule::requiredIf($requiresSpecify), 'nullable', 'string', 'max:255'],
            'priority' => ['required', 'string', Rule::in(Ticket::priorityOptions())],
            'contact_information' => ['required', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'subcategory.required' => 'Please specify the concern when the category is Others.',
        ];
    }

    protected function selectedCategory(): ?TicketCategory
    {
        return $this->resolveSelectedCategory($this->input('category_id'));
    }

    protected function resolveSelectedCategory(mixed $categoryId): ?TicketCategory
    {
        if (!$categoryId) {
            return null;
        }

        return TicketCategory::query()->find($categoryId);
    }
}
