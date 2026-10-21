<?php

namespace App\Modules\ReportorialRequirements\Controllers;

use App\Http\Controllers\Controller;

use App\Models\QuarterlyRpmesForm2Upload;

class QuarterlyRpmesForm2Controller extends AbstractQuarterlyRpmesFormController
{
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
}
