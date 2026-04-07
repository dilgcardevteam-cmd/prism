# Fix Notification Bell URLs - Progress Tracker

## Overview
Convert notification URLs to relative paths so they work on any domain. Update services/controllers using `route(name, params)` → `route(name, params, false)`.

**Live domain:** t-pdmuoms.publicdataportal.com

## Steps

### ✅ 1. Manual: Update Production .env [USER]
```
APP_URL=https://t-pdmuoms.publicdataportal.com
```
Upload to hosting, restart services.

### ⏳ 2. Edit Services & Controllers [AI]
- `app/Services/TicketNotificationService.php`
- `app/Services/InterventionNotificationService.php` 
- `app/Http/Controllers/LocallyFundedProjectController.php`
- `app/Http/Controllers/PreImplementationDocumentController.php`
- `app/Http/Controllers/DatabaseUtilityController.php`
- `app/Http/Controllers/FundUtilizationReportController.php`

### ⏳ 3. Cleanup Old Notifications [USER]
```bash
php artisan tinker
```
Then:
```php
DB::table('tbnotifications')->where('url', 'LIKE', '%127.0.0.1%')->orWhere('url', 'LIKE', '%localhost%')->delete();
exit
```

### ⏳ 4. Test
- Create test notification
- Click bell item → marks read + redirects correctly
- Verify on live domain

**Current: Starting edits...**

