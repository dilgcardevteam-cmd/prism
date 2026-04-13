<?php

namespace App\Http\Controllers;

use App\Models\QuarterlyRpmesForm5Upload;

class QuarterlyRpmesForm5Controller extends AbstractQuarterlyRpmesFormController
{
    protected function formConfig(): array
    {
        return [
            'page_title' => 'Quarterly RPMES Form 5',
            'page_heading' => 'RPMES FORM 5 : Summary of Financial and Physical Accomplishments including Project Results',
            'page_subtitle' => 'Summary of Financial and Physical Accomplishments including Project Results',
            'project_view_heading' => 'Project View',
            'project_view_description' => 'SubayBayan project details, quarterly uploads, and DILG validation workflow for RPMES Form 5.',
            'list_heading' => 'RPMES FORM 5 : Summary of Financial and Physical Accomplishments including Project Results',
            'list_title_badge' => 'SubayBayan Project List',
            'list_description' => 'All accessible SubayBayan projects are listed below, ordered by latest funding year first.',
            'report_short_label' => 'RPMES FORM 5',
            'report_short_name' => 'RPMES Form 5',
            'report_full_title' => 'Summary of Financial and Physical Accomplishments including Project Results',
            'submission_heading' => 'Submission of Summary of Financial and Physical Accomplishments including Project Results (RPMES FORM 5)',
            'submission_description' => 'Each quarter supports one report upload plus DILG Provincial Office and DILG Regional Office validation.',
            'acceptance_note' => 'Accepted files: PDF, JPG, JPEG, PNG. Maximum size: 10 MB.',
            'permission_aspect' => 'quarterly_rpmes_form_5',
            'deadline_aspect' => 'quarterly_rpmes_form_5',
            'upload_table' => 'quarterly_rpmes_form5_uploads',
            'model_class' => QuarterlyRpmesForm5Upload::class,
            'storage_directory' => 'rpmes-form-5',
            'timestamp_log_key' => 'rpmes-form-5',
            'index_route' => 'reports.quarterly.rpmes.form-5',
            'show_route' => 'reports.quarterly.rpmes.form-5.show',
            'upload_route' => 'reports.quarterly.rpmes.form-5.upload',
            'approve_route' => 'reports.quarterly.rpmes.form-5.approve',
            'document_route' => 'reports.quarterly.rpmes.form-5.document',
            'delete_route' => 'reports.quarterly.rpmes.form-5.delete-document',
        ];
    }
}
