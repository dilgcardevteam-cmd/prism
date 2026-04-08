@extends('errors.landing-layout')

@section('title', 'System Under Maintenance')
@section('status', '503')
@section('heading', 'System Under Maintenance')
@section('message', 'PRISM is temporarily unavailable while maintenance activities are in progress.')

@section('actions')
    <a href="{{ url('/') }}" class="error-btn error-btn-primary">
        <i class="fas fa-house"></i>
        <span>Back to Landing Page</span>
    </a>

    @guest
        <a href="{{ route('login') }}" class="error-btn error-btn-secondary">
            <i class="fas fa-right-to-bracket"></i>
            <span>Login</span>
        </a>
    @endguest

    @auth
        @if (!auth()->user()->isSuperAdmin())
            <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                @csrf
                <button type="submit" class="error-btn error-btn-secondary" style="cursor: pointer;">
                    <i class="fas fa-right-from-bracket"></i>
                    <span>Log Out</span>
                </button>
            </form>
        @endif
    @endauth
@endsection
