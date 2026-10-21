<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Modules\DilgDeliverables\Controllers\MonitoringEvaluationMonthlyReportController;

Route::middleware(['auth'])->group(function () {
    $assertDilgDeliverablesAccess = function (): void {
        $user = Auth::user();

        $canAccessDilgDeliverables = $user && $user->canAccessDilgDeliverables();

        abort_unless($canAccessDilgDeliverables, 403);
    };

    $assertDilgDeliverablesPermission = function (string $aspect) use ($assertDilgDeliverablesAccess): void {
        $assertDilgDeliverablesAccess();

        $user = Auth::user();

        abort_unless($user && $user->hasCrudPermission($aspect, 'view'), 403);
    };

    Route::prefix('/reports/dilg-deliverables')->group(function () use ($assertDilgDeliverablesAccess, $assertDilgDeliverablesPermission) {
        Route::get('/', function () use ($assertDilgDeliverablesAccess) {
            $assertDilgDeliverablesAccess();

            $aspects = [
                'dilg_deliverables_monitoring_evaluation',
                'dilg_deliverables_rlip_lime_monthly',
                'dilg_deliverables_qaar_tool_monitoring',
            ];

            $hasAnyPermission = collect($aspects)->contains(function (string $aspect) {
                $user = Auth::user();

                return $user && $user->hasCrudPermission($aspect, 'view');
            });

            abort_unless($hasAnyPermission, 403);

            return view('reports.dilg-deliverables.index');
        })->name('reports.dilg-deliverables');

        // Legacy Redirect
        Route::get('/monitoring-and-evaluation-reports', function() {
            return redirect()->route('reports.dilg-deliverables.monitoring-evaluation.lfp');
        })->name('reports.dilg-deliverables.monitoring-evaluation');

        // LFP M&E Reports
        Route::prefix('monitoring-and-evaluation-reports/lfp')->group(function () {
            Route::get('/', [MonitoringEvaluationMonthlyReportController::class, 'indexLfp'])->name('reports.dilg-deliverables.monitoring-evaluation.lfp');
            Route::get('/export', [MonitoringEvaluationMonthlyReportController::class, 'exportLfp'])->name('reports.dilg-deliverables.monitoring-evaluation.lfp.export');
            Route::get('/{office}/edit', [MonitoringEvaluationMonthlyReportController::class, 'editLfp'])->name('reports.dilg-deliverables.monitoring-evaluation.lfp.edit');
            Route::post('/{office}/upload', [MonitoringEvaluationMonthlyReportController::class, 'uploadLfp'])->name('reports.dilg-deliverables.monitoring-evaluation.lfp.upload');
            Route::post('/{office}/approve/{docId}', [MonitoringEvaluationMonthlyReportController::class, 'approveDocumentLfp'])->name('reports.dilg-deliverables.monitoring-evaluation.lfp.approve');
            Route::get('/{office}/document/{docId}', [MonitoringEvaluationMonthlyReportController::class, 'viewDocumentLfp'])->name('reports.dilg-deliverables.monitoring-evaluation.lfp.view-document');
            Route::delete('/{office}/document/{docId}', [MonitoringEvaluationMonthlyReportController::class, 'deleteDocumentLfp'])->name('reports.dilg-deliverables.monitoring-evaluation.lfp.delete');
            Route::post('/{office}/request-deletion/{docId}', [MonitoringEvaluationMonthlyReportController::class, 'requestDeletionLfp'])->name('reports.dilg-deliverables.monitoring-evaluation.lfp.request-deletion');
            Route::post('/{office}/decide-deletion/{docId}', [MonitoringEvaluationMonthlyReportController::class, 'decideDeletionLfp'])->name('reports.dilg-deliverables.monitoring-evaluation.lfp.decide-deletion');
        });

        // RLIP/LIME M&E Reports
        Route::prefix('monitoring-and-evaluation-reports/rlip-lime')->group(function () {
            Route::get('/', [MonitoringEvaluationMonthlyReportController::class, 'indexRlipLime'])->name('reports.dilg-deliverables.monitoring-evaluation.rlip-lime');
            Route::get('/export', [MonitoringEvaluationMonthlyReportController::class, 'exportRlipLime'])->name('reports.dilg-deliverables.monitoring-evaluation.rlip-lime.export');
            Route::get('/{office}/edit', [MonitoringEvaluationMonthlyReportController::class, 'editRlipLime'])->name('reports.dilg-deliverables.monitoring-evaluation.rlip-lime.edit');
            Route::post('/{office}/upload', [MonitoringEvaluationMonthlyReportController::class, 'uploadRlipLime'])->name('reports.dilg-deliverables.monitoring-evaluation.rlip-lime.upload');
            Route::post('/{office}/approve/{docId}', [MonitoringEvaluationMonthlyReportController::class, 'approveDocumentRlipLime'])->name('reports.dilg-deliverables.monitoring-evaluation.rlip-lime.approve');
            Route::get('/{office}/document/{docId}', [MonitoringEvaluationMonthlyReportController::class, 'viewDocumentRlipLime'])->name('reports.dilg-deliverables.monitoring-evaluation.rlip-lime.view-document');
            Route::delete('/{office}/document/{docId}', [MonitoringEvaluationMonthlyReportController::class, 'deleteDocumentRlipLime'])->name('reports.dilg-deliverables.monitoring-evaluation.rlip-lime.delete');
            Route::post('/{office}/request-deletion/{docId}', [MonitoringEvaluationMonthlyReportController::class, 'requestDeletionRlipLime'])->name('reports.dilg-deliverables.monitoring-evaluation.rlip-lime.request-deletion');
            Route::post('/{office}/decide-deletion/{docId}', [MonitoringEvaluationMonthlyReportController::class, 'decideDeletionRlipLime'])->name('reports.dilg-deliverables.monitoring-evaluation.rlip-lime.decide-deletion');
        });

        Route::get('/rlip-lime-monthly-reports', function () use ($assertDilgDeliverablesPermission) {
            $assertDilgDeliverablesPermission('dilg_deliverables_rlip_lime_monthly');

            return view('reports.dilg-deliverables.rlip-lime-monthly-reports');
        })->name('reports.dilg-deliverables.rlip-lime-monthly');

        Route::get('/qaar-tool-and-monitoring-report', function () use ($assertDilgDeliverablesPermission) {
            $assertDilgDeliverablesPermission('dilg_deliverables_qaar_tool_monitoring');

            return view('reports.dilg-deliverables.qaar-tool-and-monitoring-report');
        })->name('reports.dilg-deliverables.qaar-tool-monitoring');
    });
});

