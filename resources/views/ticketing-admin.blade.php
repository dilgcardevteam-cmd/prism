@extends('layouts.dashboard')

@section('title', 'Admin Ticket Monitoring')
@section('page-title', 'Ticketing System')

@section('styles')
    @include('partials.ticketing-styles')
@endsection

@section('content')
    <div class="content-header">
        <h1>Admin Ticket Monitoring</h1>
        <p>Monitor all tickets, review Central Office-forwarded items, and manage the ticket category list.</p>
    </div>

    <div class="ticketing-shell">
        @include('partials.ticketing-flash')

        @include('partials.ticketing-filters', [
            'categories' => $categories,
            'statuses' => $statuses,
            'priorities' => $priorities,
        ])

        @include('partials.ticketing-table', [
            'tickets' => $tickets,
            'showSubmittedBy' => true,
            'showAssignee' => true,
            'emptyMessage' => 'No tickets matched the current filters.',
        ])

        <div class="ticketing-grid ticketing-grid--2">
            <div class="ticketing-card">
                <h3 class="ticketing-card-title">Create Category</h3>
                <p class="ticketing-card-subtitle" style="margin-bottom: 16px;">Add or activate ticket categories for the submission form.</p>

                <form method="POST" action="{{ route('ticketing.admin.categories.store') }}" class="ticketing-grid">
                    @csrf
                    <div class="ticketing-field">
                        <label for="category_name">Category Name</label>
                        <input id="category_name" type="text" name="name" value="{{ old('name') }}" placeholder="e.g. System Issue">
                    </div>
                    <div class="ticketing-field">
                        <label for="category_description">Description</label>
                        <textarea id="category_description" name="description" placeholder="Briefly describe what this category is for.">{{ old('description') }}</textarea>
                    </div>
                    <div class="ticketing-grid ticketing-grid--2">
                        <div class="ticketing-field">
                            <label for="category_sort_order">Sort Order</label>
                            <input id="category_sort_order" type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                        </div>
                        <div class="ticketing-field">
                            <label for="category_is_active">Status</label>
                            <select id="category_is_active" name="is_active">
                                <option value="1" selected>Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="ticketing-btn ticketing-btn--primary">
                        <i class="fas fa-save"></i>
                        Save Category
                    </button>
                </form>
            </div>

            <div class="ticketing-card">
                <h3 class="ticketing-card-title">Category Management</h3>
                <p class="ticketing-card-subtitle" style="margin-bottom: 16px;">Update labels, ordering, and activation state. Categories with ticket records will be deactivated instead of deleted.</p>

                <div class="ticketing-category-grid">
                    @foreach ($categories as $category)
                        <div class="ticketing-category-item">
                            <form method="POST" action="{{ route('ticketing.admin.categories.update', $category) }}">
                                @csrf
                                @method('PUT')
                                <div class="ticketing-grid ticketing-grid--2">
                                    <div class="ticketing-field">
                                        <label>Category Name</label>
                                        <input type="text" name="name" value="{{ $category->name }}">
                                    </div>
                                    <div class="ticketing-field">
                                        <label>Sort Order</label>
                                        <input type="number" name="sort_order" value="{{ $category->sort_order }}" min="0">
                                    </div>
                                </div>
                                <div class="ticketing-field">
                                    <label>Description</label>
                                    <textarea name="description">{{ $category->description }}</textarea>
                                </div>
                                <div class="ticketing-field">
                                    <label>Status</label>
                                    <select name="is_active">
                                        <option value="1" @selected($category->is_active)>Active</option>
                                        <option value="0" @selected(!$category->is_active)>Inactive</option>
                                    </select>
                                </div>
                                <div class="ticketing-inline-actions">
                                    <button type="submit" class="ticketing-btn ticketing-btn--secondary">
                                        <i class="fas fa-pen"></i>
                                        Update
                                    </button>
                                    <button type="submit" form="delete-category-{{ $category->id }}" class="ticketing-btn ticketing-btn--danger">
                                        <i class="fas fa-trash"></i>
                                        Delete / Deactivate
                                    </button>
                                </div>
                            </form>

                            <form id="delete-category-{{ $category->id }}" method="POST" action="{{ route('ticketing.admin.categories.destroy', $category) }}">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
