@extends('errors.landing-layout')

@section('title', '404 Not Found')
@section('status', '404')
@section('heading', 'Page Not Found')
@section('message', 'The page you are trying to access does not exist or may have been moved to another location.')

@section('actions')
    <a href="{{ url('/') }}" class="error-btn error-btn-primary">
        <i class="fas fa-house"></i>
        <span>Back to Landing Page</span>
    </a>
    <button
        type="button"
        class="error-btn error-btn-secondary"
        onclick="window.history.length > 1 ? window.history.back() : window.location.assign('{{ url('/') }}')"
    >
        <i class="fas fa-arrow-left"></i>
        <span>Go Back</span>
    </button>
@endsection
