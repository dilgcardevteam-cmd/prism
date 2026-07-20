<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $user = \App\Models\User::first();
    \Illuminate\Support\Facades\Auth::login($user);
    
    $controller = app(\App\Http\Controllers\FundUtilizationReportController::class);
    
    // Create request with validation data manually assigned to bypass form request issues in CLI
    $req = \Illuminate\Http\Request::create('/test', 'POST', [
        'action' => 'approve'
    ]);
    
    $actionReq = \App\Http\Requests\FundUtilizationApprovalActionRequest::createFrom($req);
    $actionReq->setValidator(\Illuminate\Support\Facades\Validator::make(['action' => 'approve'], ['action' => 'required']));
    
    // Simulate what happens in approveUpload
    $workflowService = app(\App\Services\FundUtilizationWorkflowService::class);
    $res = $controller->approveUpload($actionReq, 'FA-LA-24-14-44-01-000-1', 'mov', 'Q1', $workflowService);
    
    echo "Result:\n";
    echo $res->getContent();
} catch (\Throwable $e) {
    echo "EXCEPTION:\n";
    echo $e->getMessage() . "\n";
    echo $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString();
}
