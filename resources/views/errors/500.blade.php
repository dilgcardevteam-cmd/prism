@extends('errors.landing-layout')

@section('title', '500 Server Error')
@section('status', '500')
@section('heading', 'Server Error')
@section('message', 'An unexpected server error occurred while processing your request. Please try again in a few moments.')

@section('actions')
    <button
        type="button"
        class="error-btn error-btn-primary"
        onclick="window.location.reload()"
    >
        <i class="fas fa-rotate-right"></i>
        <span>Reload Page</span>
    </button>
    <a href="{{ url('/') }}" class="error-btn error-btn-secondary">
        <i class="fas fa-house"></i>
        <span>Back to Landing Page</span>
    </a>
@endsection
