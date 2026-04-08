@extends('errors.landing-layout')

@section('title', '503 Service Unavailable')
@section('status', '503')
@section('heading', 'Service Unavailable')
@section('message', 'The system is temporarily unavailable due to maintenance or high load. Please try again shortly.')

@section('actions')
    <button
        type="button"
        class="error-btn error-btn-primary"
        onclick="window.location.reload()"
    >
        <i class="fas fa-rotate-right"></i>
        <span>Try Again</span>
    </button>
    <a href="{{ url('/') }}" class="error-btn error-btn-secondary">
        <i class="fas fa-house"></i>
        <span>Back to Landing Page</span>
    </a>
@endsection
