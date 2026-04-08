@extends('errors.landing-layout')

@section('title', '419 Page Expired')
@section('status', '419')
@section('heading', 'Session Expired')
@section('message', 'Your session has expired for security reasons. Please refresh the page and try again.')

@section('actions')
    <button
        type="button"
        class="error-btn error-btn-primary"
        onclick="window.location.reload()"
    >
        <i class="fas fa-rotate-right"></i>
        <span>Refresh Page</span>
    </button>
    <a href="{{ url('/') }}" class="error-btn error-btn-secondary">
        <i class="fas fa-house"></i>
        <span>Back to Landing Page</span>
    </a>
@endsection
