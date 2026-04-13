@extends('errors.landing-layout')

@section('title', 'System Under Maintenance')
@section('heading', 'System Under Maintenance')
@section('message', 'PRISM is temporarily unavailable while maintenance activities are in progress.')

@section('actions')
    <a href="{{ url('/') }}" class="error-btn error-btn-primary">
        <i class="fas fa-house"></i>
        <span>Back to Landing Page</span>
    </a>
@endsection
