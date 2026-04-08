@extends('errors.landing-layout')

@section('title', '429 Too Many Requests')
@section('status', '429')
@section('heading', 'Too Many Requests')
@section('message', 'Too many requests were sent in a short period. Please wait a moment before trying again.')

@section('actions')
    <button
        type="button"
        class="error-btn error-btn-primary"
        onclick="window.location.reload()"
    >
        <i class="fas fa-clock-rotate-left"></i>
        <span>Try Again</span>
    </button>
    <a href="{{ url('/') }}" class="error-btn error-btn-secondary">
        <i class="fas fa-house"></i>
        <span>Back to Landing Page</span>
    </a>
@endsection
