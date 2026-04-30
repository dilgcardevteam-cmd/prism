<?php

namespace App\Http\Controllers;

use App\Models\QuarterlyRpmesForm2Upload;
use App\Services\SecureTimestampService;
use App\Support\InputSanitizer;
use App\Support\LguReportorialDeadlineResolver;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class QuarterlyRpmesForm2Controller extends AbstractQuarterlyRpmesFormController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function formConfig(): array
    {
        return [
            'page_title' => 'Quarterly RPMES Form 2',
            'page_heading' => 'RPMES FORM 2 : Physical and Financial Accomplishment Report',
            'page_subtitle' => 'Physical and Financial Accomplishment Report',
            'project_view_heading' => 'Project View',
            'project_view_description' => 'SBDP project details, quarterly uploads, and DILG validation workflow for RPMES Form 2.',
            'list_heading' => 'RPMES FORM 2 : Physical and Financial Accomplishment Report',
            'list_title_badge' => 'SBDP Project List',
            'list_description' => 'All accessible SBDP projects are listed below, ordered by latest funding year first.',
            'report_short_label' => 'RPMES FORM 2',
            'report_short_name' => 'RPMES Form 2',
            'report_full_title' => 'Physical and Financial Accomplishment Report',
            'submission_heading' => 'Submission of Physical and Financial Accomplishment Report (RPMES FORM 2)',
            'submission_description' => 'Each quarter supports one report upload plus DILG Provincial Office and DILG Regional Office validation.',
            'acceptance_note' => 'Accepted files: PDF, JPG, JPEG, PNG. Maximum size: 10 MB.',
            'permission_aspect' => 'quarterly_rpmes_form_2',
            'deadline_aspect' => 'quarterly_rpmes_form_2',
            'upload_table' => 'quarterly_rpmes_form2_uploads',
            'model_class' => QuarterlyRpmesForm2Upload::class,
            'storage_directory' => 'rpmes-form-2',
            'timestamp_log_key' => 'rpmes-form-2',
            'allowed_fund_sources' => ['SBDP'],
            'index_route' => 'reports.quarterly.rpmes.form-2',
            'show_route' => 'reports.quarterly.rpmes.form-2.show',
            'upload_route' => 'reports.quarterly.rpmes.form-2.upload',
            'approve_route' => 'reports.quarterly.rpmes.form-2.approve',
            'document_route' => 'reports.quarterly.rpmes.form-2.document',
            'delete_route' => 'reports.quarterly.rpmes.form-2.delete-document',
        ];
    }

    public function index(Request $request)
    {
        return parent::index($request);
    }

    public function show(Request $request, string $projectCode)
    {
        $user = Auth::user();
        abort_unless($this->userCanAccessReport($user), 403);

        if (!Schema::hasTable('subay_project_profiles')) {
            abort(404);
        }

        $project = $this->resolveProjectForUser($projectCode, $user);
        abort_if(!$project, 404);

        $quarters = $this->quarters();
        $selectedQuarter = $this->normalizeQuarter($request->query('quarter', 'Q1'));
        $uploadsByQuarter = array_fill_keys(array_keys($quarters), null);
        $isProvincialDilgViewer = $this->isProvincialDilgUser($user);
        $isRegionalDilgViewer = (bool) ($user && $user->isRegionalOfficeAssignment());
        $deadlineReportingYear = $this->rpmesForm2DeadlineReportingYear();
        $configuredQuarterDeadlines = app(LguReportorialDeadlineResolver::class)->resolveMany(
            'quarterly_rpmes_form_2',
            $deadlineReportingYear,
            array_keys($quarters)
        );

        if (Schema::hasTable('quarterly_rpmes_form2_uploads')) {
            $uploads = QuarterlyRpmesForm2Upload::with([
                    'uploader:idno,fname,lname',
                    'approver:idno,fname,lname',
                    'dilgPoApprover:idno,fname,lname',
                    'dilgRoApprover:idno,fname,lname',
                ])
                ->where('project_code', $project->project_code)
                ->whereIn('quarter', array_keys($quarters))
                ->get()
                ->keyBy('quarter');

            foreach ($uploadsByQuarter as $quarterCode => $value) {
                $uploadsByQuarter[$quarterCode] = $uploads->get($quarterCode);
            }
        }

        return view('reports.quarterly.rpmes.form-2.show', compact(
            'project',
            'quarters',
            'selectedQuarter',
            'uploadsByQuarter',
            'isProvincialDilgViewer',
            'isRegionalDilgViewer',
            'deadlineReportingYear',
            'configuredQuarterDeadlines'
        ));
    }

    public function upload(Request $request, string $projectCode)
    {
        $user = Auth::user();
        abort_unless($this->userCanAccessReport($user), 403);

        $project = $this->resolveProjectForUser($projectCode, $user);
        abort_if(!$project, 404);

        abort_unless(Schema::hasTable('quarterly_rpmes_form2_uploads'), 500, 'RPMES Form 2 uploads table is not available.');

        if ($user && $user->isRegionalOfficeAssignment()) {
            return redirect()
                ->route('reports.quarterly.rpmes.form-2.show', [
                    'projectCode' => $project->project_code,
                    'quarter' => $this->normalizeQuarter($request->input('quarter', 'Q1')),
                ])
                ->withErrors(['report_file' => 'DILG Regional Office cannot upload RPMES Form 2 reports.']);
        }

        $validated = $request->validate([
            'quarter' => ['required', 'in:Q1,Q2,Q3,Q4'],
            'report_file' => ['required', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $quarter = $validated['quarter'];
        $autoElevateToRegional = $this->isProvincialDilgUser($user);
        $existingUpload = QuarterlyRpmesForm2Upload::query()
            ->where('project_code', $project->project_code)
            ->where('quarter', $quarter)
            ->first();

        if ($existingUpload && $existingUpload->file_path && $existingUpload->status !== 'returned') {
            return redirect()
                ->route('reports.quarterly.rpmes.form-2.show', [
                    'projectCode' => $project->project_code,
                    'quarter' => $quarter,
                ])
                ->withErrors(['report_file' => 'A RPMES Form 2 report already exists for this quarter. Use the current submission flow before replacing it.']);
        }

        $oldFilePath = $existingUpload?->file_path;
        $file = $request->file('report_file');
        $path = $file->store('rpmes-form-2/' . $project->project_code . '/' . $quarter, 'public');
        $secureTimestamp = SecureTimestampService::getUploadTimestamp();
        $actorId = $user->idno ?? auth()->id();

        $upload = $existingUpload ?? new QuarterlyRpmesForm2Upload();
        $upload->timestamps = false;

        if (!$upload->exists) {
            $upload->project_code = $project->project_code;
            $upload->quarter = $quarter;
            $upload->created_at = $secureTimestamp;
        }

        $upload->file_path = $path;
        $upload->original_name = $file->getClientOriginalName();
        $upload->uploaded_by = $actorId;
        $upload->uploaded_at = $secureTimestamp;
        $upload->status = $autoElevateToRegional ? 'pending_ro' : 'pending';
        $upload->approved_by = $autoElevateToRegional ? $actorId : null;
        $upload->approved_at = $autoElevateToRegional ? $secureTimestamp : null;
        $upload->approved_at_dilg_po = $autoElevateToRegional ? $secureTimestamp : null;
        $upload->approved_at_dilg_ro = null;
        $upload->approved_by_dilg_po = $autoElevateToRegional ? $actorId : null;
        $upload->approved_by_dilg_ro = null;
        $upload->approval_remarks = null;
        $upload->user_remarks = null;
        $upload->updated_at = $secureTimestamp;
        $upload->save();

        if ($oldFilePath && $oldFilePath !== $path && Storage::disk('public')->exists($oldFilePath)) {
            Storage::disk('public')->delete($oldFilePath);
        }

        SecureTimestampService::logUploadTimestamp('rpmes-form-2', $project->project_code, $quarter, $secureTimestamp);

        $message = $autoElevateToRegional
            ? 'RPMES Form 2 uploaded and validated by DILG Provincial Office. It is now pending DILG Regional Office validation.'
            : 'RPMES Form 2 report uploaded successfully.';

        return redirect()
            ->route('reports.quarterly.rpmes.form-2.show', [
                'projectCode' => $project->project_code,
                'quarter' => $quarter,
            ])
            ->with('success', $message);
    }

    public function approveDocument(Request $request, string $projectCode, string $quarter)
    {
        $user = Auth::user();
        abort_unless($this->userCanApproveReport($user), 403);

        $project = $this->resolveProjectForUser($projectCode, $user);
        abort_if(!$project, 404);

        abort_unless(Schema::hasTable('quarterly_rpmes_form2_uploads'), 404);

        $quarter = $this->normalizeQuarter($quarter);
        $validated = $request->validate([
            'action' => ['required', 'in:approve,return'],
            'remarks' => ['required_if:action,return', 'nullable', 'string', 'max:1000'],
        ]);

        $upload = QuarterlyRpmesForm2Upload::query()
            ->where('project_code', $project->project_code)
            ->where('quarter', $quarter)
            ->first();

        if (!$upload || !$upload->file_path) {
            return redirect()
                ->route('reports.quarterly.rpmes.form-2.show', [
                    'projectCode' => $project->project_code,
                    'quarter' => $quarter,
                ])
                ->withErrors(['approval' => 'No uploaded RPMES Form 2 report was found for the selected quarter.']);
        }

        $action = $validated['action'];
        $remarks = InputSanitizer::sanitizeNullablePlainText($validated['remarks'] ?? null, true);

        if ($action === 'return' && $remarks === null) {
            return redirect()
                ->route('reports.quarterly.rpmes.form-2.show', [
                    'projectCode' => $project->project_code,
                    'quarter' => $quarter,
                ])
                ->withErrors(['remarks' => 'Return remarks must contain plain text.']);
        }

        $isProvincialOffice = $this->isProvincialDilgUser($user);
        $isRegionalOffice = (bool) ($user && $user->isRegionalOfficeAssignment());

        if (!$isProvincialOffice && !$isRegionalOffice) {
            abort(403);
        }

        if ($isProvincialOffice && $upload->status !== 'pending') {
            return redirect()
                ->route('reports.quarterly.rpmes.form-2.show', [
                    'projectCode' => $project->project_code,
                    'quarter' => $quarter,
                ])
                ->withErrors(['approval' => 'This quarter is not awaiting DILG Provincial Office validation.']);
        }

        if ($isRegionalOffice && $upload->status !== 'pending_ro') {
            return redirect()
                ->route('reports.quarterly.rpmes.form-2.show', [
                    'projectCode' => $project->project_code,
                    'quarter' => $quarter,
                ])
                ->withErrors(['approval' => 'This quarter is not awaiting DILG Regional Office validation.']);
        }

        $now = SecureTimestampService::getUploadTimestamp();
        $actorId = $user->idno ?? auth()->id();

        $upload->timestamps = false;
        $upload->approved_at = $now;
        $upload->approved_by = $actorId;
        $upload->updated_at = $now;

        if ($action === 'approve') {
            if ($isProvincialOffice) {
                $upload->approved_at_dilg_po = $now;
                $upload->approved_by_dilg_po = $actorId;
                $upload->approved_at_dilg_ro = null;
                $upload->approved_by_dilg_ro = null;
                $upload->status = 'pending_ro';
                $upload->approval_remarks = null;
                $upload->user_remarks = null;
                $message = 'RPMES Form 2 validated by DILG Provincial Office and elevated for DILG Regional Office validation.';
            } else {
                $upload->approved_at_dilg_ro = $now;
                $upload->approved_by_dilg_ro = $actorId;
                $upload->status = 'approved';
                $upload->approval_remarks = null;
                $upload->user_remarks = null;
                $message = 'RPMES Form 2 approved by DILG Regional Office.';
            }
        } else {
            if ($isRegionalOffice) {
                $upload->approved_at_dilg_ro = null;
                $upload->approved_by_dilg_ro = $actorId;
            } else {
                $upload->approved_by_dilg_po = $actorId;
            }

            $upload->status = 'returned';
            $upload->approval_remarks = $remarks;
            $upload->user_remarks = $remarks;
            $message = 'RPMES Form 2 returned with remarks.';
        }

        $upload->save();

        return redirect()
            ->route('reports.quarterly.rpmes.form-2.show', [
                'projectCode' => $project->project_code,
                'quarter' => $quarter,
            ])
            ->with('success', $message);
    }

    public function viewDocument(string $projectCode, string $quarter)
    {
        $user = Auth::user();
        abort_unless($this->userCanAccessReport($user), 403);

        $project = $this->resolveProjectForUser($projectCode, $user);
        abort_if(!$project, 404);

        abort_unless(Schema::hasTable('quarterly_rpmes_form2_uploads'), 404);

        $quarter = $this->normalizeQuarter($quarter);
        $upload = QuarterlyRpmesForm2Upload::query()
            ->where('project_code', $project->project_code)
            ->where('quarter', $quarter)
            ->first();

        if (!$upload || !$upload->file_path || !Storage::disk('public')->exists($upload->file_path)) {
            abort(404, 'Document not found.');
        }

        $filePath = Storage::disk('public')->path($upload->file_path);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $inlineExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        $mimeType = @mime_content_type($filePath) ?: 'application/octet-stream';
        $headers = [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ];

        if (!in_array($extension, $inlineExtensions, true)) {
            return response()->download($filePath, $upload->original_name ?: basename($filePath), $headers);
        }

        return response()->file($filePath, $headers);
    }

    public function deleteDocument(string $projectCode, string $quarter)
    {
        $user = Auth::user();
        abort_unless($this->userCanAccessReport($user), 403);

        $project = $this->resolveProjectForUser($projectCode, $user);
        abort_if(!$project, 404);

        abort_unless(Schema::hasTable('quarterly_rpmes_form2_uploads'), 404);

        $quarter = $this->normalizeQuarter($quarter);
        $upload = QuarterlyRpmesForm2Upload::query()
            ->where('project_code', $project->project_code)
            ->where('quarter', $quarter)
            ->first();

        if (!$upload) {
            return redirect()
                ->route('reports.quarterly.rpmes.form-2.show', [
                    'projectCode' => $project->project_code,
                    'quarter' => $quarter,
                ])
                ->withErrors(['report_file' => 'No uploaded RPMES Form 2 report was found for the selected quarter.']);
        }

        if (in_array((string) $upload->status, ['pending_ro', 'approved'], true)) {
            return redirect()
                ->route('reports.quarterly.rpmes.form-2.show', [
                    'projectCode' => $project->project_code,
                    'quarter' => $quarter,
                ])
                ->withErrors(['report_file' => 'This RPMES Form 2 report can no longer be deleted after DILG validation has started.']);
        }

        if ($upload->file_path && Storage::disk('public')->exists($upload->file_path)) {
            Storage::disk('public')->delete($upload->file_path);
        }

        $upload->delete();

        return redirect()
            ->route('reports.quarterly.rpmes.form-2.show', [
                'projectCode' => $project->project_code,
                'quarter' => $quarter,
            ])
            ->with('success', 'RPMES Form 2 report deleted successfully.');
    }

    protected function userCanAccessReport($user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasCrudPermission('fund_utilization_reports', 'view')
            || $user->hasCrudPermission('local_project_monitoring_committee', 'view')
            || $user->hasCrudPermission('road_maintenance_status_reports', 'view')
            || $user->hasCrudPermission('quarterly_rpmes_form_2', 'view');
    }

    protected function userCanApproveReport($user): bool
    {
        if (!$this->userCanAccessReport($user) || !$user || !$user->isDilgUser()) {
            return false;
        }

        return $this->isProvincialDilgUser($user) || $user->isRegionalOfficeAssignment();
    }

    protected function isProvincialDilgUser($user): bool
    {
        if (!$user) {
            return false;
        }

        $agency = strtoupper(trim((string) $user->agency));
        if ($agency !== 'DILG') {
            return false;
        }

        $provinceLower = strtolower(trim((string) $user->province));
        return $provinceLower !== '' && $provinceLower !== 'regional office';
    }

    protected function buildAccessibleSubayQuery($user)
    {
        return parent::buildAccessibleSubayQuery($user);
    }

    protected function resolveProjectForUser(string $projectCode, $user): ?object
    {
        $projectCode = trim($projectCode);
        if ($projectCode === '') {
            return null;
        }

        return $this->buildAccessibleSubayQuery($user)
            ->where('spp.project_code', $projectCode)
            ->select([
                'spp.project_code',
                'spp.project_title',
                'spp.city_municipality',
                'spp.province',
                'spp.barangay',
                'spp.region',
                'spp.funding_year',
                'spp.status',
                'spp.program',
                'spp.type',
                'spp.type_of_project',
                'spp.sub_type_of_project',
                'spp.exact_location',
                'spp.project_description',
                DB::raw($this->fundSourceExpression('spp') . ' as fund_source'),
            ])
            ->first();
    }

    protected function fundSourceExpression(string $alias = 'spp'): string
    {
        return "
            CASE
                WHEN UPPER(TRIM({$alias}.project_code)) LIKE 'SBDP%' THEN 'SBDP'
                WHEN UPPER(TRIM({$alias}.project_code)) LIKE 'FA-%' THEN 'FALGU'
                WHEN UPPER(TRIM({$alias}.project_code)) LIKE 'FALGU%' THEN 'FALGU'
                WHEN UPPER(TRIM({$alias}.project_code)) LIKE 'CMGP%' THEN 'CMGP'
                WHEN UPPER(TRIM({$alias}.project_code)) LIKE 'GEF%' THEN 'GEF'
                WHEN UPPER(TRIM({$alias}.project_code)) LIKE 'SAFPB%' THEN 'SAFPB'
                WHEN UPPER(TRIM({$alias}.project_code)) LIKE 'SGLGIF%' THEN 'SGLGIF'
                WHEN TRIM(COALESCE({$alias}.program, '')) <> '' THEN UPPER(TRIM(COALESCE({$alias}.program, '')))
                ELSE 'UNSPECIFIED'
            END
        ";
    }

    private function rpmesForm2DeadlineReportingYear(): int
    {
        // RPMES Form 2 quarterly deadline tracking follows the LGU reportorial
        // configuration for the active reporting cycle, not the project's
        // funding year.
        return (int) now()->year;
    }

    protected function quarters(): array
    {
        return [
            'Q1' => '1st Quarter',
            'Q2' => '2nd Quarter',
            'Q3' => '3rd Quarter',
            'Q4' => '4th Quarter',
        ];
    }

    protected function normalizeQuarter(?string $quarter, ?string $default = null): string
    {
        $default = $default ?? 'Q1';
        $quarter = strtoupper(trim((string) $quarter));
        return array_key_exists($quarter, $this->quarters()) ? $quarter : $default;
    }
}
