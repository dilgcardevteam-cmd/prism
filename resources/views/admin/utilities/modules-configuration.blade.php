@extends('layouts.dashboard')

@section('title', 'Modules Configuration')
@section('page-title', 'Modules Configuration')

@section('content')
    <style>
        .module-category-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .module-category-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 18px 24px;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .module-item-card {
            padding: 20px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            transition: background-color 0.2s ease;
        }
        .module-item-card:last-child {
            border-bottom: none;
        }
        .module-item-card:hover {
            background-color: #f8fafc;
        }
        .module-info {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }
        .module-icon-wrapper {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #f1f5f9;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .module-item-card:hover .module-icon-wrapper {
            background: #e2e8f0;
            color: #0f172a;
        }
        /* Toggle Switch styling */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
            flex-shrink: 0;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .3s;
            border-radius: 26px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }
        input:checked + .slider {
            background-color: #10b981;
        }
        input:checked + .slider:before {
            transform: translateX(24px);
        }
        .btn-save-modules {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            background: linear-gradient(180deg, #0a4cb3 0%, #002c76 100%);
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            border: 1px solid #002c76;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 4px 12px rgba(0, 44, 118, 0.15);
        }
        .btn-save-modules:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0, 44, 118, 0.22);
        }
        .btn-save-modules:active {
            transform: translateY(0);
        }
    </style>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
        <div class="content-header" style="margin-bottom: 0;">
            <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">Modules Configuration</h1>
            <p style="margin: 4px 0 0; color: #64748b; font-size: 14px;">Globally enable or disable modules and reportorial workflow aspect configurations.</p>
        </div>
        
        <a href="{{ route('utilities.system-setup.index') }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 8px; background: #ffffff; color: #475569; text-decoration: none; font-size: 13px; font-weight: 600; border: 1px solid #cbd5e1; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: all 0.2s ease;"
           onmouseenter="this.style.backgroundColor='#f8fafc'; this.style.borderColor='#94a3b8'; this.style.color='#0f172a';"
           onmouseleave="this.style.backgroundColor='#ffffff'; this.style.borderColor='#cbd5e1'; this.style.color='#475569';">
            <i class="fas fa-arrow-left"></i>
            <span>Back to System Setup</span>
        </a>
    </div>

    <form method="POST" action="{{ route('utilities.modules-configuration.update') }}">
        @csrf
        
        @foreach ($modules as $mod)
            <div class="module-category-card">
                <div class="module-category-header">
                    <div style="font-size: 18px; opacity: 0.95;">
                        <i class="{{ strtolower($mod['module']) === 'project monitoring' ? 'fas fa-chart-line' : 'fas fa-file-invoice' }}"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0; font-size: 15px; font-weight: 700; letter-spacing: 0.02em; text-transform: uppercase;">{{ $mod['module'] }}</h2>
                        <span style="font-size: 11px; opacity: 0.75; display: block; margin-top: 2px;">{{ $mod['description'] }}</span>
                    </div>
                </div>
                
                <div class="module-items-list">
                    @foreach ($mod['items'] ?? [] as $item)
                        @php
                            $aspect = $item['aspect'] ?? '';
                            $isEnabled = $dbSettings[$aspect] ?? true;
                        @endphp
                        <div class="module-item-card">
                            <div class="module-info">
                                <div class="module-icon-wrapper">
                                    <i class="{{ strtolower($mod['module']) === 'project monitoring' ? 'fas fa-project-diagram' : 'fas fa-file-alt' }}"></i>
                                </div>
                                <div>
                                    <h3 style="margin: 0 0 4px; color: #1e293b; font-size: 14px; font-weight: 600;">{{ $item['label'] }}</h3>
                                    <p style="margin: 0; color: #64748b; font-size: 12px; line-height: 1.5;">{{ $item['description'] }}</p>
                                </div>
                            </div>
                            
                            <label class="switch" title="Toggle Module Status">
                                <input type="checkbox" name="modules[]" value="{{ $aspect }}" @checked($isEnabled)>
                                <span class="slider"></span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div style="margin-top: 30px; display: flex; justify-content: flex-end; padding-bottom: 40px;">
            <button type="submit" class="btn-save-modules">
                <i class="fas fa-save"></i>
                <span>Save Changes</span>
            </button>
        </div>
    </form>
@endsection
