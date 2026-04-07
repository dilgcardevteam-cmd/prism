@php
    $filterAction = $filterAction ?? url()->current();
    $showCategoryFilter = $showCategoryFilter ?? true;
@endphp

<form method="GET" action="{{ $filterAction }}" class="ticketing-card">
    <div class="ticketing-toolbar" style="margin-bottom: 18px;">
        <div>
            <h3 class="ticketing-card-title">Ticket Filters</h3>
            <p class="ticketing-card-subtitle">Refine the ticket list by keyword, workflow status, priority, or category.</p>
        </div>
        <div class="ticketing-toolbar-actions">
            <button type="submit" class="ticketing-btn ticketing-btn--primary">
                <i class="fas fa-filter"></i>
                Apply Filters
            </button>
            <a href="{{ $filterAction }}" class="ticketing-btn ticketing-btn--secondary">
                <i class="fas fa-rotate-left"></i>
                Reset
            </a>
        </div>
    </div>

    <div class="ticketing-filter-grid">
        <div class="ticketing-field">
            <label for="search">Search</label>
            <input id="search" type="text" name="search" value="{{ request('search') }}" placeholder="Ticket no., subject, description">
        </div>

        <div class="ticketing-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>

        <div class="ticketing-field">
            <label for="priority">Priority</label>
            <select id="priority" name="priority">
                <option value="">All priorities</option>
                @foreach ($priorities as $priority)
                    <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ $priority }}</option>
                @endforeach
            </select>
        </div>

        @if ($showCategoryFilter)
            <div class="ticketing-field">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>
</form>
