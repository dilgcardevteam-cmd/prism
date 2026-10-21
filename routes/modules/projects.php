<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Projects\Controllers\LocallyFundedProjectController;
use App\Modules\Projects\Controllers\ProjectAtRiskController;
use App\Modules\Projects\Controllers\RlipLimeProjectController;
use App\Modules\Projects\Controllers\RssaProjectController;
use App\Modules\Projects\Controllers\SglgifProjectController;
use App\Modules\Projects\Controllers\SystemManagementController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

// ====================== MOBILE ENDPOINTS =====================
Route::get('/api/mobile/locally-funded', [LocallyFundedProjectController::class, 'mobileIndex'])
    ->name('api.mobile.locally-funded');
Route::get('/api/mobile/dashboard/aggregate', [LocallyFundedProjectController::class, 'mobileAggregatedDashboard'])
    ->name('api.mobile.dashboard.aggregate');
Route::get('/api/mobile/locally-funded/dashboard-summary', [LocallyFundedProjectController::class, 'mobileDashboardSummary'])
    ->name('api.mobile.locally-funded.dashboard-summary');
Route::get('/api/mobile/locally-funded/expected-completion', [LocallyFundedProjectController::class, 'mobileExpectedCompletionThisMonth'])
    ->name('api.mobile.locally-funded.expected-completion');
Route::get('/api/mobile/project-at-risk/slippage-summary', [ProjectAtRiskController::class, 'mobileSlippageSummary'])
    ->name('api.mobile.project-at-risk.slippage-summary');
Route::get('/api/mobile/project-at-risk/aging-summary', [ProjectAtRiskController::class, 'mobileAgingSummary'])
    ->name('api.mobile.project-at-risk.aging-summary');
Route::get('/api/mobile/project-at-risk/project-update-status-summary', [ProjectAtRiskController::class, 'mobileProjectUpdateStatusSummary'])
    ->name('api.mobile.project-at-risk.project-update-status-summary');
Route::get('/api/mobile/locally-funded/{project}/gallery/{galleryImage}', [LocallyFundedProjectController::class, 'viewMobileGalleryImage'])
    ->whereNumber('project')
    ->whereNumber('galleryImage')
    ->name('api.mobile.locally-funded.gallery-image');

Route::post('/api/mobile/locally-funded/{project}/gallery', [LocallyFundedProjectController::class, 'mobileUploadGalleryImage'])
    ->whereNumber('project')
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('api.mobile.locally-funded.gallery-upload');

Route::delete('/api/mobile/locally-funded/{project}/gallery/{galleryImage}', [LocallyFundedProjectController::class, 'mobileDeleteGalleryImage'])
    ->whereNumber('project')
    ->whereNumber('galleryImage')
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('api.mobile.locally-funded.gallery-delete');

Route::patch('/api/mobile/locally-funded/{project}/gallery/{galleryImage}', [LocallyFundedProjectController::class, 'mobileUpdateGalleryImage'])
    ->whereNumber('project')
    ->whereNumber('galleryImage')
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('api.mobile.locally-funded.gallery-update');

// ====================== AUTHENTICATED PROJECT ROUTES =====================
Route::middleware(['auth'])->group(function () {
    Route::get('/projects/locally-funded', [LocallyFundedProjectController::class, 'index'])->name('projects.locally-funded');
    Route::get('/projects/locally-funded/export', [LocallyFundedProjectController::class, 'export'])->name('projects.locally-funded.export');
    Route::get('/projects/locally-funded/subay/{projectCode}', [LocallyFundedProjectController::class, 'showSubaybayan'])->name('locally-funded-project.subay-show');
    Route::get('/projects/locally-funded/create', [LocallyFundedProjectController::class, 'create'])->name('locally-funded-project.create');
    Route::get('/projects/locally-funded/ensure/{projectCode}', [LocallyFundedProjectController::class, 'ensureFromSubay'])
        ->name('locally-funded-project.ensure');
    Route::post('/projects/locally-funded', [LocallyFundedProjectController::class, 'store'])->name('locally-funded-project.store');
    Route::get('/projects/locally-funded/{project}', [LocallyFundedProjectController::class, 'show'])
        ->missing(function () {
            return redirect()
                ->route('projects.locally-funded')
                ->with('error', 'The locally funded project could not be found. It may have been removed or replaced during a data import.');
        })
        ->name('locally-funded-project.show');
    Route::get('/projects/locally-funded/{project}/pcr-mov', [LocallyFundedProjectController::class, 'viewPcrMov'])->name('locally-funded-project.view-pcr-mov');
    Route::get('/projects/locally-funded/{project}/edit', [LocallyFundedProjectController::class, 'edit'])->name('locally-funded-project.edit');
    Route::put('/projects/locally-funded/{project}', [LocallyFundedProjectController::class, 'update'])->name('locally-funded-project.update');
    Route::delete('/projects/locally-funded/{project}', [LocallyFundedProjectController::class, 'destroy'])->name('locally-funded-project.destroy');

    Route::get('/project-at-risk', [ProjectAtRiskController::class, 'index'])
        ->name('projects.at-risk');
    Route::get('/project-at-risk/export', [ProjectAtRiskController::class, 'export'])
        ->name('projects.at-risk.export');
    Route::post('/project-at-risk/import', [ProjectAtRiskController::class, 'import'])
        ->middleware('regional_dilg')
        ->name('projects.at-risk.import');

    Route::get('/projects/rssa', [RssaProjectController::class, 'index'])
        ->name('projects.rssa');

    Route::get('/projects/sglgif', [SglgifProjectController::class, 'dashboard'])
        ->name('projects.sglgif');
    Route::get('/projects/sglgif/table', [SglgifProjectController::class, 'table'])
        ->name('projects.sglgif.table');

    Route::get('/projects/rlip-lime/dashboard', [RlipLimeProjectController::class, 'dashboard'])
        ->name('projects.rlip-lime.dashboard');
    Route::get('/projects/rlip-lime', [RlipLimeProjectController::class, 'index'])->name('projects.rlip-lime');
    Route::get('/projects/rlip-lime/{rowNumber}', [RlipLimeProjectController::class, 'show'])
        ->whereNumber('rowNumber')
        ->name('projects.rlip-lime.show');

    // System Management (Data Ingestion)
    Route::middleware('regional_dilg')->group(function () {
        Route::get('/system-management', function () {
            $user = request()->user();
            $hasAccess = $user
                && (
                    $user->hasCrudPermission('subaybayan_data_uploads', 'view')
                    || $user->hasCrudPermission('rlip_lime_data_uploads', 'view')
                    || $user->hasCrudPermission('project_at_risk_data_uploads', 'view')
                    || $user->hasCrudPermission('sglgif_data_uploads', 'view')
                );

            if (!$hasAccess) {
                return response()->view('errors.restricted', [], 403);
            }

            return view('system-management.index');
        })->name('system-management.index');

        Route::get('/system-management/upload-subaybayan', [SystemManagementController::class, 'uploadSubaybayan'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-subaybayan');
        Route::get('/system-management/upload-subaybayan/template', [SystemManagementController::class, 'downloadSubaybayanTemplate'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-subaybayan.template');
        Route::get('/system-management/upload-subaybayan-2025', [SystemManagementController::class, 'uploadSubaybayan2025'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-subaybayan-2025');
        Route::get('/system-management/upload-subaybayan-2025/template', [SystemManagementController::class, 'downloadSubaybayanTemplate'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-subaybayan-2025.template');
        Route::get('/system-management/upload-rssa', [SystemManagementController::class, 'uploadRssa'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-rssa');
        Route::get('/system-management/upload-rssa/template', [SystemManagementController::class, 'downloadSubaybayanTemplate'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-rssa.template');
        Route::get('/system-management/upload-sglgif', [SystemManagementController::class, 'uploadSglgif'])
            ->middleware('crud_permission:sglgif_data_uploads,view')
            ->name('system-management.upload-sglgif');
        Route::get('/system-management/upload-sglgif/template', [SystemManagementController::class, 'downloadSubaybayanTemplate'])
            ->middleware('crud_permission:sglgif_data_uploads,view')
            ->name('system-management.upload-sglgif.template');
        Route::get('/system-management/upload-rlip-lime', [SystemManagementController::class, 'uploadRlipLime'])
            ->middleware('crud_permission:rlip_lime_data_uploads,view')
            ->name('system-management.upload-rlip-lime');
        Route::get('/system-management/upload-project-at-risk', [ProjectAtRiskController::class, 'uploadManager'])
            ->name('system-management.upload-project-at-risk');
        Route::get('/system-management/upload-project-at-risk/template', [ProjectAtRiskController::class, 'downloadTemplate'])
            ->name('system-management.upload-project-at-risk.template');
        Route::post('/system-management/upload-project-at-risk/import', [ProjectAtRiskController::class, 'import'])
            ->name('system-management.upload-project-at-risk.import');
        Route::post('/system-management/upload-project-at-risk/import/{importId}/load', [ProjectAtRiskController::class, 'loadImport'])
            ->name('system-management.upload-project-at-risk.load');
        Route::get('/system-management/upload-project-at-risk/import/{importId}/download', [ProjectAtRiskController::class, 'downloadImport'])
            ->name('system-management.upload-project-at-risk.download');
        Route::delete('/system-management/upload-project-at-risk/import/{importId}', [ProjectAtRiskController::class, 'deleteImport'])
            ->name('system-management.upload-project-at-risk.delete');
        Route::post('/system-management/upload-subaybayan/import', [SystemManagementController::class, 'importSubaybayan'])
            ->middleware('crud_permission:subaybayan_data_uploads,add')
            ->name('system-management.upload-subaybayan.import');
        Route::post('/system-management/upload-subaybayan/import/{importId}/load', [SystemManagementController::class, 'loadSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,update')
            ->name('system-management.upload-subaybayan.load');
        Route::get('/system-management/upload-subaybayan/import/{importId}/download', [SystemManagementController::class, 'downloadSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-subaybayan.download');
        Route::delete('/system-management/upload-subaybayan/import/{importId}', [SystemManagementController::class, 'deleteSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,delete')
            ->name('system-management.upload-subaybayan.delete');
        Route::post('/system-management/upload-subaybayan-2025/import', [SystemManagementController::class, 'importSubaybayan'])
            ->middleware('crud_permission:subaybayan_data_uploads,add')
            ->name('system-management.upload-subaybayan-2025.import');
        Route::post('/system-management/upload-subaybayan-2025/import/{importId}/load', [SystemManagementController::class, 'loadSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,update')
            ->name('system-management.upload-subaybayan-2025.load');
        Route::get('/system-management/upload-subaybayan-2025/import/{importId}/download', [SystemManagementController::class, 'downloadSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-subaybayan-2025.download');
        Route::delete('/system-management/upload-subaybayan-2025/import/{importId}', [SystemManagementController::class, 'deleteSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,delete')
            ->name('system-management.upload-subaybayan-2025.delete');
        Route::post('/system-management/upload-rssa/import', [SystemManagementController::class, 'importSubaybayan'])
            ->middleware('crud_permission:subaybayan_data_uploads,add')
            ->name('system-management.upload-rssa.import');
        Route::post('/system-management/upload-rssa/import/{importId}/load', [SystemManagementController::class, 'loadSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,update')
            ->name('system-management.upload-rssa.load');
        Route::get('/system-management/upload-rssa/import/{importId}/download', [SystemManagementController::class, 'downloadSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-rssa.download');
        Route::delete('/system-management/upload-rssa/import/{importId}', [SystemManagementController::class, 'deleteSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,delete')
            ->name('system-management.upload-rssa.delete');
        Route::post('/system-management/upload-sglgif/import', [SystemManagementController::class, 'importSubaybayan'])
            ->middleware('crud_permission:sglgif_data_uploads,add')
            ->name('system-management.upload-sglgif.import');
        Route::post('/system-management/upload-sglgif/import/{importId}/load', [SystemManagementController::class, 'loadSubaybayanImport'])
            ->middleware('crud_permission:sglgif_data_uploads,update')
            ->name('system-management.upload-sglgif.load');
        Route::get('/system-management/upload-sglgif/import/{importId}/download', [SystemManagementController::class, 'downloadSubaybayanImport'])
            ->middleware('crud_permission:sglgif_data_uploads,view')
            ->name('system-management.upload-sglgif.download');
        Route::delete('/system-management/upload-sglgif/import/{importId}', [SystemManagementController::class, 'deleteSubaybayanImport'])
            ->middleware('crud_permission:sglgif_data_uploads,delete')
            ->name('system-management.upload-sglgif.delete');
        Route::post('/system-management/upload-rlip-lime/import', [SystemManagementController::class, 'importRlipLime'])
            ->middleware('crud_permission:rlip_lime_data_uploads,add')
            ->name('system-management.upload-rlip-lime.import');
        Route::post('/system-management/upload-rlip-lime/import/{importId}/load', [SystemManagementController::class, 'loadRlipLimeImport'])
            ->middleware('crud_permission:rlip_lime_data_uploads,update')
            ->name('system-management.upload-rlip-lime.load');
        Route::get('/system-management/upload-rlip-lime/import/{importId}/download', [SystemManagementController::class, 'downloadRlipLimeImport'])
            ->middleware('crud_permission:rlip_lime_data_uploads,view')
            ->name('system-management.upload-rlip-lime.download');
        Route::delete('/system-management/upload-rlip-lime/import/{importId}', [SystemManagementController::class, 'deleteRlipLimeImport'])
            ->middleware('crud_permission:rlip_lime_data_uploads,delete')
            ->name('system-management.upload-rlip-lime.delete');
    });
});

