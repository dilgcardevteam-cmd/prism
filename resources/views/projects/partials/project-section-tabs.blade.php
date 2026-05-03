@php
    $activeTab = $activeTab ?? 'locally-funded';
    $projectTabs = [
        [
            'key' => 'locally-funded',
            'label' => 'Locally Funded Projects',
            'icon' => 'fa-hand-holding-usd',
            'url' => route('dashboard', ['tab' => 'locally-funded'], false),
        ],
        [
            'key' => 'rlip-lime',
            'label' => 'RLIP / LIME 20% Development Fund',
            'icon' => 'fa-leaf',
            'url' => route('projects.rlip-lime.dashboard', [], false),
        ],
        [
            'key' => 'rssa',
            'label' => 'Rapid Subproject Sustainability Assessment',
            'icon' => 'fa-list-check',
            'url' => route('dashboard', ['tab' => 'rssa'], false),
        ],
        [
            'key' => 'sglgif',
            'label' => 'SGLG Incentive Fund',
            'icon' => 'fa-award',
            'url' => route('projects.sglgif', [], false),
        ],
    ];
@endphp

@once
    <style>
        .project-section-tabs {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 20px;
            padding: 0 0 12px;
            overflow-x: auto;
            scrollbar-width: thin;
            border-bottom: 1px solid #0b3d91;
        }

        .project-section-tab {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 16px;
            border: 1px solid #a8c0e8;
            border-radius: 999px;
            background: #eef4ff;
            color: #0b3d91;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.2;
            white-space: nowrap;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
            transition: background-color 0.18s ease, color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .project-section-tab-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 14px;
            font-size: 12px;
            line-height: 1;
            flex: 0 0 auto;
        }

        .project-section-tab:hover {
            border-color: #7ea5df;
            background: #e1edff;
            color: #0b3d91;
        }

        .project-section-tab:focus-visible {
            outline: 3px solid rgba(37, 99, 235, 0.25);
            outline-offset: 2px;
        }

        .project-section-tab.is-active {
            background: #0b3d91;
            border-color: #0b3d91;
            color: #ffffff;
            box-shadow: 0 8px 16px rgba(11, 61, 145, 0.28);
        }

        @media (max-width: 700px) {
            .project-section-tabs {
                gap: 8px;
                margin-bottom: 16px;
                padding-bottom: 10px;
            }

            .project-section-tab {
                padding: 8px 13px;
                font-size: 12px;
            }
        }
    </style>
@endonce

<nav class="project-section-tabs" aria-label="Project pages">
    @foreach ($projectTabs as $tab)
        <a
            href="{{ $tab['url'] }}"
            class="project-section-tab{{ $activeTab === $tab['key'] ? ' is-active' : '' }}"
            @if ($activeTab === $tab['key']) aria-current="page" @endif
        >
            <i class="fa-solid {{ $tab['icon'] }} project-section-tab-icon" aria-hidden="true"></i>
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>

 
