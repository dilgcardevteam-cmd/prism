@extends('layouts.dashboard')

@section('title', 'Rapid Subproject Sustainability Assessment')
@section('page-title', 'Rapid Subproject Sustainability Assessment')

@section('content')
    <div class="content-header">
        <h1>Rapid Subproject Sustainability Assessment</h1>
        <p>This page is not ready yet.</p>
    </div>

    @include('projects.partials.project-section-tabs', ['activeTab' => $activeTab ?? 'rssa'])

    <section style="background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); border: 1px solid #dbe7ff; border-radius: 16px; padding: 36px 28px; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08); text-align: center;">
        <div style="width: 72px; height: 72px; margin: 0 auto 18px; border-radius: 999px; background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%); color: #1d4ed8; display: inline-flex; align-items: center; justify-content: center; font-size: 30px;">
            <i class="fas fa-list-check" aria-hidden="true"></i>
        </div>

        <h2 style="margin: 0; color: #0f172a; font-size: 30px; font-weight: 800; letter-spacing: -0.02em;">Coming Soon</h2>
        <p style="max-width: 620px; margin: 14px auto 0; color: #475569; font-size: 15px; line-height: 1.7;">
            The Rapid Subproject Sustainability Assessment page is still being prepared.
            The tab is available now, but its full dashboard and tools are not published yet.
        </p>
    </section>
@endsection
