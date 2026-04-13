<?php

namespace App\Http\Controllers;

use App\Models\QuarterlyRpmesForm6Upload;

class QuarterlyRpmesForm6Controller extends AbstractQuarterlyRpmesFormController
{
    protected function formConfig(): array
    {
        return [
            'page_title' => 'Quarterly RPMES Form 6',
            'page_heading' => 'RPMES FORM 6 : Report on the Status of Projects Encountering Problems',
            'page_subtitle' => 'Report on the Status of Projects Encountering Problems',
            'project_view_heading' => 'Project View',
            'project_view_description' => 'SubayBayan project details, quarterly uploads, and DILG validation workflow for RPMES Form 6.',
            'list_heading' => 'RPMES FORM 6 : Report on the Status of Projects Encountering Problems',
            'list_title_badge' => 'SubayBayan Project List',
            'list_description' => 'All accessible SubayBayan projects are listed below, ordered by latest funding year first.',
            'report_short_label' => 'RPMES FORM 6',
            'report_short_name' => 'RPMES Form 6',
            'report_full_title' => 'Report on the Status of Projects Encountering Problems',
            'submission_heading' => 'Submission of Report on the Status of Projects Encountering Problems (RPMES FORM 6)',
            'submission_description' => 'Each quarter supports one report upload plus DILG Provincial Office and DILG Regional Office validation.',
            'acceptance_note' => 'Accepted files: PDF, JPG, JPEG, PNG. Maximum size: 10 MB.',
            'permission_aspect' => 'quarterly_rpmes_form_6',
            'deadline_aspect' => 'quarterly_rpmes_form_6',
            'upload_table' => 'quarterly_rpmes_form6_uploads',
            'model_class' => QuarterlyRpmesForm6Upload::class,
            'storage_directory' => 'rpmes-form-6',
            'timestamp_log_key' => 'rpmes-form-6',
            'index_route' => 'reports.quarterly.rpmes.form-6',
            'show_route' => 'reports.quarterly.rpmes.form-6.show',
            'upload_route' => 'reports.quarterly.rpmes.form-6.upload',
            'approve_route' => 'reports.quarterly.rpmes.form-6.approve',
            'document_route' => 'reports.quarterly.rpmes.form-6.document',
            'delete_route' => 'reports.quarterly.rpmes.form-6.delete-document',
        ];
    }
}
