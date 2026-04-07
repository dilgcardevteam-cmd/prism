@extends('layouts.dashboard')

@section('title', 'Submit Ticket')
@section('page-title', 'Ticketing System')

@section('styles')
    @include('partials.ticketing-styles')
@endsection

@section('content')
    @php
        $selectedCategory = $categories->firstWhere('id', (int) old('category_id'));
        $showSpecifyField = $selectedCategory?->isOthers() ?? false;
    @endphp

    <div class="content-header">
        <h1>Submit Ticket</h1>
        <p>Create a new ticket or query for system or business process concerns.</p>
    </div>

    <div class="ticketing-shell">
        @include('partials.ticketing-flash')

        <form method="POST" action="{{ route('ticketing.store') }}" enctype="multipart/form-data" class="ticketing-card">
            @csrf

            <div class="ticketing-toolbar" style="margin-bottom: 18px;">
                <div>
                    <h3 class="ticketing-card-title">Ticket Details</h3>
                    <p class="ticketing-card-subtitle">All new tickets are routed first to the provincial queue for your province, where a Provincial User must accept them before review.</p>
                </div>
                <a href="{{ route('ticketing.my-tickets') }}" class="ticketing-btn ticketing-btn--secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to My Tickets
                </a>
            </div>

            <div class="ticketing-grid ticketing-grid--2">
                <div class="ticketing-field">
                    <label for="title">Subject / Title</label>
                    <input id="title" type="text" name="title" value="{{ old('title') }}" placeholder="Enter a concise ticket subject">
                </div>

                <div class="ticketing-field">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option
                                value="{{ $category->id }}"
                                data-requires-specify="{{ $category->isOthers() ? 'true' : 'false' }}"
                                @selected((string) old('category_id') === (string) $category->id)
                            >
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="ticketing-field">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <option value="">Select priority</option>
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority }}" @selected(old('priority') === $priority)>{{ $priority }}</option>
                        @endforeach
                    </select>
                </div>

                <div
                    class="ticketing-field"
                    id="subcategory_wrapper"
                    @if (! $showSpecifyField) style="display: none;" @endif
                >
                    <label for="subcategory" id="subcategory_label">Please Specify</label>
                    <input
                        id="subcategory"
                        type="text"
                        name="subcategory"
                        value="{{ old('subcategory') }}"
                        placeholder="Required when category is Others"
                        @if ($showSpecifyField) required @endif
                    >
                    <div class="ticketing-kicker" id="subcategory_help">Provide more details because the selected category is <strong>Others</strong>.</div>
                </div>

                <div class="ticketing-field" style="grid-column: 1 / -1;">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Explain the issue, question, or process concern in detail.">{{ old('description') }}</textarea>
                </div>

                <div class="ticketing-field">
                    <label for="contact_information">Contact Information</label>
                    <input id="contact_information" type="text" name="contact_information" value="{{ old('contact_information', $defaultContactInformation) }}" placeholder="Email, mobile number, or preferred contact">
                </div>

                <div class="ticketing-field">
                    <label for="attachment">Attachment</label>
                    <input id="attachment" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                </div>
            </div>

            <div class="ticketing-inline-actions" style="margin-top: 22px;">
                <button type="submit" class="ticketing-btn ticketing-btn--primary">
                    <i class="fas fa-paper-plane"></i>
                    Submit Ticket
                </button>
                <a href="{{ route('ticketing.my-tickets') }}" class="ticketing-btn ticketing-btn--secondary">
                    <i class="fas fa-ban"></i>
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        (function initializeTicketCategorySpecifyField() {
            const categorySelect = document.getElementById('category_id');
            const specifyWrapper = document.getElementById('subcategory_wrapper');
            const specifyInput = document.getElementById('subcategory');
            const specifyLabel = document.getElementById('subcategory_label');
            const specifyHelp = document.getElementById('subcategory_help');

            if (!categorySelect || !specifyWrapper || !specifyInput || !specifyLabel || !specifyHelp) {
                return;
            }

            const syncSpecifyField = () => {
                const selectedOption = categorySelect.options[categorySelect.selectedIndex];
                const requiresSpecify = selectedOption?.dataset?.requiresSpecify === 'true';

                specifyWrapper.style.display = requiresSpecify ? '' : 'none';
                specifyInput.required = requiresSpecify;
                specifyLabel.textContent = requiresSpecify ? 'Please Specify *' : 'Please Specify';
                specifyHelp.innerHTML = 'Provide more details because the selected category is <strong>Others</strong>.';

                if (!requiresSpecify) {
                    specifyInput.value = '';
                }
            };

            syncSpecifyField();
            categorySelect.addEventListener('change', syncSpecifyField);
        })();
    </script>
@endsection
