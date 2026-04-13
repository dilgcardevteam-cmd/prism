<?php

namespace App\Http\Controllers;

use App\Models\AnnualRpmesForm4Upload;

class AnnualRpmesForm4Controller extends AbstractQuarterlyRpmesFormController
{
    protected function formConfig(): array
    {
        return [
            'page_title' => 'Annual RPMES Form 4',
            'page_heading' => 'RPMES FORM 4 : Project Results',
            'page_subtitle' => 'Project Results',
            'project_view_heading' => 'Project View',
            'project_view_description' => 'SubayBayan project details, annual uploads, and DILG validation workflow for RPMES Form 4.',
            'list_heading' => 'RPMES FORM 4 : Project Results',
            'list_title_badge' => 'SubayBayan Project List',
            'list_description' => 'All accessible SubayBayan projects are listed below, ordered by latest funding year first.',
            'report_short_label' => 'RPMES FORM 4',
            'report_short_name' => 'RPMES Form 4',
            'report_full_title' => 'Project Results',
            'submission_section_label' => 'Annual Submission',
            'submission_heading' => 'Submission of Project Results (RPMES FORM 4)',
            'submission_description' => 'Each annual cycle supports one report upload plus DILG Provincial Office and DILG Regional Office validation.',
            'deadline_card_label' => 'Annual Deadline',
            'validation_scope_label' => 'annual validation',
            'acceptance_note' => 'Accepted files: PDF, JPG, JPEG, PNG. Maximum size: 10 MB.',
            'permission_aspect' => 'annual_rpmes_form_4',
            'deadline_aspect' => 'annual_rpmes_form_4',
            'upload_table' => 'annual_rpmes_form4_uploads',
            'model_class' => AnnualRpmesForm4Upload::class,
            'storage_directory' => 'annual-rpmes-form-4',
            'timestamp_log_key' => 'annual-rpmes-form-4',
            'index_route' => 'reports.annual.rpmes.form-4',
            'show_route' => 'reports.annual.rpmes.form-4.show',
            'upload_route' => 'reports.annual.rpmes.form-4.upload',
            'approve_route' => 'reports.annual.rpmes.form-4.approve',
            'document_route' => 'reports.annual.rpmes.form-4.document',
            'delete_route' => 'reports.annual.rpmes.form-4.delete-document',
        ];
    }

    protected function quarters(): array
    {
        return [
            'Annual' => 'Annual',
        ];
    }

    protected function userCanAccessReport($user): bool
    {
        if (!$user) {
            return false;
        }

        return parent::userCanAccessReport($user)
            || $user->hasCrudPermission('rbis_annual_certification', 'view');
    }
}
