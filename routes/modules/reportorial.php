<?php

use Illuminate\Support\Facades\Route;
use App\Modules\ReportorialRequirements\Controllers\AnnualMaintenanceWorkProgramController;
use App\Modules\ReportorialRequirements\Controllers\AnnualRpmesForm4Controller;
use App\Modules\ReportorialRequirements\Controllers\ConfirmationOfFundReceiptController;
use App\Modules\ReportorialRequirements\Controllers\LgsfProjectCompletionReportController;
use App\Modules\ReportorialRequirements\Controllers\LocalProjectMonitoringCommitteeController;
use App\Modules\ReportorialRequirements\Controllers\PdNoPbbmMonthlyReportController;
use App\Modules\ReportorialRequirements\Controllers\PisatReportController;
use App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController;
use App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201819Controller;
use App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201830Controller;
use App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm2Controller;
use App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm5Controller;
use App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm6Controller;
use App\Modules\ReportorialRequirements\Controllers\RbisAnnualCertificationController;
use App\Modules\ReportorialRequirements\Controllers\RoadMaintenanceStatusReportController;
use App\Modules\ReportorialRequirements\Controllers\SglgifProjectCompletionReportController;
use App\Modules\ReportorialRequirements\Controllers\SwaAnnexFReportController;

Route::middleware(['auth'])->group(function () {
    Route::get('/pre-implementation-documents/projects', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'index'])
        ->name('pre-implementation-documents.index');
    Route::get('/pre-implementation-documents/projects/{projectCode}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'show'])
        ->name('pre-implementation-documents.show');
    Route::get('/pre-implementation-documents/projects/{projectCode}/document/{documentType}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'viewDocument'])
        ->name('pre-implementation-documents.document');
    Route::get('/pre-implementation-documents/projects/{projectCode}/document-file/{fileId}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'viewDocumentFile'])
        ->name('pre-implementation-documents.document-file');
    Route::post('/pre-implementation-documents/projects/{projectCode}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'save'])
        ->name('pre-implementation-documents.save');
    Route::delete('/pre-implementation-documents/projects/{projectCode}/document/{documentType}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'deleteDocument'])
        ->name('pre-implementation-documents.delete');
    Route::delete('/pre-implementation-documents/projects/{projectCode}/document-file/{fileId}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'deleteDocumentFile'])
        ->name('pre-implementation-documents.delete-file');
    Route::post('/pre-implementation-documents/projects/{projectCode}/upload/{documentType}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'uploadMultiDocument'])
        ->name('pre-implementation-documents.upload-multi');
    Route::post('/pre-implementation-documents/projects/{projectCode}/validate/{documentType}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'validateDocument'])
        ->name('pre-implementation-documents.validate');
    Route::post('/pre-implementation-documents/projects/{projectCode}/validate-file/{fileId}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'validateDocumentFile'])
        ->name('pre-implementation-documents.validate-file');
    Route::get('/pre-implementation-documents/sbdp-projects', function () {
        return redirect()->route('pre-implementation-documents.index', request()->query());
    });
    Route::get('/pre-implementation-documents/sbdp-projects/{projectCode}', function (string $projectCode) {
        return redirect()->route('pre-implementation-documents.show', array_merge(
            ['projectCode' => $projectCode],
            request()->query()
        ));
    });
    Route::post('/pre-implementation-documents/sbdp-projects/{projectCode}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'save']);
    Route::post('/pre-implementation-documents/sbdp-projects/{projectCode}/validate/{documentType}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'validateDocument']);

    Route::get('/initial-project-documents/projects', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'index'])
        ->name('initial-project-documents.index');
    Route::get('/initial-project-documents/projects/{projectCode}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'show'])
        ->name('initial-project-documents.show');
    Route::get('/initial-project-documents/projects/{projectCode}/document/{documentType}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'viewDocument'])
        ->name('initial-project-documents.document');
    Route::get('/initial-project-documents/projects/{projectCode}/document-file/{fileId}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'viewDocumentFile'])
        ->name('initial-project-documents.document-file');
    Route::post('/initial-project-documents/projects/{projectCode}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'save'])
        ->name('initial-project-documents.save');
    Route::delete('/initial-project-documents/projects/{projectCode}/document/{documentType}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'deleteDocument'])
        ->name('initial-project-documents.delete');
    Route::delete('/initial-project-documents/projects/{projectCode}/document-file/{fileId}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'deleteDocumentFile'])
        ->name('initial-project-documents.delete-file');
    Route::post('/initial-project-documents/projects/{projectCode}/upload/{documentType}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'uploadMultiDocument'])
        ->name('initial-project-documents.upload-multi');
    Route::post('/initial-project-documents/projects/{projectCode}/validate/{documentType}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'validateDocument'])
        ->name('initial-project-documents.validate');
    Route::post('/initial-project-documents/projects/{projectCode}/validate-file/{fileId}', [App\Modules\ReportorialRequirements\Controllers\PreImplementationDocumentController::class, 'validateDocumentFile'])
        ->name('initial-project-documents.validate-file');

    // Local Project Monitoring Committee routes
    Route::get('local-project-monitoring-committee/export', [App\Modules\ReportorialRequirements\Controllers\LocalProjectMonitoringCommitteeController::class, 'export'])
        ->name('local-project-monitoring-committee.export');
    Route::post('local-project-monitoring-committee/{lpmc}/upload', [App\Modules\ReportorialRequirements\Controllers\LocalProjectMonitoringCommitteeController::class, 'upload'])
        ->name('local-project-monitoring-committee.upload');
    Route::post('local-project-monitoring-committee/{lpmc}/approve/{docId}', [App\Modules\ReportorialRequirements\Controllers\LocalProjectMonitoringCommitteeController::class, 'approveDocument'])
        ->name('local-project-monitoring-committee.approve');
    Route::get('local-project-monitoring-committee/{lpmc}/document/{docId}', [App\Modules\ReportorialRequirements\Controllers\LocalProjectMonitoringCommitteeController::class, 'viewDocument'])
        ->name('local-project-monitoring-committee.document');
    Route::delete('local-project-monitoring-committee/{lpmc}/document/{docId}', [App\Modules\ReportorialRequirements\Controllers\LocalProjectMonitoringCommitteeController::class, 'deleteDocument'])
        ->name('local-project-monitoring-committee.delete-document');
    Route::get('local-project-monitoring-committee/{lpmc}/file/{fileId}', [App\Modules\ReportorialRequirements\Controllers\LocalProjectMonitoringCommitteeController::class, 'viewDocumentFile'])
        ->name('local-project-monitoring-committee.view-file');
    Route::delete('local-project-monitoring-committee/{lpmc}/file/{fileId}', [App\Modules\ReportorialRequirements\Controllers\LocalProjectMonitoringCommitteeController::class, 'deleteDocumentFile'])
        ->name('local-project-monitoring-committee.delete-file');
    Route::resource('local-project-monitoring-committee', App\Modules\ReportorialRequirements\Controllers\LocalProjectMonitoringCommitteeController::class)
        ->parameters(['local-project-monitoring-committee' => 'lpmc']);

    // Road Maintenance Status Report routes
    Route::get('road-maintenance-status/export', [App\Modules\ReportorialRequirements\Controllers\RoadMaintenanceStatusReportController::class, 'export'])
        ->name('road-maintenance-status.export');
    Route::post('road-maintenance-status/{roadMaintenance}/upload', [App\Modules\ReportorialRequirements\Controllers\RoadMaintenanceStatusReportController::class, 'upload'])
        ->name('road-maintenance-status.upload');
    Route::post('road-maintenance-status/{roadMaintenance}/approve/{docId}', [App\Modules\ReportorialRequirements\Controllers\RoadMaintenanceStatusReportController::class, 'approveDocument'])
        ->name('road-maintenance-status.approve');
    Route::get('road-maintenance-status/{roadMaintenance}/document/{docId}', [App\Modules\ReportorialRequirements\Controllers\RoadMaintenanceStatusReportController::class, 'viewDocument'])
        ->name('road-maintenance-status.document');
    Route::delete('road-maintenance-status/{roadMaintenance}/document/{docId}', [App\Modules\ReportorialRequirements\Controllers\RoadMaintenanceStatusReportController::class, 'deleteDocument'])
        ->name('road-maintenance-status.delete-document');
    Route::resource('road-maintenance-status', App\Modules\ReportorialRequirements\Controllers\RoadMaintenanceStatusReportController::class)
        ->parameters(['road-maintenance-status' => 'roadMaintenance']);

    Route::get('/reports/monthly/pd-no-pbbm-2025-1572-1573', [App\Modules\ReportorialRequirements\Controllers\PdNoPbbmMonthlyReportController::class, 'index'])
        ->name('reports.monthly.pd-no-pbbm-2025-1572-1573');
    Route::get('/reports/monthly/pd-no-pbbm-2025-1572-1573/{office}/edit', [App\Modules\ReportorialRequirements\Controllers\PdNoPbbmMonthlyReportController::class, 'edit'])
        ->name('reports.monthly.pd-no-pbbm-2025-1572-1573.edit');
    Route::post('/reports/monthly/pd-no-pbbm-2025-1572-1573/{office}/upload', [App\Modules\ReportorialRequirements\Controllers\PdNoPbbmMonthlyReportController::class, 'upload'])
        ->name('reports.monthly.pd-no-pbbm-2025-1572-1573.upload');
    Route::post('/reports/monthly/pd-no-pbbm-2025-1572-1573/{office}/approve/{docId}', [App\Modules\ReportorialRequirements\Controllers\PdNoPbbmMonthlyReportController::class, 'approveDocument'])
        ->name('reports.monthly.pd-no-pbbm-2025-1572-1573.approve');
    Route::get('/reports/monthly/pd-no-pbbm-2025-1572-1573/{office}/document/{docId}', [App\Modules\ReportorialRequirements\Controllers\PdNoPbbmMonthlyReportController::class, 'viewDocument'])
        ->name('reports.monthly.pd-no-pbbm-2025-1572-1573.document');
    Route::delete('/reports/monthly/pd-no-pbbm-2025-1572-1573/{office}/document/{docId}', [App\Modules\ReportorialRequirements\Controllers\PdNoPbbmMonthlyReportController::class, 'deleteDocument'])
        ->name('reports.monthly.pd-no-pbbm-2025-1572-1573.delete-document');

    Route::get('/reports/monthly/swa-annex-f', [App\Modules\ReportorialRequirements\Controllers\SwaAnnexFReportController::class, 'index'])
        ->name('reports.monthly.swa-annex-f');
    Route::get('/reports/monthly/swa-annex-f/{office}/edit', [App\Modules\ReportorialRequirements\Controllers\SwaAnnexFReportController::class, 'edit'])
        ->name('reports.monthly.swa-annex-f.edit');
    Route::post('/reports/monthly/swa-annex-f/{office}/upload', [App\Modules\ReportorialRequirements\Controllers\SwaAnnexFReportController::class, 'upload'])
        ->name('reports.monthly.swa-annex-f.upload');
    Route::post('/reports/monthly/swa-annex-f/{office}/approve/{docId}', [App\Modules\ReportorialRequirements\Controllers\SwaAnnexFReportController::class, 'approveDocument'])
        ->name('reports.monthly.swa-annex-f.approve');
    Route::get('/reports/monthly/swa-annex-f/{office}/document/{docId}', [App\Modules\ReportorialRequirements\Controllers\SwaAnnexFReportController::class, 'viewDocument'])
        ->name('reports.monthly.swa-annex-f.document');
    Route::delete('/reports/monthly/swa-annex-f/{office}/document/{docId}', [App\Modules\ReportorialRequirements\Controllers\SwaAnnexFReportController::class, 'deleteDocument'])
        ->name('reports.monthly.swa-annex-f.delete-document');

    Route::get('/reports/quarterly/rpmes/form-2', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm2Controller::class, 'index'])
        ->name('reports.quarterly.rpmes.form-2');
    Route::post('/reports/quarterly/rpmes/form-2/{projectCode}/upload', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm2Controller::class, 'upload'])
        ->name('reports.quarterly.rpmes.form-2.upload');
    Route::post('/reports/quarterly/rpmes/form-2/{projectCode}/approve/{quarter}', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm2Controller::class, 'approveDocument'])
        ->name('reports.quarterly.rpmes.form-2.approve');
    Route::get('/reports/quarterly/rpmes/form-2/{projectCode}/document/{quarter}', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm2Controller::class, 'viewDocument'])
        ->name('reports.quarterly.rpmes.form-2.document');
    Route::delete('/reports/quarterly/rpmes/form-2/{projectCode}/document/{quarter}', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm2Controller::class, 'deleteDocument'])
        ->name('reports.quarterly.rpmes.form-2.delete-document');
    Route::get('/reports/quarterly/rpmes/form-2/{projectCode}', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm2Controller::class, 'show'])
        ->name('reports.quarterly.rpmes.form-2.show');
    Route::get('/reports/quarterly/rpmes/form-5', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm5Controller::class, 'index'])
        ->name('reports.quarterly.rpmes.form-5');
    Route::post('/reports/quarterly/rpmes/form-5/{projectCode}/upload', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm5Controller::class, 'upload'])
        ->name('reports.quarterly.rpmes.form-5.upload');
    Route::post('/reports/quarterly/rpmes/form-5/{projectCode}/approve/{quarter}', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm5Controller::class, 'approveDocument'])
        ->name('reports.quarterly.rpmes.form-5.approve');
    Route::get('/reports/quarterly/rpmes/form-5/{projectCode}/document/{quarter}', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm5Controller::class, 'viewDocument'])
        ->name('reports.quarterly.rpmes.form-5.document');
    Route::delete('/reports/quarterly/rpmes/form-5/{projectCode}/document/{quarter}', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm5Controller::class, 'deleteDocument'])
        ->name('reports.quarterly.rpmes.form-5.delete-document');
    Route::get('/reports/quarterly/rpmes/form-5/{projectCode}', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm5Controller::class, 'show'])
        ->name('reports.quarterly.rpmes.form-5.show');
    Route::get('/reports/quarterly/rpmes/form-6', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm6Controller::class, 'index'])
        ->name('reports.quarterly.rpmes.form-6');
    Route::post('/reports/quarterly/rpmes/form-6/{projectCode}/upload', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm6Controller::class, 'upload'])
        ->name('reports.quarterly.rpmes.form-6.upload');
    Route::post('/reports/quarterly/rpmes/form-6/{projectCode}/approve/{quarter}', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm6Controller::class, 'approveDocument'])
        ->name('reports.quarterly.rpmes.form-6.approve');
    Route::get('/reports/quarterly/rpmes/form-6/{projectCode}/document/{quarter}', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm6Controller::class, 'viewDocument'])
        ->name('reports.quarterly.rpmes.form-6.document');
    Route::delete('/reports/quarterly/rpmes/form-6/{projectCode}/document/{quarter}', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm6Controller::class, 'deleteDocument'])
        ->name('reports.quarterly.rpmes.form-6.delete-document');
    Route::get('/reports/quarterly/rpmes/form-6/{projectCode}', [App\Modules\ReportorialRequirements\Controllers\QuarterlyRpmesForm6Controller::class, 'show'])
        ->name('reports.quarterly.rpmes.form-6.show');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-19',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201819Controller::class, 'index']
    )->name('reports.quarterly.dilg-mc-2018-19');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-19/{office}/edit/{quarter}',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201819Controller::class, 'edit']
    )->name('reports.quarterly.dilg-mc-2018-19.edit');
    Route::post(
        '/reports/quarterly/dilg-mc-2018-19/{office}/edit/{quarter}',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201819Controller::class, 'saveEncoding']
    )->name('reports.quarterly.dilg-mc-2018-19.save-encoding');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-19/{office}/edit/{quarter}/export',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201819Controller::class, 'exportEncoding']
    )->name('reports.quarterly.dilg-mc-2018-19.export-encoding');
    Route::post(
        '/reports/quarterly/dilg-mc-2018-19/{office}/upload',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201819Controller::class, 'upload']
    )->name('reports.quarterly.dilg-mc-2018-19.upload');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-19/{office}/document/{docId}',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201819Controller::class, 'viewDocument']
    )->name('reports.quarterly.dilg-mc-2018-19.document');
    Route::post(
        '/reports/quarterly/dilg-mc-2018-19/{office}/approve/{docId}',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201819Controller::class, 'approveDocument']
    )->name('reports.quarterly.dilg-mc-2018-19.approve');
    Route::delete(
        '/reports/quarterly/dilg-mc-2018-19/{office}/document/{docId}',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201819Controller::class, 'deleteDocument']
    )->name('reports.quarterly.dilg-mc-2018-19.delete-document');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-19/{office}',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201819Controller::class, 'show']
    )->name('reports.quarterly.dilg-mc-2018-19.show');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-30',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201830Controller::class, 'index']
    )->name('reports.quarterly.dilg-mc-2018-30');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-30/{office}/edit/{quarter}',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201830Controller::class, 'edit']
    )->name('reports.quarterly.dilg-mc-2018-30.edit');
    Route::post(
        '/reports/quarterly/dilg-mc-2018-30/{office}/edit/{quarter}',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201830Controller::class, 'saveEncoding']
    )->name('reports.quarterly.dilg-mc-2018-30.save-encoding');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-30/{office}/edit/{quarter}/export',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201830Controller::class, 'exportEncoding']
    )->name('reports.quarterly.dilg-mc-2018-30.export-encoding');
    Route::post(
        '/reports/quarterly/dilg-mc-2018-30/{office}/upload',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201830Controller::class, 'upload']
    )->name('reports.quarterly.dilg-mc-2018-30.upload');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-30/{office}/document/{docId}',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201830Controller::class, 'viewDocument']
    )->name('reports.quarterly.dilg-mc-2018-30.document');
    Route::post(
        '/reports/quarterly/dilg-mc-2018-30/{office}/approve/{docId}',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201830Controller::class, 'approveDocument']
    )->name('reports.quarterly.dilg-mc-2018-30.approve');
    Route::delete(
        '/reports/quarterly/dilg-mc-2018-30/{office}/document/{docId}',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201830Controller::class, 'deleteDocument']
    )->name('reports.quarterly.dilg-mc-2018-30.delete-document');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-30/{office}',
        [App\Modules\ReportorialRequirements\Controllers\QuarterlyDilgMc201830Controller::class, 'show']
    )->name('reports.quarterly.dilg-mc-2018-30.show');
    Route::get('/reports/annual/rpmes/form-4', [App\Modules\ReportorialRequirements\Controllers\AnnualRpmesForm4Controller::class, 'index'])
        ->name('reports.annual.rpmes.form-4');
    Route::post('/reports/annual/rpmes/form-4/{projectCode}/upload', [App\Modules\ReportorialRequirements\Controllers\AnnualRpmesForm4Controller::class, 'upload'])
        ->name('reports.annual.rpmes.form-4.upload');
    Route::post('/reports/annual/rpmes/form-4/{projectCode}/approve/{quarter}', [App\Modules\ReportorialRequirements\Controllers\AnnualRpmesForm4Controller::class, 'approveDocument'])
        ->name('reports.annual.rpmes.form-4.approve');
    Route::get('/reports/annual/rpmes/form-4/{projectCode}/document/{quarter}', [App\Modules\ReportorialRequirements\Controllers\AnnualRpmesForm4Controller::class, 'viewDocument'])
        ->name('reports.annual.rpmes.form-4.document');
    Route::delete('/reports/annual/rpmes/form-4/{projectCode}/document/{quarter}', [App\Modules\ReportorialRequirements\Controllers\AnnualRpmesForm4Controller::class, 'deleteDocument'])
        ->name('reports.annual.rpmes.form-4.delete-document');
    Route::get('/reports/annual/rpmes/form-4/{projectCode}', [App\Modules\ReportorialRequirements\Controllers\AnnualRpmesForm4Controller::class, 'show'])
        ->name('reports.annual.rpmes.form-4.show');
    Route::get('/reports/annual/amwp', [App\Modules\ReportorialRequirements\Controllers\AnnualMaintenanceWorkProgramController::class, 'index'])
        ->name('reports.annual.amwp');
    Route::get('/reports/annual/amwp/{office}/edit', [App\Modules\ReportorialRequirements\Controllers\AnnualMaintenanceWorkProgramController::class, 'edit'])
        ->name('reports.annual.amwp.edit');
    Route::post('/reports/annual/amwp/{office}/upload', [App\Modules\ReportorialRequirements\Controllers\AnnualMaintenanceWorkProgramController::class, 'upload'])
        ->name('reports.annual.amwp.upload');
    Route::post('/reports/annual/amwp/{office}/approve/{docId}', [App\Modules\ReportorialRequirements\Controllers\AnnualMaintenanceWorkProgramController::class, 'approveDocument'])
        ->name('reports.annual.amwp.approve');
    Route::get('/reports/annual/amwp/{office}/document/{docId}', [App\Modules\ReportorialRequirements\Controllers\AnnualMaintenanceWorkProgramController::class, 'viewDocument'])
        ->name('reports.annual.amwp.document');
    Route::delete('/reports/annual/amwp/{office}/document/{docId}', [App\Modules\ReportorialRequirements\Controllers\AnnualMaintenanceWorkProgramController::class, 'deleteDocument'])
        ->name('reports.annual.amwp.delete-document');
    Route::get('/reports/one-time/confirmation-of-fund-receipt', [App\Modules\ReportorialRequirements\Controllers\ConfirmationOfFundReceiptController::class, 'index'])
        ->name('reports.one-time.confirmation-of-fund-receipt.index');
    Route::get('/reports/one-time/confirmation-of-fund-receipt/{office}', [App\Modules\ReportorialRequirements\Controllers\ConfirmationOfFundReceiptController::class, 'show'])
        ->name('reports.one-time.confirmation-of-fund-receipt.show');
    Route::post('/reports/one-time/confirmation-of-fund-receipt/{office}/accept/{docId}', [App\Modules\ReportorialRequirements\Controllers\ConfirmationOfFundReceiptController::class, 'acceptDocument'])
        ->name('reports.one-time.confirmation-of-fund-receipt.accept');
    Route::post('/reports/one-time/confirmation-of-fund-receipt/{office}/upload/{docId}', [App\Modules\ReportorialRequirements\Controllers\ConfirmationOfFundReceiptController::class, 'store'])
        ->name('reports.one-time.confirmation-of-fund-receipt.upload');
    Route::get('/reports/one-time/confirmation-of-fund-receipt/{office}/document/{docId}', [App\Modules\ReportorialRequirements\Controllers\ConfirmationOfFundReceiptController::class, 'viewDocument'])
        ->name('reports.one-time.confirmation-of-fund-receipt.document');
    Route::get('/reports/one-time/confirmation-of-fund-receipt/{office}/attachment/{attachmentId}', [App\Modules\ReportorialRequirements\Controllers\ConfirmationOfFundReceiptController::class, 'viewConfirmationDocument'])
        ->name('reports.one-time.confirmation-of-fund-receipt.attachment');
    Route::get('/reports/one-time/project-initial-documents', function () {
        return redirect()->route('initial-project-documents.index', request()->query());
    })->name('reports.one-time.project-initial-documents');
    Route::get('/reports/one-time/project-completion-reports/falgu-gef-sbdp', function () {
        return redirect()->route('initial-project-documents.index', request()->query());
    })->name('reports.one-time.project-completion-reports.falgu-gef-sbdp');
    Route::get('/reports/one-time/project-completion-reports/falgu-gef-sbdp/{projectCode}', function (string $projectCode) {
        return redirect()->route('initial-project-documents.show', array_merge(
            ['projectCode' => $projectCode],
            request()->query()
        ));
    })->name('reports.one-time.project-completion-reports.falgu-gef-sbdp.show');
    Route::get('/reports/one-time/project-completion-reports/safpb', function () {
        return redirect()->route('initial-project-documents.index', request()->query());
    })->name('reports.one-time.project-completion-reports.safpb');
    Route::get(
        '/reports/one-time/project-completion-reports/falgu-gef-sbdp/{projectCode}/document/{documentType}',
        [App\Modules\ReportorialRequirements\Controllers\LgsfProjectCompletionReportController::class, 'viewDocument']
    )->name('reports.one-time.project-completion-reports.falgu-gef-sbdp.document');
    Route::get(
        '/reports/one-time/project-completion-reports/falgu-gef-sbdp/{projectCode}/document-file/{fileId}',
        [App\Modules\ReportorialRequirements\Controllers\LgsfProjectCompletionReportController::class, 'viewDocumentFile']
    )->name('reports.one-time.project-completion-reports.falgu-gef-sbdp.document-file');
    Route::post(
        '/reports/one-time/project-completion-reports/falgu-gef-sbdp/{projectCode}',
        [App\Modules\ReportorialRequirements\Controllers\LgsfProjectCompletionReportController::class, 'save']
    )->name('reports.one-time.project-completion-reports.falgu-gef-sbdp.save');
    Route::post(
        '/reports/one-time/project-completion-reports/falgu-gef-sbdp/{projectCode}/upload/{documentType}',
        [App\Modules\ReportorialRequirements\Controllers\LgsfProjectCompletionReportController::class, 'uploadMultiDocument']
    )->name('reports.one-time.project-completion-reports.falgu-gef-sbdp.upload-multi');
    Route::post(
        '/reports/one-time/project-completion-reports/falgu-gef-sbdp/{projectCode}/validate/{documentType}',
        [App\Modules\ReportorialRequirements\Controllers\LgsfProjectCompletionReportController::class, 'validateDocument']
    )->name('reports.one-time.project-completion-reports.falgu-gef-sbdp.validate');
    Route::post(
        '/reports/one-time/project-completion-reports/falgu-gef-sbdp/{projectCode}/validate-file/{fileId}',
        [App\Modules\ReportorialRequirements\Controllers\LgsfProjectCompletionReportController::class, 'validateDocumentFile']
    )->name('reports.one-time.project-completion-reports.falgu-gef-sbdp.validate-file');
    Route::delete(
        '/reports/one-time/project-completion-reports/falgu-gef-sbdp/{projectCode}/document/{documentType}',
        [App\Modules\ReportorialRequirements\Controllers\LgsfProjectCompletionReportController::class, 'deleteDocument']
    )->name('reports.one-time.project-completion-reports.falgu-gef-sbdp.delete');
    Route::delete(
        '/reports/one-time/project-completion-reports/falgu-gef-sbdp/{projectCode}/document-file/{fileId}',
        [App\Modules\ReportorialRequirements\Controllers\LgsfProjectCompletionReportController::class, 'deleteDocumentFile']
    )->name('reports.one-time.project-completion-reports.falgu-gef-sbdp.delete-file');
    Route::get(
        '/reports/one-time/project-completion-reports/sglgif',
        function () {
            return redirect()->route('initial-project-documents.index', request()->query());
        }
    )->name('reports.one-time.project-completion-reports.sglgif');
    Route::get(
        '/reports/one-time/project-completion-reports/sglgif/{projectCode}',
        function (string $projectCode) {
            return redirect()->route('initial-project-documents.show', array_merge(
                ['projectCode' => $projectCode],
                request()->query()
            ));
        }
    )->name('reports.one-time.project-completion-reports.sglgif.show');
    Route::get(
        '/reports/one-time/project-completion-reports/sglgif/{projectCode}/document/{documentType}',
        [App\Modules\ReportorialRequirements\Controllers\SglgifProjectCompletionReportController::class, 'viewDocument']
    )->name('reports.one-time.project-completion-reports.sglgif.document');
    Route::get(
        '/reports/one-time/project-completion-reports/sglgif/{projectCode}/document-file/{fileId}',
        [App\Modules\ReportorialRequirements\Controllers\SglgifProjectCompletionReportController::class, 'viewDocumentFile']
    )->name('reports.one-time.project-completion-reports.sglgif.document-file');
    Route::post(
        '/reports/one-time/project-completion-reports/sglgif/{projectCode}',
        [App\Modules\ReportorialRequirements\Controllers\SglgifProjectCompletionReportController::class, 'save']
    )->name('reports.one-time.project-completion-reports.sglgif.save');
    Route::post(
        '/reports/one-time/project-completion-reports/sglgif/{projectCode}/upload/{documentType}',
        [App\Modules\ReportorialRequirements\Controllers\SglgifProjectCompletionReportController::class, 'uploadMultiDocument']
    )->name('reports.one-time.project-completion-reports.sglgif.upload-multi');
    Route::post(
        '/reports/one-time/project-completion-reports/sglgif/{projectCode}/validate/{documentType}',
        [App\Modules\ReportorialRequirements\Controllers\SglgifProjectCompletionReportController::class, 'validateDocument']
    )->name('reports.one-time.project-completion-reports.sglgif.validate');
    Route::post(
        '/reports/one-time/project-completion-reports/sglgif/{projectCode}/validate-file/{fileId}',
        [App\Modules\ReportorialRequirements\Controllers\SglgifProjectCompletionReportController::class, 'validateDocumentFile']
    )->name('reports.one-time.project-completion-reports.sglgif.validate-file');
    Route::delete(
        '/reports/one-time/project-completion-reports/sglgif/{projectCode}/document/{documentType}',
        [App\Modules\ReportorialRequirements\Controllers\SglgifProjectCompletionReportController::class, 'deleteDocument']
    )->name('reports.one-time.project-completion-reports.sglgif.delete');
    Route::delete(
        '/reports/one-time/project-completion-reports/sglgif/{projectCode}/document-file/{fileId}',
        [App\Modules\ReportorialRequirements\Controllers\SglgifProjectCompletionReportController::class, 'deleteDocumentFile']
    )->name('reports.one-time.project-completion-reports.sglgif.delete-file');
    Route::get('/reports/one-time/pisat', [App\Modules\ReportorialRequirements\Controllers\PisatReportController::class, 'index'])
        ->middleware('crud_permission:pisat_reports,view')
        ->name('reports.one-time.pisat');
    Route::get('/reports/one-time/pisat/create', [App\Modules\ReportorialRequirements\Controllers\PisatReportController::class, 'create'])
        ->middleware('crud_permission:pisat_reports,add')
        ->name('reports.one-time.pisat.create');
    Route::post('/reports/one-time/pisat', [App\Modules\ReportorialRequirements\Controllers\PisatReportController::class, 'store'])
        ->middleware('crud_permission:pisat_reports,add')
        ->name('reports.one-time.pisat.store');
    Route::get('/reports/one-time/pisat/{assessment}', [App\Modules\ReportorialRequirements\Controllers\PisatReportController::class, 'show'])
        ->middleware('crud_permission:pisat_reports,view')
        ->name('reports.one-time.pisat.show');
    Route::get('/reports/one-time/pisat/{assessment}/edit', [App\Modules\ReportorialRequirements\Controllers\PisatReportController::class, 'edit'])
        ->middleware('crud_permission:pisat_reports,update')
        ->name('reports.one-time.pisat.edit');
    Route::put('/reports/one-time/pisat/{assessment}', [App\Modules\ReportorialRequirements\Controllers\PisatReportController::class, 'update'])
        ->middleware('crud_permission:pisat_reports,update')
        ->name('reports.one-time.pisat.update');
    Route::post('/reports/one-time/pisat/{assessment}/validate', [App\Modules\ReportorialRequirements\Controllers\PisatReportController::class, 'validateAssessment'])
        ->middleware('crud_permission:pisat_reports,update')
        ->name('reports.one-time.pisat.validate');

    Route::get('/reports/rbis-annual-certification', [App\Modules\ReportorialRequirements\Controllers\RbisAnnualCertificationController::class, 'index'])
        ->name('rbis-annual-certification.index');
    Route::get('/reports/rbis-annual-certification/{office}/edit', [App\Modules\ReportorialRequirements\Controllers\RbisAnnualCertificationController::class, 'edit'])
        ->name('rbis-annual-certification.edit');
    Route::post('/reports/rbis-annual-certification/{office}/upload', [App\Modules\ReportorialRequirements\Controllers\RbisAnnualCertificationController::class, 'upload'])
        ->name('rbis-annual-certification.upload');
    Route::post('/reports/rbis-annual-certification/{office}/approve/{docId}', [App\Modules\ReportorialRequirements\Controllers\RbisAnnualCertificationController::class, 'approveDocument'])
        ->name('rbis-annual-certification.approve');
    Route::get('/reports/rbis-annual-certification/{office}/document/{docId}', [App\Modules\ReportorialRequirements\Controllers\RbisAnnualCertificationController::class, 'viewDocument'])
        ->name('rbis-annual-certification.document');
    Route::delete('/reports/rbis-annual-certification/{office}/document/{docId}', [App\Modules\ReportorialRequirements\Controllers\RbisAnnualCertificationController::class, 'deleteDocument'])
        ->name('rbis-annual-certification.delete-document');
});
