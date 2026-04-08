@extends('layouts.dashboard')

@section('title', 'System Setup')
@section('page-title', 'System Setup')

@section('content')
    @if (session('success'))
        <div style="margin-bottom: 18px; padding: 12px 16px; border-radius: 10px; border: 1px solid #a7f3d0; background: #ecfdf5; color: #166534; font-size: 13px; font-weight: 600;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div style="margin-bottom: 18px; padding: 12px 16px; border-radius: 10px; border: 1px solid #fecaca; background: #fff1f2; color: #be123c; font-size: 13px; font-weight: 600;">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="margin-bottom: 18px; padding: 14px 16px; border-radius: 10px; border: 1px solid #fecaca; background: #fff7f7; color: #991b1b;">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="content-header">
        <h1>System Setup</h1>
        <p>Central access point for core application setup pages and configuration reviews.</p>
    </div>

    <section style="background: white; padding: 28px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08); margin-bottom: 20px;">
        <div style="display: flex; align-items: flex-start; gap: 14px; margin-bottom: 18px;">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: #dbeafe; color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="fas fa-cogs"></i>
            </div>
            <div>
                <h2 style="margin: 0; color: #002C76; font-size: 20px;">Configuration Overview</h2>
                <p style="margin: 6px 0 0; color: #6b7280; font-size: 14px; line-height: 1.6;">
                    Open a dedicated configuration page for each system area instead of managing everything from one screen.
                </p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px;">
            @forelse ($systemSetupItems as $item)
                <a href="{{ $item['route'] }}" style="display: block; border: 1px solid #dbe4f0; border-radius: 12px; padding: 20px; background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); text-decoration: none; transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;">
                    <div style="width: 42px; height: 42px; border-radius: 12px; background: #eff6ff; color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 14px;">
                        <i class="{{ $item['icon'] }}"></i>
                    </div>
                    <h3 style="margin: 0 0 8px; color: #002C76; font-size: 16px;">{{ $item['title'] }}</h3>
                    <p style="margin: 0; color: #475569; font-size: 13px; line-height: 1.6;">{{ $item['description'] }}</p>
                </a>
            @empty
                <article style="border: 1px dashed #cbd5e1; border-radius: 12px; padding: 20px; background: #f8fafc;">
                    <div style="width: 42px; height: 42px; border-radius: 12px; background: #e2e8f0; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 14px;">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3 style="margin: 0 0 8px; color: #334155; font-size: 16px;">No Utility Pages Assigned</h3>
                    <p style="margin: 0; color: #64748b; font-size: 13px; line-height: 1.6;">
                        This account can open the utilities workspace, but no individual utility pages are currently assigned through Role Configuration.
                    </p>
                </article>
            @endforelse
        </div>
    </section>
@endsection
