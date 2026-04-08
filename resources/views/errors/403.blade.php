@extends('errors.landing-layout')

@section('title', '403 Forbidden')
@section('status', '403')
@section('heading', 'Access Denied')
@section('message', 'You do not have permission to access this page or resource with your current account.')

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
