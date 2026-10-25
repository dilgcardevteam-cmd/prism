<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Modules\FundUtilization\Controllers\FundUtilizationReportController;

Route::middleware(['auth'])->group(function () {
    // Fund Utilization Report routes
    Route::prefix('fund-utilization')->group(function () {
        $redirectLegacyFundUtilizationAction = function (Request $request, string $projectCode, string $defaultDocument) {
            $quarter = trim((string) $request->query('quarter', ''));
            $document = trim((string) $request->query('document', ''));
            $refererQuery = [];
            $referer = trim((string) $request->headers->get('referer', ''));

            if ($referer !== '') {
                $refererQueryString = parse_url($referer, PHP_URL_QUERY);
                if (is_string($refererQueryString) && $refererQueryString !== '') {
                    parse_str($refererQueryString, $refererQuery);
                }
            }

            if ($quarter === '') {
                $quarter = trim((string) ($refererQuery['quarter'] ?? ''));
            }

            if ($document === '') {
                $document = trim((string) ($refererQuery['document'] ?? ''));
            }

            if ($document === '') {
                $document = $defaultDocument;
            }

            $redirectParameters = ['projectCode' => $projectCode];

            if ($quarter !== '') {
                $redirectParameters['quarter'] = $quarter;
            }

            if ($document !== '') {
                $redirectParameters['document'] = $document;
            }

            return redirect()->route('fund-utilization.show', $redirectParameters);
        };

        Route::get('/', [FundUtilizationReportController::class, 'index'])->name('fund-utilization.index');
        Route::get('/export', [FundUtilizationReportController::class, 'export'])->name('fund-utilization.export');
        Route::get('/create', [FundUtilizationReportController::class, 'create'])->name('fund-utilization.create');
        Route::get('/get-municipalities/{province}', [FundUtilizationReportController::class, 'getMunicipalities'])->name('fund-utilization.get-municipalities');
        Route::post('/', [FundUtilizationReportController::class, 'store'])->name('fund-utilization.store');
        Route::get('/batch-upload-documents', function (Request $request) {
            $redirectParameters = ['batch_upload' => 1];

            $perPage = trim((string) $request->query('per_page', ''));
            if ($perPage !== '') {
                $redirectParameters['per_page'] = $perPage;
            }

            return redirect()->route('fund-utilization.index', $redirectParameters);
        })->name('fund-utilization.batch-upload-documents.get');
        Route::post('/batch-upload-documents', [FundUtilizationReportController::class, 'uploadBatchDocumentsBulk'])->name('fund-utilization.batch-upload-documents');
        Route::post('/batch-upload-individual-documents', [FundUtilizationReportController::class, 'uploadIndividualDocumentsBulk'])->name('fund-utilization.batch-upload-individual-documents');

        // Batch Approvals
        Route::get('/batch-approvals/{batchCode}', [\App\Modules\FundUtilization\Controllers\FundUtilizationBatchApprovalController::class, 'show'])->name('fund-utilization.batch-approval.show');
        Route::post('/batch-approvals/{batchCode}/document-validation', [\App\Modules\FundUtilization\Controllers\FundUtilizationBatchApprovalController::class, 'validateDocument'])->name('fund-utilization.batch-approval.document-validation');
        Route::post('/batch-approvals/{batchCode}/approve', [\App\Modules\FundUtilization\Controllers\FundUtilizationBatchApprovalController::class, 'approve'])->name('fund-utilization.batch-approval.approve');
        Route::post('/batch-approvals/{batchCode}/return', [\App\Modules\FundUtilization\Controllers\FundUtilizationBatchApprovalController::class, 'returnBatch'])->name('fund-utilization.batch-approval.return');

        Route::get('/{projectCode}', [FundUtilizationReportController::class, 'show'])->name('fund-utilization.show');
        Route::get('/{projectCode}/edit', [FundUtilizationReportController::class, 'edit'])->name('fund-utilization.edit');
        Route::put('/{projectCode}', [FundUtilizationReportController::class, 'update'])->name('fund-utilization.update');
        Route::delete('/{projectCode}', [FundUtilizationReportController::class, 'deleteProject'])->name('fund-utilization.delete-project');
        Route::get('/{projectCode}/upload-mov', fn (Request $request, string $projectCode) => $redirectLegacyFundUtilizationAction($request, $projectCode, 'mov'))->name('fund-utilization.upload-mov.get');
        Route::get('/{projectCode}/upload-batch-document', fn (Request $request, string $projectCode) => $redirectLegacyFundUtilizationAction($request, $projectCode, 'batch-document'))->name('fund-utilization.upload-batch-document.get');
        Route::get('/{projectCode}/upload-written-notice', fn (Request $request, string $projectCode) => $redirectLegacyFundUtilizationAction($request, $projectCode, 'written-notice-dbm'))->name('fund-utilization.upload-written-notice.get');
        Route::get('/{projectCode}/upload-fdp', fn (Request $request, string $projectCode) => $redirectLegacyFundUtilizationAction($request, $projectCode, 'fdp'))->name('fund-utilization.upload-fdp.get');
        Route::get('/{projectCode}/save-posting-link', fn (Request $request, string $projectCode) => $redirectLegacyFundUtilizationAction($request, $projectCode, 'posting-link'))->name('fund-utilization.save-posting-link.get');
        Route::post('/{projectCode}/upload-mov', [FundUtilizationReportController::class, 'uploadMOV'])->name('fund-utilization.upload-mov');
        Route::post('/{projectCode}/upload-batch-document', [FundUtilizationReportController::class, 'uploadBatchDocument'])->name('fund-utilization.upload-batch-document');
        Route::post('/{projectCode}/upload-written-notice', [FundUtilizationReportController::class, 'uploadWrittenNotice'])->name('fund-utilization.upload-written-notice');
        Route::post('/{projectCode}/upload-fdp', [FundUtilizationReportController::class, 'uploadFDP'])->name('fund-utilization.upload-fdp');
        Route::post('/{projectCode}/save-posting-link', [FundUtilizationReportController::class, 'savePostingLink'])->name('fund-utilization.save-posting-link');
        Route::post('/{projectCode}/add-remark', [FundUtilizationReportController::class, 'addRemark'])->name('fund-utilization.add-remark');
        Route::get('/{projectCode}/approve/{uploadType}/{quarter}', function (string $projectCode, string $uploadType, string $quarter) {
            return redirect()
                ->route('fund-utilization.show', ['projectCode' => $projectCode])
                ->with('error', 'Approval actions must be submitted from the document approval form.');
        })->name('fund-utilization.approve-upload.get');
        Route::post('/{projectCode}/approve/{uploadType}/{quarter}', [FundUtilizationReportController::class, 'approveUpload'])->name('fund-utilization.approve-upload');
        Route::post('/{projectCode}/request-resubmission/{uploadType}/{quarter}', [FundUtilizationReportController::class, 'requestResubmission'])->name('fund-utilization.request-resubmission');
        Route::post('/{projectCode}/resubmission-request/{uploadType}/{quarter}', [FundUtilizationReportController::class, 'decideResubmissionRequest'])->name('fund-utilization.decide-resubmission-request');
        Route::post('/{projectCode}/save-remarks/{uploadType}/{quarter}', [FundUtilizationReportController::class, 'saveUserRemarks'])->name('fund-utilization.save-remarks');
        Route::get('/{projectCode}/view-document/{docType}/{quarter}', [FundUtilizationReportController::class, 'viewDocument'])->name('fund-utilization.view-document');
        Route::post('/{projectCode}/delete-document/{docType}/{quarter}', [FundUtilizationReportController::class, 'deleteDocument'])->name('fund-utilization.delete-document');
    });
});
