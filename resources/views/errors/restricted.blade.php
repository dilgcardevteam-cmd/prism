@extends('layouts.dashboard')

@section('title', 'Access Restricted')
@section('page-title', 'Access Restricted')

@section('content')
    <div style="display: flex; align-items: center; justify-content: center; min-height: 60vh;">
        <div style="background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); max-width: 500px; text-align: center;">
            <!-- Icon -->
            <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 48px; margin: 0 auto 30px;">
                <i class="fas fa-lock"></i>
            </div>

            <!-- Title -->
            <h1 style="color: #002C76; font-size: 28px; margin: 0 0 15px;">Access Restricted</h1>

            <!-- Message -->
            <p style="color: #6b7280; font-size: 16px; margin: 0 0 20px; line-height: 1.6;">
                You do not have the necessary permissions to access this page. This area is restricted to users with specific roles.
            </p>

            <!-- Additional Info -->
            <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 15px; margin-bottom: 25px;">
                <p style="color: #7f1d1d; font-size: 14px; margin: 0;">
                    <i class="fas fa-info-circle" style="margin-right: 8px;"></i>
                    If you believe this is a mistake, please contact your administrator.
                </p>
            </div>

            <!-- Back Button -->
            <a href="{{ route('dashboard') }}" style="padding: 12px 30px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease;">
                <i class="fas fa-arrow-left"></i> Go Back to Dashboard
            </a>
        </div>
    </div>

    <style>
        a:hover {
            background-color: #001a4d !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 44, 118, 0.2);
        }
    </style>
@endsection
