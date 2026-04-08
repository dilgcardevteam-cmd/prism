@extends('errors.landing-layout')

@section('title', 'Access Restricted')
@section('status', '403')
@section('heading', 'Access Restricted')
@section('message', 'You do not have the necessary permissions to access this page. This area is limited to specific user roles.')

@section('meta')
    If you believe this is incorrect, please contact your system administrator to review your account access.
@endsection

@section('actions')
    <a href="{{ url('/') }}" class="error-btn error-btn-primary">
        <i class="fas fa-house"></i>
        <span>Back to Landing Page</span>
    </a>
    @if (Route::has('dashboard'))
        <a href="{{ route('dashboard') }}" class="error-btn error-btn-secondary">
            <i class="fas fa-table-columns"></i>
            <span>Go to Dashboard</span>
        </a>
    @endif
@endsection
