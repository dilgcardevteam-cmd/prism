@extends('layouts.dashboard')

@section('title', 'DILG Deliverables')
@section('page-title', 'DILG Deliverables')

@section('content')
    @include('reports.dilg-deliverables._workspace', [
        'pageTitle' => 'DILG Deliverables',
        'pageIntro' => 'Workspace for deliverables handled by DILG Provincial and Regional Office users.',
        'heroIcon' => 'fas fa-clipboard-list',
        'heroTitle' => 'DILG Office Deliverables',
        'heroCopy' => 'Open a dedicated deliverables workspace for monitoring and evaluation, RLIP/LIME monthly reporting, or QAAR tracking. Each page can be extended with status boards, document actions, and compliance summaries.',
        'workspaceLinks' => [
            ['name' => 'reports.dilg-deliverables', 'label' => 'Overview', 'route' => route('reports.dilg-deliverables')],
            ['name' => 'reports.dilg-deliverables.monitoring-evaluation', 'label' => 'Monitoring and Evaluation Reports', 'route' => route('reports.dilg-deliverables.monitoring-evaluation')],
            ['name' => 'reports.dilg-deliverables.rlip-lime-monthly', 'label' => 'RLIP/LIME Monthly Reports', 'route' => route('reports.dilg-deliverables.rlip-lime-monthly')],
            ['name' => 'reports.dilg-deliverables.qaar-tool-monitoring', 'label' => 'QAAR Tool and Monitoring Report', 'route' => route('reports.dilg-deliverables.qaar-tool-monitoring')],
        ],
        'cards' => [
            [
                'title' => 'Monitoring and Evaluation Reports',
                'copy' => 'Use a dedicated page for monitoring status, evaluation notes, validation checkpoints, and follow-up actions for DILG deliverables.',
                'href' => route('reports.dilg-deliverables.monitoring-evaluation'),
                'cta' => 'Open page',
            ],
            [
                'title' => 'RLIP/LIME Monthly Reports',
                'copy' => 'Stage monthly RLIP/LIME reporting work, review submissions, and route users into the operational RLIP/LIME workspaces when needed.',
                'href' => route('reports.dilg-deliverables.rlip-lime-monthly'),
                'cta' => 'Open page',
            ],
            [
                'title' => 'QAAR Tool and Monitoring Report',
                'copy' => 'Track QAAR monitoring requirements, maintain action items, and prepare report packages before opening the detailed monitoring module.',
                'href' => route('reports.dilg-deliverables.qaar-tool-monitoring'),
                'cta' => 'Open page',
            ],
        ],
    ])
@endsection
