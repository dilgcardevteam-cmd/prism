// Dashboard Cascading Filters - Client-side AJAX Updates
// Step 5: Core cascading logic

(function () {
    'use strict';

    const FILTER_DEPENDENCIES = {
        'province': ['city_municipality', 'barangay'],  // province → cities, barangays
        'city_municipality': ['barangay'],             // cities → barangays
        'barangay': [],                                // barangays → nothing
        'program': [],                                 // independent
        'funding_year': [],                            // independent
        'project_type': [],                            // independent
        'project_status': []                           // independent
    };

    const AJAX_ENDPOINT = '/dashboard/filters';
    const DEBOUNCE_DELAY = 300; // ms

    class DashboardFilterCascader {
        constructor() {
            this.debounceTimer = null;
            this.init();
        }

        init() {
            document.addEventListener('change', this.handleFilterChange.bind(this), true);
            document.addEventListener('click', this.handleBadgeRemove.bind(this), true);
        }

        getCurrentFilters() {
            const filters = {};
            document.querySelectorAll('.dashboard-stacked-filter-source').forEach(select => {
                const name = select.name.replace('[]', '');
                filters[name] = Array.from(select.selectedOptions).map(opt => opt.value);
            });
            return filters;
        }

        handleFilterChange(event) {
            const select = event.target.closest('.dashboard-stacked-filter-source');
            if (!select) return;

            const filterName = select.name.replace('[]', '');
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.updateDependents(filterName);
            }, DEBOUNCE_DELAY);
        }

        async updateDependents(changedFilter) {
            const dependents = FILTER_DEPENDENCIES[changedFilter] || [];
            const currentFilters = this.getCurrentFilters();

            for (const dependentFilter of dependents) {
                await this.updateFilterOptions(dependentFilter, currentFilters);
            }
        }

        async updateFilterOptions(filterType, currentFilters) {
            try {
                // Preserve current selection before update
                const sourceSelect = document.querySelector(`#${filterType}`);
                const preserve = Array.from(sourceSelect.selectedOptions).map(opt => opt.value);

                const params = new URLSearchParams(currentFilters);
                params.append('preserve_' + filterType, preserve.join(','));

                const response = await fetch(`${AJAX_ENDPOINT}/${filterType}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const data = await response.json();

                this.applyFilterOptions(filterType, data.options, data.preserve);

            } catch (error) {
                console.error('Filter update failed:', error);
                // Optionally show user toast notification
            }
        }

        applyFilterOptions(filterType, newOptions, preserveValues) {
            const sourceSelect = document.querySelector(`#${filterType}`);
            const dropdownMenu = document.querySelector(`#${filterType}_dropdown_menu`);
            
            if (!sourceSelect || !dropdownMenu) return;

            // Clear existing options
            sourceSelect.innerHTML = '';
            dropdownMenu.innerHTML = '';

            // Add new options
            newOptions.forEach(optionValue => {
                const option = document.createElement('option');
                option.value = optionValue;
                option.textContent = optionValue;
                option.selected = preserveValues.includes(optionValue);
                sourceSelect.appendChild(option);
            });

            // Rebuild dropdown menu
            this.rebuildDropdownMenu(filterType);

            // Trigger custom event for stacked-filter JS
            sourceSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }

        rebuildDropdownMenu(filterType) {
            const sourceSelect = document.querySelector(`#${filterType}`);
            const dropdownMenu = document.querySelector(`#${filterType}_dropdown_menu`);
            const badgeContainer = document.querySelector(`#${filterType}_badges`);

            if (!dropdownMenu) return;

            Array.from(sourceSelect.selectedOptions).forEach(option => {
                const menuItem = document.createElement('div');
                menuItem.className = 'dashboard-stacked-filter-option' + (option.selected ? ' is-selected' : '');
                menuItem.dataset.value = option.value;
                menuItem.textContent = option.textContent;
                if (option.selected) {
                    menuItem.innerHTML += ' <i class="fas fa-check dashboard-stacked-filter-option-check"></i>';
                }
                dropdownMenu.appendChild(menuItem);
            });

            // Update badges
            this.updateFilterBadges(filterType);
        }

        handleBadgeRemove(event) {
            const removeBtn = event.target.closest('.dashboard-filter-badge-remove');
            if (!removeBtn) return;

            event.stopPropagation();
            const badge = removeBtn.closest('.dashboard-filter-badge');
            const filterType = badge.dataset.filterType;
            const value = badge.dataset.value;

            const sourceSelect = document.querySelector(`#${filterType}`);
            if (sourceSelect) {
                sourceSelect.querySelector(`option[value="${value}"]`).selected = false;
                sourceSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        updateFilterBadges(filterType) {
            const sourceSelect = document.querySelector(`#${filterType}`);
            const badgeContainer = document.querySelector(`#${filterType}_badges`);

            if (!badgeContainer) return;

            badgeContainer.innerHTML = '';

            Array.from(sourceSelect.selectedOptions).forEach(option => {
                const badge = document.createElement('span');
                badge.className = 'dashboard-filter-badge';
                badge.dataset.filterType = filterType;
                badge.dataset.value = option.value;
                badge.innerHTML = `
                    <span class="dashboard-filter-badge-label">${this.truncateLabel(option.textContent, 20)}</span>
                    <button type="button" class="dashboard-filter-badge-remove" aria-label="Remove filter">&times;</button>
                `;
                badgeContainer.appendChild(badge);
            });

            const emptyText = sourceSelect.dataset.emptyBadgeText || 'All';
            if (sourceSelect.selectedOptions.length === 0) {
                const emptyBadge = document.createElement('span');
                emptyBadge.className = 'dashboard-filter-badge dashboard-filter-badge-empty';
                emptyBadge.textContent = emptyText;
                badgeContainer.appendChild(emptyBadge);
            }
        }

        truncateLabel(label, maxLength) {
            if (label.length <= maxLength) return label;
            return label.substring(0, maxLength - 3) + '...';
        }
    }

    // Initialize when DOM loaded
    document.addEventListener('DOMContentLoaded', () => {
        new DashboardFilterCascader();
    });

    // Make global for external access
    window.DashboardFilterCascader = DashboardFilterCascader;
})();

