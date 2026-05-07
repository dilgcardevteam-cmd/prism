@extends('layouts.dashboard')

@section('title', 'QAAR Tool and Monitoring Report')
@section('page-title', 'QAAR Tool and Monitoring Report')

@section('content')
    @include('reports.dilg-deliverables._workspace', [
        'pageTitle' => 'QAAR Tool and Monitoring Report',
        'pageIntro' => 'Dedicated workspace for QAAR tracking, monitoring actions, and report preparation.',
        'heroIcon' => 'fas fa-clipboard-check',
        'heroTitle' => 'QAAR Monitoring Workspace',
        'heroCopy' => 'Use this page to prepare QAAR deliverables, stage monitoring actions, and centralize readiness checks before users open the detailed monitoring report module.',
        'workspaceLinks' => [
            ['name' => 'reports.dilg-deliverables', 'label' => 'Overview', 'route' => route('reports.dilg-deliverables')],
            ['name' => 'reports.dilg-deliverables.monitoring-evaluation', 'label' => 'Monitoring and Evaluation Reports', 'route' => route('reports.dilg-deliverables.monitoring-evaluation')],
            ['name' => 'reports.dilg-deliverables.rlip-lime-monthly', 'label' => 'RLIP/LIME Monthly Reports', 'route' => route('reports.dilg-deliverables.rlip-lime-monthly')],
            ['name' => 'reports.dilg-deliverables.qaar-tool-monitoring', 'label' => 'QAAR Tool and Monitoring Report', 'route' => route('reports.dilg-deliverables.qaar-tool-monitoring')],
        ],
        'cards' => [
            [
                'title' => 'Monitoring Checklist',
                'copy' => 'Prepare and review QAAR monitoring requirements, required attachments, and validation checkpoints before submission.',
            ],
            [
                'title' => 'Findings and Actions',
                'copy' => 'Maintain issue summaries, accountability notes, and action items for the next monitoring cycle.',
            ],
            [
                'title' => 'Open Detailed Module',
                'copy' => 'Open the existing operational page when you need the full QAAR tool flow, uploads, and detailed report handling.',
                'href' => route('reports.quarterly.dilg-mc-2018-30'),
                'cta' => 'Open detailed module',
            ],
        ],
    ])
@endsection
