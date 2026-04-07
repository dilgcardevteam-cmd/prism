<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'PDMU') }} - Verify Email</title>

    @include('partials.google-sans-font')

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { font-family: var(--app-font-sans); background-color: #f4f4f4; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; padding:20px 0; }
        .card { background:#fff; width:100%; max-width:640px; padding:40px; border-radius:10px; box-shadow:0 6px 24px rgba(10,10,10,0.08); }
        .card h2 { color:#002C76; margin:0 0 12px; font-size:24px; text-align:center; }
        .card p { margin:0 0 18px; color:#6b7280; text-align:center; line-height:1.6; }
        .icon-box { text-align:center; margin-bottom:24px; }
        .icon-box i { font-size:48px; color:#28a745; }
        .alert { margin-bottom:24px; }
        .actions { display:flex; gap:12px; flex-direction:column; margin-top:24px; }
        .actions button, .actions a { padding:12px; border-radius:8px; border:none; font-weight:600; cursor:pointer; text-decoration:none; text-align:center; }
        .actions .btn-primary { background:#002C76; color:#fff; }
        .actions .btn-primary:hover { background:#001a4d; color:#fff; text-decoration:none; }
        .actions .btn-secondary { background:#e6eef8; color:#ffffff; border:1px solid #002C76; }
        .actions .btn-secondary:hover { background:#d9e5f0; text-decoration:none; }
        .info-box { background:#f0f9ff; border-left:4px solid #0284c7; padding:16px; border-radius:4px; margin-bottom:24px; }
        .info-box strong { color:#0284c7; }
        .resend-info { font-size:14px; color:#6b7280; margin-top:16px; padding-top:16px; border-top:1px solid #e5e7eb; }
        @media (max-width:720px){ .card{padding:24px; margin:0 10px;} }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-box">
            <i class="fas fa-envelope-circle-check"></i>
        </div>
        <h2>{{ __('Verify Your Email Address') }}</h2>
        
        @if (session('resent'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ __('A fresh verification link has been sent to your email address.') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="info-box">
            <strong>{{ __('What\'s next?') }}</strong>
            <p style="margin-bottom:0; margin-top:8px;">{{ __('We\'ve sent a verification email to your registered email address. Please check your email and click the verification link to activate your account.') }}</p>
        </div>

        <p>{{ __('If you did not receive the email, click the button below to request a new verification link.') }}</p>

        <div class="actions">
            <form method="POST" action="{{ route('verification.resend') }}">
                @csrf
                <button type="submit" class="btn-primary w-100">
                    <i class="fas fa-envelope me-2"></i>{{ __('Send Verification Email Again') }}
                </button>
            </form>
            <a href="{{ route('login') }}" class="btn-secondary w-100">
                <i class="fas fa-arrow-left me-2"></i>{{ __('Back to Login') }}
            </a>
        </div>

        <div class="resend-info">
            <i class="fas fa-info-circle me-2"></i>
            {{ __('The verification link will expire in 60 minutes. Check your spam/junk folder if you don\'t see the email.') }}
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
