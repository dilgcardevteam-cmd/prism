@extends('layouts.dashboard')

@section('title', 'Monitoring and Evaluation Reports')
@section('page-title', 'Monitoring and Evaluation Reports')

@section('content')
    @include('reports.dilg-deliverables._workspace', [
        'pageTitle' => 'Monitoring and Evaluation Reports',
        'pageIntro' => 'Dedicated workspace for DILG monitoring and evaluation deliverables.',
        'heroIcon' => 'fas fa-file-lines',
        'heroTitle' => 'Monitoring and Evaluation Workspace',
        'heroCopy' => 'Use this page to stage reportorial work before it moves into the full monitoring tool. It is structured for intake, review, evaluation notes, and status coordination across provincial and regional teams.',
        'workspaceLinks' => [
            ['name' => 'reports.dilg-deliverables', 'label' => 'Overview', 'route' => route('reports.dilg-deliverables')],
            ['name' => 'reports.dilg-deliverables.monitoring-evaluation', 'label' => 'Monitoring and Evaluation Reports', 'route' => route('reports.dilg-deliverables.monitoring-evaluation')],
            ['name' => 'reports.dilg-deliverables.rlip-lime-monthly', 'label' => 'RLIP/LIME Monthly Reports', 'route' => route('reports.dilg-deliverables.rlip-lime-monthly')],
            ['name' => 'reports.dilg-deliverables.qaar-tool-monitoring', 'label' => 'QAAR Tool and Monitoring Report', 'route' => route('reports.dilg-deliverables.qaar-tool-monitoring')],
        ],
        'cards' => [
            [
                'title' => 'Submission Intake',
                'copy' => 'Centralize incoming monitoring and evaluation deliverables, assign ownership, and flag incomplete report packages before further processing.',
            ],
            [
                'title' => 'Evaluation Notes',
                'copy' => 'Capture observations, validation remarks, and next-step recommendations from provincial and regional reviewers in one place.',
            ],
            [
                'title' => 'Open Detailed Module',
                'copy' => 'Jump to the existing detailed monitoring workspace when you need the operational report flow and document management tools.',
                'href' => route('reports.quarterly.dilg-mc-2018-19'),
                'cta' => 'Open detailed module',
            ],
        ],
    ])
@endsection
