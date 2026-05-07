@extends('layouts.dashboard')

@section('title', 'RLIP/LIME Monthly Reports')
@section('page-title', 'RLIP/LIME Monthly Reports')

@section('content')
    @include('reports.dilg-deliverables._workspace', [
        'pageTitle' => 'RLIP/LIME Monthly Reports',
        'pageIntro' => 'Dedicated workspace for DILG RLIP/LIME monthly reporting activities.',
        'heroIcon' => 'fas fa-chart-column',
        'heroTitle' => 'RLIP/LIME Monthly Reporting Workspace',
        'heroCopy' => 'Use this page as the landing area for RLIP/LIME monthly deliverables, internal follow-ups, and reporting checkpoints before moving into the detailed project views.',
        'workspaceLinks' => [
            ['name' => 'reports.dilg-deliverables', 'label' => 'Overview', 'route' => route('reports.dilg-deliverables')],
            ['name' => 'reports.dilg-deliverables.monitoring-evaluation', 'label' => 'Monitoring and Evaluation Reports', 'route' => route('reports.dilg-deliverables.monitoring-evaluation')],
            ['name' => 'reports.dilg-deliverables.rlip-lime-monthly', 'label' => 'RLIP/LIME Monthly Reports', 'route' => route('reports.dilg-deliverables.rlip-lime-monthly')],
            ['name' => 'reports.dilg-deliverables.qaar-tool-monitoring', 'label' => 'QAAR Tool and Monitoring Report', 'route' => route('reports.dilg-deliverables.qaar-tool-monitoring')],
        ],
        'cards' => [
            [
                'title' => 'Monthly Submission Queue',
                'copy' => 'Track pending monthly RLIP/LIME reportorial items, validate incoming submissions, and stage office follow-ups.',
            ],
            [
                'title' => 'Dashboard Access',
                'copy' => 'Open the RLIP/LIME dashboard for broader monitoring, summary views, and trend analysis across the dataset.',
                'href' => route('projects.rlip-lime.dashboard'),
                'cta' => 'Open dashboard',
            ],
            [
                'title' => 'Project Table',
                'copy' => 'Jump into the RLIP/LIME project table when you need row-level data review, filtering, and project navigation.',
                'href' => route('projects.rlip-lime'),
                'cta' => 'Open project table',
            ],
        ],
    ])
@endsection
