# Dashboard Cascading Filters - Client-side Auto-adjust

Status: **Approved** ✅ Proceed step-by-step.

## Breakdown (8 steps)

### Phase 1: Setup (Steps 1-2)
1. **✅ CREATE** `routes/web.php` → AJAX filter endpoint
2. **✅ CREATE** `app/Http/Controllers/DashboardFilterController.php` → JSON filter options

### Phase 2: Backend Logic (Steps 3-4)
3. **Extract** filter query logic from `ProjectDashboardController.php` → reusable service
4. **Test** AJAX endpoint: `GET /dashboard/filters/cities?province[]=Abra` → JSON response

### Phase 3: Frontend JS (Steps 5-6)
5. **CREATE** `public/js/dashboard-filters.js` → AJAX cascading logic
6. **UPDATE** `resources/views/dashboard/index.blade.php` → wire JS, data attrs

### Phase 4: Integration & Test (Steps 7-8)
7. **INTEGRATE** JS in view → select province → auto-update cities (no reload)
8. **TEST** all cascades + multi-select preserve → attempt_completion

## Current Progress
```
✅ 1. routes/web.php (route added)
✅ 2. DashboardFilterController.php (endpoint created)
⏳ 3. Extract filter service from routes/web.php closure
[ ] 4. Test AJAX endpoint
[ ] 5. dashboard-filters.js
[ ] 6. Update dashboard/index.blade.php
[ ] 7. Integrate JS
[ ] 8. Full test ✅
```

**Next**: Step 3 → Extract `$filterOptions` logic from routes/web.php → DashboardFilterController service method.
```
php artisan serve --port=8001  # Test /dashboard/filters/cities after Step 4
```
```
php artisan serve  # Test after each step
```

